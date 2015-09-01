
Template.canvas.created = function(){
    this.cursor = [0,0];
    this.defaultActiveTool = "draw";

};

Template.canvas.rendered = function(){

};

Template.canvas.helpers({
    tileset: function(){
        var tilesetId = Session.get('activeTileset');
        if(!!tilesetId){
            return Tilesets.findOne(tilesetId);
        }
        else{
            return {
                tilewidth: 0,
                tileheight: 0
            }
        }
    },
    showGrid: function(){
        return (typeof Session.get('showGrid') === "undefined") ? true : Session.get('showGrid');
    },
    brushSelection: function(){

        var brushSelection = Session.get('brushSelection');

        if(brushSelection === false){
            //console.log("no brushSelection");
            return {
                width: 0,
                height: 0,
                backgroundImage: "",
                backgroundPosition: ""
            }
        }
        else {
            //console.log("brushSelection active");
            return brushSelection;
        }

    }
});


Template.canvas.events({
    /**
     * Activate a Brush on the Canvas
     * @param event
     */
    'mousedown #canvas': function (event) {
        var container = $("#canvas");
        var selection = container.find(".selection");

        var offset = container.offset();

        var activeTileset = Tilesets.findOne(Session.get('activeTileset'));
        var tileWidth = activeTileset.tilewidth;
        var tileHeight = activeTileset.tileheight;

        var x = Math.floor((event.pageX - offset.left) / tileWidth);
        var y = Math.floor((event.pageY - offset.top) / tileHeight);

        var activeTool = Session.get('activeTool') || Template.instance().defaultActiveTool;

        console.log("click on: "+x+"/"+y);


        /*
        selection.css({
            left: selectionX,
            top: selectionY,
            width: tileWidth,
            height: tileHeight
        });
        */
    },
    /**
     * Move the selection element
     * @param event
     */
    'mousemove #canvas': function(event){

        var container = $("#canvas");
        var offset =  container.offset();

        var selection = container.find(".selection");

        var activeTileset = Tilesets.findOne(Session.get('activeTileset'));
        var tileWidth = activeTileset.tilewidth;
        var tileHeight = activeTileset.tileheight;

        var x = Math.floor((event.pageX - offset.left) / tileWidth);
        var y = Math.floor((event.pageY - offset.top) / tileHeight);

        var currentCursor = Template.instance().cursor;

        // Only move if the position changed to another tile
        if(currentCursor[0] !== x || currentCursor[1] !== y){
            Template.instance().cursor = [x,y];
            selection.css({
                top: y * tileHeight,
                left: x * tileWidth
            });
        }

    },
    /**
     * @param event
     */
    'mouseup #canvas': function(event){

    }
});
