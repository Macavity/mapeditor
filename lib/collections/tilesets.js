Tilesets = new Mongo.Collection('tilesets');

Tilesets.attachSchema(new SimpleSchema({
    name: {
        type: String,
        unique: true
    },
    image: {
        type: String
    },

    // Image Information
    imageheight: {
        type: Number,
        min: 1,
        optional: true
    },
    imagewidth: {
        type: Number,
        min: 1,
        optional: true
    },
    margin: {
        type: Number,
        min: 0,
        optional: true
    },
    spacing: {
        type: Number,
        min: 0,
        optional: true
    },

    // Tileset Information
    firstgid: {
        type: Number,
        unique: true,
        min: 1,
        optional: true
    },

    tilecount: {
        type: Number,
        min: 1,
        optional: true
    },
    tileheight: {
        type: Number,
        min: 1
    },
    tilewidth: {
        type: Number,
        min: 1
    },

    // Additional Properties
    properties: {
        type: Object,
        optional: true
    }
}));