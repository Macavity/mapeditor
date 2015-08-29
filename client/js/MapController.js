MapController = RouteController.extend({
    onBeforeAction: function(){
        if (! Meteor.user()) {
            if (Meteor.loggingIn()) {
                this.render(this.loadingTemplate);
            } else {
                this.render('accessDenied');
            }
        } else {
            this.next();
        }
    },
    index: function(){

        var ownMaps = Maps.find({creatorId: Meteor.userId()});
        var hasMaps = (ownMaps.count());


        Template.mapIndex.helpers({
            ownMaps: function(){
                return Maps.find({creatorId: Meteor.userId()}).fetch();
            },
            hasMaps: function(){
                return Maps.find({creatorId: Meteor.userId()}).count();
            }
        });

        this.render('mapIndex', {
            data: {
            }
        });

    },
    edit: function(id){

        var map = Maps.find({creatorId: Meteor.userId(), id: id});

        //var characters = Characters.find({userId: Meteor.userId()});
        /*return {
         characters:characters,
         canCreateMoreChars: ~(characters.length < 6)
         }*/
        this.render('mapEdit');
    }
});