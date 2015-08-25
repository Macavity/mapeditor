Tilesets = new Mongo.Collection('tilesets');

Tilesets.attachSchema(new SimpleSchema({
    firstgid: {
        type: Number,
        unique: true,
        min: 1
    },
    image: {
        type: String
    },
    imageheight: {
        type: Number,
        min: 1
    },
    imagewidth: {
        type: Number,
        min: 1
    },
    margin: {
        type: Number,
        min: 0,
        optional: true
    },
    name: {
        type: String,
        unique: true
    },
    properties: {
        type: Object
    },
    spacing: {
        type: Number,
        min: 0,
        optional: true
    },
    tilecount: {
        type: Number,
        min: 1
    },
    tileheight: {
        type: Number,
        min: 1
    },
    tilewidth: {
        type: Number,
        min: 1
    }
}));