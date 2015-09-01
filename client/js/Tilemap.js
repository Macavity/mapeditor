/**
 *
 */
Tilemap = (function ($) {

    /**
     * Available loggin level types
     * @type {{NONE: number, INFO: number, WARN: number, DEBUG: number}}
     */
    var LOG_LEVEL = {
        NONE: 0,
        INFO: 1,
        WARN: 2,
        DEBUG: 3
    };

    /**
     * Enables/Disables the severel different logging level types
     * @type {number}
     */
    var logLevel = LOG_LEVEL.DEBUG;

    var keyCodeMap = {
        tab: 9,
        enter: 13,
        esc: 27,
        left: 37,
        up: 38,
        right: 39,
        down: 40
    };

    var tileBySet = [];

    var Controller = this;

    var canvas;

    /**
     * @type {CanvasRenderingContext2D}
     */
    var mapElement;
    var layers = [];

    var initialized = false;

    var sprites = [];
    var spritesToLoad = 0;

    var mapData = {
        "properties": {
            "author": "shizo",
            "name": "Dalaran - Nordtor"
        },
        "height": 30,
        "width": 30,
        "orientation": "orthogonal",
        "renderorder": "right-down",
        "tileheight": 32,
        "tilewidth": 32,
        "layers": [
            {
                "name": "Background",
                "type": "tilelayer",
                "height": 30,
                "width": 30,
                "data": [
                    1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1,
                    3, 0, 0, 0, 0, 0, 2, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2,
                    3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2,
                    3, 0, 0, 0, 0, 0, 0, 0, 2, 1, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2,
                    3, 0, 0, 0, 0, 0, 0, 0, 2, 11, 1, 1, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2,
                    3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 1, 1, 1, 1, 3, 0, 0, 0, 0, 0, 0, 0, 2,
                    3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2,
                    3, 0, 0, 0, 2, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2,
                    3, 0, 2, 1, 1, 1, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 1, 1, 1,
                    3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 1, 1, 1, 1, 1,
                    1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1
                ]
            }
        ],
        "tilesets": [
            {
                "_id" : "hsimANpFmwuzRJGHF",
                "name" : "000-Types",
                "image" : "tilesets/000-Types.png",
                "firstgid" : 1,
                "tilecount" : 9,
                "tileheight" : 32,
                "tilewidth" : 32,
                "imageheight" : 96,
                "imagewidth" : 96,
                "margin" : 0,
                "spacing" : 0
            }, {
                "_id" : "FaWPqnNdAQd7WoE9P",
                "name" : "magecity",
                "image" : "tilesets/magecity.png",
                "firstgid" : 10,
                "tilecount" : 368,
                "tileheight" : 32,
                "tilewidth" : 32,
                "imageheight" : 1450,
                "imagewidth" : 256,
                "margin" : 0,
                "spacing" : 0
            }
        ],
        "version": 1
    };
    var initialize = function(){

        var canvasElements = $("#canvas").find("canvas");

        _.each(canvasElements, function(canvas, index){
            layers[index] = canvas.getContext("2d");
        });

        _.each(mapData.tilesets, function(tileset, index){
            var img = new Image();
            img.src = "/.uploads/" + tileset.image;
            img.onload = allSpritesLoaded;
            mapData.tilesets[index].img = img;
            sprites[index] = img;
        });
        spritesToLoad = sprites.length;

        initialized = true;

    };

    var allSpritesLoaded = function(){
        spritesToLoad--;
        if(!spritesToLoad){
            drawMap();
        }
    };

    var drawMap = function(){

        var layerData = mapData.layers[0].data;

        //mapElement.drawImage(image, 0, 0, 32, 32, 0, 0, 32, 32);


        _(layerData).each(function(tileId, index) {
            //_(row).each(function(tileId, x){
                //debug("x:"+x);

                if(tileId !== 0){
                    var x = Math.floor(index % mapData.layers[0].width);
                    var y = Math.floor(index / mapData.layers[0].height);
                    drawTile(x, y, tileId);
                }
            //});
        });

    };

    var drawTile = function(x,y, tileId){
        debug("drawTile "+x+","+y+","+tileId);

        var tile = getTile(tileId);



        mapElement.drawImage(tile.image, tile.x, tile.y, mapData.tilewidth, mapData.tileheight, x* mapData.tilewidth, y*mapData.tileheight, mapData.tilewidth, mapData.tileheight);

        /*mapElement.fillRect(
            x * 32, y * 32,
            32, 32
        );*/
    };

    /**
     *
     * @param tileId
     * @returns {{x: number, y: number, image: HTMLImageElement}}
     */
    var getTile = function(tileId){
        var tile = {};

        /*
         * Loop through the tilesets searching for the one where firstgid <= tileId
         */
        var i;
        for (i = mapData.tilesets.length-1; i >= 0; i--) {
            if (mapData.tilesets[i].firstgid <= tileId)
                break;
        }

        var tileset = mapData.tilesets[i];

        tile.image = sprites[i];

        /*
         * Calculate x/y offset based on the properties of this tileset
         */
        var rowCount = Math.floor(tileset.imagewidth / tileset.tilewidth);
        var colCount = Math.floor(tileset.imageheight / tileset.tileheight);

        var localTileId = tileId - tileset.firstgid;

        var localTileX = Math.floor(localTileId % colCount);
        var localTileY = Math.floor(localTileId / rowCount);


        tile.x = (localTileX * tileset.tilewidth);
        tile.y = (localTileY * tileset.tileheight);

        //debug(tile);

        return tile;
    };

    var info = function(string){
        if(logLevel >= LOG_LEVEL.INFO){
            console.log(string);
        }
    };

    var warn = function(string){
        if(logLevel >= LOG_LEVEL.WARN){
            console.log(string);
        }
    };

    var debug = function(string){
        if(logLevel == LOG_LEVEL.DEBUG){
            console.log(string);
        }
    };

    // Return public variables and methods
    return {
        initialize: initialize,
        drawMap: drawMap,
        drawTile: drawTile
    };

}($));
