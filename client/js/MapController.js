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
                    if(!!error){
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

        if(!map){
            sAlert("This Map doesn't exist.");
            this.render("access_denied");
            return;
        }

        var allTilesets = Tilesets.find().fetch();

        // allTilesets[0] is for FieldTypes
        var activeTileset = Session.get('activeTileset') || allTilesets[1]._id || false;
        Session.set('activeTileset', activeTileset);

        /**
         * Properties in Array form for the template
         * @type {Array}
         */
        var properties = [];

        for (var key in map.properties) {
            if (map.properties.hasOwnProperty(key)) {
                properties.push({
                    field: key, value: map.properties[key]
                });
            }
        }
        properties.push({
            field: "author", value: map.creatorName, protected: true
        });
        properties.push({
            field: "name", value: map.name
        });
        properties.push({
            field: "width", value: map.width
        });
        properties.push({
            field: "height", value: map.height
        });
        properties.push({
            field: "tileheight", value: map.tileheight
        });
        properties.push({
            field: "tilewidth", value: map.tilewidth
        });

        var canvasWidth = map.width * map.tilewidth;
        var canvasHeight = map.height * map.tileheight;

        var hasBackgroundLayer = false;
        var hasFieldTypeLayer = false;
        var countFloorLayer = 0;
        var countSkyLayer = 0;


        _.each(map.layers, function(layer, index){

            layer.active = false;
            layer.visible = true;
            layer.canvasWidth = canvasWidth;
            layer.canvasHeight = canvasHeight;

            if(layer.type == "background"){
                // Background Layer
                hasBackgroundLayer = true;
            }
            else if(layer.type == "fieldtypes"){
                // Field Type Layer
                hasFieldTypeLayer = true;

                // FieldTypes are invisible at start
                layer.visible = false;
            }
            else if(layer.type == "sky"){
                // Sky Layer
            }
            else{
                // Floor Layer
            }

            map.layers[index] = layer;
        });

        this.render('mapEdit', {
            data: {
                map: map,
                allTilesets: allTilesets,
                mapProperties: properties,
                mapLayers: map.layers,
                canvasWidth: canvasWidth,
                canvasHeight: canvasHeight
            }
        });
    }
});