MapController = RouteController.extend({
    onBeforeAction: function(){
        if (! Meteor.user()) {
            if (Meteor.loggingIn()) {
                this.render(this.loadingTemplate);
            } else {
                this.render('accessDenied');
            }
        } else {
            this.next();
        }
    },
    index: function(){

        Template.mapIndex.helpers({
            ownMaps: function(){
                return Maps.find({creatorId: Meteor.userId()}).fetch();
            },
            hasMaps: function(){
                return Maps.find({creatorId: Meteor.userId()}).count();
            }
        });

        Template.mapIndex.events({
            'click #create-map': function(event){
                var newMap = {
                    name: $("#mapName").val(),
                    width: $("#mapWidth").val(),
                    height: $("#mapHeight").val()
                };
                Meteor.call('createMap', newMap, function(error, result){
                    if(!!error && Meteor.isClient){
                        sAlert.error(error);
                    }
                    else {
                        Router.go('/map/edit/'+result);
                    }
                });
            }
        });

        this.render('mapIndex', {
            data: {
            }
        });

    },
    edit: function(){

        var mapId = Router.current().params._id;

        /**
         * @type {MapSchema}
         */
        var map = Maps.findOne({_id: mapId, creatorId: Meteor.userId()});

        if(!map && Meteor.isClient){
            sAlert("This Map doesn't exist.");
            this.render("access_denied");
            return;
        }

        var allTilesets = Tilesets.find().fetch();

        // allTilesets[0] is for FieldTypes
        var activeTileset = Session.get('activeTileset') || allTilesets[1]._id || false;
        Session.set('activeTileset', activeTileset);

        // If the map has no tilesets yet, assign all tilesets
        // @TODO Always use all tilesets
        if(!map.tilesets){
            map.tilesets = allTilesets;
        }

        // Add the default layers if non are existent yet
        if(!map.layers){
            map.layers = [{
                    "id": "fieldtypes",
                    "name" : "FieldTypes",
                    "opacity" : 0.8,
                    "type" : "fieldtypes",
                    "visible" : false,
                    "width" : map.width,
                    "height": map.height,
                    "x" : 0,
                    "y" : 0,
                    "z": 100
                },
                {
                    "id": "background",
                    "name" : "Background",
                    "opacity" : 0.8,
                    "type" : "background",
                    "visible" : true,
                    "width" : map.width,
                    "height": map.height,
                    "x" : 0,
                    "y" : 0,
                    "z": 1
                }];
        }

        /**
         * Properties in Array form for the template
         * @type {Array}
         */


        var canvasWidth = map.width * map.tilewidth;
        var canvasHeight = map.height * map.tileheight;

        map.layers = Tilemap.prepareLayersForCanvas(map.layers, canvasWidth, canvasHeight);

        this.render('mapEdit', {
            data: {
                map: map,
                allTilesets: allTilesets,
                mapProperties: Tilemap.mapPropertiesList(map),
                mapLayers: map.layers,
                canvasWidth: canvasWidth,
                canvasHeight: canvasHeight
            }
        });
    }
});