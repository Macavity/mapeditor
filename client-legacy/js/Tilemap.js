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

    var layerIdIndex;

    var layerTypeBg = 0;
    var layerTypeFt = 1;
    var layerTypeFloor = 2;
    var layerTypeSky = 3;

    var layerTypeStartZ = [
        // Background   1-9
        1,
        // Field Type   100+
        100,
        // Floor        10-49
        11,
        // Sky          50-99
        51
    ];

    /**
     *
     * @param {MapSchema} mapParam
     * @param allTilesetsParam
     */
    var initialize = function (mapParam, allTilesetsParam) {

        map = mapParam;

        if (typeof allTilesetsParam !== "undefined") {
            allTilesets = allTilesetsParam;
        }

        // Reset variables
        layers = [];

        var lastTileset = map.tilesets[map.tilesets.length - 1];

        // preallocate arrays
        localToGlobalTile = new Array(lastTileset.firstgid + lastTileset.tilecount);
        layerIdIndex = new Array(map.layers.length);

        initCanvasElements();

        convertTileData();

        if (map.tilesets.length) {
            loadSprites();
        } else {
            drawMap();
        }

        initialized = true;

    };

    var importTileMap = function (newMapData) {
        map = newMapData;

        // Reset Variables
        layers = [];

        var lastTileset = allTilesets[allTilesets.length - 1];

        // preallocate arrays
        localToGlobalTile = new Array(lastTileset.firstgid + lastTileset.tilecount);
        layerIdIndex = new Array(map.layers.length);

        /*
         * Section: Canvas
         */
        var canvasSection = $("#section-canvas");

        // Delete all currently existing canvas elements
        canvasSection.empty();

        var brushSelection = Session.get('brushSelection');

        var canvasWidth = map.width * map.tilewidth;
        var canvasHeight = map.height * map.tileheight;

        map.layers = prepareLayersForCanvas(map.layers, canvasWidth, canvasHeight);

        Blaze.renderWithData(Template.canvas, {
            canvasWidth: canvasWidth,
            canvasHeight: canvasHeight,
            layers: map.layers
        }, canvasSection[0]);

        /*
         * Section: Properties
         */
        var propertiesSection = $("#section-properties").empty();

        // Delete all currently existing canvas elements
        canvasSection.empty();

        Blaze.renderWithData(Template.properties, {
            properties: mapPropertiesList(map)
        }, propertiesSection[0]);

    };

    /**
     *
     * @param {[LayerSchema]} layers
     * @returns {*}
     * @param canvasWidth
     * @param canvasHeight
     */
    var prepareLayersForCanvas = function (layers, canvasWidth, canvasHeight) {
        _.each(layers, function (layer, index) {
            /**
             * @type LayerSchema
             * @var layer
             */

            layer.active = false;
            layer.visible = true;
            layer.canvasWidth = canvasWidth;
            layer.canvasHeight = canvasHeight;

            if (layer.type == "background") {
                layer.active = true;
            } else if (layer.type == "fieldtypes") {
                // FieldTypes are invisible at start
                layer.visible = false;
            } else if (layer.type == "sky") {
                // Sky Layer
            } else {
                // Floor Layer
            }

            layers[index] = layer;
        });

        return layers;

    };


    /**
     * Get the context of every layer's canvas
     */
    var initCanvasElements = function () {
        var canvasElements = $("#canvas").find("canvas");

        _.each(canvasElements, function (canvas, index) {
            layers[index] = canvas.getContext("2d");
            layerIdIndex[index] = $(canvas).data("id");
        });
    };

    var loadSprites = function () {
        _.each(allTilesets, function (tileset, index) {
            spritesToLoad++;
            var img = new Image();
            img.src = "/.uploads/" + tileset.image;
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
    var convertTileData = function () {

        var localTileset, globalTileset, relativeId, globalId;

        var tryouts = 5;
        var countAllTilesets = allTilesets.length;

        _.each(map.layers, function (layer, layerIndex) {
            _.each(layer.data, function (localTile, tileIndex) {

                //if(--tryouts > 0){

                if (typeof localToGlobalTile[localTile] !== "undefined") {
                    layer.data[tileIndex] = localToGlobalTile[localTile];
                } else if (localTile !== 0) {
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

    var allSpritesLoaded = function () {
        spritesToLoad--;
        if (!spritesToLoad) {
            drawMap();
        }
    };

    var drawMap = function () {

        _.each(map.layers, function (layer) {
            debug("draw layer " + layer.id);
            var canvas = $("#layer-" + layer.id);
            var context = canvas[0].getContext("2d");

            _(layer.data).each(function (tileId, index) {
                if (tileId !== 0) {
                    var x = Math.floor(index % map.width);
                    var y = Math.floor(index / map.height);
                    drawTile(context, x, y, tileId);
                }
            });

        });

    };

    var drawTile = function (context, x, y, tileId) {
        //debug("drawTile "+x+","+y+":");

        var tile = getTile(tileId);

        if (!tile || tile === 0) {
            return;
        }

        // Clear the tile first.
        context.clearRect(
            x * map.tilewidth,
            y * map.tileheight,
            map.tilewidth,
            map.tileheight
        );

        // Draw the Tile
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

    var eraseTile = function (context, posX, posY) {

        var tilewidth = map.tilewidth;
        var tileheight = map.tileheight;

        var x = Math.floor(posX / tilewidth);
        var y = Math.floor(posY / tileheight);

        // Clear the tile first.
        debug("eraseTile: " + x + "/" + y);
        context.clearRect(
            x * tilewidth,
            y * tileheight,
            tilewidth,
            tileheight
        );
    };

    var getLocalTilesetByTile = function (tileId) {
        var i;
        for (i = map.tilesets.length - 1; i >= 0; i--) {
            if (map.tilesets[i].firstgid <= tileId)
                break;
        }
        return map.tilesets[i];
    };

    var getGlobalTilesetByTile = function (tileId) {
        var i;
        for (i = allTilesets.length - 1; i >= 0; i--) {
            if (allTilesets[i].firstgid <= tileId)
                break;
        }
        return allTilesets[i];
    };

    /**
     *
     * @param tileName
     * @returns {Boolean|Object}
     */
    var getGlobalTilesetByName = function (tileName) {
        var i, length = allTilesets.length;
        for (i = 0; i < length; i++) {
            if (allTilesets[i].name == tileName) {
                return allTilesets[i];
            }
        }
        return false;
    };

    /**
     *
     * @param tileId
     * @returns {{x: number, y: number, image: HTMLImageElement}}
     */
    var getTile = function (tileId) {
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

    var getLayerContext = function (layerId) {

        var index = layerIdIndex.indexOf(layerId);
        return layers[index];

    };

    /**
     * Calculates the Z Value of a layer according to its type
     * and the count of layer of the same type
     * @param layerType
     * @param countLayerTypes
     * @returns {number}
     */
    var calcLayerZ = function (layerType, countLayerTypes) {

        //console.log("calcLayerZ "+layerType);

        var layerTypeId = getLayerTypeId(layerType);


        var layerTypeCount = countLayerTypes[layerTypeId];

        //console.log("|- LayerType: "+layerTypeId);
        //console.log("|- LayerType Count: "+layerTypeCount);

        var z = (layerTypeStartZ[layerTypeId] + layerTypeCount - 1);

        //console.log("|- Z: "+z);

        return z;
    };

    /**
     *
     * @param layerType
     * @returns {Number}
     */
    var getLayerTypeId = function (layerType) {
        if (layerType == "background") {
            return layerTypeBg;
        } else if (layerType == "fieldtypes") {
            return layerTypeFt;
        } else if (layerType == "sky") {
            return layerTypeSky;
        } else {
            return layerTypeFloor;
        }

    };

    var info = function (string) {
        if (logLevel >= LOG_LEVEL.INFO) {
            console.log(string);
        }
    };

    var warn = function (string) {
        if (logLevel == LOG_LEVEL.WARN || logLevel == LOG_LEVEL.DEBUG) {
            console.log(string);
        }
    };

    var debug = function (string) {
        if (logLevel == LOG_LEVEL.DEBUG) {
            console.log(string);
        }
    };

    // Return public variables and methods
    return {
        initialize: initialize,
        importTileMap: importTileMap,
        prepareLayersForCanvas: prepareLayersForCanvas,
        mapPropertiesList: mapPropertiesList,
        drawMap: drawMap,
        eraseTile: eraseTile,
        drawTile: drawTile,
        calcLayerZ: calcLayerZ,
        allTilesets: function () {
            return allTilesets
        },
        getGlobalTilesetByName: getGlobalTilesetByName,
        getLayer: getLayerContext
    };

}($));
