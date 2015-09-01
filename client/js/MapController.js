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

        var activeTileset = Session.get('activeTileset');
        if(typeof activeTileset === "undefined" || activeTileset === ""){
            var oneTileset = Tilesets.findOne({name: {$ne: "000-Types"}});
            if(oneTileset){
                Session.set('activeTileset', oneTileset._id);
            }
        }

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
            var lcName = layer.name.toLowerCase().replace(" ", "");

            layer.id = lcName;
            layer.active = false;
            layer.visible = true;
            layer.canvasWidth = canvasWidth;
            layer.canvasHeight = canvasHeight;

            if(lcName == "background" || layer.type == "background"){
                layer.z = 1;
                hasBackgroundLayer = true;
                layer.type = "background";
                layer.active = true;
            }
            else if(lcName == "fieldtypes" || layer.type == "fieldtypes"){
                layer.z = 100;
                hasFieldTypeLayer = true;
                layer.visible = false;      // FieldTypes are invisible at start
                layer.type = "fieldtypes";
            }
            else{
                // Remove spaces for layer id.

                if(lcName.indexOf("sky") > -1 || layer.type == "sky"){
                    countSkyLayer++;
                    // 51 - 99
                    layer.z = 50 + countSkyLayer;
                    layer.type = "sky";
                }
                else{
                    countFloorLayer++;
                    layer.z = 10 + countFloorLayer;
                    layer.type = "floor";
                }
            }
            map.layers[index] = layer;
        });


        Template.mapEdit.helpers({
            activeTileset: function(){
                return Session.get('activeTileset');
            }

        });

        this.render('mapEdit', {
            data: {
                map: map,
                mapProperties: properties,
                mapLayers: map.layers,
                canvasWidth: canvasWidth,
                canvasHeight: canvasHeight
            }
        });
    }
});