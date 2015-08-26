Meteor.methods({
    insertTileset: function(fileInfo){

        // Get the tileset with the largest firstgid value.
        var lastTileset = Tilesets.findOne({}, {$orderby: { firstgid: -1 }});

        var firstgid = 1;
        if(!!lastTileset){
            firstgid = lastTileset.firstgid+lastTileset.tilecount;
        }

        /**
         * Get tileset data
         */

        var imageInfo = Imagemagick.identify(fileInfo.url);

        var imagewidth = imageInfo.width;
        var imageheight = imageInfo.height;
        var perRow = Math.floor(imagewidth/32);
        var perCol = Math.ceil(imageheight/32);

        console.log("perRow: "+imagewidth+" => "+perRow);
        console.log("perCol: "+imageheight+" => "+perCol);
        var tilecount = (perCol*perRow);
        console.log("tilecount: "+tilecount);

        var tilesetData = {
            name: fileInfo.name.replace(".png",""),
            image: fileInfo.path,
            firstgid: firstgid,
            tilecount: tilecount,
            tileheight: 32,
            tilewidth: 32,
            imageheight: imageheight,
            imagewidth: imagewidth,
            margin: 0,
            spacing: 0
        };

        Tilesets.insert(tilesetData, function(error, result){
            if(!!error){
                console.log("Server exception");
                console.log(tilesetData);
                console.log(error.sanitizedError);
                throw new Meteor.Error(error.sanitizedError.error, error.sanitizedError.reason);
            }
        });

    }
});
