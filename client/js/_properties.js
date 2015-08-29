Template.properties.helpers({
    mapProperties: function(){
        if(typeof Session.get('mapProperties') === "undefined" || !Session.get('mapProperties')){
            return [];
        }
        return Session.get('mapProperties');
    }
});