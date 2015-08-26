
Router.configure({
    layoutTemplate: "default",
    loadingTemplate: "loading",
    notFoundTemplate: "notFound"
});

/**
 * News
 */
Router.route('/', {
    name: 'news'
});

Router.route('/test', {
    name: 'test'
});

Router.route('/editor', {
    name: 'editor'
});

Router.route('/manage/tilesets', {
    name: 'manage.tilesets'
});

/**
 * Map
 */
var requireLogin = function() {
    if (! Meteor.user()) {
        if (Meteor.loggingIn()) {
            this.render(this.loadingTemplate);
        } else {
            this.render('news');
        }
    } else {
        this.next();
    }
};


/**
 * Login required
 */
Router.onBeforeAction(requireLogin, {
    only: ['load', 'edit']
});