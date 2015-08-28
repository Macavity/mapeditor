
Template.canvas.created = function(){
    this.cursor = [0,0];


};

Template.canvas.rendered = function(){

    var activeTileset = Session.get('activeTileset');
    if(typeof activeTileset === "undefined" || activeTileset === ""){
        var oneTileset = Tilesets.findOne({name: {$ne: "000-Types"}});
        if(oneTileset){
            Session.set('activeTileset', oneTileset._id);
        }
    }
    else{
        console.log(typeof Session.get('activeTileset'));
    }
};

Template.canvas.helpers({
    layers: function(){
        var layers = [
          {
              z: 1,
              id: "background",
              name: "Background",
              tiles: []
          },
          {
              z: 2,
              id: "floor1",
              name: "Layer Floor 1",
              tiles: []
          },
          {
              z: 3,
              id: "floor2",
              name: "Layer Floor 2",
              tiles: []
          },
          {
              z: 11,
              id: "sky1",
              name: "Layer Sky 1",
              tiles: []
          },
          {
              z: 12,
              id: "sky2",
              name: "Layer Sky 2",
              tiles: []
          }
        ];

        return layers;
    },
    canvasWidth: function(){
        return 30*32;
    },
    canvasHeight: function(){
        return 30*32;
    },
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
        var container = $("#active-tileset-container");
        var selection = container.find(".selection");

        var offset = container.offset();

        var activeTileset = Tilesets.findOne(Session.get('activeTileset'));
        var tileWidth = activeTileset.tilewidth;
        var tileHeight = activeTileset.tileheight;

        var selectionX = Math.floor(((event.pageX - offset.left) + container.scrollTop()) / tileWidth) * tileWidth;
        var selectionY = Math.floor(((event.pageY - offset.top) + container.scrollLeft()) / tileHeight) * tileHeight;
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
     * Complete selection of tiles
     * @param event
     */
    'mouseup #canvas': function(event){

        var activeTileset = Session.get('activeTileset');
        var tileWidth = activeTileset.tilewidth;
        var tileHeight = activeTileset.tileheight;

        var container = $("#active-tileset-container");
        var offset =  container.offset();

        // Current x position relative to the tileset area
        var selectionX = Math.floor(((event.pageX - offset.left) + container.scrollTop()) / tileWidth) * tileWidth;
        var selectionY = Math.floor(((event.pageY - offset.top) + container.scrollLeft()) / tileHeight) * tileHeight;

        var selection = container.find(".selection");

        var selectionActive = (Template.instance().newSelectionFrom !== false);

        var startX;
        var startY;

        var endX;
        var endY;

        if (selectionActive) {

            var selectionFrom = Template.instance().newSelectionFrom;
            var selectionTo = Template.instance().newSelectionTo;

            selectionTo[0] = selectionX;
            selectionTo[1] = selectionY;

            // Normalize selection, so that the start coordinates
            // are smaller than the end coordinates
            startX = selectionFrom[0] < selectionTo[0] ? selectionFrom[0] : selectionTo[0];
            startY = selectionFrom[1] < selectionTo[1] ? selectionFrom[1] : selectionTo[1];
            endX = selectionFrom[0] > selectionTo[0] ? selectionFrom[0] : selectionTo[0];
            endY = selectionFrom[1] > selectionTo[1] ? selectionFrom[1] : selectionTo[1];

            Template.instance().selectionFrom = [startX/tileWidth, startY/tileHeight];
            Template.instance().selectionTo = [endX/tileWidth, endY/tileHeight];
        }


        startX = selectionFrom[0] * tileWidth;
        startY = selectionFrom[1] * tileHeight;
        endX = selectionTo[0] * tileWidth;
        endY = selectionTo[1] * tileHeight;

        $("#canvas").find(".selection").css({
            width: (endX-startX) + tileWidth,
            height: (endY-startY) + tileHeight,
            backgroundColor: "transparent",
            backgroundPosition: (-startX) + "px " + (-startY) + "px"
        }).attr("class", "selection ts_" + activeTileset._id);

        //Editor.$("#tileset_container").find(".selection").remove();

    }
});
