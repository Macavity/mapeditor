Meteor.startup(function () {
    UploadServer.init({
        tmpDir: process.env.PWD + '/.uploads/tmp',
        uploadDir: process.env.PWD + '/.uploads/',
        checkCreateDirectories: true, //create the directories for you
        getDirectory: function(fileInfo, formData) {
            // create a sub-directory in the uploadDir based on the content type (e.g. 'images')
            if(formData.uploadType == "tileset"){
                return "tilesets";
            }
            else {
                return "images";
            }
        },
        overwrite:true
    })
});