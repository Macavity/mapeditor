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
    }
});

Template.mapEdit.events({
    /**
     * Import JSON Map File
     * @param event
     */
    'change #importJsonFile': function(event){
        var files = event.target.files; // FileList object

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



                Session.set('importJson', json);
            };

            // Read in the image file as a data URL.
            reader.readAsText(file);
        }
    },
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