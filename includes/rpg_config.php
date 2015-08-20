<?php
// Config of RPG
// {$table_prefix}xyz
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


function getRates($user){
    global $table_prefix;
    global $db,$usertemp;
    global $hp_max,$sp_max,$skill,$debug,$bHP_regen,$bSP_regen;
    global $char,$int,$bInt,$con,$bCon;
    $flag = 0;

    $sql = "SELECT * FROM {$table_prefix}rpg_config";
    if (!($result = $db->sql_query($sql))){
        message_die(GENERAL_ERROR, "Fatal Error: Could not retrieve RPG Configuration.", "getRates($user)", __LINE__, __FILE__, $sql);
    }
    while($row = $db->sql_fetchrow($result)){
        $rpg_config[$row['name']] = $row['value'];
    }

    // Drop Rate Std: 1
    if($char['char_job'] == 'gm0' || $char['char_job'] == 'gm1'){
        $rate['drop'] = $rpg_config['rate_drop_gm'];
        $rate['exp'] = $rpg_config['rate_bexp_gm'];
        $rate['jexp'] = $rpg_config['rate_jexp_gm'];
    }
    elseif($userdata['user_level'] == ADMIN){
        $rate['drop'] = $rpg_config['rate_drop_admin'];
        $rate['exp'] = $rpg_config['rate_bexp_admin'];
        $rate['jexp'] = $rpg_config['rate_jexp_admin'];
    }
    else{
        $rate['drop'] = $rpg_config['rate_drop'];
        $rate['exp'] = $rpg_config['rate_bexp'];
        $rate['jexp'] = $rpg_config['rate_jexp'];
    }

    if($char['char_prizelimit'] <= 0 || $usertemp['cost_pl'] == 0){
        $rate['drop'] = 1;
        $rate['exp'] = 1;
        $rate['jexp'] = 1;
        $rate['rate_regen_hp'] = 1;
        $rate['rate_regen_sp'] = 1;
    }
    // Possible to increase the regeneration rate of HP and SP after a won fight and during hiding (thief skill)

    // HP Regeneration after Fight
    $regen_hp_rate = $rpg_config['rate_regen_hp'];			// Default: 1
    $rate['regen_hp'] = ( floor(($con+$bCon) / 5) + floor($hp_max / 200) ) * $regen_hp_rate + $bHP_regen;
    $debug[] = "<br>bHP_regen :: $bHP_regen";

    // SP Regeneration after Fight
    $regen_sp_rate = $rpg_config['rate_regen_sp'];			// Default: 1
    $rate['regen_sp'] = ( 1 + floor(($int+$bInt)/6) + floor($sp_max/100) ) * $regen_sp_rate+ $bSP_regen;
    $debug[] = "<br>bSP_regen :: {$bSP_regen}";

    //Specials
    /*
    if($user['user_id'] == 104){
        $rate['drop'] = 20;
        $rate['exp'] = 10;
        $rate['jexp'] = 9;
    }*/
    // baka + mac
    if($user['user_id'] == 104 || $user['user_id'] == 15 || $user['user_id'] == 75){
        $rate['debug'] = true;
    }
    /*                              falscher Skill und falsche Datei dafr
if($skill[218] > 0){
    $rate['regen_hp'] += floor((5 * $skill[218]) + ($hp_max * 0.002 * $skill[218]));
    $flag += 2;
}                 */
    /*if($skill[250] > 0){          falscher Skill und falsche Datei dafr
        $rate['regen_sp'] += floor(($sp_max/500 + 3) * $skill[250]);
        $flag += 4;
        $debug[] = "skill 250: SP += floor(({$sp_max}/500 + 3) * {$skill[250]})";
    }*/
    $debug[] = '<br>Regen: HP:'.$rate['regen_hp'].',SP:'.$rate['regen_sp'].',Flag:'.$flag;
    $debug[] = '<br>Rates: Exp:'.$rate['exp'].', Job Exp:'.$rate['jexp'].', Drops:'.$rate['drop'];
    return $rate;
}

function getRates2($user){
    global $table_prefix;
    global $db;
    global $hp_max,$skill,$debug,$bHP_regen,$bSP_regen;
    global $char,$int,$bInt,$con,$bCon;
    $flag = 0;

    $sql = "SELECT * FROM {$table_prefix}rpg_config";
    if (!($result = $db->sql_query($sql))){
        message_die(GENERAL_ERROR, "Fatal Error: Could not retrieve RPG Configuration.", "getRates($user)", __LINE__, __FILE__, $sql);
    }
    while($row = $db->sql_fetchrow($result)){
        $rpg_config[$row['name']] = $row['value'];
    }

    // Drop Rate Std: 1
    if($char->job == 'gm0' || $char->job == 'gm1'){
        $rate['drop'] = $rpg_config['rate_drop_gm'];
        $rate['exp'] = $rpg_config['rate_bexp_gm'];
        $rate['jexp'] = $rpg_config['rate_jexp_gm'];
    }
    elseif($user['user_level'] == ADMIN){
        $rate['drop'] = $rpg_config['rate_drop_admin'];
        $rate['exp'] = $rpg_config['rate_bexp_admin'];
        $rate['jexp'] = $rpg_config['rate_jexp_admin'];
    }
    else{
        $rate['drop'] = $rpg_config['rate_drop'];
        $rate['exp'] = $rpg_config['rate_bexp'];
        $rate['jexp'] = $rpg_config['rate_jexp'];
    }

    // Possible to increase the regeneration rate of HP and SP after a won fight and during hiding (thief skill)

    // HP Regeneration after Fight
    $regen_hp_rate = $rpg_config['rate_regen_hp'];			// Default: 1
    $rate['regen_hp'] = ( floor(($con+$bCon) / 5) + floor($hp_max / 200) ) * $regen_hp_rate + $bHP_regen;
    $debug[] = "bHP_regen :: $bHP_regen";

    // SP Regeneration after Fight
    $regen_sp_rate = $rpg_config['rate_regen_sp'];			// Default: 1
    $rate['regen_sp'] = ( 1 + floor(($int+$bInt)/6) + floor($sp_max/100) ) * $regen_sp_rate+ $bSP_regen;
    $debug[] = "bSP_regen :: $bSP_regen";

    //Specials
    if($user['user_id'] == 104){
        $rate['drop'] = 20;
        $rate['exp'] = 10;
        $rate['jexp'] = 9;
    }
    // baka + mac
    if($user['user_id'] == 104 || $user['user_id'] == 15 || $user['user_id'] == 75){
        $rate['debug'] = true;
    }

    if($skill[218] > 0){
        $rate['regen_hp'] += floor((5 * $skill[218]) + ($hp_max * 0.002 * $skill[218]));
        $flag += 2;
    }
    if($skill[250] > 0){
        $rate['regen_sp'] += floor(($sp_max/500 + 3) * $skill[250]);
        $flag += 4;
    }
    $debug[] = '<br>Regen: HP:'.$rate['regen_hp'].',SP:'.$rate['regen_sp'].',Flag:'.$flag;
    $debug[] = '<br>Rates: Exp:'.$rate['exp'].', Job Exp:'.$rate['jexp'].', Drops:'.$rate['drop'];
    return $rate;
}
