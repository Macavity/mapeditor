
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

/**
 * Administration
 */
Router.route('/manage/tilesets', {
    name: 'manage.tilesets'
});

Router.route('/manage/maps', {
    name: 'manage.maps'
});

/**
 * Map Editor
 */
Router.route('/map',{
    name: 'map',
    controller: 'MapController',
    action: 'index'
});

Router.route('/map/edit/:_id',{
    name: 'map.edit',
    controller: 'MapController',
    action: 'edit'
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
    only: ['editor', 'manage.tilesets','manage.maps']
});