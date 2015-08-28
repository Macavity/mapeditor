Template.editor.helpers({
    tilesets: function(){
        return Tilesets.find();
    },
    tilemapFormData: function(){
        return {
            uploadType: "tilemap"
        };
    },
    mapUploadComplete: function(){
        return {
            finished: function(index, fileInfo, context) {

                Meteor.call('insertTileset', fileInfo);
            }
        };
    }
});

Template.editor.events({
    'change #btn-upload-tileset': function(event) {
        _.each(event.srcElement.files, function(file) {
            Meteor.saveFile(file, file.name);
        });
    }
});