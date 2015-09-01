Template.mapEdit.created = function () {

    Session.set('importJson', {});

    this.activeTool = "draw";

};

Template.mapEdit.rendered = function(){
    Tilemap.initialize(this.data.map);
};

Template.mapEdit.helpers({
    tilesets: function(){
        return Tilesets.find();
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

        var activeTool = Template.instance().activeTool;

        _.each(tools, function(tool){
            if(tool.tool == activeTool){
                tool.active = true;
            }
        });
    },
    activeTool: function(){
        return Template.instance().activeTool;
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

    },
    'click #btn-grid-checkbox': function(/*event*/){
        var $checkbox = $("#grid-checkbox");
        var $button = $("#btn-grid-checkbox");
        var $grid = $("#grid");
        var $icon = $button.find("i");

        var isChecked = $checkbox.is(':checked');

        if(isChecked){
            // Deaktivate Grid
            $checkbox.prop("checked", false);
            $button.data('state', "off");
            $button.removeClass('btn-primary active')
                .addClass('btn-default');
            $icon.removeClass('glyphicon-check').addClass('glyphicon-unchecked');
            $grid.hide();
        }
        else{
            // Activate Grid
            $checkbox.prop("checked", true);
            $button.data('state', "on");
            $button.removeClass('btn-default')
                .addClass('btn-primary active');
            $icon.removeClass('glyphicon-unchecked').addClass('glyphicon-check');
            $grid.show();
        }
    }
});