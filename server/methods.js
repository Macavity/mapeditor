Meteor.methods({
    newMap: function(data){
        var userId = Meteor.userId();

        data.creatorId = userId;
        //data.creatorName = Characters.findOne({userId: userId, active: true}).name;

        data.width = 1;
        data.height = 1;

        Maps.insert(data, function(error, result){
            if(!!error){
                console.log("Server exception");
                console.log(error);
                throw new Meteor.Error(error.sanitizedError.error, error.sanitizedError.reason);
            }
        });

    },

    uploadTilemap: function(file){
        Uploads.insert(file, function (err, fileObj) {
            if (err){
                // handle error
            } else {
                // handle success depending what you need to do
                var userId = Meteor.userId();

                console.log(fileObj);

                var imagesURL = {
                    "profile.image": "/cfs/files/uploads/" + fileObj._id
                };
                Meteor.users.update(userId, {$set: imagesURL});
            }
        });
    }
});