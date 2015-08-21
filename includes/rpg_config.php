<?php
// Config of RPG
define('RPG_CONFIG_INCLUDED',true);

// Auf false setzen wenn nur shizo und Macavity Zugang zum Spiel haben sollen.
define('RPG_IS_ONLINE', false);

$rpg_conf['layer']["swd"] = true;
$rpg_conf['layer']["mag"] = false;
$rpg_conf['layer']["arc"] = false;
$rpg_conf['layer']["aco"] = false;
$rpg_conf['layer']["mer"] = false;
$rpg_conf['layer']["thf"] = false;


// since we don't have a phpbb frame anymore, let's make some fake arrays.
$users = array(
    54 => array( 'name' => 'Morpheusz', 	'rank' => 'dev' ),
    57 => array( 'name' => 'Kadaj', 		'rank' => 'dev' ),
    75 => array( 'name' => 'shizo', 		'rank' => 'dev' ),
    104 => array( 'name' => 'Macavity', 	'rank' => 'admin' ),
    638 => array( 'name' => 'Deval', 		'rank' => 'intern', 'prefix' => '[d]' ),
    713 => array( 'name' => 'Santana', 	    'rank' => 'editor', 'prefix' => '[s]' ),
    202 => array( 'name' => 'Pietro', 		'rank' => 'editor', 'prefix' => '[p]' ),
    1066 => array( 'name' => 'Lunk', 		'rank' => 'editor', 'prefix' => '[l]' ),
    1110 => array( 'name' => 'Tsunade', 	'rank' => 'intern', 'prefix' => '[t]' ),
    1075 => array( 'name' => 'Istvan', 		'rank' => 'editor', 'prefix' => '[i]' ),
);
