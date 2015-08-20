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

// verschiedene Arrays
// effects have a duration
// status is permanent up to change!
$db_effects = array('SpeedPot0','SpeedPot1','SpeedPot2','Angelus','Berserk','Stunned','mProvoke','pProvoke','Silence','Sleep','StoneCurse','Blind','Confusion','Frozen');
$db_status = array('Chaos','Curse','Poison');

// Verkaufswert? prozentual vom Kaufpreis
$itemdb_sell_per = 49;
