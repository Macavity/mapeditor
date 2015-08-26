Meteor.startup(function () {

    /**
     * Posts
     */
    if (Posts.find().count() === 0) {
        Posts.insert({
            title: 'Last Anixile RPG - Revived',
            body: 'Man hat ja sonst nichts zu tun..'
        });

        Posts.insert({
            title: 'Mithelfer gesucht!',
            body: 'Gerne auch frühere Spieler um wiederherzustellen was mal war ;-)'
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
    }

});