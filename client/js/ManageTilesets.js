Template.ManageTilesets.helpers({
    tilesets: function(){
        return Tilesets.find();
    },
    tilesetFormData: function(){
        return {
            uploadType: "tileset"
        };
    },
    tilesetUploadComplete: function(){
        return {
            finished: function(index, fileInfo, context) {

                Meteor.call('insertTileset', fileInfo);
            }
        };
    }
});

Template.ManageTilesets.events({
    
});