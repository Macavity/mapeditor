/**
 * News
 */
Meteor.publish('posts', function() {
    return Posts.find();
});

/**
 * Chat
 */
Meteor.publish('channels', function(){
    return Channels.find();
});


Meteor.publish('messages', function(channel){
    return Messages.find({
        channel: channel
    });
});

/**
 * Game
 */
Meteor.publish('myCharacters', function(){
    return Characters.find({userId: this.userId});
});

Meteor.publish('publicCharacterData', function(){
   return Meteor.users.find({}, {
       fields: {
           "name": true,
           "avatar": true
       }
   });
});

Meteor.publish("maps", function(){
    return Maps.find();
});

Meteor.publish("uploads", function(){
    return Uploads.find();
});