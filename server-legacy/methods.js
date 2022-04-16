Meteor.methods({
    insertTileset: function(fileInfo){

        // Get the tileset with the largest firstgid value.
        var lastTileset = Tilesets.findOne({}, {sort: { firstgid: -1 }});

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

    },
    createMap: function(mapData){
        // Default Values. Can be changed during the edit process.
        mapData.tileheight = 32;
        mapData.tilewidth = 32;

        mapData.creatorId = Meteor.userId();
        mapData.creatorName = Meteor.user().username;

        var result = Maps.insert(mapData, function(error, result){
            if(!!error){
                console.log("Server exception");
                console.log(mapData);
                console.log(error.sanitizedError);
                throw new Meteor.Error(error.sanitizedError.error, error.sanitizedError.reason);
            }
            else {
                return result;
            }
        });

        return result;

    },
    getMapFiles: function(){
        /*var exec = Npm.require('child_process').exec;


        var child = exec('ls -l ' + process.env.PWD + '/.uploads/maps/*.json', function(error, stdout, stderr) {

            var files = [];
            var lines = stdout.split("\n");

            console.log(lines);

            var regex = /.uploads\/maps\/(.+)\.json/;

            var i, match;
            for(i = 0; i < lines.length; i++){
                if(lines[i].match(regex)){
                    match = regex.exec(lines[i]);
                    files.push(match[0]);
                }
            }

            console.log(files);

            if(error !== null) {
                console.log('exec error: ' + error);
            }
            console.log("set mapJsonFiles Session data.");
            Session.set('mapJsonFiles', files);

        });*/
    }
});
