/**
 * Subscriptions
 */
Meteor.subscribe('posts');

Meteor.subscribe('messages');

Meteor.subscribe('publicCharacterData');

Meteor.subscribe('myCharacters');

Meteor.subscribe('channels');

Meteor.subscribe('uploads');

Meteor.subscribe('maps');

/**
 * Startup
 */
Meteor.startup(function () {

    Session.set('channel', 'general');

    sAlert.config({
        effect: '',
        position: 'top-right',
        timeout: 5000,
        html: false,
        onRouteClose: true,
        stack: true,
        offset: 0
    });

});

/**
 * Accounts
 * - Username and E-Mail
 */
Accounts.ui.config({
    passwordSignupFields: 'USERNAME_AND_EMAIL'
});

/**
 * Global Helpers
 */
Template.registerHelper("activeCharNameFromId", function (userId) {
    var char = Meteor.characters.findOne({ userId: userId, active: true });
    if (typeof char === "undefined") {
        throw new Meteor.Error("not-authorized");
    }
    return char.name;
});

Template.registerHelper("timestampToTime", function (timestamp) {
    var date = new Date(timestamp);
    var hours = date.getHours();
    var minutes = "0" + date.getMinutes();
    var seconds = "0" + date.getSeconds();
    return hours + ':' + minutes.substr(minutes.length-2) + ':' + seconds.substr(seconds.length-2);
});
