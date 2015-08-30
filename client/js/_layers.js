
Template.layers.created = function(){

};

Template.layers.helpers({
});

Template.layers.events({
    'click .toggle-layer': function(event){
        var li = $(event.currentTarget).closest("li");
        var layerId = li.data("id");
        var layer = $("#layer-"+layerId);

        if(li.hasClass("layer-invisible")){
          li.removeClass("layer-invisible");
          layer.removeClass("layer-invisible");
        }
        else {
          li.addClass("layer-invisible");
          layer.addClass("layer-invisible");
        }

    },
    'click #layerlist li': function(event){
        var li = $(event.currentTarget).closest("li");
        var layerId = li.data("id");
        var layer = $("#layer-"+layerId);

        $("#layerlist").find(".layer-active").removeClass("layer-active");

        if(li.hasClass("layer-active") == false){
            li.addClass("layer-active");
            layer.addClass("layer-active");
        }

    }
});
