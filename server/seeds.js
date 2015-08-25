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
        /**
         * @TODO Find a standard tileset
         */
    }

    if(Channels.find().count() == 0){
        Channels.insert({
            name: "general"
        });
    }

    /**
     * Roles
     */
    var admin = Meteor.users.findOne({email: "a.pape@paneon.de"});
    if (admin){
        Roles.addUsersToRoles(admin.id(), ['admin']);
    }

    // create a couple of roles if they don't already exist (THESE ARE NOT NEEDED -- just for the demo)
    if(!Meteor.roles.findOne({name: "secret"}))
        Roles.createRole("secret");

    if(!Meteor.roles.findOne({name: "double-secret"}))
        Roles.createRole("double-secret");
});