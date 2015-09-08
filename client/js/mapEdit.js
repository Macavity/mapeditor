Template.mapEdit.created = function () {

    Session.set('importJson', {});

    this.defaultActiveTool = "draw";

    var activeTool = Session.get('activeTool') || this.defaultActiveTool;
    Session.set('activeTool', activeTool);

};

Template.mapEdit.rendered = function(){
    Tilemap.initialize(this.data.map, this.data.allTilesets);
};

Template.mapEdit.helpers({
    tilesets: function(){
        return Tilesets.find();
    },
    activeTileset: function(){
        return Session.get('activeTileset');
    },
    showProperties: function(){
        return (typeof Session.get('showProperties') === "undefined") ? true : Session.get('showProperties');
    },
    showLeftSidebar: function(){
        return (typeof Session.get('showProperties') === "undefined") ? true : Session.get('showProperties');
    },
    showGrid: function(){
        return (typeof Session.get('showGrid') === "undefined") ? true : Session.get('showGrid');
    },

    importJsonPreview: function(){
        return Session.get('importJsonPreview');
    },

    /*
     * Tools
     */
    isToolActive: function(tool){
        var activeTool = Session.get('activeTool') || Template.instance().defaultActiveTool;
        return (activeTool == tool);
    },

    tilemapFormData: function(){
        return {
            uploadType: "tilemap"
        };
    },
    mapFiles: function(){
        //return this.fs.cwd().files();
    },
    availableTools: function(){
        var tools = [
            {
                label: "Brush Tool (B)",
                tool: "draw",
                icon: "glyphicon-pencil",
                active: false
            },
            {
                label: "Bucket Fill Tool (F)",
                tool: "fill",
                icon: "glyphicon-tint",
                active: false
            },
            {
                label: "Eraser (E)",
                tool: "erase",
                icon: "glyphicon-erase",
                active: false
            }
        ];

        var activeTool = Session.get('activeTool') || Template.instance().defaultActiveTool;

        _.each(tools, function(tool, index){
            if(tool.tool == activeTool){
                tool.active = true;
            }
            tools[index] = tool;
        });
        return tools;
    },
    activeTool: function(){
        var activeTool = Session.get('activeTool') || Template.instance().defaultActiveTool;
        return activeTool;
    },
    allTilesets: function(){
        return Tilemap.allTilesets();
    }
});

Template.importPreview.helpers({
    isFieldType: function(comparison){
        if(this.newType == comparison){
            return true;
        }
        return false;
    },
    tilesetWarning: function(){
        return (this.tilesetOptions.length == 0);
    }
});

/*
 * Events
 */
