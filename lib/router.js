
Router.configure({
    layoutTemplate: "default",
    loadingTemplate: "loading",
    notFoundTemplate: "notFound",
    waitOn: function(){
        return Meteor.subscribe('posts');
    }
});

/**
 * News
 */
Router.route('/', {
    name: 'postsList'
});
Router.route('/posts/:_id', {
    name: 'postPage',
    data: function(){
        return Posts.findOne(this.params._id);
    }
});

/**
 * Map
 */
Router.route('/game', {
    name: 'game',
    controller: 'GameController',
    action: 'index'
});

Router.route('/game/char/list',{
    name: 'charList',
    controller: 'GameController',
    action: 'charList'
});

Router.route('game/admin/world', {
    name: 'adminWorld',
    data: function(){
        return {
            maps: Maps.find()
        }
    }
});

var requireLogin = function() {
    if (! Meteor.user()) {
        if (Meteor.loggingIn()) {
            this.render(this.loadingTemplate);
        } else {
            this.render('accessDenied');
        }
    } else {
        this.next();
    }
}

// Show not found if there is not data found
Router.onBeforeAction('dataNotFound', {only: 'postPage'});

/**
 * Login required
 */
Router.onBeforeAction(requireLogin, {
    only: 'postSubmit'
});