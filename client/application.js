/**
 * Subscriptions
 */
/*Meteor.subscribe('uploads');

Meteor.subscribe('maps');

Meteor.subscribe('tilesets');*/

/**
 * Startup
 */
Meteor.startup(function () {

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
Template.registerHelper("timestampToTime", function (timestamp) {
    var date = new Date(timestamp);
    var hours = date.getHours();
    var minutes = "0" + date.getMinutes();
    var seconds = "0" + date.getSeconds();
    return hours + ':' + minutes.substr(minutes.length-2) + ':' + seconds.substr(seconds.length-2);
});
