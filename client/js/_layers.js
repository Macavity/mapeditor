
Template.layers.created = function(){

    this.layerData = [
        {
            z: 1,
            id: "background",
            name: "Background",
            active: true,
            tiles: []
        },
        {
            z: 2,
            id: "floor1",
            name: "Layer Floor 1",
            active: false,
            tiles: []
        },
        {
            z: 3,
            id: "floor2",
            name: "Layer Floor 2",
            active: false,
            tiles: []
        },
        {
            z: 11,
            id: "sky1",
            name: "Layer Sky 1",
            active: false,
            tiles: []
        },
        {
            z: 12,
            id: "sky2",
            name: "Layer Sky 2",
            active: false,
            tiles: []
        }
    ];
};

Template.layers.helpers({
    layers: function(){
        return Template.instance().layerData;
    }
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
