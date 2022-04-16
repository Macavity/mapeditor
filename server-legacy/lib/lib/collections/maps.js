Maps = new Mongo.Collection('maps');

/**
 * Schema for Layers
 *
 * @type {{id: String, name: String, type: String, height: Number, width: Number, x: Number, y: Number, z: Number, data: Array, visible: Boolean, opacity: Number}}
 */
var LayerSchema = {
    id: {
        type: String
    },
    name: {
        type: String
    },
    /**
     * @type {"background"|"floor"|"sky"|"fieldtypes"}
     */
    type: {
        type: String
    },
    height: { type: Number },
    width: { type: Number },
    x: { type: Number },
    y: { type: Number },
    z: { type: Number },
    data: {
        type: Array
    },
    visible: {
        type: Boolean
    },
    opacity: {
        type: Number
    }
};

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