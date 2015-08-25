<?php

include('includes/rpg_functions.php');

/*
 * Available Maps for this user
 */
$availableMaps = array();
foreach(glob('maps/*.js') as $file) {
    $availableMaps[] = $file;
}

natcasesort($availableMaps);

$mapFileName = empty($_GET['map']) ? false : $_GET['map'];
$matchingMaps = array();

if(file_exists($mapFileName) || $mapFileName == "all"){
    $matchingMaps = ($mapFileName == "all") ? glob('maps/*.js') : array($mapFileName);
}

$stats = array();

//print_r($matchingMaps);

foreach($matchingMaps as $mapFileName){


    /**
     * Load Map Data into php
     */
    $mapSource = file_get_contents($mapFileName);

    $pathinfo = pathinfo($mapFileName);
    $fileName = $pathinfo['filename'];

    $mapTypeDefinition = "map/ft_defs/".$fileName."_ft.js";

    $fieldTypes = file_get_contents($mapTypeDefinition);

    echo '<br>Export: '.$fileName;

    // JS to PHP
    $mapSource = preg_replace("/var name = '([^']*)'/", "\$name = '$1';", $mapSource);

    $mapSource = str_replace("// Editor: ", 'var author = ', $mapSource);
    $mapSource = preg_replace("/var author = ([A-z\s]+)/", "\$author = '$1';", $mapSource);

    $mapSource = str_replace('var ', '$', $mapSource);
    $mapSource = str_replace('new Array','array',$mapSource);
    $mapSource = str_replace('field_', '$field_', $mapSource);
    $mapSource = str_replace('$$','$',$mapSource);

    $fieldTypes = preg_replace("/var name = '([^']*)'/", "\$name = '$1';", $fieldTypes);

    $mapSource = str_replace("// Editor: ", 'var author = ', $mapSource);
    $mapSource = preg_replace("/var author = ([A-z\s]+)/", "\$author = '$1';", $mapSource);

    $mapSource = str_replace('var ', '$', $mapSource);
    $mapSource = str_replace('new Array','array',$mapSource);
    $mapSource = str_replace('field_', '$field_', $mapSource);
    $mapSource = str_replace('$$','$',$mapSource);



    eval($mapSource);

    if(!isset($width)){
        $width = 20;
    }
    if(!isset($height)){
        $height = 20;
    }
    if(!isset($name)){
        $name = "";
    }
    if(!isset($author)){
        $author = "Macavity";
    }
    if(!isset($mainBg)){
        $main_bg = "forest_mc/153.png";
    }
    if(!isset($field_bg)){
        $field_bg = array();
    }
    if(!isset($field_layer1)){
        $field_layer1 = array();
    }
    if(!isset($field_layer2)){
        $field_layer2 = array();
    }
    if(!isset($field_layer4)){
        $field_layer4 = array();
    }

    $layers = array();

    /**
     * Tilesets by Name
     */
    $tilesets = json_decode(file_get_contents('tileset_list.json'), true);
    $tilesetsByName = array();

    foreach($tilesets as $key => $set){
        $tilesets[$key]['image'] = str_replace('/static/','static/', $set['image']);
        $tilesetsByName[$set['name']] = $set;
    }

    /**
     * Main BG
     */
    $field = explode('/', str_replace('.','/', $main_bg));

    $setName = $field[0];
    $localTileId = $field[1]*1;

    $tileset = $tilesetsByName[$setName];

    $main_bg = $localTileId + $tileset['firstgid'] - 1;

    /**
     * Background - Layer BG
     */
    $bgFieldData = array();
    $layer1FieldData = array();
    $layer2FieldData = array();
    $layer4FieldData = array();

    for($x = 0; $x < $width; $x++){
        for($y = 0; $y < $height; $y++){
            $bgFieldData[] =        (empty($field_bg[$x][$y]))      ? $main_bg  : getTileId($field_bg[$x][$y]);
            $layer1FieldData[] =    (empty($field_layer1[$x][$y]))  ? 0         : getTileId($field_layer1[$x][$y]);
            $layer2FieldData[] =    (empty($field_layer2[$x][$y]))  ? 0         : getTileId($field_layer2[$x][$y]);
            $layer4FieldData[] =    (empty($field_layer4[$x][$y]))  ? 0         : getTileId($field_layer4[$x][$y]);
        }
    }

    $layers = array(
        array(
            "name" => "Background",
            "type" => "tilelayer",
            "height" => $height,
            "width" => $width,
            "data" => $bgFieldData,
            "opacity" => 1,
            "visible" => true,
            "x" => 0,
            "y" => 0
        ),
        array(
            "name" => "Layer 1",
            "type" => "tilelayer",
            "height" => $height,
            "width" => $width,
            "data" => $layer1FieldData,
            "opacity" => 1,
            "visible" => true,
            "x" => 0,
            "y" => 0
        ),
        array(
            "name" => "Layer 2",
            "type" => "tilelayer",
            "height" => $height,
            "width" => $width,
            "data" => $layer2FieldData,
            "opacity" => 1,
            "visible" => true,
            "x" => 0,
            "y" => 0
        ),
        array(
            "name" => "Layer 4",
            "type" => "tilelayer",
            "height" => $height,
            "width" => $width,
            "data" => $layer4FieldData,
            "opacity" => 1,
            "visible" => true,
            "x" => 0,
            "y" => 0
        ),
    );


    $json = array(
        "properties" =>
            array(
                "author" => $author,
                "name" => $name,
            ),
        "height" => $height,
        "width" => $width,
        "layers" => $layers,
        "nextobjectid" => 1,
        "orientation" => "orthogonal",
        "renderorder" => "right-down",
        "tileheight" => 32,
        "tilesets" => $tilesets,
        "tilewidth" => 32,
        "version" => 1,
    );


    file_put_contents('export/'.$fileName.'.json', json_encode($json));

    echo ' => '.$fileName.'.json';


}
?>
<h1>Kartendatei wählen</h1>
<form action="" method="get">
    <input type="hidden" id="stage" name="stage" value="export">
    <select id="map" name="map">
        <option value="all">Alle</option>
        <?php foreach($availableMaps as $fileName){ ?>
    <option value="<?=$fileName?>" <?php if($fileName == $mapFileName){ echo 'selected="selected"';}?>><?=$fileName?></option>
<?php } ?>
</select>
<input type="submit" value="Weiter">
</form>
