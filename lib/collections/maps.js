Maps = new Meteor.Collection('maps');

Maps.attachSchema(new SimpleSchema({
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
    }
}));