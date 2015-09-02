
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

        // The currently used tool
        var activeTool = Session.get('activeTool') || Template.instance().defaultActiveTool;

        if(activeTool == "erase"){
            return {
                width: 32,
                height: 32,
                backgroundImage: "",
                backgroundPosition: ""
            }
        }

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

        // The currently active Layer
        var activeLayer = container.find("canvas.layer-active");
        var context = activeLayer[0].getContext("2d");

        // The currently used tool
        var activeTool = Session.get('activeTool') || Template.instance().defaultActiveTool;

        var posX = (event.pageX - offset.left);
        var posY = (event.pageY - offset.top);

        /*
         * Erase Tool
         */
        if(activeTool == "erase"){
            Tilemap.eraseTile(context, posX, posY);
            return;
        }
        /*
         * Draw Tool
         */

        var brushSelection = Session.get('brushSelection');

        var tileset = brushSelection.tileset;

        var tilesetTileWidth = tileset.tilewidth;
        var tilesetTileHeight = tileset.tileheight;

        var tilesetCols = tileset.imagewidth / tilesetTileWidth;

        var brushCols = brushSelection.width / tilesetTileWidth;
        var brushRows = brushSelection.height / tilesetTileHeight;

        // Coordinates of the clicked tile
        var x = Math.floor(posX / tilesetTileWidth);
        var y = Math.floor(posY / tilesetTileHeight);

        console.log("Active Layer: "+activeLayer.data("name"));

        console.dir(tileset);
        console.dir(brushSelection);

        var indexX;
        var indexY;
        var tileId;
        var firstTileId = tileset.firstgid;

        var tilePosition = 0;

        // Get X/Y Coordinates of selected brush tile inside tileset
        var brushStartX = brushSelection.startX/tilesetTileWidth;

        var brushStartY = brushSelection.startY/tilesetTileHeight;

        if(brushCols == 1 && brushRows == 1){

            tileId = firstTileId + (brushStartY * tilesetCols) + brushStartX;

            console.log("Tile:"+tileId+", "+x+"/"+y);

            Tilemap.drawTile(context, x, y, tileId);
        }


        /*for(indexY = 0; indexY < brushRows; indexY++){
            for(indexX = 0; indexX < brushCols; indexX){
                /*
                    brushRows 1
                    brushCols 1
                    background -64 -32

                    1       2       3
                    0,0     1,0     2,0

                    4       (5)       6
                            32/32
                    0,1     1,1     2,1

                    7       8       9
                    0,2     1,2     2,2

                    32/32 durch 32 => x1 y1 => tileId = y*ColCount (3) + (x+1) = 5

                    tile 8:
                    x1/y2 => firstgid + y*3 + x = 1+6+1 = 8
            }
        }
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

        // The currently used tool
        var activeTool = Session.get('activeTool') || Template.instance().defaultActiveTool;

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
    'mouseleave #canvas': function(){
        var selection = $("#canvas").find(".selection");

        selection.hide();
    },
    'mouseenter #canvas': function(){
        var selection = $("#canvas").find(".selection");
        var activeBrush = Session.get('brushSelection') || false;

        if(activeBrush){
            selection.show();
        }
    },
    /**
     * @param event
     */
    'mouseup #canvas': function(event){

    }
});
