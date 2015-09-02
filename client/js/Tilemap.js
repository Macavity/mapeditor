/**
 * Tilemap Functions
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

    /**
     * @type {MapSchema}
     */
    var map;

    var allTilesets;

    var localToGlobalTile;

    /**
     *
     * @param {MapSchema} mapParam
     * @param allTilesetsParam
     */
    var initialize = function(mapParam, allTilesetsParam){

        map = mapParam;

        allTilesets = allTilesetsParam;

        var lastTileset = map.tilesets[map.tilesets.length-1];

        // preallocate array
        localToGlobalTile = new Array(lastTileset.firstgid + lastTileset.tilecount);

        initCanvasElements();

        convertTileData();

        if(map.tilesets.length){
            loadSprites();
        }
        else {
            drawMap();
        }

        initialized = true;

    };

    /**
     * Get the context of every layer's canvas
     */
    var initCanvasElements = function(){
        var canvasElements = $("#canvas").find("canvas");

        _.each(canvasElements, function(canvas, index){
            layers[index] = canvas.getContext("2d");
        });
    };

    var loadSprites = function(){
        _.each(allTilesets, function(tileset, index){
            spritesToLoad++;
            var img = new Image();
            img.src = "/.uploads/"+tileset.image;
            img.onload = allSpritesLoaded;

            tileset.colCount = Math.floor(tileset.imagewidth / tileset.tilewidth);
            tileset.rowCount = Math.floor(tileset.imageheight / tileset.tileheight);

            tileset.img = img;

            allTilesets[index] = tileset;
        });
    };

    /**
     * The Maps use tile ids relative to the actually used tilesets
     * The Editor has all tilesets available so all relative tile ids have to be converted
     * to the global tile ids
     */
    var convertTileData = function(){

        var localTileset, globalTileset, relativeId, globalId;

        var tryouts = 5;
        var countAllTilesets = allTilesets.length;

        _.each(map.layers, function(layer, layerIndex){
            _.each(layer.data, function(localTile, tileIndex){

                //if(--tryouts > 0){

                    if(typeof localToGlobalTile[localTile] !== "undefined"){
                        layer.data[tileIndex] = localToGlobalTile[localTile];
                    }
                    else if(localTile !== 0){
                        //debug("---------------------");
                        //debug("LocalTile: "+localTile);

                        // Find Local Tileset
                        localTileset = getLocalTilesetByTile(localTile);
                        //debug("localTileset:"); debug(localTileset);

                        // Find Global Tileset
                        globalTileset = false;
                        for (var i = 0; i < countAllTilesets; i++) {
                            if (allTilesets[i]._id == localTileset.id)
                                globalTileset = allTilesets[i];
                        }
                        //debug("globalTileset:"); debug(globalTileset);

                        relativeId = localTile - localTileset.firstgid;
                        //debug("relativeId: "+relativeId);

                        globalId = globalTileset.firstgid + relativeId;
                        //debug("globalId: "+globalId);
                        localToGlobalTile[localTile] = globalId;
                        layer.data[tileIndex] = globalId;

                    }
                //}
            });
            map.layers[layerIndex] = layer;
        });
    };

    var allSpritesLoaded = function(){
        spritesToLoad--;
        if(!spritesToLoad){
            drawMap();
        }
    };

    var drawMap = function(){

        _.each(map.layers, function(layer){
            debug("draw layer "+layer.id);
            var canvas = $("#layer-"+layer.id);
            var context = canvas[0].getContext("2d");

            _(layer.data).each(function(tileId, index) {
                if (tileId !== 0) {
                    var x = Math.floor(index % map.width);
                    var y = Math.floor(index / map.height);
                    drawTile(context, x, y, tileId);
                }
            });

        });

    };

    var drawTile = function(context, x,y, tileId){
        //debug("drawTile "+x+","+y+":");

        var tile = getTile(tileId);

        if(!tile || tile === 0){
            return;
        }
        context.drawImage(
            tile.image,
            // The X coordinate where to start clipping
            tile.x,
            // The Y coordinate where to start clipping
            tile.y,
            // Clipping width
            tile.width,
            // Clipping height
            tile.height,
            // The X coordinate where to place the image on the canvas
            x * map.tilewidth,
            // The Y coordinate where to place the image on the canvas
            y * map.tileheight,
            // Width & Height on the canvas
            map.tilewidth,
            map.tileheight);
    };

    var getLocalTilesetByTile = function(tileId){
        var i;
        for (i = map.tilesets.length-1; i >= 0; i--) {
            if (map.tilesets[i].firstgid <= tileId)
                break;
        }
        return map.tilesets[i];
    };

    var getGlobalTilesetByTile = function(tileId){
        var i;
        for (i = allTilesets.length-1; i >= 0; i--) {
            if (allTilesets[i].firstgid <= tileId)
                break;
        }
        return allTilesets[i];
    };

    /**
     *
     * @param tileId
     * @returns {{x: number, y: number, image: HTMLImageElement}}
     */
    var getTile = function(tileId){
        //debug("getTile: "+tileId);
        var tile = {
            x: 0,
            y: 0,
            image: HTMLImageElement
        };


        /*
         * Loop through the tilesets searching for the one where firstgid <= tileId
         */
        var tileset = getGlobalTilesetByTile(tileId);

        tile.image = tileset.img;

        /*
         * Calculate x/y offset based on the properties of this tileset
         */

        var localTileId = tileId - tileset.firstgid;

        var localTileX = Math.floor(localTileId % tileset.colCount);
        var localTileY = Math.floor(localTileId / tileset.colCount);

        //debug(["localTile: "+localTileId+", "+localTileX+"/"+localTileY]);

        tile.x = (localTileX * tileset.tilewidth);
        tile.y = (localTileY * tileset.tileheight);
        tile.width = tileset.tilewidth;
        tile.height = tileset.tileheight;

        //debug(tile);

        return tile;
    };

    var info = function(string){
        if(logLevel >= LOG_LEVEL.INFO){
            console.log(string);
        }
    };

    var warn = function(string){
        if(logLevel == LOG_LEVEL.WARN || logLevel == LOG_LEVEL.DEBUG){
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
