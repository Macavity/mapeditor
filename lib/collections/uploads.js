
var uploadStore = new FS.Store.GridFS("uploads");
Uploads = new FS.Collection("uploads", {
    stores: [uploadStore],
    filter: {
        maxSize: 1048576, // in bytes
        allow: {
            extensions: ['json']
        },
        onInvalid: function (message) {
            if (Meteor.isClient) {
                sAlert.warning("Bitte nur JSON-Dateien hochladen.");
            } else {
                console.log(message);
            }
        }
    }
});