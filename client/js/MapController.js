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

        var map = Maps.findOne({_id: mapId, creatorId: Meteor.userId()});

        if(!map){
            sAlert("This Map doesn't exist.");
            this.render("access_denied");
            return;
        }

        var activeTileset = Session.get('activeTileset');
        if(typeof activeTileset === "undefined" || activeTileset === ""){
            var oneTileset = Tilesets.findOne({name: {$ne: "000-Types"}});
            if(oneTileset){
                Session.set('activeTileset', oneTileset._id);
            }
        }

        /**
         * Properties
         * @type {Array}
         */
        var properties = [];

        properties.push({
            field: "author", value: map.creatorName
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

        /**
         * Layers
         */
        var mapLayers = [
            {
                z: 1,
                id: "background",
                name: "Background",
                active: true,
                tiles: []
            },
            {
                z: 2,
                id: "floor1",
                name: "Layer Floor 1",
                active: false,
                tiles: []
            },
            {
                z: 3,
                id: "floor2",
                name: "Layer Floor 2",
                active: false,
                tiles: []
            },
            {
                z: 11,
                id: "sky1",
                name: "Layer Sky 1",
                active: false,
                tiles: []
            }
        ];

        Template.mapEdit.helpers({
            activeTileset: function(){
                return Session.get('activeTileset');
            }

        });

        this.render('mapEdit', {
            data: {
                map: map,
                mapProperties: properties,
                mapLayers: mapLayers,
                canvasWidth: (map.width * map.tilewidth),
                canvasHeight: (map.height * map.tileheight)
            }
        });
    }
});