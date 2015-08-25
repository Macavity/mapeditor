<?php

$tilesetsUsed = array();

$availableMaps = array();
foreach(glob('export/*.json') as $file) {
    $availableMaps[] = $file;
}

$tilesets = json_decode(file_get_contents("tileset_list.json"), true);

$tsFirstGids = array();

foreach($tilesets as $tileset){

}

foreach($availableMaps as $map){

    $data = json_decode(file_get_contents($map),true);



}