Template.mapEdit.events({
    /**
     * Import JSON Map File
     * @param event
     */
    'change #importJsonFile': function(event){
        var files = event.target.files; // FileList object

        var allTilesets = Tilemap.allTilesets();
        var countAllTilesets = allTilesets.length;

        var importContent = $("#importContent");
        importContent.empty().show();

        // Loop through the FileList and render image files as thumbnails.
        for (var i = 0, file; file = files[i]; i++) {

            // Only process image files.
            if (!(file.type === 'application/json')) {
                continue;
            }

            var reader = new FileReader();

            // Closure to capture the file information.
            reader.onload = function(e) {
                var json = JSON.parse(e.target.result);

                /*
                 * --------------------------------------
                 * Preview layer types
                 * --------------------------------------
                 */
                var layers = new Array(json.layers.length);

                var newType = "";
                var lcName = "";

                _.each(json.layers, function(layer,index){

                    lcName = layer.name.toLowerCase();

                    if(lcName === "background"){
                        newType = "background";
                    }
                    else if(lcName === "fieldtypes" || lcName === "field types"){
                        newType = "fieldtypes"
                    }
                    else if(lcName.indexOf("sky") > -1 || lcName == "layer 4"){
                        newType = "sky";
                    }
                    else {
                        newType = "floor";
                    }
                    layers[index] = {
                        id: index,
                        name: layer.name,
                        type: layer.type,
                        data: layer.data,
                        newType: newType
                    }
                });

                /*
                 * --------------------------------------
                 * Preview tileset assignments
                 * --------------------------------------
                 */
                var importTilesets = new Array(json.tilesets.length);
                var foundTileset;
                var i = 0;
                var tilesetIteration;

                _.each(json.tilesets, function(tileset, index){

                    importTilesets[index] = {
                        id: index,
                        name: tileset.name,
                        image: tileset.image,
                        foundTileset: false,
                        tilesetOptions: new Array(0),
                        oldFirstGid: tileset.firstgid
                    };
                    //console.log("Tileset in map file: "+tileset.name+", "+tileset.tilecount)

                    for(i = 0; i < countAllTilesets; i++){

                        tilesetIteration = allTilesets[i];

                        // The base values of the selected tilesets have to match
                        if(tilesetIteration.tilecount == tileset.tilecount
                            && tilesetIteration.imageheight == tileset.imageheight
                            && tilesetIteration.imagewidth == tileset.imagewidth){

                            tilesetIteration.isFoundTileset = false;

                            if(tilesetIteration.name == tileset.name){
                                foundTileset = Tilemap.getGlobalTilesetByName(tileset.name);

                                if(foundTileset){
                                    //console.log("[+] match: "+tilesetIteration.name+", "+tilesetIteration.tilecount);
                                    tilesetIteration.isFoundTileset = true;
                                    importTilesets[index].foundTileset = foundTileset._id;
                                }
                            }

                            importTilesets[index].tilesetOptions[i] = tilesetIteration;
                        }
                        else {
                            //console.log("[-] no match: "+tilesetIteration.name+", "+tilesetIteration.tilecount);
                        }


                    }

                    if(importTilesets[index].tilesetOptions.length == 0){
                        //importTilesets[index].warning = true;
                    }

                });

                /*
                 * --------------------------------------
                 * Preview Map Properties
                 * --------------------------------------
                 */
                var importMapData = {
                    name: json.properties.name,
                    width: json.width,
                    height: json.height,
                    tilewidth: json.tilewidth,
                    tileheight: json.tileheight,
                    oldTilesets: json.tilesets,
                    importTilesetData: importTilesets,
                    layers: layers,
                    properties: {

                    }
                };


                var jsonPreview = {
                    properties: [
                        { label: "Name", value: json.properties.name },
                        { label: "Creator", value: json.properties.author || false },
                        { label: "Width", value: json.width },
                        { label: "Height", value: json.height },
                        { label: "Tile Width", value: json.tilewidth },
                        { label: "Tile Height", value: json.tileheight },
                    ],
                    layers: layers,
                    tilesets: importTilesets
                };
                Blaze.renderWithData(Template.importPreview, {
                        layers: layers,
                        properties: jsonPreview.properties,
                        tilesets: importTilesets
                    },
                    importContent[0]);
                Session.set('importMapData', importMapData);
            };

            // Read in the image file as a data URL.
            reader.readAsText(file);
        }
    },
    /**
     * Switch currently used tool
     * @param event
     */
    'click #toolkit button': function(event){
        var activeTool = Session.get('activeTool') || Template.instance().defaultActiveTool;
        var newTool = $(event.currentTarget).data("tool");
        Session.set('activeTool', newTool);
    },
    'click #btn-show-grid': function(event){
        // Invert the value
        var isActive = $(event.currentTarget).hasClass("active");
        Session.set('showGrid', !isActive);
    },
    'click #btn-show-properties': function(event){
        // Invert the value
        Session.set('showProperties', !$(event.currentTarget).hasClass("active"));
    },

    /**
     * Submit Import Modal
     * @param event
     */
    'click #import-json-data': function(event){
        var importMapData = Session.get('importMapData');

        var oldTilesets = importMapData.tilesets;
        var importTilesetData = importMapData.importTilesetData;
        var importLayers = importMapData.layers;

        var assignedLayerType;
        var layer;
        var countLayerTypes = [];
        var layerTypeBg = 0;
        var layerTypeFt = 1;
        var layerTypeFloor = 2;
        var layerTypeSky = 3;

        /*
         * Prepare Imported Tilesets
         */
        var assignedTilesetId, assignedTileset, oldFirstGid;
        _.each(importTilesetData, function(importTileset, index){
            assignedTilesetId = $("#tileset-"+importTileset.id).val();

            oldFirstGid = importTileset.oldFirstGid;

            _.each(importTileset.tilesetOptions, function(option, index){
                if(!!option && option._id == assignedTilesetId){
                    importTileset.newFirstGid = option.firstgid;
                }
            });

            importTilesetData[index] = importTileset;

        });

        _.each(importLayers, function(importLayer, layerIndex){

            // Reset Counters
            countLayerTypes = [0,0,0,0];

            layer = {};

            // Layer Type
            assignedLayerType = $("#layer-type-"+importLayer.id).val();
            layer.type = assignedLayerType;

            // Layer Id based on selected type
            if(layer.type == "background"){
                layer.id = (++countLayerTypes[layerTypeBg] === 1) ? "background" : "background"+countLayerTypes[layerTypeBg];
            }
            else if(layer.type == "fieldtypes"){
                layer.id = (++countLayerTypes[layerTypeFt] === 1) ? "fieldtypes" : "fieldtypes"+countLayerTypes[layerTypeFt];
            }
            else if(layer.type == "floor"){
                layer.id = (++countLayerTypes[layerTypeFloor] === 1) ? "floor" : "floor"+countLayerTypes[layerTypeFloor];
            }
            else if(layer.type == "sky"){
                layer.id = (++countLayerTypes[layerTypeSky] === 1) ? "sky" : "sky"+countLayerTypes[layerTypeSky];
            }

            layer.name = importLayer.name;
            layer.height = importLayer.height;
            layer.width = importLayer.width;

            layer.x = 0;
            layer.y = 0;
            layer.z = Tilemap.calcLayerZ(layer.type, countLayerTypes);
            layer.visible = (layer.type !== "fieldtypes");
            layer.opacity = (layer.type === "fieldtypes") ? 0.8 : 1.0;

            // preallocate data array
            layer.data = new Array(importLayer.data.length);



            /*
             * Layer Data
             */

            var i, importTileset;

            _.each(importLayer.data, function(importTile, importTileIndex){

                // Get Import Tileset
                for (i = importTilesetData.length-1; i >= 0; i--) {
                    if (importTilesetData[i].oldFirstGid <= importTile)
                        break;
                }
                importTileset = importTilesetData[i];

                if(typeof importTileset === "undefined" || typeof importTileset.newFirstGid === "undefined"){
                    // The user didn't select a matching tileset => remove this tile
                    importTile = 0;
                }
                else if(importTileset.oldFirstGid == importTileset.newFirstGid){
                    // Lucky one
                }
                else {
                    importTile = importTile + (importTileset.newFirstGid - importTileset.oldFirstGid);
                }

                layer.data[importTileIndex] = importTile;

            });

            importLayers[layerIndex] = layer;

        });

        importMapData.layers = importLayers;

        importMapData.tilesets = Tilemap.allTilesets();

        // Cleanup
        delete importMapData.oldTilesets;
        delete importMapData.importTilesetData;

        Tilemap.initialize(importMapData);

    }
});

Template.importPreview.events({
});