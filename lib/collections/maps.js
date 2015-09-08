Maps = new Mongo.Collection('maps');

/**
 *
 * @type {{name: {String}, creatorId: {String}, creatorName: {String}, width: {Number}, height: {Number}, tilewidth: {Number}, tileheight: {Number}, tilesets: {[Object]}, layers: Array[Object], nextobjectid: {Number}, orientation: {String}, renderorder: {String}, properties: {Object}, version: {Number}}}
 */
var MapSchema = {
    name: {
        type: String,
        index: true,
        unique: true
    },
    creatorId: {
        type: String
    },
    creatorName: {
        type: String,
        optional: true
    },

    width: {
        type: Number
    },
    height: {
        type: Number
    },

    tilewidth: {
        type: Number
    },
    tileheight: {
        type: Number
    },
    tilesets: {
        type: [Object],
        optional: true
    },
    layers: {
        type: [Object],
        optional: true
    },
    nextobjectid: {
        type: Number,
        optional: true
    },
    orientation: {
        type: String,
        optional: true
    },
    renderorder: {
        type: String,
        optional: true
    },

    properties: {
        type: Object,
        optional: true
    },

    version: {
        type: Number,
        optional: true
    }
};

Maps.attachSchema(new SimpleSchema(MapSchema));