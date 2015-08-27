Template.tilesetBox.created = function(){

    /**
     * Start point of a tile selection
     * @type {Array|boolean}
     */
    this.newSelectionFrom = false;

    /**
     * End point of a tile selection
     * @type {Array|boolean}
     */
    this.newSelectionTo = false;

};

Template.tilesetBox.helpers({
    tilesets: function(){
        return Tilesets.find({name: {$ne: "000-Types"}});
    },
    activeTileset: function(){
        var savedTileset = Tilesets.findOne({_id: Session.get('activeTileset')});
        if(!savedTileset){
            savedTileset = Tilesets.findOne({name: {$ne: "000-Types"}});
        }
        if(!savedTileset){
            return false;
        }
        savedTileset.image = "/.uploads/"+savedTileset.image;

        return savedTileset;
    }
});

Template.tilesetBox.events({
    'click .tileset-switch': function (event) {
        var target = $(event.currentTarget).closest('.tileset-switch');
        var targetTilesetId = target.data('tsid');

        Session.set('activeTileset', targetTilesetId);
    },
    'mousedown': function(event){
        AppState.mousedown = true;
        console.log("AppState.mousedown: "+AppState.mousedown);
    },
    'mouseup': function(event){
        AppState.mousedown = false;
        console.log("AppState.mousedown: "+AppState.mousedown);
    },

    /**
     * Start a new selection of tiles
     * @param event
     */
    'mousedown #active-tileset-container': function (event) {
        var container = $("#active-tileset-container");
        var selection = container.find(".selection");

        var offset = container.offset();

        var activeTileset = Tilesets.findOne(Session.get('activeTileset'));
        var tileWidth = activeTileset.tilewidth;
        var tileHeight = activeTileset.tileheight;

        var selectionX = Math.floor(((event.pageX - offset.left) + container.scrollTop()) / tileWidth) * tileWidth;
        var selectionY = Math.floor(((event.pageY - offset.top) + container.scrollLeft()) / tileHeight) * tileHeight;

        selection.css({
            left: selectionX,
            top: selectionY,
            width: tileWidth,
            height: tileHeight
        });

        //console.log("delete brushSelection");
        Session.set('brushSelection', false);

        Template.instance().newSelectionFrom = [selectionX, selectionY];
    },
    /**
     * Drag a selection or just move the selection tile
     * @param event
     */
    'mousemove #active-tileset-container': function(event){

        var container = $("#active-tileset-container");
        var offset =  container.offset();

        var selection = container.find(".selection");

        var activeTileset = Tilesets.findOne(Session.get('activeTileset'));
        var tileWidth = activeTileset.tilewidth;
        var tileHeight = activeTileset.tileheight;

        var selectionX = Math.floor(((event.pageX - offset.left) + container.scrollTop()) / tileWidth) * tileWidth;
        var selectionY = Math.floor(((event.pageY - offset.top) + container.scrollLeft()) / tileHeight) * tileHeight;

        /**
         * Resize the selection element
         */
        if (AppState.mousedown) {

            var selectionFrom = Template.instance().newSelectionFrom;

            var fromX = selectionFrom[0];
            var fromY = selectionFrom[1];

            var selectionWidth = Math.abs((selectionX - fromX) + tileWidth);
            var selectionHeight = Math.abs((selectionY - fromY) + tileHeight);

            // Selection goes right
            if (fromX <= selectionX) {
                selection.css({
                    left: fromX,
                    width: selectionWidth
                });
            }
            // Selection goes left
            else {
                selection.css({
                    left: selectionX,
                    width: selectionWidth + tileWidth*2
                });
            }
            // Selection goes down
            if (fromY <= selectionY) {
                selection.css({
                    top: fromY,
                    height: selectionHeight
                });
            }
            // Selection goes up
            else {
                selection.css({
                    top: selectionY,
                    height: selectionHeight + tileHeight*2
                });
            }
        }
        /**
         * Move the selection element
         */
        else {
            selection.css({
                top: selectionY,
                left: selectionX,
                width: tileWidth,
                height: tileHeight
            });
        }
    },
    /**
     * Complete selection of tiles
     * @param event
     */
    'mouseup #active-tileset-container': function(event){

        var activeTileset = Tilesets.findOne(Session.get('activeTileset'));
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
            var selectionTo = [];

            selectionTo[0] = selectionX;
            selectionTo[1] = selectionY;

            // Normalize selection, so that the start coordinates
            // are smaller than the end coordinates
            startX = selectionFrom[0] < selectionTo[0] ? selectionFrom[0] : selectionTo[0];
            startY = selectionFrom[1] < selectionTo[1] ? selectionFrom[1] : selectionTo[1];
            endX = selectionFrom[0] > selectionTo[0] ? selectionFrom[0] : selectionTo[0];
            endY = selectionFrom[1] > selectionTo[1] ? selectionFrom[1] : selectionTo[1];

            // Save brushSelection
            /*Session.set('brushSelection', {
                //[[startX/tileWidth, startY/tileHeight],[endX/tileWidth, endY/tileHeight]]
            });*/
        }


        startX = selectionFrom[0];
        startY = selectionFrom[1];
        endX = selectionTo[0];
        endY = selectionTo[1];

        /**
         * Change the canvas brush
         */
        var brushWidth = (endX-startX) + tileWidth;
        var brushHeight = (endY-startY) + tileHeight;
        Session.set('brushSelection', {
            width: brushWidth,
            height: brushHeight,
            backgroundImage: "/.uploads/"+activeTileset.image,
            backgroundPosition: (-startX) + "px " + (-startY) + "px"
        });
        //console.log("saved brushSelection: w:"+brushWidth+",h:"+brushHeight);

        //selection.remove();

    }
});