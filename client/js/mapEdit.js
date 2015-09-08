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

                // Preview Map Properties
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
                        newType: newType
                    }
                });

                var importTilesets = new Array(json.tilesets.length);
                var foundTileset;
                var i = 0;
                var tilesetIteration;

                _.each(json.tilesets, function(tileset, index){

                    importTilesets[index] = {
                        name: tileset.name,
                        image: tileset.image,
                        foundTileset: false,
                        allTilesets: new Array(countAllTilesets)
                    };


                    for(i = 0; i < countAllTilesets; i++){

                        tilesetIteration = allTilesets[i];

                        if(allTilesets[i].name == tileset.name){
                            tilesetIteration.isFoundTileset = true;
                        }
                        else{
                            tilesetIteration.isFoundTileset = false;
                        }
                        importTilesets[index].allTilesets[i] = tilesetIteration;
                    }

                    foundTileset = Tilemap.getGlobalTilesetByName(tileset.name);

                    if(foundTileset){
                        if(foundTileset.tilecount == tileset.tilecount
                            && foundTileset.imageheight == tileset.imageheight
                            && foundTileset.imagewidth == tileset.imagewidth){

                            importTilesets[index].foundTileset = foundTileset._id;
                        }
                    }

                });

                console.dir(layers);

                var jsonPreview = {
                    properties: [
                        { label: "Name", value: json.properties.name },
                        { label: "Creator", value: json.properties.author || false },
                        { label: "Width", value: json.width },
                        { label: "Height", value: json.height },
                        { label: "Tile Width", value: json.tilewidth },
                        { label: "Tile Height", value: json.tileheight },
                        { label: "Width", value: json.width },
                        { label: "Width", value: json.width }
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
                Session.set('importJsonPreview', jsonPreview);
            };

            // Read in the image file as a data URL.
            reader.readAsText(file);
        }
    },
    /**
     * Submit Import Modal
     * @param event
     */
    'click #import-json-data': function(event){
        var json = Session.get('importJson');

        var properties = [];


        if(typeof json.properties.author !== "undefined"){
            properties.push({
                field: "author", value: json.properties.author
            });
        }
        if(typeof json.properties.name !== "undefined"){
            properties.push({
                field: "name", value: json.properties.name
            });
        }
        properties.push({
            field: "width", value: json.width
        });
        properties.push({
            field: "height", value: json.height
        });
        properties.push({
            field: "tileheight", value: json.tileheight
        });
        properties.push({
            field: "tilewidth", value: json.tilewidth
        });

        Session.set('mapProperties', properties);
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
    }
});