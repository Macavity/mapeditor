<?php

/*
 * Available Maps for this user
 */
$availableTileSets = array();
foreach(glob('export/static/images/tilesets/*.png') as $file) {
    $availableTileSets[] = $file;
}

$xmlExport = "";
$firstTileId = 1;

/*foreach($availableTileSets as $fileName){

    $xmlExport .= '
    <tileset firstgid="'.$firstTileId.'" name="'.$name.'" tilewidth="32" tileheight="32" tilecount="'.$tilesCount.'">
        <image source="/'.$fileName.'" width="256" height="576"/>
    </tileset>';

    $firstTileId += $tilesCount;

}*/

?>
<style type="text/css">
    .tilesets .active {
        border: 2px solid green;
    }
    .tilesets .inactive {
        border: 2px dotted red;
    }
    .tileset {
        float:left;
        min-height:200px:
        max-height: 300px;
        position: relative;
    }
    .tileset img {
        height: 100%;
        width: auto;
    }
    .tileset p {
        position: absolute;
        top: 0px;
        left: 5px;
        background-color: white;

    }
</style>
<h1>XML Export</h1>

<div class="tilesets">
<?php foreach($availableTileSets as $fileName){
    $imgSet = ImageCreateFromPNG($fileName) or die ('failed to create TileSet '.$fileName);

    $path = pathinfo($fileName);
    $name = $path['filename'];

    $width = ImageSX($imgSet);
    $height = ImageSY($imgSet);

    $tilesPerRow = $width / 32;
    $rowCount = $height / 32;

    $tileCount = $tilesPerRow * $rowCount;
    ?>
    <div class="tileset">
        <img src="<?=$fileName?>" data-name="<?=$name?>" title="<?=$name?>" data-width="<?=$width?>" data-height="<?=$height?>" data-tilecount="<?=$tileCount?>" class="active">
        <p>
            <?=$name?><br>
            <?=$tilesPerRow.' x '.$rowCount?>
        </p>
    </div>
    <?php
} ?>
</div>

<textarea id="export" style="width:100%; height: 100%"><?=$xmlExport?></textarea>
<script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
<script type="">
    var tilesets = $(".tilesets");

    tilesets.find("img").on("click", function(event){
        var image = $(event.currentTarget);
        console.log("click");
        if(image.hasClass('active')){
            image.removeClass('active').addClass('inactive');
        }
        else {
            image.removeClass('inactive').addClass('active');
        }
        generateJson();
    });

    var generateXml = function(){
        var xml = "";
        var firstTile = 1;
        tilesets.find('.active').each(function(index){
            var image = $(this);

            var tileCount = image.data("tilecount")*1;

            console.log("- add "+image.data("name")+" => firstTile: "+firstTile);

            xml += '<tileset firstgid="'+firstTile+'" name="'+image.data("name")+'" tilewidth="32" tileheight="32" tilecount="'+tileCount+'">'
                +'<image source="/'+image.attr("src")+'" width="'+image.data("width")+'" height="'+image.data("height")+'"/>'
                +'</tileset>';
            firstTile += tileCount;
        });

        console.log("xml generated");
        $("#export").text(xml);

    };

    var generateJson = function(){
        var json = [];
        var firstTile = 1;
        tilesets.find('.active').each(function(index){
            var image = $(this);

            var tileCount = image.data("tilecount")*1;

            console.log("- add "+image.data("name")+" => firstTile: "+firstTile);

            json.push({
                    "firstgid": firstTile,
                    "image":"\/"+image.attr("src").replace('export/static','static'),
                    "imageheight":image.data("height"),
                    "imagewidth":image.data("width"),
                    "margin":0,
                    "name": image.data("name"),
                    "properties": { },
                    "spacing":0,
                    "tilecount": tileCount,
                    "tileheight":32,
                    "tilewidth":32
                });
            firstTile += tileCount;
        });

        console.log("json generated");
        $("#export").text(JSON.stringify(json));

    };

    generateJson();

</script>