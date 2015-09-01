Meteor.startup(function () {

    /**
     * Posts
     */
    if (Posts.find().count() === 0) {
        Posts.insert({
            title: 'Tilemap Editor',
            body: 'Open to suggestions.'
        });
    }

    /**
     * Tilesets
     */
    if(Tilesets.find().count() == 0){
        Tilesets.insert({
            "name" : "000-Types",
            "image" : "tilesets/000-Types.png",
            "firstgid" : 1,
            "tilecount" : 9,
            "tileheight" : 32,
            "tilewidth" : 32,
            "imageheight" : 96,
            "imagewidth" : 96,
            "margin" : 0,
            "spacing" : 0
        });
        Tilesets.insert({
            "name" : "magecity",
            "image" : "tilesets/magecity.png",
            "firstgid" : 10,
            "tilecount" : 368,
            "tileheight" : 32,
            "tilewidth" : 32,
            "imageheight" : 1450,
            "imagewidth" : 256,
            "margin" : 0,
            "spacing" : 0
        });
    }

});