<?php

function addLog($log_type,$log_detail,$log_text){
  global $table_prefix,$db;
  
  $sql = "SELECT log_id,log_text FROM {$table_prefix}rpg_logbook 
          WHERE log_type = '$log_type' AND log_typedetail = '$log_detail'";
  if ( !($result = $db->sql_query($sql)) ) {
		message_die(GENERAL_ERROR, "Fatal Error Getting Log Entry.", '', __LINE__, __FILE__,$sql); 
	}
	$sqlcount = $db->sql_numrows($result);
	
  if($sqlcount > 0){			// Log schon vorhanden
		$log_row = $db->sql_fetchrow($result);
    $log_text = $log_row['log_text']."\n".htmlentities($log_text);

    $sql = "UPDATE {$table_prefix}rpg_logbook SET log_text = '$log_text'
            WHERE log_type = '$log_type' AND log_typedetail = '$log_detail'";
	}
	else{										// Neuer Log
		$sql = "INSERT INTO {$table_prefix}rpg_logbook (log_type,log_typedetail,log_text)
            VALUES ('$log_type','$log_detail','$log_text');";
	}

	if ( !($result = $db->sql_query($sql)) ) {
		message_die(GENERAL_ERROR, "Fatal Error Adding Log Entry!", '', __LINE__, __FILE__,$sql);
	}
}

function addItem($char_id,$item_id,$amount,$type = '+',$upgrade = 0){
	global $db,$table_prefix;
	if($item_id == "" || $char_id == "" || $char_id <= 0 || $amount == ""){
		return false;
	}
	
	$sql = "SELECT * FROM {$table_prefix}useritems WHERE user=$char_id AND item_id = $item_id AND item_upgrade = $upgrade";
	if ( !($result = $db->sql_query($sql)) ) {
		message_die(GENERAL_ERROR, "Fatal Error Getting Item $item_id!", '', __LINE__, __FILE__,$sql);
	}
	$sqlcount = $db->sql_numrows($result);
	$item_name = getItemName($item_id,'ger');
	if($sqlcount > 0){			// item schon vorhanden
		$sql = "UPDATE {$table_prefix}useritems SET item_number=item_number $type $amount WHERE user=$char_id AND item_id = $item_id AND item_upgrade = $upgrade";
	}
	else{										// neues item
		$sql = "INSERT INTO {$table_prefix}useritems (user,item_id,item_number,item_upgrade) VALUES ($char_id,$item_id,$amount,$upgrade);";
	}
	if ( !($result = $db->sql_query($sql)) ) {
		message_die(GENERAL_ERROR, "Fatal Error Adding Item $item_id!", '', __LINE__, __FILE__,$sql);
	}
	return true;
}

function addMoku($save_id,$m_count,$type = '+'){
	global $db,$table_prefix;
	$sql = "SELECT * FROM {$table_prefix}charas WHERE save_id=$save_id";
	if ( !($result = $db->sql_query($sql)) ) {
		message_die(GENERAL_ERROR, "Fatal Error Getting Chara $save_id!", '', __LINE__, __FILE__,$sql);
        }
        if ($counter = '') {$amount = 0;}
	$sql = "UPDATE {$table_prefix}charas SET char_points=char_points $type $m_count WHERE save_id=$save_id";

	if ( !($result = $db->sql_query($sql)) ) {
		message_die(GENERAL_ERROR, "Fatal Error Adding * $m_count * Moku by chara $save_id!", '', __LINE__, __FILE__,$sql);
	}
}

// **** Stat calculations ****

function calcHit($char){
        $hit = $char['char_blevel'] + $char['char_dex'];
        return $hit;
}
function calcFlee($char){
        $flee = $char['char_blevel'] + $char['char_agi'];
        return $flee;
}

// rechnet aus wieviele Statuspunkte der User noch zur Verfügung hat
function calcSTP($char){
        global $db_job_stp_add;
        $stat[0] = $char['char_str'];
        $stat[1] = $char['char_agi'];
        $stat[2] = $char['char_int'];
        $stat[3] = $char['char_con'];
        $stat[4] = $char['char_dex'];
        $stat[5] = $char['char_luk'];
        $level = $char['char_blevel'];
        for ($i = 0; $i < 6; $i++){
                $lvpts = getStatIncValue($stat[$i]);        // Kosten zur Erhöhung eines Stats
                $reqpts += $lvpts * ($lvpts - 1) * 5 - 10 + $lvpts * (($stat[$i] - 1) % 10);
        }
        $pts = 48 - $reqpts;        // 48 = grundpunkte
        $lvpts = floor(($level - 1) / 5) + 3;
        $pts += $lvpts * ($lvpts - 1) * 5 / 2 - 18 + $lvpts * (1 + (($level - 1) % 5));
        $pts += $db_job_stp_add[$char['char_jlevel']];
        $pts += $char['char_add_stp'];

        return $pts;
}

// berechnet die anzahl der verfügbaren skill points
function calcSKP($char){
        global $db_skills;
        //$skp = floor($user['user_statlevel']/2);
        $skp = $char['char_jlevel'];
        $s_names = $db_skills[$char['char_job']];
        $skills = getUserSkills();
        for($i = 0; $i < sizeof($s_names); $i++){
                $value[$i] = $skills[$s_names[$i]];
                $skp -= $value[$i];
        }
        $skp += $char['char_add_skp'];
        return $skp;
}


// berechnet Modifikationen an den 6 Stats durch Statusveränderungen
function calcStaMod($char)
{
	global $status,$debug, $ebonus;
        //0 => "str", 1 => "agi", 2 => "con", 3 => "int", 4 => "dex", 5 => "luk");
        $sta_bonus[0] = $ebonus['str']; 
        $sta_bonus[1] = $ebonus['agi'];
        $sta_bonus[2] = $ebonus['con'];
        $sta_bonus[3] = $ebonus['int'];
        $sta_bonus[4] = $ebonus['dex'];
        $sta_bonus[5] = $ebonus['luk'];

        $stat = array();
        $sta_mod = array();
        $level = $char['char_jlevel'];
        $job = getClassNumber($char['char_job']);
        // Status Effekte?
        if ($status["Adrenaline"] >= 1){
                //$p_agiu = true;
        }
        if (substr_count($char['char_status'],"|AGIUP|") >= 1)        {
                //$p_agiu = true;
        }
        if (substr_count($char['char_status'],"|ANGELUS|") >= 1)        {
                //$p_agiu = true;
        }
        if (substr_count($char['char_status'],"|BLESSED|") >= 1)        {
                $p_ble = true;
                $pos = strpos($user['user_status'],"|BLESSED|");
                $o_ble = substr($user['user_status'],$pos+8,1);
                if($o_ble=="a") $o_ble = 10;
                $o_ble = abs($o_ble);
        }
        if (substr_count($user['user_status'],"|BLIND|") >= 1)        {
                //$p_agiu = true;
        }
        // B.S.S.
        if (substr_count($user['user_status'],"|o_bss|") >= 1)        {
                //$o_bss = true;
        }
        if (substr_count($user['user_status'],"|p_burn|") >= 1)        {
                //$p_burn = true;
        }
        // crazy uproar
        if (substr_count($user['user_status'],"|p_cu|") >= 1)        {
                //$p_cu = true;
        }
        // cursed
        if (substr_count($user['user_status'],"|o_cu|") >= 1)        {
                $o_cur = true;
        }
        if (substr_count($user['user_status'],"|DEFAURA|") >= 1)        {
                //$p_def = true;
        }
        if (substr_count($user['user_status'],"|FREEZE|") >= 1)        {
                //$p_fre = true;
        }
        if (substr_count($user['user_status'],"|GLORIA|") >= 1)        {
                $p_glo = true;
        }
        if (substr_count($user['user_status'],"|IMPOSITIO|") >= 1)        {
                //$p_agiu = true;
        }
        if (substr_count($user['user_status'],"|IMPROVECONC") >= 1)        {
                //$p_ic = true;
        }
        if (substr_count($user['user_status'],"|MAGNIFICAT|") >= 1)        {
                //$p_agiu = true;
        }
        if (substr_count($user['user_status'],"|OWL|") >= 1)        {
                //$p_owl = true;
        }
        if (substr_count($user['user_status'],"|POISONED|") >= 1)        {
                //$p_agiu = true;
        }
        if (substr_count($user['user_status'],"|PROVOKED|") >= 1)        {
                //$p_agiu = true;
        }
        if (substr_count($user['user_status'],"|RESISTANTS|") >= 1)        {
                //$p_agiu = true;
        }
        if (substr_count($user['user_status'],"|SIGNUM|") >= 1)        {
                //$p_agiu = true;
        }
        if ($status[''] >= 1)        {
                $o_qua = true;
        }
        if (substr_count($user['user_status'],"|SUFFRA|") >= 1)        {
                //$p_agiu = true;
        }
        if (substr_count($user['user_status'],"|STONECURSE|") >= 1)        {
                //$p_agiu = true;
        }
        global $mod_bonus;
        for ($i = 0; $i < 6; $i++) {
                $sta_mod[$i] = $stat[$i] + $sta_bonus[$i] + $mod_bonus[$job][$level][$i];
                $debug[] = "sta_mod[$i] = {$stat[$i]} + {$sta_bonus[$i]} + {$mod_bonus[$job][$level][$i]};";
        }

        if ($p_cu) $sta_mod[0] += 4;
        $sta_mod[0] += $o_ble;

        if ($p_ic) $sta_mod[1] *= 1.02 + $p_ic * 0.01;
        if ($o_agiu) $sta_mod[1] += 2 + $o_agiu;

        $sta_mod[3] += $o_ble;

        if ($p_owl) $sta_mod[4] += $p_owl;
        if ($p_ic) $sta_mod[4] *= 1.02 + $p_ic * 0.01;
        $sta_mod[4] += $o_ble;

        if ($o_glo) $sta_mod[5] += 30;
        $sta_mod[5] += $bonus[9][5];

        if ($o_cur) {
                $sta_mod[5] = 0;
                $dmg_mod_p -= 25;
        }

        if ($o_qua) {
                $sta_mod[1] *= 0.5;
                $sta_mod[4] *= 0.5;
        }

        for ($i = 0; $i < 6; $i++) {
                $sta_mod[$i] = floor($sta_mod[$i]);
        }
        return $sta_mod;
}

function calcHP($char,$sta_mod){
        global $db_job_misc,$ebonus;
        $blv = $char['char_blevel'];
        $job = getClassNumber($char['char_job']);

        $hp = 35 + $blv * $db_job_misc['hpcon'][$job] / 100;
        $hp_mod = $db_job_misc['hpmod'][$job] / 1000;

        for ($i = 2; $i <= $blv; $i++){
                $hp += round($hp_mod * $i);
        }
        $hp = floor($hp * (1 + ($sta_mod[2]+$char['char_con'])/100))+ $char['char_add_hp'] + $ebonus['maxhp'];

        return $hp;
}

function calcSP($char,$sta_mod){
        global $db_job_misc,$ebonus;
        $blv = $char['char_blevel'];
        $job = getClassNumber($char['char_job']);

        //$sp = 10 + floor($db_job_misc['spmod'][$job] / 100 * $blv);
        //$sp = floor($sp * (1 + ($sta_mod[3]+$userdata['user_int'])/100));

        $sp = floor((10 + $blv*$db_job_misc['spmod'][$job]/100)*
                                (1+($sta_mod[3]+$char['char_int'])/100)
                                )+$char['char_add_sp'] + $ebonus['maxsp'];
        return $sp;
}
function calcBattleDamage($player,$min,$max,$luk,$items){
        $luk += getItemBonus("luk",$items);
        if($player){
                $add = getItemBonus("damage",$items);
        }
        else{
                $add = 0;
        }
        mt_srand((double)microtime()*1000000);        // New Seed
        $random = mt_rand() % $max + $min;

        $damage = round($add + $random);
        $crit = critical($luk);
        if($crit=="1"){
                mt_srand((double)microtime()*1000000);        // New Seed
                $rdm = mt_rand(200,500);
                $damage = $damage/100*$rdm;                // Critical hat 200% bis 500% !!!
                $crit = "a <b>critical</b> amount of ";
        }
        elseif($crit=="-1"){
                mt_srand((double)microtime()*1000000);        // New Seed
                $rdm = mt_rand(20,80);
                $damage = $damage/100*$rdm;                // -Critical hat 20% bis 80% !!!
                $crit = "a frustrating amount of ";
        }
        else{
                $crit = "";
        }
        $damage = round($damage);
        $return = array($damage,$crit);
        return $return;
}

function calcMagicDamage($int,$pClass,$level){
        global $matk_f;
        $damage =         $matk_f[$pClass][0] + $matk_f[$pClass][1]*$lvl +
                                $matk_f[$pClass][2]*$int + $matk_f[$pClass][3]*$int*$level;
        $damage += (1+floor($int/5));

        return $damage;
}

function checkTrades(){
	global $db,$table_prefix;
	
	$sql = "SELECT id,userid_1,userid_2,owner_1,owner_2 FROM {$table_prefix}trades WHERE owner_1 = -1 OR owner_2 = -1";
	if( !($result = $db->sql_query($sql)) ){
		message_die(GENERAL_MESSAGE, 'SQL Fehler in checkTrades ['.__LINE__.']');
	}
	
	while ( $row = $db->sql_fetchrow($result) ){
		$owner1 = getOwner($row['userid_1'],'id');
		$owner2 = getOwner($row['userid_2'],'id');
		$trade_id = $row['id'];
		$sql2 = "UPDATE {$table_prefix}trades SET owner_1 = $owner1, owner_2 = $owner2 WHERE id = $trade_id";
		if (!($result2 = $db->sql_query($sql2))) {
			message_die(GENERAL_ERROR, 'SQL Fehler:'.mysql_error(), '', __LINE__, __FILE__, $sql2);
		}
	}
	
}

// checks if a critical appears, crits ignore the enemies defence
function critical($luck,$eLuck,$ass = false){
        if($ass)
                $crit = 2*(1 + floor($luck*0.3)) - floor($eLuck / 5);
        else
                $crit = 1 + floor($luck*0.3) - floor($eLuck / 5);
        // luck 99 => chance of crit is 30%
        mt_srand((double)microtime()*1000000);        // New Seed
        $rdm = mt_rand(1,100);

        if($rdm<=$crit)
                return true;
        else
                return false;

}

function debug_echo($text,$viewer){
	if($viewer['user_id'] == 104){
		echo "<br>$text";
	}

}

function decreaseTimers($effects){
        global $db_effects;
        $s_names = $db_effects;
        for($i=0; $i < sizeof($s_names); $i++){
                if($effects[$s_names[$i]] > 0){
                        $effects[$s_names[$i]]--;
                }
        }
        return $effects;
}
function echoMagicDamage($pClass,$pInt,$bInt){
        global $magicDam_min,$magicDam_max;

        if($pClass==("mag"||"aco") ){
                $matk = calcMagicDamage($pInt+$bInt,$pClass,$pLevel);
                $min = $pInt + floor(round($matk));
                $max = $min + ceil(round($matk));
                for($i=1;$i<=($pInt+$bInt);$i++){
                        if($i%$magicDam_min[0])
                                $min += $magicDam_max[1];
                        if($i%$magicDam_max[0])
                                $max += $magicDam_max[1];
                }
                return "<b>~ $min - $max</b>&nbsp;&nbsp;&nbsp;";
        }
        else
                return "none";
}
function executeUseScript($char,$effect,$param){
        switch($effect){
                case "inc_level":
                        $change = 'char_blevel'; $value = $param;
                        $type="num"; break;
                case "dec_level":
                        $change = 'char_blevel'; $value = $param;
                        $type="num"; break;
                case "inc_exp":
                        $change = 'char_bexp'; $value = $param;
                        $type="num"; break;
                case "dec_exp":
                        $change = 'char_bexp'; $value = $param;
                        $type="num"; break;
                case "inc_exprate":
                        $change = 'char_exp'; $value = floor($param/100*$user['user_exp']);
                        $type="num"; break;
                case "dec_exprate":
                        $change = 'char_exp'; $value = floor($param/100*$user['user_exp']);
                        $type="num"; break;
                case "inc_hp":
                        $change = 'char_hpmax'; $value = $param;
                        $type="num"; break;
                case "dec_hp":
                        $change = 'char_hpmax'; $value = $param;
                        $type="num"; break;
                case "inc_sp":
                        $change = 'char_mpmax'; $value = $param;
                        $type="num"; break;
                case "dec_sp":
                        $change = 'char_mpmax'; $value = $param;
                        $type="num"; break;
                case "itemheal":
                        $change[0] = 'char_hp'; $value[0] = $param[0];
                        $change[1] = 'user_mp'; $value[1] = $param[1];
                        $type = "ar_num"; break;
                case "percentheal":
                        $change[0] = 'char_hp'; $value[0] = floor($param[0]/100*$param[2]);
                        $change[1] = 'char_mp'; $value[1] = floor($param[1]/100*$param[3]);
                        $type = "ar_num"; break;
                case "sc_start":
                        $change = 'char_status'; $value = $param;
                        $type = "sc_start";
                        break;
                case "sc_end":
                        $change = 'char_status'; $value = $param;
                        $type = "sc_end";
                        break;
        }

        if($type == "num"){
                $char[$change] = (($char[$change] + $value) > 0) ? ($char[$change] + $value) : 0;
        }
        elseif($type == "ar_num"){
                $char[$change[0]] = (($char[$change[0]] + $value[0]) > 0) ? ($char[$change[0]] + $value[0]) : 0;
                $char[$change[1]] = (($char[$change[1]] + $value[1]) > 0) ? ($char[$change[1]] + $value[1]) : 0;
        }
        elseif($type == "sc_start"){
                statusChange($value,1);
        }
        elseif($type == "sc_end"){
                statusChange($value,0);
        }
        return $char;
}

function genHeadImage_v2($char,$gender,$equip,$bool_admin = false){
	global $db,$table_prefix,$global_top,$global_left,$debug;
	$pos = '00000';

  $imagefile = ($bool_admin) ? "../images/avatars/%s_head.png" : "images/avatars/%s_head.png";

  $hstyle = $char['char_hstyle'];
	$hcolor = $char['char_hcolor'];

	$str_hair = ($bool_admin) ? '../rpg/classes/hair/%s/%s_%s.png' 		: 'rpg/classes/hair/%s/%s_%s.png';
	$str_mask = ($bool_admin) ? '../rpg/classes/hair/%s/%s_%s_mask.png' : 'rpg/classes/hair/%s/%s_%s_mask.png';
	$str_gear = ($bool_admin) ? '../rpg/headgear/%s_%s.png' : 'rpg/headgear/%s_%s.png';

	$hair = sprintf($str_hair,$gender,$hstyle,$pos);
	$mask = sprintf($str_mask,$gender,$hstyle,$pos);

	$hairImg = (file_exists($hair)) ? ImageCreateFromPNG($hair) : $die .= 'Keine Frisur ausgesucht';

	// Color the Hair if needed
  if(file_exists($mask))
  {
	  if(strlen($hcolor) == 6 && file_exists($hair))    // consists of exactly 6 digits
    {
		  $hairMask = (file_exists($mask)) ? ImageCreateFromPNG($mask) : $die .= '<br>failed to create Hair Mask';

		  $dye = hex2int($hcolor);
		  $dye['red'] = $dye['r'];
		  $dye['green'] = $dye['g'];
		  $dye['blue'] = $dye['b'];

		  $x = ImageSX($hairImg);
		  $y = ImageSY($hairImg);

		  // First Step: Grayscale
		  for($i=0; $i<$y; $i++){
			  for($j=0; $j<$x; $j++){
				  $pos = imagecolorat($hairImg, $j, $i);
				  $f = imagecolorsforindex($hairImg, $pos);

				  $posM = imagecolorat($hairMask, $j, $i);
				  $mask = imagecolorsforindex($hairMask, $posM);

				  $gst = $f["red"]*0.15 + $f["green"]*0.5 + $f["blue"]*0.35;
				  if($mask['red'] == 0 && $mask['green'] == 0 && $mask['blue'] == 0)
          {
					  $col = imagecolorresolve($hairImg, $gst, $gst, $gst);
					  imagesetpixel($hairImg, $j, $i, $col);
				  }
			  }
		  }
		  // Then Lay the Color over the Grayscale
		  for($i=0; $i<$x; $i++){
			  for($j=0; $j<$y; $j++){
			  	$pos = imagecolorat($hairImg, $i, $j);
			  	$f = imagecolorsforindex($hairImg, $pos);

			  	$posM = imagecolorat($hairMask, $i, $j);
			  	$mask = imagecolorsforindex($hairMask, $posM);

			  	if($mask['red'] == 0 && $mask['green'] == 0 && $mask['blue'] == 0)
          {
			  		if($f['red'] <= 127)
            {
			  			$new_color = multiPlus($f,$dye);
				  	}
					  elseif($f['red'] > 127)
            {
		  				$new_color = multiMinus($f,$dye);
			  		}

			  		$r = $new_color['red'];
			  		$g = $new_color['green'];
			  		$b = $new_color['blue'];

			  		$col = imagecolorresolve($hairImg, $r, $g, $b);
			  		imagesetpixel($hairImg, $i, $j, $col);
			  	}
			  }
		  }
	  }
  }
  else
  {
    $adm_info .= '<br>nm'.$gender.$hstyle;
  }

	if($die)
  {
    return $die;
	}

  $hair_w = ImageSX($hairImg);
	$hair_h = ImageSY($hairImg);

	//
	$eq[0] = $equip[0];
	$eq[1] = $equip[1];
	$eq[2] = $equip[9];

	$counter = 0;
	$bool_gear = false;

  foreach($eq as $e)
  {
		$counter++;
		$sql = "SELECT * FROM {$table_prefix}rpg_image_positions WHERE type='gear' AND type2='$e'";
		$result = $db->sql_query($sql);
		if($db->sql_numrows($result) > 0)
    {
			$gearRow = $db->sql_fetchrow($result);
			$gear = sprintf($str_gear,$e,'00000');
			if(file_exists($gear))
      {
				$gearImg = ImageCreateFromPNG($gear);
				$bool_gear = true;
        $g_top = $gearRow['pos_top'];
				$g_left = $gearRow['pos_left'];
				$gear_w = ImageSX($gearImg);
				$gear_h = ImageSY($gearImg);
				
				$sql = "SELECT * FROM {$table_prefix}rpg_image_positions
						WHERE type='head2gear' AND gender='$gender' AND type2=$hstyle";
				$result = $db->sql_query($sql);
				if($db->sql_numrows($result) > 0)
        {
					$row = $db->sql_fetchrow($result);
				}
				$h2g_top = $row['pos_top'];
				$h2g_left = $row['pos_left'];

				$t = floor($hair_h/2) - floor($gear_h/2) + $h2g_top + $g_top;
				$l = floor($hair_w/2) - floor($gear_w/2) + $h2g_left + $g_left;
			}
			else
      {
				$die .= 'gearImg not found'.$gear;
			}
		}
	}

	$x = floor($hair_w/2);
	$y = floor($hair_h/2);

  $width = max($hair_w - $l, $gear_w, $hair_w); 
  $height = max($hair_h - $t, $gear_h, $hair_h);
  
  $compilation = ImageCreate($width, $height) or $die .= 'Fehler X02';
  
  $gray = hex2int('333333');
  $gray = ImageColorAllocate($compilation, $gray['r'], $gray['g'], $gray['b']);
  $black = hex2int('000000');
  $black = ImageColorAllocate($compilation, $black['r'], $black['g'], $black['b']);
  $none = ImageColorTransparent($compilation,$gray);

  ImageCopyMerge($compilation, $hairImg,   (($l < 0) ? abs($l) : 0), (($t < 0) ? abs($t) : 0), 0, 0, $hair_w, $hair_h, 100);
	if($bool_gear){
    ImageCopyMerge($compilation, $gearImg, (($l > 0) ? abs($l) : 0), (($t > 0) ? abs($t) : 0), 0, 0, $gear_w, $gear_h, 100);
  }

  $imagefile = ($bool_admin) 
                  ? sprintf($imagefile,'admin_'.$char['save_id']) 
                  : sprintf($imagefile,'chara_'.$char['save_id']);
	
	//ImageCopyMerge($compilation, $hairImg, 0+$h_left, 0+$h_top, 0, 0, $hair_w, $hair_h, 100);
		
	ImagePNG($compilation,$imagefile,100) or $die .= 'Fehler X03';
	
  // Aufraeumen
  ImageDestroy($hairImg);
	ImageDestroy($compilation);
	if($gear)
  {
		ImageDestroy($gearImg);
	}
	
  return '<img src="'.$imagefile.'" border="0">'.$die.$adm_info;
}

// Version 2
function genHeadImage($char,$gender,$equip,$bool_admin = false){
	global $db,$table_prefix,$global_top,$global_left,$output;
	$pos = '00000';
	
  $imagefile = ($bool_admin) ? "../images/avatars/%s_head.png" : "images/avatars/%s_head.png";

  $hstyle = $char['char_hstyle'];
	$hcolor = $char['char_hcolor'];

	$str_hair = ($bool_admin) ? '../rpg/classes/hair/%s/%s_%s.png' 		: 'rpg/classes/hair/%s/%s_%s.png';
	$str_mask = ($bool_admin) ? '../rpg/classes/hair/%s/%s_%s_mask.png' : 'rpg/classes/hair/%s/%s_%s_mask.png';
	$str_gear = ($bool_admin) ? '../rpg/headgear/%s_%s.png' : 'rpg/headgear/%s_%s.png';

	$hair = sprintf($str_hair,$gender,$hstyle,$pos);
	$mask = sprintf($str_mask,$gender,$hstyle,$pos);

	$hairImg = (file_exists($hair)) ? ImageCreateFromPNG($hair) : $die .= 'Keine Frisur ausgesucht';
		
	// Color the Hair if needed
  if(file_exists($mask))
  {
	  if(strlen($hcolor) == 6 && file_exists($hair))    // consists of exactly 6 digits
    {
		  $hairMask = (file_exists($mask)) ? ImageCreateFromPNG($mask) : $die .= '<br>failed to create Hair Mask';
		
		  $dye = hex2int($hcolor);
		  $dye['red'] = $dye['r'];
		  $dye['green'] = $dye['g'];
		  $dye['blue'] = $dye['b'];
		
		  $x = ImageSX($hairImg);
		  $y = ImageSY($hairImg);
				
		  // First Step: Grayscale
		  for($i=0; $i<$y; $i++){
			  for($j=0; $j<$x; $j++){
				  $pos = imagecolorat($hairImg, $j, $i);
				  $f = imagecolorsforindex($hairImg, $pos);
				
				  $posM = imagecolorat($hairMask, $j, $i);
				  $mask = imagecolorsforindex($hairMask, $posM);

				  $gst = $f["red"]*0.15 + $f["green"]*0.5 + $f["blue"]*0.35;
				  if($mask['red'] == 0 && $mask['green'] == 0 && $mask['blue'] == 0)
          {
					  $col = imagecolorresolve($hairImg, $gst, $gst, $gst);
					  imagesetpixel($hairImg, $j, $i, $col);
				  }
			  }
		  }
		  // Then Lay the Color over the Grayscale
		  for($i=0; $i<$x; $i++){
			  for($j=0; $j<$y; $j++){
			  	$pos = imagecolorat($hairImg, $i, $j);
			  	$f = imagecolorsforindex($hairImg, $pos);

			  	$posM = imagecolorat($hairMask, $i, $j);
			  	$mask = imagecolorsforindex($hairMask, $posM);
				
			  	if($mask['red'] == 0 && $mask['green'] == 0 && $mask['blue'] == 0)
          {
			  		if($f['red'] <= 127)
            {
			  			$new_color = multiPlus($f,$dye);
				  	}
					  elseif($f['red'] > 127)
            {
		  				$new_color = multiMinus($f,$dye);
			  		}

			  		$r = $new_color['red'];
			  		$g = $new_color['green'];
			  		$b = $new_color['blue'];
			
			  		$col = imagecolorresolve($hairImg, $r, $g, $b);
			  		imagesetpixel($hairImg, $i, $j, $col);
			  	}
			  }
		  }
	  }
  }
  else
  {
    $adm_info .= '<br>nm'.$gender.$hstyle;
  }

	if($die)	
  {
    return $die;
	}
	
  $hair_w = ImageSX($hairImg);
	$hair_h = ImageSY($hairImg);
  $origHair_w = ImageSX($hairImg);
	$origHair_h = ImageSY($hairImg);

	//
	$eq[0] = $equip[0];
	$eq[1] = $equip[1];
	$eq[2] = $equip[9];
	$counter = 0;
	$bool_gear = false;
  $last_l = 0;
  $last_t = 0;

        ////////////////////////ID-Wechsel von Useritems zu Itemids/////////////////////
        for($i=0;$i<=2;$i++){
          $where = '';
          if($eq[$i]) $where .= "useritem_id = {$eq[$i]}";
          if($where != ''){
            $sql = "SELECT  useritem_id, item_id FROM {$table_prefix}useritems
                    WHERE $where";
            if (!($result = $db->sql_query($sql))) { message_die(GENERAL_MESSAGE, 'Glühend heiß... autsch, aua >.< 13<br>'.$sql.'<br>-'.$equip[3].'<br>-'.$equip[5].'<br>'); }
            while($row = $db->sql_fetchrow($result)){
              $eq[$i] = ($row['useritem_id']>0) ? $row['item_id'] : 0;
              //$debug[] = '--------------------------------------------<br>$eq['.$i.'] = '.$eq[$i].'<br>';

            }
          }
        }
        ////////////////////////////////////////////////////////////////////////////////

  foreach($eq as $e)
  {
		$counter++;
		$sql = "SELECT * FROM {$table_prefix}rpg_image_positions WHERE type='gear' AND type2='$e'";
		$result = $db->sql_query($sql);
    if($db->sql_numrows($result) > 0)
    {
			$gearRow = $db->sql_fetchrow($result);
			$gear = sprintf($str_gear,$e,'00000');
			if(file_exists($gear))
      {
				$gearImg = ImageCreateFromPNG($gear);
				$bool_gear = true;
        $g_top = $gearRow['pos_top'];
				$g_left = $gearRow['pos_left'];
				$gear_w = ImageSX($gearImg);
				$gear_h = ImageSY($gearImg);

				$sql = "SELECT * FROM {$table_prefix}rpg_image_positions
						WHERE type='head2gear' AND gender='$gender' AND type2=$hstyle";
				$result = $db->sql_query($sql);
				if($db->sql_numrows($result) > 0)
        {
					$row = $db->sql_fetchrow($result);
				}
				$h2g_top = $row['pos_top'];
				$h2g_left = $row['pos_left'];

				$l = floor($origHair_w/2) - floor($gear_w/2) + $h2g_left + $g_left;
				$t = floor($origHair_h/2) - floor($gear_h/2) + $h2g_top  + $g_top;

        $l += $last_l;
        $t += $last_t;

				//ImageCopyMerge($hairImg, $gearImg, $l, $t, 0, 0, $gear_w, $gear_h, 100);
        $width = max($hair_w - $l, $gear_w, $hair_w);
        $height = max($hair_h - $t, $gear_h, $hair_h);

        $compilation = ImageCreate($width, $height) or $die .= 'Fehler X02';

        $gray = hex2int('333333');
        $gray = ImageColorAllocate($compilation, $gray['r'], $gray['g'], $gray['b']);
        $black = hex2int('000000');
        $black = ImageColorAllocate($compilation, $black['r'], $black['g'], $black['b']);
        $none = ImageColorTransparent($compilation,$gray);

        ImageCopyMerge($compilation, $hairImg,   (($l < 0) ? abs($l) : 0), (($t < 0) ? abs($t) : 0), 0, 0, $hair_w, $hair_h, 100);
      	ImageCopyMerge($compilation, $gearImg, (($l > 0) ? abs($l) : 0), (($t > 0) ? abs($t) : 0), 0, 0, $gear_w, $gear_h, 100);

        // Neuer hairImg
        $hairImg = ImageCreate($width, $height);
        $gray = hex2int('333333');
        $gray = ImageColorAllocate($hairImg, $gray['r'], $gray['g'], $gray['b']);
        $black = hex2int('000000');
        $black = ImageColorAllocate($hairImg, $black['r'], $black['g'], $black['b']);
        $none = ImageColorTransparent($hairImg,$gray);

        ImageCopyMerge($hairImg, $compilation, 0, 0, 0, 0, $width, $height, 100);

        $hair_w = ImageSX($hairImg);
	      $hair_h = ImageSY($hairImg);

        $last_l = (($l < 0) ? abs($l) : $last_l);
        $last_t = (($t < 0) ? abs($t) : $last_t);

			  ImagePNG($hairImg,'images/avatars/test_'.$e,100) or $die .= 'Fehler X03';
        //echo ' <img src="images/avatars/test_'.$e.'" border="1"> ';
      }
			else
      {
				$die .= 'gearImg not found'.$gear;
			}
		}
	}

	$x = floor($hair_w/2);
	$y = floor($hair_h/2);

  $imagefile = ($bool_admin)
                  ? sprintf($imagefile,'admin_'.$char['save_id'])
                  : sprintf($imagefile,'chara_'.$char['save_id']);

	ImagePNG($hairImg,$imagefile,100) or $die .= 'Fehler X03';

  // Aufraeumen
  ImageDestroy($hairImg);

  if($gear)
  {
		ImageDestroy($gearImg);
	}
	return '<img src="'.$imagefile.'" border="1">'.$die.$adm_info;
}

function genImage($char,$gender,$pos = '00000', $bool_admin = false){
	global $db,$table_prefix,$equip,$debug;
	$imagefile = ($bool_admin)? "../images/avatars/%s.png" : "images/avatars/%s.png";
        $equipment = explode(';',$char['char_equip']);     //Um himmels Willen, blos nicht $equip, das ist eine riesen Matrix
  genHeadImage($char,$gender,$equipment,$bool_admin);

	$char_name = $char['char_name'];
	$job = $char['char_job'];
	$hstyle = $char['char_hstyle'];
	$hcolor = $char['char_hcolor'];
	//$hcolor = '-1';

	$str_hair = ($bool_admin) ? '../rpg/classes/hair/%s/%s_%s.png' 		: 'rpg/classes/hair/%s/%s_%s.png';
	$str_mask = ($bool_admin) ? '../rpg/classes/hair/%s/%s_%s_mask.png' : 'rpg/classes/hair/%s/%s_%s_mask.png';
	$str_gear = ($bool_admin) ? '../rpg/headgear/%s_%s.png' : 'rpg/headgear/%s_%s.png';
	$str_bg = ($bool_admin)   ? '../rpg/classes/body/%s/%s_%s_%s.png' 	: 'rpg/classes/body/%s/%s_%s_%s.png';
  $str_head = ($bool_admin) ? '../images/avatars/chara_%s_head.png' : 'images/avatars/chara_%s_head.png';
  
	$hair = sprintf($str_hair,$gender,$hstyle,$pos);
	$mask = sprintf($str_mask,$gender,$hstyle,$pos);
	$body = sprintf($str_bg,$job,$job,$gender,$pos);
	$head = sprintf($str_head,$char['save_id']);
	
  $hairImg = (file_exists($hair)) ? ImageCreateFromPNG($hair) : $die .= 'Keine Frisur ausgesucht';
	$bodyImg = (file_exists($body)) ? ImageCreateFromPNG($body) : $die .= '<br>Keine Klasse ausgesucht';

	// Color the Hair if needed
  if(file_exists($mask)){

	if(strlen($hcolor) == 6 && file_exists($hair)){			// consists of exactly 6 digits
		$hairMask = (file_exists($mask)) ? ImageCreateFromPNG($mask) : $die .= '<br>failed to create Hair Mask';

		$dye = hex2int($hcolor);
		$dye['red'] = $dye['r'];
		$dye['green'] = $dye['g'];
		$dye['blue'] = $dye['b'];
		
		$x = ImageSX($hairImg);
		$y = ImageSY($hairImg);
				
		// First Step: Grayscale
		for($i=0; $i<$y; $i++){
			for($j=0; $j<$x; $j++){
				$pos = imagecolorat($hairImg, $j, $i);
				$f = imagecolorsforindex($hairImg, $pos);
				
				$posM = imagecolorat($hairMask, $j, $i);
				$mask = imagecolorsforindex($hairMask, $posM);
				
				$gst = $f["red"]*0.15 + $f["green"]*0.5 + $f["blue"]*0.35;
				if($mask['red'] == 0 && $mask['green'] == 0 && $mask['blue'] == 0){
					$col = imagecolorresolve($hairImg, $gst, $gst, $gst);
					imagesetpixel($hairImg, $j, $i, $col);
				}
			}
		}
		// Then Lay the Color over the Grayscale
		for($i=0; $i<$x; $i++){
			for($j=0; $j<$y; $j++){
				$pos = imagecolorat($hairImg, $i, $j);
				$f = imagecolorsforindex($hairImg, $pos);
				
				$posM = imagecolorat($hairMask, $i, $j);
				$mask = imagecolorsforindex($hairMask, $posM);
				
				if($mask['red'] == 0 && $mask['green'] == 0 && $mask['blue'] == 0){
					if($f['red'] <= 127){
						$new_color = multiPlus($f,$dye);
						//$out .= '<br><font color="ff0000">Grauwert: '.$f[red]." (127-) ";
						//foreach($new_color as $t => $u){
						//	$out .= ", $t => $u";
						//}
						//$out .= "</font>";
					}
					elseif($f['red'] > 127){
						$new_color = multiMinus($f,$dye);
						//$out .= '<br><font color="00ff00">Grauwert: '.$f[red]." (127+) ";
						//foreach($new_color as $t => $u){
						//	$out .= ", $t => $u";
						//}
						//$out .= "</font>";
					}

					$r = $new_color['red'];
					$g = $new_color['green'];
					$b = $new_color['blue'];

					$col = imagecolorresolve($hairImg, $r, $g, $b);
					imagesetpixel($hairImg, $i, $j, $col);
					//print "<br>($i,$j) $r, $g, $b,";
				}
			}
		}
	}
}	// End If file_exists($mask)
else{$adm_info .= '<br>nm'.$gender.$hstyle; }


	if($die)	return $die;

	$hair_w = ImageSX($hairImg);
	$hair_h = ImageSY($hairImg);
	$body_w = ImageSX($bodyImg);
	$body_h = ImageSY($bodyImg);
	//

        ////////////////////////ID-Wechsel von Useritems zu Itemids/////////////////////
        $equipment[2]=$equipment[9]; //Umbenennung zur einfacheren Verarbeitung
        for($i=0;$i<=2;$i++){
          $where = '';
          if($equipment[$i]) $where .= "useritem_id = {$equipment[$i]}";
          if($where != ''){
            $sql = "SELECT  useritem_id, item_id FROM {$table_prefix}useritems
                    WHERE $where";
            if (!($result = $db->sql_query($sql))) { message_die(GENERAL_MESSAGE, 'Glühend heiß... autsch, aua >.< 13<br>'.$sql.'<br>-'.$equip[3].'<br>-'.$equip[5].'<br>'); }
            while($row = $db->sql_fetchrow($result)){
              $equipment[$i] = ($row['useritem_id']>0) ? $row['item_id'] : 0;
              //$debug[] = '--------------------------------------------<br>$equipment['.$i.'] = '.$equipment[$i].'<br>';

            }
          }
        }
        ////////////////////////////////////////////////////////////////////////////////

	$e = $equipment[0];
	if($e > 500){
		$sql = "SELECT * FROM {$table_prefix}rpg_image_positions WHERE type='gear' AND type2='$e'";
		$result = $db->sql_query($sql);
		$debug[] = $sql.'<br>--> '.$db->sql_numrows($result).' Results';
		if($db->sql_numrows($result) > 0){
			$gearRow = $db->sql_fetchrow($result);
			$gear1 = sprintf($str_gear,$e,'00000');
			if(file_exists($gear1)){
				$gear1Img = ImageCreateFromPNG($gear1);
				$g1_top = $gearRow['pos_top'];
				$g1_left = $gearRow['pos_left'];
				$gear1_w = ImageSX($gear1Img);
				$gear1_h = ImageSY($gear1Img);
				$bool_gear1 = true;
				$gear = true;
			}
			else{
				$die .= 'gear1Img not found:'.$gear1;
			}
		}
	}
	$e = $equipment[1];
	if($e > 500){
		$sql = "SELECT * FROM {$table_prefix}rpg_image_positions WHERE type='gear' AND type2='$e'";
		$result = $db->sql_query($sql);
		$debug[] = $sql.'<br>--> '.$db->sql_numrows($result).' Results';
		if($db->sql_numrows($result) > 0){
			$gearRow = $db->sql_fetchrow($result);
			$gear2 = sprintf($str_gear,$e,'00000');
			if(file_exists($gear2)){
				$gear2Img = ImageCreateFromPNG($gear2);
				$g2_top = $gearRow['pos_top'];
				$g2_left = $gearRow['pos_left'];
				$gear2_w = ImageSX($gear2Img);
				$gear2_h = ImageSY($gear2Img);
				$bool_gear2 = true;
				$gear = true;
			}
			else{
				$die .= 'gear2Img not found';
			}
		}
	}
	$e = $equipment[2]; //Eigentlich $equipment[9], aber oben umbenannt. Umbenennung hat keine Auswirkungen.
	if($e > 500){
		$sql = "SELECT * FROM {$table_prefix}rpg_image_positions WHERE type='gear' AND type2='$e'";
		$result = $db->sql_query($sql);
		$debug[] = $sql.'<br>--> '.$db->sql_numrows($result).' Results';
		if($db->sql_numrows($result) > 0){
			$gearRow = $db->sql_fetchrow($result);
			$gear3 = sprintf($str_gear,$e,'00000');
			if(file_exists($gear3)){
				$gear3Img = ImageCreateFromPNG($gear3);
				$g3_top = $gearRow['pos_top'];
				$g3_left = $gearRow['pos_left'];
				$gear3_w = ImageSX($gear3Img);
				$gear3_h = ImageSY($gear3Img);
				$bool_gear3 = true;
				$gear = true;
			}
			else{
				$die .= 'gear3Img not found';
			}
		}
	}

	if($gear){
		$sql = "SELECT * FROM {$table_prefix}rpg_image_positions WHERE type='head2gear' AND gender='$gender' AND type2=$hstyle";
		$result = $db->sql_query($sql);
		if($db->sql_numrows($result) > 0){
			$row = $db->sql_fetchrow($result);
		}
		$h2g_top = $row['pos_top'];
		$h2g_left = $row['pos_left'];
	}

	// Get Positions from DB
	$sql = "SELECT * FROM {$table_prefix}rpg_image_positions WHERE gender='$gender' AND type='job' AND type2='$job'";
	if( !$result = $db->sql_query ($sql) ){
		$error = $db->sql_error();
		$debug .= '<br>Sql Fehler: '.$error;
	}
	$row = $db->sql_fetchrow($result);

	$job_top = $row[pos_top];
	$job_left = $row[pos_left];

	$sql = "SELECT * FROM {$table_prefix}rpg_image_positions WHERE gender = '$gender' AND type = 'hstyle' AND type2 = '$hstyle'";
	if (!($result = $db->sql_query($sql))){
		message_die(GENERAL_ERROR, "Fatal Error: Could not retrieve Image Positions.", "genImage()", __LINE__, __FILE__, $sql);
	}
	if($db->sql_numrows($result)<=0){
		$h_top = $job_top + 0;
		$h_left = $job_left + 0;
	}
	else{
		$row = $db->sql_fetchrow($result);
		$h_top = $job_top + $row['pos_top'];
		$h_left = $job_left + $row['pos_left'];
	}
	//echo "<br>asd: $h_top $h_left $job_top $job_left";

	$width = max($body_w,$hair_w,$gear1_w,$gear2_w,$gear3_w); // Später die Breite des Rechtecks
	$height = max($body_h + $hair_h,$gear1_h,$gear2_h,$gear3_h); // Später die Höhe des Rechtecks

	$compilation = ImageCreate($width, $height) or $die .= 'Fehler X02';
	$gray = hex2int('333333');
	$gray = ImageColorAllocate($compilation, $gray[r], $gray[g], $gray[b]);
	$black = hex2int('000000');
	$black = ImageColorAllocate($compilation, $black[r], $black[g], $black[b]);
	$none = ImageColorTransparent($compilation,$gray);

	//$h_top = 8;
	//$h_left = 5;

	$imagefile = sprintf($imagefile,'chara_'.$char['save_id']);

	ImageCopyMerge($compilation, $bodyImg, 0, $hair_h, 0, 0, $body_w, $body_h, 100);
	ImageCopyMerge($compilation, $hairImg, 0+$h_left, 0+$h_top, 0, 0, $hair_w, $hair_h, 100);
	if($bool_gear3){
		$t = floor($hair_h/2) - floor($gear3_h/2) + $h2g_top + $g3_top;
		$l = floor($hair_w/2) - floor($gear3_w/2) + $h2g_left + $g3_left;
		ImageCopyMerge($compilation, $gear3Img, $l+$h_left, $t+$h_top, 0, 0, $gear3_w, $gear3_h, 100);
	}
	if($bool_gear2){
		$t = floor($hair_h/2) - floor($gear2_h/2) + $h2g_top + $g2_top;
		$l = floor($hair_w/2) - floor($gear2_w/2) + $h2g_left + $g2_left;
		ImageCopyMerge($compilation, $gear2Img, $l+$h_left, $t+$h_top, 0, 0, $gear2_w, $gear2_h, 100);
	}
	if($bool_gear1){
		$t = floor($hair_h/2) - floor($gear1_h/2) + $h2g_top + $g1_top;
		$l = floor($hair_w/2) - floor($gear1_w/2) + $h2g_left + $g1_left;
		ImageCopyMerge($compilation, $gear1Img, $l+$h_left, $t+$h_top, 0, 0, $gear1_w, $gear1_h, 100);
	}
	ImagePNG($compilation,$imagefile,100) or $die .= 'Fehler X03';
	ImageDestroy($bodyImg);
	ImageDestroy($hairImg);
	ImageDestroy($compilation);

	return '<img src="'.$imagefile.'" border=0><br>'.$die.$adm_info;
}

function getArrowCombos(){
	$combo_arrow = array();
	$combo_arrow[] = array(909,1750,4);					// Jellopy
	$combo_arrow[] = array(902,1750,7);					// Tree Root
	$combo_arrow[] = array(1066,1750,20);					// Fine-grained Trunk
	$combo_arrow[] = array(1067,1750,20);					// Solid Trunk
	$combo_arrow[] = array(1068,1750,20);					// Barren Trunk
	$combo_arrow[] = array(1019,1750,40);					// Trunk
	$combo_arrow[] = array(952,1750,50);					// Cactus Needle
	$combo_arrow[] = array(1027,1750,70,1756,1);	      		        // Porcupine Quill
	$combo_arrow[] = array(1095,1750,100,1768,5);			        // Needle of Alarm
	$combo_arrow[] = array(2328,1750,700,1770,500);		                // Wooden Mail
	$combo_arrow[] = array(2329,1750,1000,1770,700);		        // Wooden Mail (1Slot)

	$combo_shadow = array();
        $combo_shadow[] = array(913,1767,1);                                        // Tooth of Bat
        $combo_shadow[] = array(957,1767,1,1762,1);                                 // Decayed Nail
        $combo_shadow[] = array(1021,1767,2,1770,40);                               // Dokkaebi Horn
        $combo_shadow[] = array(958,1767,5);                                        // Horrendous Mouth
        $combo_shadow[] = array(1003,1767,8);                                       // Coal
        $combo_shadow[] = array(923,1767,20,1760,10,1758,5);                        // Evil Horn
        $combo_shadow[] = array(7027,1767,100);                                     // Key of Unterground
        $combo_shadow[] = array(7019,1767,1000);                                    // Loki's Whisper

        $combo_wind = array();
        $combo_wind[] = array(992,1755,50);                                         // Wind of Vendure
        $combo_wind[] = array(2618,1755,50,1753,100,1764,10);                       // Matyrs Leash
        $combo_wind[] = array(996,1755,150,1768,5);                                 // Rough Wind

        $combo_crystal = array();
        $combo_crystal[] = array(717,1754,10,1759,1);                               // Blue Gemstone
        $combo_crystal[] = array(1053,1754,10,1753,20);                             // Ancient Tooth
        $combo_crystal[] = array(991,1754,50);                                      // Crystal Blue
        $combo_crystal[] = array(7036,1754,100);                                    // Fang of Hatii
        $combo_crystal[] = array(956,1754,150,1770,80);                             // Gill
        $combo_crystal[] = array(995,1754,150,1759,5);                              // Mysic Frozen
   
        $combo_curse = array();
        $combo_curse[] = array(716,1761,1,1762,10,1763,1);                        // Red Gemstone
        $combo_curse[] = array(1038,1761,2,1770,50);                              // Little Evil Horn
        $combo_curse[] = array(609,1761,40);                                      // Amulet
        $combo_curse[] = array(724,1761,50,1768,10);                              // Cursed Ruby
        $combo_curse[] = array(7024,1761,200,1764,600);                           // Bloody Edge
        $combo_curse[] = array(7020,1761,1000);                                   // Mothers Nightmare
  
        $combo_fire = array();
        $combo_fire[] = array(990,1752,600);                                      // Red Blood
        $combo_fire[] = array(994,1752,600,1769,5);                               // Flameheart
        $combo_fire[] = array(7035,1752,1000);                                    // Matchstick

        $combo_flash = array();
        $combo_flash[] = array(997,1760,5,1756,150);                              // Great Nature
        $combo_flash[] = array(1001,1760,10);                                     // Stardust
        $combo_flash[] = array(923,1760,10,1767,20,1758,5);                       // Evil Horn
        $combo_flash[] = array(1000,1760,30);                                     // Star Crumb
        $combo_flash[] = array(969,1760,50,1765,50);                              // Gold
        $combo_flash[] = array(7021,1760,200);                                    // Foolishness of the Blind
        $combo_flash[] = array(2319,1760,1000);                                   // Blittering Jacket

        $combo_freeze = array();
        $combo_freeze[] = array(717,1759,1,1754,10);                              // Blue Gemstone
        $combo_freeze[] = array(995,1759,5,1754,150);                             // Mysic Frozen

        $combo_mute = array();
        $combo_mute[] = array(994,1769,5,1752,600);                                // Flameheart
        $combo_mute[] = array(604,1769,40);                                        // Dead Branch
        $combo_mute[] = array(7025,1769,400,1768,800,1758,800);                    // Lucifers Lament
        $combo_mute[] = array(714,1769,600,1765,600,1757,600);                     // Emperium

        $combo_iron = array();
        $combo_iron[] = array(713,1770,2);                                         // Empty Bottle
        $combo_iron[] = array(910,1770,12);                                        // Garlet
        $combo_iron[] = array(920,1770,15);                                        // Wolf Claw
        $combo_iron[] = array(922,1770,30,1753,5,1756,1);                          // Orcs Fang
        $combo_iron[] = array(947,1770,35);                                        // Horn
        $combo_iron[] = array(1021,1770,40,1767,2);                                // Dokkaebi Horn
        $combo_iron[] = array(1002,1770,50);                                       // Iron Ore
        $combo_iron[] = array(1010,1770,50);                                       // Phracon
        $combo_iron[] = array(1035,1770,50,1765,1);                                // Dragon Canine
        $combo_iron[] = array(1038,1770,50,1761,2);                                // Little Evil Horn
        $combo_iron[] = array(1018,1770,50,1756,2);                                // Mole Claw
        $combo_iron[] = array(1041,1770,80);                                       // Latern
        $combo_iron[] = array(956,1770,80,1754,150);                               // Gill
        $combo_iron[] = array(998,1770,100);                                       // Iron
        $combo_iron[] = array(1064,1770,100,1753,50);                              // Reins
        $combo_iron[] = array(1011,1770,200,1751,40);                              // Emveretarcon
        $combo_iron[] = array(2328,1770,500,1750,700);                             // Wooden Mail
        $combo_iron[] = array(2408,1770,700,1753,50);                              // Ball n Chain
        $combo_iron[] = array(2329,1770,700,1750,1000);                            // Wooden Mail [1]

        $combo_immaterial = array();
        $combo_immaterial[] = array(2333,1757,10,1751,1000);                          // Silver Robe [1]
        $combo_immaterial[] = array(714,1757,600,1769,600,1765,600);                  // Emperium

        $combo_oridecon = array();
        $combo_oridecon[] = array(931,1765,1,1758,5);                                 // Orcish Voucher
        $combo_oridecon[] = array(1035,1765,1,1770,50);                               // Dragon Canine
        $combo_oridecon[] = array(7026,1765,50);                                      // Key of Clocktower
        $combo_oridecon[] = array(756,1765,50);                                       // Rough Oridecon
        $combo_oridecon[] = array(969,1765,50,1760,50);                               // Gold
        $combo_oridecon[] = array(984,1765,250);                                      // Oridecon
        $combo_oridecon[] = array(714,1765,600,1757,600,1769,600);                    // Emperium
        $combo_oridecon[] = array(7022,1765,1000);                                    // Old Hilt

        $combo_poison = array();
        $combo_poison[] = array(937,1763,1);                                          // Venom Canine
        $combo_poison[] = array(959,1763,1);                                          // Stinky Scale
        $combo_poison[] = array(716,1763,1,1762,10,1761,1);                           // Red Gemstone
        $combo_poison[] = array(7010,1763,1,1753,250);                                // Tail of Steel Scorpion

        $combo_rustey = array();
        $combo_rustey[] = array(939,1762,1);                                          // Bee Sting
        $combo_rustey[] = array(957,1762,1,1767,1);                                   // Dacayed Nail
        $combo_rustey[] = array(904,1762,3);                                          // Scorpion Tail
        $combo_rustey[] = array(1044,1762,5);                                         // Zenorcs Fang
        $combo_rustey[] = array(7002,1762,5,1753,30);                                 // Ogre Tooth
        $combo_rustey[] = array(716,1762,10,1763,1,1761,1);                           // Red Gemstone

        $combo_steel = array();
        $combo_steel[] = array(922,1753,5,1770,30,1756,1);                            // Orcs Fang
        $combo_steel[] = array(911,1753,8);                                           // Scell
        $combo_steel[] = array(1043,1753,10);                                         // Orc Claw
        $combo_steel[] = array(1053,1753,20,1754,10);                                 // Ancient Tooth
        $combo_steel[] = array(7002,1753,30,1762,5);                                  // Ogre Tooth
        $combo_steel[] = array(1098,1753,50);                                         // Manacles
        $combo_steel[] = array(1064,1753,50,1770,100);                                // Reins
        $combo_steel[] = array(2408,1753,50,1770,700);                                // Ball n Chain
        $combo_steel[] = array(999,1753,100);                                         // Steel
        $combo_steel[] = array(2618,1753,100,1755,50,1764,10);                        // Matyrs Leash
        $combo_steel[] = array(757,1753,200,1758,5);                                  // Rough Elunium
        $combo_steel[] = array(2292,1753,200,1758,40);                                // Welding Mask
        $combo_steel[] = array(2281,1753,200,1769,40);                                // Phantom of the Opera
        $combo_steel[] = array(7010,1753,250,1763,1);                                 // Tail of Steel Scorpion
        $combo_steel[] = array(2288,1753,300,1764,200);                               // Mr. Scream
        $combo_steel[] = array(985,1753,1000,1758,50);                                // Elunium

        $combo_sharp = array();
        $combo_sharp[] = array(1031,1764,1);                                     // Mantis Scythe
        $combo_sharp[] = array(1063,1764,2,1751,40);                             // Fang
        $combo_sharp[] = array(2618,1764,10,1753,100,1755,50);                   // Matyrs Leash
        $combo_sharp[] = array(733,1764,50);                                     // Cracked Diamond
        $combo_sharp[] = array(2288,1764,200,1753,300);                          // Mr. Scream
        $combo_sharp[] = array(7023,1764,600,1767,200);                          // Blade lost in Darkness
        $combo_sharp[] = array(7024,1764,600,1761,200);                          // Bloody Edge

        $combo_silver = array();
        $combo_silver[] = array(1063,1751,40,1764,2);                             // Fang
        $combo_silver[] = array(1011,1751,40,1770,200);                           // Emveretarcon
        $combo_silver[] = array(912,1751,50);                                     // Zargon
        $combo_silver[] = array(2332,1751,700);                                   // Silver Robe
        $combo_silver[] = array(2257,1751,1000);                                  // Snow Horn
        $combo_silver[] = array(2333,1751,1000,1757,10);                          // Silver Robe [1]

        $combo_sleep = array();
        $combo_sleep[] = array(715,1768,1,1756,10);                              // Yellow Gemstone
        $combo_sleep[] = array(1095,1768,5,1750,100);                            // Needle of Alarm
        $combo_sleep[] = array(996,1768,5,1755,150);                             // Rough Wind
        $combo_sleep[] = array(724,1768,10,1761,50);                             // Cursed Ruby
        $combo_sleep[] = array(7025,1768,800,1758,800,1769,400);                 // Lucifers Lament

        $combo_stone = array();
        $combo_stone[] = array(922,1756,1,1770,30,1753,5);                       // Orcs Fang
        $combo_stone[] = array(1027,1756,1,1750,70);                             // Porcupine Quill
        $combo_stone[] = array(1018,1756,2,1770,50);                             // Moll Claw
        $combo_stone[] = array(715,1756,10,1768,1);                              // Yellow Gemstone
        $combo_stone[] = array(993,1756,50);                                     // Green Live
        $combo_stone[] = array(997,1756,150,1760,6);                             // Great Nature

        $combo_stun = array();
        $combo_stun[] = array(7008,1758,2);                                     // Stiff Horn
        $combo_stun[] = array(931,1758,5,1765,1);                               // Orcish Voucher
        $combo_stun[] = array(923,1758,5,1767,20,1760,10);                      // Evil Horn
        $combo_stun[] = array(757,1758,5,1753,200);                             // Rough Elunium
        $combo_stun[] = array(2292,1758,40,1753,200);                           // Welding Mask
        $combo_stun[] = array(985,1758,50,1753,1000);                           // Elunium
        $combo_stun[] = array(7025,1758,800,1768,800,1769,400);                 // Luzifers Lament

$arrow_names = array();
for ($q = 1750; $q < 1771; $q++) {$arrow_names[$q] = getItemName($q,'ger');};

$combo=array(
$arrow_names[1750] => $combo_arrow,                     //                    'Pfeile'
$arrow_names[1767] => $combo_shadow,                    //             'Schattenpfeil'
$arrow_names[1755] => $combo_wind,                      //                 'Windpfeil'
$arrow_names[1754] => $combo_crystal,                   //             'Kristallpfeil'
$arrow_names[1761] => $combo_curse,                     //         'Verfluchter Pfeil'
$arrow_names[1752] => $combo_fire,                      //                'Feuerpfeil'
$arrow_names[1760] => $combo_flash,                     //                'Blendpfeil'
$arrow_names[1759] => $combo_freeze,                    //                  'Eispfeil'
$arrow_names[1769] => $combo_mute,                      //             'Stummer Pfeil'
$arrow_names[1770] => $combo_iron,                      //                'Eisenpfeil'
$arrow_names[1757] => $combo_immaterial,                //        'Spiritueller Pfeil'
$arrow_names[1765] => $combo_oridecon,                  //            'Oridecon Pfeil'
$arrow_names[1763] => $combo_poison,                    //                 'Giftpfeil'
$arrow_names[1762] => $combo_rustey,                    //            'Rostiger Pfeil'
$arrow_names[1753] => $combo_steel,                     //                'Stahlpfeil'
$arrow_names[1764] => $combo_sharp,                     //             'Spitzer Pfeil'
$arrow_names[1751] => $combo_silver,                    //               'Silberpfeil'
$arrow_names[1768] => $combo_sleep,                     //               'Schlafpfeil'
$arrow_names[1756] => $combo_stone,                     //                'Steinpfeil'
$arrow_names[1758] => $combo_stun                       //           'Betäubungspfeil'
);

return $combo;
}

// *****
// Attack Calculation
// *****
function getAttackPercent($a_ele,$d_scale,$d_ele,$d_ele_lv,$wType){
        global $db_mod_size,$db_mod_element,$debug;
        $percent = $db_mod_size[$wType][$d_scale];
        $debug[] = '<br>get Attack Percent:';
        $debug[] = '-> size: $db_mod_size['.$wType.']['.$d_scale.'] = '.$percent;
        //$info[] = ",per:$percent,[$d_ele_lv][$a_ele][$d_ele],".$db_mod_element[$d_ele_lv][$a_ele][$d_ele];
        $percent = ($percent/100)*($db_mod_element[$d_ele_lv][$a_ele][$d_ele]);
        $debug[] = '-> element: (size/100)*$db_mod_element['.$d_ele_lv.']['.$a_ele.']['.$d_ele.'] = '.$percent.'%';
        $percent = round($percent);
        return $percent;
}

function getBar($name,$value1,$value2,$color1,$color2,$view_type){
  // $name name of the Bar
  // $value1 value of the FULL part of the Bar
  // $value2 value of the MAX part of the bar
  // $color1 color of FULL part of the Bar
  // $color2 color of EMPTY part of the Bar
  $max_value = $value2 + ($value/100 * 5);
  if( $value1 > $max_value ){
    $value1 = $max_value;
  }
  $drug_value = $value1 - $value2;
  
  
  if(!$value2 || $value2 <= 0){
          return '';
  }
  
  if(substr_count($view_type,"percent")>0){
          $value1 = round($value1 * 100 / $value2,2);
          $value2 = 100;
          //$pr_right = "/$value2%";
          $pr_right = "%";
  }
  else{
          $pr_right = "/$value2";
  }
	if(substr_count($view_type,"break")>0){
		$break_line = '<br>';
	}
  if(substr_count($view_type,"2")>0){
    $smallbar = true;
  }

  $fullwidth = round(($value1/$value2)*50);
  $alt = $name.': '.$value1.$pr_right;
  $full = ($fullwidth != 0) ? '<img src="rpg/images/bar_'.$color1.'_middle.gif" width="'.$fullwidth.'" height="13" border="0" alt="'.$alt.'" title="'.$alt.'">' : '';
  $emptywidth = 50-$fullwidth;
  $empty = ($emptywidth != 0) ? '<img src="rpg/images/bar_'.$color2.'_middle.gif" width="'.$emptywidth.'" height="13" border="0" alt="'.$alt.'" title="'.$alt.'">' : '';
  if($smallbar){
    $full = '<img src="rpg/images/bar_2_full.gif" width="'.$fullwidth.'" height="7" alt="'.$alt.'" title="'.$alt.'">';
    $empty = '<img src="rpg/images/bar_2_empty.gif" width="'.$emptywidth.'" height="7" alt="'.$alt.'" title="'.$alt.'">';
  }

  if($value1 == 0)
    $clr = $color2;
  else
    $clr = $color1;
  $bar = ($smallbar) ? '<img src="rpg/images/bar_2_begin.gif">' : "<img width=\"6\" height=\"13\" src=\"rpg/images/bar_".$clr."_begin.gif\">";
  $bar .= $full.$empty;
  if($value1<$value2){        
    $clr = $color2;
  }
  elseif($value1 > $value2){
    $drug_width = $fullwidth/$value * $drug_value;
    $bar .= '<img src="rpg/images/bar_orange_middle.gif" width="'.$drug_width.'" height="13" alt="+'.$drug_value.'">';
  }
  else{
    $clr = $color1;
  }
  $bar .= ($smallbar) ? '<img src="rpg/images/bar_2_end.gif">' : "<img width=\"6\" height=\"13\" src=\"rpg/images/bar_".$clr."_end.gif\">";
  if(!substr_count($view_type,"bar")>0){
    $bar .= " $break_line<b>$value1$pr_right</b>&nbsp;&nbsp;&nbsp;";
  }
  return $bar;
}

function getCharData($user){
	global $db,$table_prefix;
	$sql = "SELECT * FROM {$table_prefix}charas WHERE save_id={$user['user_save']}";
	if (!($result = $db->sql_query($sql))){
		message_die(GENERAL_ERROR, "Fatal Error: Could not retrieve Char Data.", "getCharData($save_id)", __LINE__, __FILE__, $sql);  
	}
	if($db->sql_numrows($result)<=0){
		message_die(GENERAL_ERROR, "Du hast keinen Charakter aktiv!!");  
	}
	$char = $db->sql_fetchrow($result);

	return $char;
}

function getCharData2($id){
	global $db,$table_prefix,$viewer;
	$sql = "SELECT * FROM {$table_prefix}charas WHERE save_id={$id}";
	//debug_echo('ID:'.$id,$viewer);
	if (!($result = $db->sql_query($sql))){
		message_die(GENERAL_ERROR,'Error in getCharData2($id).');
    //message_die(GENERAL_ERROR, "Fatal Error: Could not retrieve Char Data.", "getCharData($save_id)", __LINE__, __FILE__, $sql);
	}
	if($db->sql_numrows($result)<=0){
		//message_die(GENERAL_ERROR, "Das ist kein aktiver Charakter!!<br>ID: $id");
	}
	else{
		$char = $db->sql_fetchrow($result);
	}
	return $char;
}

function getCharQuestVars($char){
	$a = explode(';',$char['char_questvars']);
	$vars = array();
	for($i=0; $i<sizeof($a);$i++){
		$b = explode(',',$a[$i]);
		$c = '';
		for($j=1;$j<sizeof($b);$j++){
                  $c .= $b[$j];
                  if(($j+1) != sizeof($b)){$c .= ',';}
                }
		$vars[$b[0]] = $c;
	}
	return $vars;
}

function getFtData($file){
	$js_lines = file('rpg/maps/ft_defs/'.$file.'_ft.js');
	foreach($js_lines as $i => $line){
		$s = $line;
		$s = str_replace('var','',$s);
		$s = str_replace('map_default_x','$map_default_x',$s);
		$s = str_replace('map_default_y','$map_default_y',$s);
		$s = str_replace('field_type','$field_type',$s);
		$s = str_replace('new Array','array',$s);
		$php_code .= "$s";
	}
	eval($php_code);
        return $field_type;
}


// gibt die richtige zahl zurück für die berechnungen der boni etc.
function getClassNumber($class){
        switch($class){
                case "swd":        $n = 1; break;
                case "arc":        $n = 2; break;
                case "thf":        $n = 3; break;
                case "aco":        $n = 4; break;
                case "mer":        $n = 5; break;
                case "mag":        $n = 6; break;
                case "kni":        $n = 7; break;
                case "hun":        $n = 8; break;
                case "ass":        $n = 9; break;
                case "pri":        $n = 10; break;
                case "bla":        $n = 11; break;
                case "wiz":        $n = 12; break;
                case "gm0":        $n = 21; break;
                case "gm1":        $n = 22; break;
                default:        $n = 0; break;
        }
        return $n;
}

// Get Bonus from equipped Items
function getEquipmentBonus($itemid,$ebonus,$db2){
	global $table_prefix;
        $sql2 = "SELECT * FROM {$table_prefix}item_db2 WHERE id = $itemid";
        $db2->sql_query($sql2);
        if (!($result2 = $db2->sql_query($sql2))){
                message_die(GENERAL_ERROR, "Fatal Error: Could not retrieve Item $itemid.<br>
                getEquipmentBonus",  "Error in getEquipB",__LINE__, __FILE__, $sql2);
        }
        $item = $db2->sql_fetchrow($result2);
		$ebonus = interpreteScript($item['script_equip'],$ebonus);
        if($item['type'] == 10){					// Arrow
			$ebonus['arrow_att'] = $item['attack'];
        }
		else{
			$ebonus['att'] += $item['attack'];
        }
		$ebonus['def'] += $item['defence'];

        return $ebonus;
}
function getImageFile($char,$sex,$use){
	require('includes/db_items.php');
	$equip = explode(';',$char['char_equip']);
	$class = $char['char_job'];
	$hair = $char['char_hstyle'];
	$hcolor = $char['char_hcolor'];
	//$num_ani = 8;
	//$sex = 'f';
	//$ar = array('00005','00006','00007','00008','00009','00010','00011','00012');
	if($hair == '-1')	$hair = ($sex == 'm') ? '2' : '1';
	$hair2 = ($hcolor==0) ? $hair : $hair.'_'.$hcolor;
	$ar = array('00000');
	$bpos = getBodyPos($class,$sex);
	$hpos = getHeadPos($class,$sex,$hair);

	for($i = 0; $i < sizeof($ar); $i++){
		if($i == 0){
			$pos_ani_h_top .= 	"\n".'"'.$hpos[$ar[$i]]['top'].'"';
			$pos_ani_h_left .= 	"\n".'"'.$hpos[$ar[$i]]['left'].'"';
			$pos_ani_b_top .= 	"\n".'"'.$bpos[$ar[$i]]['top'].'"';
			$pos_ani_b_left .= 	"\n".'"'.$bpos[$ar[$i]]['left'].'"';
		}/*
		else{
			$pos_ani_h_top .= 	','."\n".'"'.$hpos[$ar[$i]]['top'].'"';
			$pos_ani_h_left .= 	','."\n".'"'.$hpos[$ar[$i]]['left'].'"';
			$pos_ani_b_top .= 	','."\n".'"'.$bpos[$ar[$i]]['top'].'"';
			$pos_ani_b_left .= 	','."\n".'"'.$bpos[$ar[$i]]['left'].'"';
		}*/
	}
	if(file_exists('rpg/classes/hair/'.$sex.'/'.$hair2.'_00000.gif')){
		$hair = 'rpg/classes/hair/'.$sex.'/'.$hair2.'_00000.gif';
	}
	else{
		$hair = 'rpg/classes/hair/'.$sex.'/'.$hair.'_00000.gif';
	}
	if(in_array($equip[1],$db_view_items)){
		$mod_pos = $db_pos_item[$equip[1]];
		$mod_pos_t = $mod_pos[4];
		$mod_pos_l = $mod_pos[5];
		$hg_pos = 'top:'.$mod_pos[2].'px; left:'.$mod_pos[3].'px;';
		$lay_hg .= 	'<div width="80" name="headgear" id="headgear" style="position:relative; z-index:'.$mod_pos[0].'; '.$hg_pos.'">
                    	<img src="rpg/headgear/'.$mod_pos[1].'_00000.gif" border="0">
                    </div>';
	}
	$cur_hpos = 'top:'.($hpos['00000']['top']+$mod_pos_t).'px; left:'.($hpos['00000']['left']+$mod_pos_l).'px;';
	if($use == 'list') 
		$cur_hpos = 'top:'.($hpos[00000]['top']+$mod_pos_t).'px; left:'.($hpos['00000']['left']+$mod_pos_l).'px;';
	
	$body = 'rpg/classes/body/'.$class.'/'.$class.'_'.$sex.'_00000.gif';
	$cur_bpos = 'top:'.($bpos['00000']['top']+$mod_pos_t).'px; left:'.($bpos['00000']['left']+$mod_pos_l).'px;';
	if($use == 'list')
		$cur_bpos = 'top:'.($bpos[00000]['top']+$mod_pos_t).'px; left:'.($bpos['00000']['left']+$mod_pos_l).'px;';
	
	$debug[] = "hair: $hair, body: $body";

	$image = '<table width="80" border="0" align="middle">
          	<tr>
            	<td width="80" height="105" valign="top">
					'.$lay_hg.'
                	<div width="80" height="105" name="ani_hair" id="ani_hair" style="position:relative; z-index:2; '.$cur_hpos.'">
                    	<img src="'.$hair.'" border="0">
                    </div>
                    <div width="80" height="105" name="ani_body" id="ani_body" style="position:relative; z-index:1; '.$cur_bpos.'">
                    	<img src="'.$body.'" border="0">
                    </div>
                </td>
            </tr>
          </table>';
	$r['image'] = $image;
	$r['pos_ani_h_top'] = $pos_ani_h_top;
	$r['pos_ani_h_left'] = $pos_ani_h_left;
	$r['pos_ani_b_top'] = $pos_ani_b_top;
	$r['pos_ani_b_left'] = $pos_ani_b_left;

	if(($class == '')||($class == '-1'))
		$r['image'] = '<table width="80" border="0" align="middle">
          	<tr valign="top">
            	<td width="80" height="105">
                	<img src="rpg/classes/full_'.$sex.'_nov_stand_00.gif" border="0">
                </td>
            </tr>
          </table>';
	
	return $r;
}
function getItemData($id){
        global $db, $table_prefix;
        $sql = "SELECT * FROM {$table_prefix}item_db2 WHERE id=$id";
        if (!($result = $db->sql_query($sql))) { message_die(GENERAL_ERROR, "Fatal Error: Could not retrieve Item Data.",  "Error in getItemID",__LINE__, __FILE__, $sql); }
        $row = $db->sql_fetchrow($result);
        //echo "asd".$row['id'];
        return $row;
}
// logisch oder?
function getItemId($name){
        global $db, $table_prefix;
        $sql = "SELECT id,name FROM {$table_prefix}item_db2 WHERE name='$name'";
        if (!($result = $db->sql_query($sql))) { message_die(GENERAL_ERROR, "Fatal Error: Could not retrieve Item ID.",  "Error in getItemID",__LINE__, __FILE__, $sqla); }
        $row = $db->sql_fetchrow($result);
        //echo "asd".$row['id'];
        return $row['id'];
}
function getItemImage($id){
        if(!empty($id)){
                $name = getItemName($id,'ger');
                if(file_exists('rpg/items/'.$id.'.gif') ){
                        $return = '<img src="rpg/items/'.$id.'.gif" alt="'.$name.'" title="'.$name.'">';
                }
                elseif(file_exists('rpg/items/'.$name.'.gif') ){
                        $return = '<img src="rpg/items/'.$name.'.gif" alt="'.$name.'" title="'.$name.'">';
                }
                else{
                        $return = '<img src="rpg/items/noimage.gif" alt="'.$name.'" title="'.$name.'">';
                }
        }
        else{
                $return = '';
        }
        return $return;
}

// und andersrum
function getItemName($id,$lang = 'eng'){
	global $db, $table_prefix;
    $sql = "SELECT id, name, name_ger FROM {$table_prefix}item_db2 WHERE id= '$id'";
    if (!($result = $db->sql_query($sql))) {
		message_die(GENERAL_ERROR, "Fatal Error: Could not retrieve Item Name of Item $id<br>sql:$sql",  "Error in getItemName",__LINE__, __FILE__, $sqla); 
	}
    $row = $db->sql_fetchrow($result);
    //echo "asd".$row['name_ger'].$row['name'];
    $return = ($lang == 'eng' || strlen($row['name_ger'])<3) ? $row['name'] : $row['name_ger'];
	return $return;
}

function getItemType($itemid){
	// Items mit einer Id die im Bereich von 501 - 618 & 644 - 700 liegen aber trotzdem (hier) nicht benutzbar sind
	$not_usable = array(659,660,661);
	if($itemid < 500 || $itemid == 7042/* || $itemid == 20003*/){
		return 'special';
	}
	elseif( ( ($itemid >= 501 && $itemid <= 618) || ($itemid >= 644 && $itemid <= 700) ) && !in_array($itemid,$not_usable)){
		return 'use';
	}
	elseif( ($itemid >= 1101 && $itemid <= 4000) || ($itemid >= 5001 && $itemid <= 5128) || ($itemid >= 20001 && $itemid <= 20002)){
		return 'equip';
	}
	else{
		return 'etc';
	}

}

// Liest einfache numerische und alphanumerische Variablen aus einer JS-Script-Datei
function get_js_vars($js_file){
	$js_array = array();
	$js_lines = file($js_file);

	$numeric_var = "#var (\w+) = (\d+);#";
	$string_var = "#var (\w+) = ((\"(\w+)\")|('(\w+)'));#";

	foreach($js_lines as $i => $line){
		$match = array();
		preg_match($numeric_var,$line,$match);
		if($match[0]){
			/*echo "<br>Numeric:";
			foreach($match as $m => $n){
				echo "<br>$m => $n";
			}*/
			/* etwaige Ausgabe: 
				Numeric:
				0 => var width = 10;
				1 => width
				2 => 10
			*/
			$js_array[$match[1]] = end($match);
		}
		$match = array();
		preg_match($string_var,$line,$match);
		if($match[0]){
			/*echo "<br>String found:";
			foreach($match as $m => $n){
				echo "<br>$m => $n";
			}*/
			/* etwaige Ausgabe:
			String found:
				0 => var name = 'Hallo';
				1 => name
				2 => 'Hallo'
				3 =>
				4 =>
				5 => 'Hallo'
				6 => Hallo
			*/
			$js_array[$match[1]] = end($match);
		}
	}
	return $js_array;
}

// wählt ein monster aus gegen das man kämpfen wird.
function getMobID($mode,$battleground){
        global $db, $db_boss_mob,$db_ht1_mob,$table_prefix;

	if(empty($battleground)){    
		message_die(GENERAL_MESSAGE, "Kampf ungültig. Kämpfe müssen über die Karte initiiert werden. <a href=\"rpg_map.php\" class=\"forumlink\">Zurück zur Karte</a>");
        }        
	
	if($mode == "BOSS"){
    mt_srand((double)microtime()*1000000);        // New Seed
    $rdm = mt_rand(0,sizeof($db_boss_mob)-1);
    $mobID = $db_boss_mob[$rdm][0];
  }
  elseif($mode == "HT_1"){
    mt_srand((double)microtime()*1000000);        // New Seed
    $rdm = mt_rand(0,sizeof($db_ht1_mob)-1);
    $mobID = $db_ht1_mob[$rdm][0];
  }
  else{
    $sql = "SELECT * FROM {$table_prefix}dungeons WHERE  id = $battleground";
    if (!($result = $db->sql_query($sql))) {
      message_die(GENERAL_ERROR, "Fataler Fehler: Kampfort ung&uuml;ltig!", "Fehler aufgetreten",__LINE__, __FILE__, $sql);
    }
    $bground = $db->sql_fetchrow($result);

    // BOSS ? (but not in MVP-Mode)
    mt_srand((double)microtime()*1000000);        // New Seed
    $rdm = mt_rand(1,100);
    if($rdm <= $bground['boss_per']){
      $mobID = $bground['boss_id'];
    }
    else{
      //echo "no boss for you";
      // make list which monster are possible in this area
    	$p = 0;
      for($i=1;$i<=6;$i++){
      	$mob[$i] = $bground["id_$i"];
        $per[$i] = $p + $bground["per_$i"];
        $p += $bground["per_$i"];
   	    //echo "<br>$i::".$mob[$i].":".$per[$i].",p:".$p;
      }
      mt_srand((double)microtime()*1000000);        // New Seed
      $rdm = mt_rand(0,100);
      for($i=1;$i<=6;$i++){
      	if($rdm <= $per[$i]){
         	$mobID = $mob[$i];
          $i = 10;        // schleife abbrechen
      	}
      }
    }
  }
  return $mobID;
}

function getMonsterImage($monster,$state = 'stand'){
	$name = str_replace(' ','_',$monster['name_eng']);
	switch($state){
		case 'stand':
			$s = '1';
			break;
		default: $s = 'a';
	}
	if(file_exists('rpg/mob/'.$monster['id'].'_'.$s.'.gif'))
        $mob_image = '<img src="rpg/mob/'.$monster['id'].'_'.$s.'.gif" alt="'.$monster['name_ger'].'">';
	elseif(file_exists('rpg/mob/'.$monster['id'].'_1.gif'))
        $mob_image = '<img src="rpg/mob/'.$monster['id'].'_1.gif" alt="'.$monster['name_ger'].'">';
	elseif(file_exists('rpg/mob/'.$name.'_'.$s.'.gif'))
        $mob_image = '<img src="rpg/mob/'.$name.'_'.$s.'.gif" alt="'.$monster['name_ger'].'">';
	elseif(file_exists('rpg/mob/'.$name.'_a.gif'))
        $mob_image = '<img src="rpg/mob/'.$name.'_a.gif" alt="'.$monster['name_ger'].'">';
	else
       	$mob_image = '<img src="rpg/mob/noimage.gif" alt="no image for '.$monster['name_ger'].'">';
	return $mob_image;
}

function getMobName($id,$lang){
	global $db,$table_prefix;
	$sql = "select name_eng,name_ger from {$table_prefix}mob_db where id=$id";
	if (!($result = $db->sql_query($sql))) { message_die(GENERAL_ERROR, "Fatal Error: Could not retrieve Mob Name of Mob $id<br>sql:$sql",  "Error in getMobName",__LINE__, __FILE__, $sqla); }
    $row = $db->sql_fetchrow($result);
	$name = ($lang == 'german' && strlen($row['name_ger']) > 3 ) ? $row['name_ger'] : $row['name_eng'];
	return $name; 
}
 
function getOwner($char_id,$rtype = 'both'){
	global $db,$table_prefix;
	$sql = "SELECT USERS.user_id,username,user_ips FROM {$table_prefix}users AS USERS,{$table_prefix}charas AS CHARAS
	       WHERE CHARAS.save_id = '$char_id' AND CHARAS.user_id = USERS.user_id";
	$result = $db->sql_query($sql);
	$debug[] = $sql.'<br>--> '.$db->sql_numrows($result).' Results';
	if($db->sql_numrows($result) > 0){
		$row = $db->sql_fetchrow($result);
	}
 	else{
		$row['user_id'] = -1;
 	    	$row['username'] = 'none';
	}
	if($rtype == 'id')		
		$return = $row['user_id'];
	elseif($rtype == 'name')
		$return = $row['username'];
	else
		$return = $row;
	
	return $return;
}

function getPersonalBar($char){
	global $board_config,$lang,$db,$table_prefix;
  
  $personal = '
	<tr>
		<td class="row1" width="50%">
			<span class="gensmall" alt="Link ins Inventar">
				<a href="'.append_sid("rpg_portal.php?s=inventory&searchid=".$char['save_id']).'" class="navsmall">
					'.$lang['SHOP_YOUR_INVENTORY'].'
				</a>
			</span>
		</td>
		<td class="row1" align="right" width="50%">
			<span class="gensmall" alt="Geldstand">'.$char['char_points'].' '.$board_config['points_name'].'</span>
		</td>
	</tr>';
	
	$floating_trades = false;
		
	$sql = "SELECT * FROM `{$table_prefix}trades` 
          WHERE `userid_2` = '".$char['save_id']."' AND `state` = 'floating'";
	if ( !($result = $db->sql_query($sql)) ){
		message_die(GENERAL_MESSAGE, 'Es gab einen Fehler:<br>'.$sql.'<br>'.mysql_error()); 
	}
	if($row = $db->sql_fetchrow($result)){
		$floating_trades = true;
		do{
			$partner = getCharData2($row[userid_1]);
			$trades .= '
			<tr>
				<td>Dir wurde ein Handel von '.$partner[char_name].' vorgeschlagen! </td>
				<td><a href="rpg_portal.php?s=shop_actions&action=trade_stage5&trade='.$row[id].'">Handel ansehen.</a></td>
			</tr>';
		}while($row = $db->sql_fetchrow($result));
	}
	
	if ($floating_trades) {
        $personal .= ''.$trades;
        }
	if (strlen($char['char_specmsg']) > 2) {
        $personal .= '
	<tr>
		<td class="row2" colspan="2">
			<span class="gensmall"><font color="red">'.$char['char_specmsg'].'</font></span>
		</td>
	</tr>
	<tr>
		<td class="row2" colspan="2">
			<span class="gensmall"><a href="rpg_portal.php?s=inventory&clm=true" class="gen">'.$lang['SHOP_CLEAR_MESSAGES'].'</a></span>
		</td>
	</tr>';
	}
/*	if (strlen($char['char_specmsg']) > 2) {
        $personal .= '
	<tr>
		<td class="row2" colspan="2">
			<span class="gensmall"><font color="red">'.$char['char_specmsg'].'</font></span>
		</td>
	</tr>
	<tr>
		<td class="row2" colspan="2">
			<span class="gensmall"><a href="rpg_portal.php?s=inventory&clm=true" class="gen">'.$lang['SHOP_CLEAR_MESSAGES'].'</a></span>
		</td>
	</tr>';
	} */
  return $personal;
}

function getPersonals($row){
	global $lang,$phpEx,$spmax,$hpmax;
	$save_id = $row['save_id'];
	$sta_mod = calcStaMod($row);
	$row_pl = ($row[char_prizelimit] > 0 ) ? $row[char_prizelimit]-1 : 0;
    $personal = '
	<tr>
		<td class="row1" width="33%"><span class="genmed"><a href="'.append_sid("rpg_portal.php?s=inventory").'">'.$lang['SHOP_YOUR_INVENTORY'].'</a></span></td>
		<td class="row1" align="center" width="33%"><span class="genmed">HP '.$row[char_hp].'/'.$hpmax.' | MP '.$row[char_sp].'/'.$spmax.' | Exp '.$row[char_bexp].' | Job Exp '.$row[char_jexp].' | Pl '.$row_pl.'</span></td>
		<td class="row1" align="right" width="33%"><span class="genmed">'.$row[char_points].' Moku</span></td>
	</tr>';
    if (strlen($rows['user_specmsg']) > 2) { 
    	$personal .= '
    <tr>
    	<td class="row1" colspan="3"><span class="genmed" style="color:red">'.$row[user_specmsg].'</span><br><span class="gensmall">[<a href="'.append_sid("shop_msgclear.php").'">'.$lang['SHOP_CLEAR_MESSAGES'].'</a>]</span></td>
 	</tr>
 	</td>
 	</tr>';
 	}

 	return $personal;
}

function getPlayerEffectsVar(){
        global $debug,$char,$db_effects,$p_effects;
        $e_names = $db_effects;

        for($i = 0; $i < sizeof($e_names); $i++){
			$debug[] = "<br>p_effects[".$e_names[$i]."]: ".$p_effects[$e_names[$i]];
                if( !$p_effects[$e_names[$i]] )   		// wenn der wert noch nicht vorhanden war
                        $var[$i] = $e_names[$i].",0";
                else
                        $var[$i] = $e_names[$i].','.$p_effects[$e_names[$i]];
        }
        $return = implode(';',$var);
        return $return;
}

function getPlayerEffects(){
        global $char,$db_effects;
        $s_names = $db_effects;
        $status = explode(";",$char['char_stimer']);
        for($i = 0; $i < sizeof($status); $i++){
                $s_temp = explode(",",$status[$i]);
                $st[$s_temp[0]] = $s_temp[1];
        }
        for($i = 0; $i < sizeof($s_names); $i++){
                if( !$st[$s_names[$i]] )                        // wenn der wert noch nicht vorhanden war
                        $new_status[$s_names[$i]] = "0";
                else
                        $new_status[$s_names[$i]] = $st[$s_names[$i]];
        }
        return $new_status;
}

// gibt ein Array zurück. alla array('Blind'=>0,etc.)
function getPlayerStatus(){
        global $char,$db_status,$debug;
        $debug[] = 'getPlayerStatus = '.$char['char_status'];
        $s_names = $db_status;
        $status = explode(";",$char['char_status']);
        for($i = 0; $i < sizeof($status); $i++){
                $s_temp = explode(",",$status[$i]);
                $st[$s_temp[0]] = $s_temp[1];
                $debug[] = '->'.$s_temp[0].' -> '.$s_temp[1];
        }
        $debug[] = '-';
        for($i = 0; $i < sizeof($s_names); $i++){
                if( !$st[$s_names[$i]] )                        // wenn der wert noch nicht vorhanden war
                        $new_status[$s_names[$i]] = "0";
                else
                        $new_status[$s_names[$i]] = $st[$s_names[$i]];
                $debug[] = '->'.$s_names[$i].' -> '.$new_status[$s_names[$i]];
        }
        return $new_status;
}

function getSkillInfo($id,$level){

        global $db_skill_max,$db_mob_info;

        $name_weap['ger'] = array('none' => 'Alle', 'dagger' => 'Dolch', 'sword' => 'Schwert', '2hsword' => '2H Schwert','axe' => 'Axt', '2haxe' => '2H Axt', 'spear' => 'Speer','2hspear' => '2H Speer', 'mace' => 'Streitkolben', '2hmace' => '2H Streitkolben', 'rod' => 'Stab', 'bow' => 'Bogen', 'knuckle' => 'Faustwaffen', 'instrument' => 'Instrument', 'whip' => 'Peitsche', 'book' => 'Buch', 'katar' => 'Katar');
        $s[$id] = $level;
        $str = "$id_german";
        $max = $db_skill_max[$id];
        $s_effect = getSkillBonus($s,'info');
        $text = ($level > 0) ? "Level: $level/$max, ".$output : '-';
        return $text;
}

// Gibt zurück wieviel StatPoints eine Erhöhung kostet. wenn das joblevel < 0 ist dann nur 1.
function getStatIncValue($statLevel){
        // um einen stat zu erhöhen kostet es eine bestimmte anzahl von statpoints.
        // zb str von 2 auf 3 zu erhöhen kostet 1
        // str 11->12 kostet 2, str 21->22 kostet 3...
        $step = floor(($statLevel - 1) / 10) + 2;
        return $step;
}

// gibt zurück welche upgrades möglich sind
function getUpperClasses($pClass){
        return '';
}
//
function getUserSkills(){
        global $debug,$char,$db_skills,$info;
        $s_names = $db_skills[$char['char_job']];
        //$debug[] = "<br>av_skills:".implode(',',$s_names).",<br>my_skills".$user['user_skills'];
        //$info .= $debug;
        $u_skills = explode(";",$char['char_skills']);
        for($i = 0; $i < sizeof($u_skills); $i++){
                $s_temp = explode(",",$u_skills[$i]);
                $sk[$s_temp[0]] = $s_temp[1];
        }
        for($i = 0; $i < sizeof($s_names); $i++){
                if( !$sk[$s_names[$i]] ){                        // wenn der wert noch nicht vorhanden war
                        $skills[$s_names[$i]] = "0";
                }
                else{
                        $skills[$s_names[$i]] = $sk[$s_names[$i]];
                }
        }
        return $skills;
}

// *****
// Output Functions
// *****



function getCharItems($char_id){
	global $db,$table_prefix;
	$sql = "select * from {$table_prefix}useritems where user=$char_id ORDER BY item_id";
	if (!($result = $db->sql_query($sql))) { message_die(GENERAL_ERROR, "Fatal Error: Could not retrieve Items of Character $char_id.",  "Error in getCharItems($char_id)",__LINE__, __FILE__, $sql); }
	while($row = $db->sql_fetchrow($result)){
		$s1 = $row['item_id'];
		$s2 = $row['item_number'];
		$items[$s1] = $s2;
	}
	return $items;

}

function getCharUseritems($char_id){
	global $db,$table_prefix;
	$sql = "select * from {$table_prefix}useritems where user=$char_id ORDER BY item_id";
	if (!($result = $db->sql_query($sql))) { message_die(GENERAL_ERROR, "Fatal Error: Could not retrieve Items of Character $char_id.",  "Error in getCharItems($char_id)",__LINE__, __FILE__, $sql); }
	while($row = $db->sql_fetchrow($result)){
		$s1 = $row['useritem_id'];
		$s2 = $row['item_number'];
		$items[$s1] = $s2;
	}
	return $items;

}

// list of all charas
function getListOfChars($without = 0){
	global $db,$table_prefix;
	$where = ($without > 0) ? " where save_id <> $without ": '';
	$sql = "select * from {$table_prefix}charas $where ORDER BY char_name";
	if ( !($result = $db->sql_query($sql)) ) { message_die(GENERAL_MESSAGE, "Keine Charas zu finden...<br>SQL: $sql"); }
	$us_item = array();
	$counter = 0;
	if ( $row = $db->sql_fetchrow($result) ){
        do{
			if($row['save_id'] > 0){
            	$chara_list .= "\n<option value=\"{$row['save_id']}\">{$row['char_name']}</option>";
                $counter++;
           	}
        }while ( $row = $db->sql_fetchrow($result) );
	}
	else{
        $chara_list = "<option value=\"0\">Niemand da..</option>";
	}
	return $chara_list;
}

function getImageFileOld($class,$hair,$gender,$use){
        global $rpg_conf;
        switch($use){
                case "char":        $act = "sit"; break;
                case "die":                $act = "die"; break;
                case "equip":        $act = "stand"; break;
                case "gethit":        $act = "gethit"; break;
                case "hit":                $act = "hit"; break;
                case "list":        $act = "stand"; break;
                case "profile":        $act = "sitS"; break;
                default:                $act = "stand"; break;
        }
        	$file1_ex = 'rpg/classes/body/'.$gender.'_'.$act.'_'.$class.'.gif';
            $file2_ex = 'rpg/classes/hair/h'.$gender.'_'.$class.'_'.$act.'_'.$hair.'.gif';
            $file_no1 = 'rpg/classes/full_'.$gender.'_'.$class.'_'.$act.'_00.gif';
            $file_no2 = 'rpg/classes/full_'.$gender.'_'.$class.'_stand_00.gif';
            $file_no3 = 'rpg/classes/full_'.$gender.'_nov_'.$act.'_00.gif';
            $file_no4 = 'rpg/classes/full_'.$gender.'_nov_stand_00.gif';
            if(file_exists($file1_ex) && file_exists($file2_ex)){
                        $image = '
                        <table width="80" border="0">
                        <tr>
                                <td width="80" height="105" valign="top">
                                        <div id="body" style="position:absolute; z-index:1; ">
                                                 <img src="'.$file1_ex.'" border="0">
                                        </div>
                                        <div id="hair" style="position:absolute; z-index:2;">
                                                <img src="'.$file2_ex.'" border="0">
                                        </div>
                                </td>
                        </tr>
                        </table>';
         	}
            elseif(file_exists($file_no1)){
                        $image = '<img src="'.$file_no1.'">';
            }
            elseif(file_exists($file_no2)){
                        $image = '<img src="'.$file_no2.'">';
            }
                elseif(file_exists($file_no3)){
                        $image = '<img src="'.$file_no3.'">';
                }
                else{
                        $image = '<img src="'.$file_no4.'">';
                }
        return $image;
}

// BattleTemp
function getBattleTemp(){
        global $user,$char,$debug;
        $user_temp_battle = $char['char_temp_battle'];
        //$debug[] = "<br>utemp: $user_temp_battle";
				
        if(empty($user_temp_battle) || strlen($user_temp_battle) < 6 || $user_temp_battle == 'nope'){
    			            $usertemp['state'] = 'start_new_fight';
        }
        else{
                $usertemp['state'] = 'continue_old_fight';
                $user_temp = explode(';',$user_temp_battle);
                $usertemp['round'] = $user_temp[0];
                $usertemp['user_hp'] = $user_temp[1];
                $usertemp['user_sp'] = $user_temp[2];
                $usertemp['helm'] = $user_temp[3];
                $usertemp['face'] = $user_temp[4];
                $usertemp['garment'] = $user_temp[5];
                $usertemp['armour'] = $user_temp[6];
                $usertemp['shoes'] = $user_temp[7];
                $usertemp['r_hand'] = $user_temp[8];
                $usertemp['l_hand'] = $user_temp[9];
                $usertemp['acc1'] = $user_temp[10];
                $usertemp['acc2'] = $user_temp[11];
                $usertemp['face2'] = $user_temp[12];
				$usertemp['mid'] = $user_temp[13];
                $usertemp['mhp'] = $user_temp[14];
                $usertemp['msp'] = $user_temp[15];
                $usertemp['bground'] = $user_temp[16];
                $usertemp['m_status'] = $user_temp[17];
                $usertemp['steal'] = $user_temp[18];
                $usertemp['pTime'] = $user_temp[19];
                $usertemp['mTime'] = $user_temp[20];
                $usertemp['cost_pl'] = $user_temp[21];
        }
        return $usertemp;
}

function getJobName($class = 'nov', $language = 'ger'){
	require("includes/db_job.php");
	
	if($language == 'german')	$language = 'ger';
	if($language == 'english')	$language = 'eng';
	
	$jname = $db_job_name[$language][$class];

	return $jname;
}

function getJobC($class){
    switch($class){
                case 'aco':
                case 'arc':
                case 'mag':
                case 'mer':
                case 'swd':
                case 'thf':
                        $job =        1;
                case 'ass':
                case 'cru':
                case 'hun':
                case 'kni':
                case 'mnk':
                case 'pri':
                case 'rog':
                case 'smi':
                case 'wiz':
                        $job = 2;
                default:
                        $job = 1;
        }
        return $job;
}

function getEquippedItemId($equip_id){
	global $db,$table_prefix,$db_item_data;

	$sql = "SELECT * FROM {$table_prefix}useritems WHERE user = useritem_id = '$weapon_id'";
	if (!($result = $db->sql_query($sql))) { 
		message_die(GENERAL_ERROR, "Fatal Error: Could not retrieve  Weapon $weapon_id.", "Fatal Error in getWeaponType", __LINE__, __FILE__, $sql2);  
	}
	$weapon = $db->sql_fetchrow($result2);
	return $weapon['item_id'];
}

function getWeaponLevel($weapon_id){
	global $db,$table_prefix,$db_item_data;
	if($weapon_id){
		$sql2 = "SELECT id,level FROM {$table_prefix}item_db2 WHERE id = $weapon_id";
		if (!($result2 = $db->sql_query($sql2))) { message_die(GENERAL_ERROR, "Fatal Error: Could not retrieve  Weapon $weapon_id.", "Fatal Error in getWeaponType", __LINE__, __FILE__, $sql2);  }
			$weapon = $db->sql_fetchrow($result2);
		}
		if($weapon['level'] <= 0){
			$level = $db_item_data[$weapon['id']][9];
	}
	else{
		$level = $weapon['level'];
	}
	return $level;
}

function getWeaponType($weapon_id){
        global $db,$table_prefix;
        if($weapon_id){
                $sql2 = "SELECT id,type,equip_type FROM {$table_prefix}item_db2 WHERE id = $weapon_id";
                if (!($result2 = $db->sql_query($sql2))) { message_die(GENERAL_ERROR, "Fatal Error: Could not retrieve  Weapon $weapon_id.", "Fatal Error in getWeaponType", __LINE__, __FILE__, $sql2);  }
                $weapon = $db->sql_fetchrow($result2);
        }
        switch($weapon['equip_type']){
                case "none": return 0;
                case "dagger": return 1;
                case "sword": return 2;
                case "2hsword": return 3;
                case "axe": return 4;
                case "2haxe": return 5;
                case "spear": return 6;
                case "2hspear": return 7;
                case "mace": return 8;
                case "2hmace": return 9;
                case "rod": return 10;
                case "bow": return 11;
                case "knuckle": return 12;
                case "instrument": return 13;
                case "whip": return 14;
                case "book": return 15;
                case "katar": return 16;
        }
		if($weapon['type'] == 10){
			return 17;		// Arrow
		}
        return 0;
}
function hex2int($hex) {
        return array( 'r' => hexdec(substr($hex, 0, 2)), // 1st pair of digits
                      'g' => hexdec(substr($hex, 2, 2)), // 2nd pair
                      'b' => hexdec(substr($hex, 4, 2))  // 3rd pair
                    );
}

function inRange($mob_level,$level){
        $rdm = rand(1,100);
        if($mob_level == $level)        return true;
        elseif($mob_level < $level){
                if(($level - $mob_level)<=5)        return true;
                else                                return false;
        }
        elseif($mob_level > $level){
                if(($mob_level - $level)<=5)        return true;
                else                                return false;
        }
        // sollte nie eintreten aber sicher ist sicher...
        return false;
}

// Interprete the Script in the item DB
function interpreteScript($script,$ebonus){
        global $debug;
        $debug[] = "interpreteEquipScript:";
        $debug[] = "->Script:".$script;
        $script = str_replace("; ","#",$script);
        $script = str_replace(";","#",$script);
        $debug[] = "->Script:".$script;
        $scripts1 = explode("#",$script);
        $lg = sizeof($scripts1);
        for($i=0; $i<$lg; $i++)
        {
                $debug[] = "-->Teilscript: ".$scripts1[$i];
                //$scripts1[$i] = substr_replace(" ","",0,1);	// entferne space am anfang falls vorhanden
                $debug[] = "-->".$scripts1[$i];
                $script2 = explode(" ",$scripts1[$i]);
                $debug[] = "-->".$script2[0];
                if($script2[0] == 'bonus'){
		    $script3 = explode(',',$script2[1]);
		    $debug[] = "->".$script3[0];
                    switch($script3[0]){
                        case "bStr":        $ebonus['str'] += $script2[1];break;
                        case "bVit":        $ebonus['con'] += $script2[1];break;
                        case "bInt":        $ebonus['int'] += $script2[1];break;
                        case "bDex":        $ebonus['dex'] += $script2[1];break;
                        case "bAgi":        $ebonus['agi'] += $script2[1];break;
                        case "bLuk":        $ebonus['luk'] += $script2[1];break;
                        case "bMdef":        $ebonus['mdef'] += $script2[1];break;
                        case "bDef":        $ebonus['def'] += $script2[1];break;
                        case "bSpeedRate":        $ebonus['speed'] += $script2[1];break;
                        case "bMatkRate":        $ebonus['cast'] += $script2[1];break;
                        case "bMaxHPrate":        $ebonus['maxhp'] += $script2[1];break;
                        case "bMaxSPrate":        $ebonus['maxsp'] += $script2[1];break;
                        case "bAspd":        $ebonus['aspd'] += $script2[1];break;
                        case "bAspdAddRate":        $ebonus['aspd'] += $script2[1];break;
                        case "bAtkEle":        $ebonus['atk_element'] = $script2[1];break;
                        case "bFlee":        $ebonus['flee'] += $script2[1];break;
                        case "bFlee2":        $ebonus['flee'] += $script2[1];break;
                        case "bLongAtkDef":        $ebonus['unknown'] += $script2[1];break;
                        case "bIgnoreDefRace":        $ebonus['ignore_def_race'] = $script2[1];break;
                        case "bGetZenyNum":        $ebonus['gold'] += $script2[1];break;
                        case "bDefRate":        $ebonus['unknown'] += $script2[1];break;
                        case "bDef2Rate":        $ebonus['unknown'] += $script2[1];break;
                        case "bDoubleRate":        $ebonus['unknown'] += $script2[1];break;
                        case "bDefRatioAtkRace":        $ebonus['unknown'] += $script2[1];break;
                        case "bCritical":        $ebonus['critical'] += $script2[1];break;
                	}
				}
				elseif($script2[0] == 'bonus2'){
					$script3 = explode(',',$script2[1]);
					switch($script3[0]){
						case 'bAddEff':
							$ebonus['addeff-'.$script3[1]] += $script3[2]; break;
						case 'bAddEle':
							$ebonus['addele-'.$script3[1]] += $script3[2]; break;
						case 'bAddRace':
							$ebonus['addrace-'.$script3[1]] += $script3[2]; break;
						case 'bSubRace':
							$ebonus['subrace-'.$script3[1]] += $script3[2]; break;
						case 'bResEff':
							$ebonus['res-'.$script3[1]] += $script3[2]; break;
						case 'bWeaponComaRace':
							$ebonus['suddenkill-'.$script3[1]] += $script3[2]; break;
					}
				}
           }
        //echo "<br>str ".$bonus['str'];
        //echo "<br>int ".$bonus['int'];
        return $ebonus;
}
function makeItemList($statlevel,$type,$value,$class,$itemlist,$current){
        global $table_prefix;
		global $face_items;
		global $css_items,$css_items_array;
        global $info,$inf_debug,$debug,$equip,$db_job_name,$db,$userdata,$char;
        $css_itemsup_array = array();
        $class2 = $db_job_name["eng"][$class];
        if($value == 'WEAPONS'){
                $typecheck = "($type = '$value' OR type = 4 OR type = 5)";
        }
        elseif($value == 'HEAL'){
                $typecheck = "($type = '$value')";
        }
        elseif($value == 'ATTACK'){
                $typecheck = "($type = '$value')";
        }
	elseif($type == 'SECOND'){
		        $typecheck = "(equip_type = '$value')";
				$checki = true;
        }
        elseif($value == 'WARP'){
                $typecheck = "($type = '$value')";
        }
        else{
                $typecheck = "$type = '$value'";
        }
        if($class == 'gm1' || $class == 'gm0'){

                $sql2 = "SELECT name,name_ger,id FROM {$table_prefix}item_db2
                WHERE equip_level <= $statlevel
                AND $typecheck";
        }

	else{
		$sql2 = "SELECT name,name_ger,id FROM {$table_prefix}item_db2
                WHERE equip_level <= $statlevel
                AND $typecheck
                AND (class LIKE '%".$class."%' OR class LIKE '%".$class2."%' OR class LIKE '%ALL%' OR class LIKE '%all%')";
	}
        if (!($result2 = $db->sql_query($sql2))) { return ""; }
        $sqlcount = $db->sql_numrows($result2);
        $shopitems = array();

        for ($y = 0; $y < $sqlcount; $y++)
        {
                $row2 = $db->sql_fetchrow($result2);
                $id = $row2['id'];
                $shopitems[] = $id;
        }
        $sql2 = ($type == 'SECOND')
			? "SELECT * FROM {$table_prefix}useritems WHERE user = {$char['save_id']} AND ((item_number > 0 AND item_id <> {$itemlist})
            OR (item_number > 1)) ORDER BY item_id"
			: "SELECT * FROM {$table_prefix}useritems WHERE user = {$char['save_id']} AND item_number > 0 ORDER BY item_id";


        if (!($result2 = $db->sql_query($sql2))) { return ""; }
        $sqlcount = $db->sql_numrows($result2);
		$ui_id = array();
        $ui_name = array();
        $ui_amount = array();

        for ($y = 0; $y < $sqlcount; $y++)
        {
	    $row2 = $db->sql_fetchrow($result2);
	    $ui_id[$y] = $row2['item_id'];
	    $ui_rowid[$y] = $row2['useritem_id'];
	    $ui_up[$y] = $row2['item_upgrade'];
            $ui_name[$y] = $row2['item_name'];
            $ui_amount[$y] = $row2['item_number'];
        }
        $itemscount = count($ui_rowid);
        $equip_array=explode(';',$char['char_equip']);
        for ($x = 0; $x < $itemscount; $x++)
        {
                //$battle_items.= "\n----user:-----\nid: ".$useritemid[$x].", name: ".$useritems[$x];
                //show only one item
                $itemname = getItemName($ui_id[$x],'ger');
                if (in_array($ui_id[$x],$shopitems)){
                     $acc_check=true;
                     if($value == 'acc'){
                       if(($ui_rowid[$x] == $equip_array[7] || $ui_rowid[$x] == $equip_array[8]) && $equip_array[7] == $equip_array[8])
					  	$ui_amount[$x]--;
                       if(in_array($ui_rowid[$x],$equip_array))
					  	$ui_amount[$x]--;
                       $acc_check = ($ui_amount[$x] > 0 || ($current == $ui_rowid[$x] && $ui_amount[$x] == 0))?true:false;
                     }
                     //$op_value = ($value == 'WEAPONS' || $value == 'armor') ? $ui_rowid[$x] : $ui_id[$x];
                     $op_value = ($value=="HEAL" || $value=="ATTACK" || $value=="WARP") ? $ui_id[$x] : $ui_rowid[$x];

                     if($acc_check)$battle_items .= "\n".'<option class="genmed" value="'.$op_value.'"';

		     if($value == 'face1' || $value == 'face2' || $value == 'face3' || $value == 'face12' || $value == 'face13' || $value == 'face23' || $value == 'face123'){
		       if($face_items == '-1')
		            $face_items = $ui_rowid[$x].": '$value'";
		       else
			     $face_items .= ", ".$ui_rowid[$x].": '$value'";
		     }
		     if(/*!in_array($ui_id[$x],$css_items_array) && */!in_array($ui_rowid[$x].'+'.$ui_up[$x],$css_itemsup_array)){
                      //->Für Weapon und armor ändern in $ui_rowid
                                
                       if(file_exists("rpg/items/".$ui_id[$x].".gif")){
			     $css_items .= "\n option[value=\"".$op_value."\"]:before { content:url(\"rpg/items/".$ui_id[$x].".gif\"); vertical-align:middle; }";
		       }
		       else{
			     $css_items .= "\n option[value=".$op_value."]:before { content:url(\"rpg/items/noimage_item.gif\"); }";
		       }

		       $css_items_array[] = $ui_rowid[$x];
		       $css_itemsup_array[] = $ui_rowid[$x].'+'.$ui_up[$x];
	               $upgradeinfo = ($ui_up[$x]>0) ? ' +'.$ui_up[$x] :'';
		       $debug[] = $itemname.'+'.$ui_up[$x].' : '.$op_value;
		     }
		    
		     //->Für Weapon und armor ändern in $ui_rowid
		     //$debug[] = $value.' , '.$current.' == '.$ui_id[$x].' OR '.$current.' == '.$ui_rowid[$x];
		     if($op_value == $current){
		       $sel = ' selected';
		     }
		     else{
		       $sel = '';
		     }
		     if($value=="HEAL" || $value=="ATTACK" || $value=="WARP"){
		       $battle_items .= $sel.'>'.$itemname.' ('.$ui_amount[$x].')</option>';
		     }
		     else{
		       if($acc_check)$battle_items .= $sel.'>'.$itemname.''.$upgradeinfo.'</option>';
		     }
		     ${$useritems[$x]} = "set";
		}
        }
        if($value == 'WEAPONS' && sizeof($css_itemsup_array)>0){
//          foreach($css_itemsup_array AS $r){$debug[] = $r;}
        }

        return $battle_items;
}

function make_quest_text($msg){
	$text = implode('<br>',$msg);

	$text = str_replace('','&szlig;',$text);
	$text = str_replace('ß','&szlig;',$text);

	$text = str_replace('ß','&szlig;',$text);
	$text = str_replace('ä','&auml;',$text);
	$text = str_replace('Ä','&Auml;',$text);
	$text = str_replace('ö','&ouml;',$text);
	$text = str_replace('Ö','&Ouml;',$text);
	$text = str_replace('ü','&uuml;',$text);
	$text = str_replace('Ü','&Uuml;',$text);
	
	return $text;
}

function multiPlus($col_1,$col_2){
	$r['red'] = round($col_1['red'] * $col_2['red'] / 255 * 2);
	$r['green'] = round($col_1['green'] * $col_2['green'] / 255 * 2);
	$r['blue'] = round($col_1['blue'] * $col_2['blue'] / 255 * 2);
	return $r;
}

function multiMinus($col_1,$col_2){
	$r['red'] = round(255 - ((255 - $col_1['red']) * (255 - $col_2['red']) / 255 * 2) );
	$r['green'] = round(255 - ((255 - $col_1['green']) * (255-$col_2['green']) / 255 * 2) );
	$r['blue'] = round(255 - ((255 - $col_1['blue']) * (255-$col_2['blue']) / 255 * 2) );
	return $r;
}

function resetPlayerEffects(){
        global $debug,$db_effects,$p_effects;
        $e_names = $db_effects;

        for($i = 0; $i < sizeof($e_names); $i++){
                $p_effects[$e_names[$i]] = 0;
        }
        return $p_effects;
}
function setBattleTemp(){
        global $debug,$usertemp,$round,$hp,$sp,$equip,$m_id,$m_hp,$m_sp,$battleground,$pTime,$mTime;
        $newtemp = '';
        //$debug[] = "<br>utemp: $user_temp_battle";
        $newtemp =	$round.';'.                                             // 0
                    $hp.';'.                                                // 1
                    $sp.';'.                                              	// 2
                    $equip[0].';'.                                        	// 3 helm
                    $equip[1].';'.                                        	// 4 face
                    $equip[2].';'.                                        	// 5 garment
                    $equip[3].';'.                                        	// 6 armour
                    $equip[4].';'.                                        	// 7 shoes
                    $equip[5].';'.                                        	// 8 r_hand
                    $equip[6].';'.                 	                      	// 9 l_hand
                    $equip[7].';'.                                        	// 10 acc1
                    $equip[8].';'.                                        	// 11 acc2
                    $equip[9].';'.                                        	// 12 face2
                    $m_id.';'.                                              // 13
                    $m_hp.';'.                                              // 14
                    $m_sp.';'.                                              // 15
                    $battleground.';'.                                		// 16
                    $usertemp['m_status'].';'.                				// 17
                    $usertemp['steal'].';'.                                	// 18
                    $pTime.';'.                                				// 19
                    $mTime.';'.																				// 20
										$usertemp['cost_pl'];                             // 21   					

        return $newtemp;
}

function setCharQuestVars($save_id,$vars){
	global $db,$table_prefix;
	foreach($vars as $x => $y){
		$a[] = $x.','.$y;
		//echo "<br>$x,$y";
	}
	$b = implode(';',$a);

	$sql = "UPDATE {$table_prefix}charas SET char_questvars='$b' WHERE save_id=$save_id";
	if (!($result = $db->sql_query($sql))){
		message_die(GENERAL_ERROR, "Fatal Error: Could not set QuestVars.", "setCharQuestVars($save_id,$vars)", __LINE__, __FILE__, $sql);
	}
	//echo "<br>sql: <br>$sql";
	
}


// *****
//  Item Functions
// *****



function statUp($stat,$char){
	$string = "char_$stat";
    $increase = getStatIncValue($char[$string]);
    $stp = calcSTP($char);

    if($stp >= $increase){
    	if($char[$string] < 99){
        	return " | $increase | <input name=\"inc_$stat\" type=\"submit\" id=\"inc_$stat\" value=\"+\" class=\"liteoption\">";
        }
    }
    return "";
}

// changes the status of a character
function statusChange($name,$value){
        global $char,$save_id,$db,$debug,$status,$table_prefix;
        $str = str_replace("SC_","",$name);
        //$status = explode(";",$user['user_status']);
        $status[$str] = $value;
        $debug[] = 'Status setzen:<br>->$status['.$str.'] = '.$value.'<br>Suchlauf:';
        $s_names = array("Blind","Chaos","Confusion","Curse","Frozen","Poison","Silence","Sleep","StoneCurse","Stun","SpeedPot0","SpeedPot1","SpeedPot2",'Angelus','Blessing','ImproveConc');
        for($i = 0; $i < sizeof($s_names); $i++){
                if( !$status[$s_names[$i]] ){                        // wenn der wert noch nicht vorhanden war
                        $s_value[$s_names[$i]] = "0";
                        $debug[] = '-->if( !'.$status[$s_names[$i]].' )$s_value['.$s_names[$i].'] = "0";';}
                else{
                        $s_value[$s_names[$i]] = $status[$s_names[$i]];
                        $debug[] = '-->else $s_value['.$s_names[$i].'] = '.$status[$s_names[$i]].';';}
        }

        if($str != "SpeedPot0" && $str != "SpeedPot1" && $str != "SpeedPot2"){
                $s_value[$str] = $value;
        }
        else{
                $status[$str] += $value;
        }

        for($i = 0; $i < sizeof($s_names); $i++){
                $new_status .= $s_names[$i].",".$s_value[$s_names[$i]].";";
        }
        $debug[] = "->new status sql: $new_status";
        $sql = "UPDATE {$table_prefix}charas SET char_status='$new_status' WHERE save_id = $save_id";
        if (!($result = $db->sql_query($sql))) {
 message_die(GENERAL_ERROR, "Fatal Error while trying to change status of user $save_id.",  "Error in statChange",__LINE__, __FILE__, $sql);
        }
}

// zur benutzung von items während eines kampfes!! und NUR da!
function useItem($item_id){
        global $table_prefix,$userdata,$char;
        global $userid,$char,$hp,$sp,$hp_max,$sp_max,$useritems,$db,$info;

        $sql2 = "SELECT * FROM {$table_prefix}item_db2 WHERE id = $item_id";
        if (!($result2 = $db->sql_query($sql2))) { message_die(GENERAL_ERROR, "Fatal Error: Could not retrieve Item $item_id.", "Error in useItem",__LINE__, __FILE__, $sql2);  }
        $item = $db->sql_fetchrow($result2);

        $discard = true;
        $damage = 0;
        $sql = "SELECT * FROM {$table_prefix}useritems WHERE user={$char['save_id']} AND item_id=$item_id";
		if ( !($result = $db->sql_query($sql)) ) { message_die(GENERAL_MESSAGE, $lang['SHOP_FATAL_ERROR'].'[x]<br>sql:<br>'.$sql); }
		$ui_row = mysql_fetch_array($result);
		if ($ui_row['item_number'] < 1)
                {message_die(GENERAL_MESSAGE, "Du hast kein ".$item['name']."!");        }
        if ($sp < $item['mp_cost'])
                {message_die(GENERAL_MESSAGE,"Du hast nicht genug SP um diesen Gegenstand einzusetzen!");}

        $r = array();
        $item_script = explode(";",$item['script_use']);
 if(sizeof($item_script)<=0){
        $info[] = "<br>Das Item ".$item['name']." kann nicht benutzt werden.";
        $discard = false;
 }
 elseif(substr_count($item_script[$i],'magic') >= 1){
        $info[] = " spricht ".$item['name']." aus ";
        // magic min,max,elementnummer
        $rest = str_replace('magic ','',$item_script[$i]);
        $attribute = explode(',',$rest);
        $r['type'] = 'magic';
        $r['damage'] = mt_rand($attribute[0],$attribute[1]);
        $r['dam_ele'] = ($attribute[2]);
        $sp -= $item['mp_cost'];
        $discard = false;
        return $r;
 }
 else{   $itemnameger = getItemName($item['id'],'ger');
         $discard = true;
        $info[] = "Du setzt ".$itemnameger." ein";
        for($i = 0; $i < sizeof($item_script); $i++){

                if(substr_count($item_script[$i],"sc_start") >= 1){
                        $rest = str_replace("sc_start ","",$item_script[$i]);
                        $attribute = explode(",",$rest);
                        if($attribute[0] == "SC_SpeedPot0" || $attribute[0] == "SC_SpeedPot1" || $attribute[0] == "SC_SpeedPot2"){
                                $rounds = $attribute[1]/60;                // 60s entsprechen einer Kampfrunde
                                statusChange($attribute[0],$rounds);
                        }
                        else{
                                statusChange($attribute[0],1);
                        }
                }
                if(substr_count($item_script[$i],"sc_end") >= 1){
                        $rest = str_replace("sc_end ","",$item_script[$i]);
                        statusChange($rest,0);
                }
                if(substr_count($item_script[$i],"itemheal") >= 1){
                        // hat eine der folgenden formen:
                        // x,y
                        // rand(x1,x2),y
                        // rand x,rand(y1,y2)
                        // rand(x1,x2),rand(y1,y2)
                        $rest = str_replace("itemheal ","",$item_script[$i]);
                        $attr = explode(",",$rest);
                        // x,y
                        if(sizeof($attr)== 2){
                                $add_hp += $attr[0];
                                $add_sp += $attr[1];
                        }
                        // rand(x1,x2),y oder rand x,rand(y1,y2)
                        if(sizeof($attr)== 3){
                                // rand(x1,x2),y
                                if(substr_count($attr[0],"rand(") > 0){
                                        $attr[0] = str_replace("rand(","",$attr[0]);
                                        $attr[1] = str_replace(")","",$attr[1]);
                                        mt_srand((double)microtime()*1000000);        // New Seed
                                        $add_hp += mt_rand($attr[0],$attr[1]);
                                        $add_sp += $attr[2];
                                }
                                // rand x,rand(y1,y2)
                                elseif(substr_count($attr[1],"rand(") > 0){
                                        $attr[1] = str_replace("rand(","",$attr[1]);
                                        $attr[2] = str_replace(")","",$attr[2]);
                                        mt_srand((double)microtime()*1000000);        // New Seed
                                        $add_hp += $attr[0];
                                        $add_sp += mt_rand($attr[1],$attr[2]);
                                }
                                else {
                                        message_die(GENERAL_ERROR, "Strange Error ?__? ", "?_? h&auml;?",__LINE__, __FILE__, "itemheal");
                                }
                        }
                        // rand(x1,x2),rand(y1,y2)
                        if(sizeof($attr)==4){
                                $attr[0] = str_replace("rand(","",$attr[0]);
                                $attr[1] = str_replace(")","",$attr[1]);
                                mt_srand((double)microtime()*1000000);        // New Seed
                                $add_hp += mt_rand($attr[0],$attr[1]);

                                $attr[2] = str_replace("rand(","",$attr[2]);
                                $attr[3] = str_replace(")","",$attr[3]);
                                mt_srand((double)microtime()*1000000);        // New Seed
                                $add_sp += mt_rand($attr[2],$attr[3]);
                        }
                }
                if(substr_count($item_script[$i],"percentheal") >= 1){
                        $rest = str_replace("percentheal ","",$item_script[$i]);
                        $attr = explode(",",$rest);
                        $add_hp += $hp_max/100*$attr[0];
                        $add_sp += $sp_max/100*$attr[1];
                }
                if(substr_count($item_script[$i],"warp") >= 1){
                    //$item_scripts = explode(' ',$item_scripts);
                    if(substr_count($item_script[$i],'"City"') >= 1){
                      $savepoint = explode('#',$char['char_savepoint']);
                      if($savepoint[0] == ''){$savepoint[0]= 'dalaran';$savepoint[1]= 7;$savepoint[2] = 18;}
                      switch($savepoint[0]){
                        case 'dal_kap03': $place = 'nach Dalarans Kirche';break;
                        case 'dalaran': $place = 'nach Dalaran';break;
                        case 'dal_secretroom': $place = 'in den Sphärenriss';break;
                        default: $place = 'an einen sicheren Ort';
                      }
                      //$info[] .= 'und sie tragen dich zurück '.$place.'.';
                      $r['map_id'] = $savepoint[0];
                      $r['map_x'] = $savepoint[1];
                      $r['map_y'] = $savepoint[2];
                      $r['type'] = 'warp';
                      $r['place'] = $place;
                      $r['name'] = $itemnameger;
                      $aaa = 'BENUTZT SCHMETTERLINGSFLÜGEL';
                    }
                    if(substr_count($item_script[$i],'"Map"') >= 1){
                      $aaa = 'BENUTZT SCHWINGEN';

                      $field_typ = getFtData($char['char_map_id']);
                      $aaa .= '<br>'.$char['char_map_id'];
                      $xb = sizeof($field_typ[0]);
                      $yb = 0;
                      $count = -1;        //nicht Null, wegen des momentanen Standortes, das nicht ft=3 sein kann
                      foreach($field_typ AS $q){$yb++;
                        foreach($q AS $p){if($p != 3)$count++;}
                      }
                      $rdm = rand(1,$count);
                      $check=0;
                      $y=-1;
                      $right = true;
                      foreach($field_typ AS $yy){
                        if($right){
                          $y++;
                          $x=-1;
                          foreach($yy AS $xx){
                            if($right){
                              $x++;
                              if($xx != 3 && ($char['char_map_x'] != $x && $char['char_map_y'] != $y))$check++;
                              if($check == $rdm){
                                $aaa .= '<br>'.$check.'<->'.$rdm.'<br>';
                                $r['map_id'] = $char['char_map_id'];
                                $r['map_x'] = $x;
                                $r['map_y'] = $y;
                                $r['type'] = 'warp';
                                $r['place'] = 'auf die Karte';
                                $r['name'] = $itemnameger;
                                $right=false;
                              }
                            }
                          }
                        }
                      }
                    }
                    if($char['user_id'] == 75)$info[] = $aaa.'<br>'.
                                    $r['map_id'].':'.$r['map_x'].'-'.$r['map_y'].'<br>'.
                                    $r['type'].':'.$r['place'].':'.$r['name'];
                    addItem($char['save_id'],$item_id,1,'-');
                    return $r;

                }
                if(substr_count($item_script[$i],"getitem") >= 1){
                        $rest = str_replace("getitem ","",$item_script[$i]);
                        $attr = explode(",",$rest);
                        if($attr[0] == -1){
                                global $db_give_blue;
                                mt_srand((double)microtime()*1000000);        // New Seed
                                $rdm = mt_rand(0,sizeof($db_give_blue));
                                $rdm_item_id = $db_give_blue[$rdm];
                        }
                        elseif($attr[0] == -3){
                                global $db_give_violet;
                                mt_srand((double)microtime()*1000000);        // New Seed
                                $rdm = mt_rand(0,sizeof($db_give_violet));
                                $rdm_item_id = $db_give_violet[$rdm];
                        }
                        elseif($attr[0] == -4){
                                global $db_give_gift;
                                mt_srand((double)microtime()*1000000);        // New Seed
                                $rdm = mt_rand(0,sizeof($db_give_gift));
                                $rdm_item_id = $db_give_gift[$rdm];
                        }
                        else{
                                mt_srand((double)microtime()*1000000);        // New Seed
                                $rdm_item_id = mt_rand(500,1070);
                        }
                        $sql2 = "SELECT id,name FROM {$table_prefix}item_db2 WHERE id = $rdm_item_id";
                        if (!($result2 = $db->sql_query($sql2))) { message_die(GENERAL_ERROR, "Fatal Error: Could not retrieve Random Item $rdm_item_id.",  "Error in useItem",__LINE__, __FILE__, $sql2);  }
                        $add_item = $db->sql_fetchrow($result2);
                }
        }
        $hp += $add_hp;
        $sp += $add_sp;
        $iii = '';
        if($add_hp > 0 || $add_sp > 0){$iii .= " und f&uuml;llst ";}
        if($add_hp > 0){ $iii .= "deine HP um $add_hp ";}
        if($add_hp > 0 && $add_sp > 0){ $iii .= "und ";}
        if($add_sp > 0){ $iii .= "deine SP um $add_sp ";}
        if($add_hp > 0 || $add_sp > 0){ $iii .= "auf.";}
        $info[] = $iii;
//        $info[] = ".";

        // use up item
}
 if($discard){
    $newitems = substr_replace($useritems, "", strpos($useritems, "|".$item['name']."|"), strlen("|".$item['name']."|"));
    $r['user_items'] = $newitems;
    $sql = "UPDATE {$table_prefix}useritems SET item_number=item_number-1 WHERE user={$char['save_id']} AND item_id=$item_id";
	$debug[] = "<br>useitem sql: $sql";
    if (!($result = $db->sql_query($sql))) {
    	message_die(GENERAL_ERROR, "Fatal Error while trying to update your items.", "Fehler!",__LINE__, __FILE__, $sql);
	}
 }
 else{
         $r['user_items'] = $useritems;
 }
 if($add_item['id'] > 0){
 	$sql = "SELECT * FROM {$table_prefix}useritems WHERE user={$char['save_id']} AND item_id=$additem}";
	if ( !($result = $db->sql_query($sql)) ) { message_die(GENERAL_MESSAGE, $lang['SHOP_FATAL_ERROR'].'[y]<br>sql:<br>'.$sql); }
	$add_ui_row = mysql_fetch_array($result);
	if($add_ui_row['item_id'] == $additem)		// wenn das item schon in der liste ist
		$itemsql = "UPDATE {$table_prefix}useritems SET item_number=item_number+1 WHERE user={$char['save_id']} AND item_id=$additem";
	else											// wenn das item noch nicht in der liste ist
		$itemsql = "INSERT INTO {$table_prefix}useritems (user,item_id,item_name,item_number) VALUES ({$char['save_id']},$additem,'',1);";
	if ( !($db->sql_query($itemsql)) )
	{
		message_die(GENERAL_MESSAGE, $lang['SHOP_FATAL_ERROR'].'[1]: '.$lang['SHOP_ERROR_UPDATING_USER']);
	}
 }
 return $r;
}

function getEquipmentBonus2($wstring,$debug,$table_prefix,$db){
  //global $db;
$slang = array();
/*****************************
*  DB-Abfrage der Ausrüstung *
******************************/

$wstring= explode(';',$wstring);




$where = '';
$debug[] = '<br>-----------------------------------------------------<br>Equimpent:<br>';

for($i=0;$i<=9;$i++){
$where = '';
if($wstring[$i]['useritem_id']) $where .= "useritem_id = {$wstring[$i]}";
if($where != ''){
  $sql = "SELECT  useritem_id, user, item_id, item_number FROM {$table_prefix}useritems
          WHERE $where";
  if (!($result = $db->sql_query($sql))) { message_die(GENERAL_MESSAGE, 'Glühend heiß... autsch, aua >.< 11<br>'.$sql.'<br>-'.$equip[3].'<br>-'.$equip[5].'<br>'); }
  while($row = $db->sql_fetchrow($result)){
    if($row['useritem_id']>0){
      $eq = $row['item_id']; //Arrayeinträge überschreiben, SonderID wird hier nicht gebraucht
      //$i = (getWeaponType($equipment)>0) ? 5 : 3;
      $wstring[$i] = $eq;
      $debug[] = '$equip['.$i.'] = '.$wstring[$i];
    }
  }
}
}

$debug[] = '<br>';
$where = '';$warray = array();
for($i=0;$i<=9;$i++){if($wstring[$i]){$warray[] = $wstring[$i];}}
if(sizeof($warray)>0){
for($i=0;$i<sizeof($warray);$i++){

$where .= ' id = '.$warray[$i].' ';
$where .= ($i == sizeof($warray)-1) ? '' : 'OR' ;

}}
elseif(sizeof($warray)==0){$where = 'id = 0';}
$sql = "SELECT script_equip, attack, defence, type FROM {$table_prefix}item_db2
        WHERE ({$where})";
if (!($result = $db->sql_query($sql))) { message_die(GENERAL_MESSAGE, 'Ein Höllenfeuer entfacht deine Kleidung T_T'); }
$item_script = '';
while($row = $db->sql_fetchrow($result)){
  $item_script .= $row['script_equip'];
  $slang['def'] += $row['defence'];
  if($row['type'] == 10){$slang['arrow_att'] += $row['attack'];}
  else{$slang['att'] += $row['attack'];}
}
$debug[] = 'Attacke: '.$slang['att'].', Verteidigung: '.$slang['def'];
if($slang['arrow_att'])$debug[] = 'Pfeilattacke: '.$slang['arrow_att'];

/*****************************
*  Auswertung/Interpretation *
******************************/
$debug[] = '<br>Totales Equimentskript: '.$item_script;
$item_script = explode(';',$item_script);
foreach($item_script AS $script){
  $script = explode(',',$script);
  $script[0] = ltrim(rtrim($script[0]));
  $bcheck = explode(' ',$script[0]);
  if($bcheck[0] == '')$bcheck[0]='leer';
  switch($bcheck[0]){
    case 'bonus':$debug[] = 'Bonustyp-Unterart->Wert'.$bcheck[0].'-'.$bcheck[1].'->'.$script[1];
       switch($bcheck[1]){
          case 'bStr':                          $slang['str']  +=$script[1]; break;
          case 'bVit':                          $slang['con']  +=$script[1]; break;
          case 'bDex':                          $slang['dex']  +=$script[1]; break;
          case 'bInt':                          $slang['int']  +=$script[1]; break;
          case 'bAgi':                          $slang['agi']  +=$script[1]; break;
          case 'bLuk':                          $slang['luk']  +=$script[1]; break;
          case 'bHit':                          $slang['hit']  +=$script[1]; break;
          case 'bFlee':                         $slang['flee'] +=$script[1]; break;
          case 'bFlee2':                        $slang['flee'] +=$script[1]; break;
          case 'bAtk':                          $slang['att']  +=$script[1]; break;
          case 'bMatk':                         $slang['matk'] +=$script[1]; break;
          case 'bDef':                          $slang['def']  +=$script[1]; break;
          case 'bMdef':                         $slang['mdef'] +=$script[1]; break;
          case 'bMaxHP':                        $slang['maxhp']+=$script[1]; break;
          case 'bMaxSP':                        $slang['maxsp']+=$script[1]; break;
          case 'bMatkRate':                     $slang['matkrate']  +=$script[1]; break;                         //Matk + %
          case 'bMaxHPrate':                    $slang['maxhprate'] +=$script[1]; break;                         //HP + %
          case 'bMaxSPrate':                    $slang['maxsprate'] +=$script[1]; break;                         //SP + %
          case 'bAtkEle':                       $slang['atk_element']=$script[1]; break;                         //Angriffselement (eins und nur eins)
          case 'bCastrate':                     $slang['castrate']  +=$script[1]; break;                         //cast + % (negativ)
          case 'bCritical':                     $slang['critical']  +=$script[1]; break;                         //critical + %
          case 'bDoubleRate':                   $slang['doublerate']+=$script[1]; break;             //-->       //Doppelschlag %
          case 'bGetZenyNum':                   $slang['gold']      +=$script[1]; break;                         //Gold nach Kampf
          case 'bLongAtkDef':                   $slang['l_attdef']  +=$script[1]; break;             //-->       //%-Resistenz gegen Fernkampfwaffen
          case 'bAspd':                         $slang['aspd']      +=$script[1]; break;             //-->       //Attack Speed
          case 'bAspdRate':                     $slang['aspdrate']  +=$script[1]; break;             //-->       //Attack Speed + %
          case 'bSpeedRate':                    $slang['speed']     +=$script[1]; break;                         //Speed +
          case 'bNoSizeFix':                    $slang['nosizefix'] +=$script[1]; break;             //-->       //100% gegen alle Grüßen
          case 'bUseSPrate':                    $slang['sp_cost']   +=$script[1]; break;                         //Verbraucht SP pro Runde (negativ)
          case 'bRange':                        $slang['range']     +=$script[1]; break;      //--> //bekommt spezielle Items von bestimmten Monstern
          case 'bAspdAddRate':                  $slang['aspdrate']      +=$script[1]; break;  //--> //Attack Speed + %
          case 'bSplashRange':                  $slang['splash']        +=$script[1]; break;  //--> //Alle Gegner erleiden Schaden
          case 'bSPrecovRate':                  $slang['sp_regen']      +=$script[1]; break;        //bessere SP-Regeneration um %
          case 'bIgnoreDefRace':                $slang['ignore_def_race']=$script[1]; break;  //--> //Ignoriert RassenDEF (eine und nur eine)
          case 'bPerfectHitRate':               $slang['hitrate']       +=$script[1]; break;  //--> //Hit + %
          case 'bMagicDamageReturn':            $slang['m_return']      +=$script[1]; break;  //--> //% Magie zurückzuschlagen
          case 'bRestartFullRecover':           $slang['RFR']           +=$script[1]; break;        //Regeneriert nach dem Tod komplett
          case 'bShortWeaponDamageReturn':      $slang['a_return']      +=$script[1]; break;        //% Waffenschaden zurückzuschlagen
          default:$debug[] = 'FEHLER IN BONUS! prüfe ->'.$script[0].'<-';break;
       }
    break;
    case 'bonus2':$debug[] = 'Bonustyp-Unterart->Wert1, Wert2'.$bcheck[0].'-'.$bcheck[1].'->'.$script[1].', '.$script[2];
       switch($bcheck[1]){
          case 'bAddEle':
						$slang['addele-'.$script[1]]  += $script[2]; break;
          case 'bAddEff': 
						$slang['addeff-'.$script[1]]  += $script[2]; break;
          case 'bAddRace':
						$slang['addrace-'.$script[1]] += $script[2]; break;
          case 'bSubRace':
						$slang['subrace-'.$script[1]] += $script[2]; break;
          case 'bResEff': 
						$slang['res-'.$script[1]]     += $script[2]; break;
          case 'bSubEle':
						$slang['subele-'.$script[1]]  += $script[2]; break;
          case 'bAddSize':
						$slang['addsize-'.$script[1]] += $script[2]; break;
          case 'bWeaponComaRace':
						$slang['suddenkill-'.$script[1]] += $script[2]; break;
          case 'bHPDrainRate':
						$slang['hpdrain-'.$script[1]]    += $script[2]; break;
          case 'bSpDrainRate':         
						$slang['spdrain-'.$script[1]]    += $script[2]; break;
          case 'bAddDamageClass':
						$slang['adddamclass-'.$script[1]]+= $script[2]; break;
          case 'bRandomAttackIncrease':
						$slang['randomatt-'.$script[1]]  += $script[2]; break;
          default:
						$debug[] = 'FEHLER IN BONUS2! prüfe ->'.$script[0].'<-';break;
       }
    break;



    case 'bonus3':$debug[] = 'Bonustyp-Unterart->Wert1, Wert2, Wert3'.$bcheck[0].'-'.$bcheck[1].'->'.$script[1].', '.$script[2].', '.$script[3];
       switch($bcheck[1]){
          case 'bAutoSpell':         $slang['autospell'] = array($script[1],$script[2],$script[3]); break;
          case 'bAddMonsterDropItem':$slang['dropitem']  = array($script[1],$script[2],$script[3]); break;
          default:$debug[] = 'FEHLER IN BONUS3! prüfe ->'.$script[0].'<-';break;
       }
    break;
    /*case 'skill':$debug[] = 'Bonustyp-Unterart->Wert1, Wert2'.$bcheck[0].'-'.$bcheck[1].'->'.$script[1].', '.$script[2];
       $slang['skill-'.$script[1]] += $script[2];
    break;
    case 'refined':$debug[] = 'Bonustyp-Unterart->Wert1'.$bcheck[0].'-'.$bcheck[1].'->'.$script[1];
       $slang['???'] = $script[1];
    break;
    case 'slot':$debug[] = 'Bonustyp-Unterart->Wert1'.$bcheck[0].'-'.$bcheck[1].'->'.$script[1];
       $slang['slots'] += $script[1];
    break;*/
    case 'leer':$debug[] = 'leerer Bonus';
    break;
    default:$debug[] = 'UNGüLTIGER BONUSTYP! prüfe ->'.$script[0].'<-';
    break;
  }//switch
}//foreach


/*****************************
*      Mögliche Skripte      *
******************************/
/*
bonus bStr,2
bonus bVit,10
bonus bInt,3
bonus bDex,-1
bonus bAgi,1
bonus bLuk,10
bonus bHit,10
bonus bFlee,20
bonus bFlee2,20
bonus bAtk,30
bonus bMatk,8
bonus bDef,1
bonus bMdef,5
bonus bMaxHP,500
bonus bMaxSP,-50
bonus bMatkRate,10
bonus bMaxHPrate,20
bonus bMaxSPrate,15
bonus bCritical,30
bonus bDoubleRate,25
bonus bGetZenyNum,100
bonus bAtkEle,1
bonus bIgnoreDefRace,3
bonus bAspd,2
bonus bSpeedRate,25
bonus bPerfectHitRate,25
bonus bLongAtkDef,10
bonus bCastrate,-3
bonus bSPrecovRate,15
bonus bShortWeaponDamageReturn,30
bonus bNoSizeFix,0
bonus bRestartFullRecover,0
bonus bMagicDamageReturn,30
bonus bSplashRange,1
bonus bUseSPrate,-30
bonus bRange,1

bonus2 bAddEle,8,15
bonus2 bAddEff,Eff_Freeze,500
bonus2 bAddRace,7,5
bonus2 bSubRace,11,-10
bonus2 bWeaponComaRace,11,10
bonus2 bHPDrainRate,100,1
bonus2 bSubEle,6,-5
bonus2 bAddDamageClass,1188,150
bonus2 bResEff,Eff_Poison,5000
bonus2 bSpDrainRate,3,5
bonus2 bRandomAttackIncrease,300, 4
bonus2 bAddSize,1,50

bonus3 bAutoSpell,14,3,25
bonus3 bAddMonsterDropItem,544,5,2500

refined,1
skill 9,1
slot 1
*/
$debug[] = '<br>-----------------------------------------------------<br>';
	return $slang;
}

function getObjectInfo($obj_row,$char,$debug){
  global $table_prefix,$db;
  $debug[] = 'objectscript: '.$obj_row['obj_script'];          //ganzes object_script
  $lang['pic'] = (file_exists('rpg/images/tiles/special/'.$obj_row['obj_picture']) && $obj_row['obj_picture'] != -1)
                    ? $obj_row['obj_picture']
                    : 'blank.gif';
  $lang['id'] = $obj_row['obj_id'];
  $lang['x'] = $obj_row['obj_x'];
  $lang['y'] = $obj_row['obj_y'];
  $lang['title'] = $obj_row['obj_name'];

  $strings = explode(';',$obj_row['obj_script']);              //Voller Einzelbefehl
  if($strings != ''){
    foreach($strings AS $scripts){
      if($scripts != ''){
        $debug[] = '->object full: '.$scripts;
        $script = explode(':',$scripts);           //Hauptbefehl
        $scrip = explode(',',$script[1]);          //Nebenbefehle
        $debug[] = '-->object main: '.$script[0];
        switch($script[0]){                                   //Setzt Feldtypen auf 3 (nicht begehbar)
          case 'BLOCK':
            $lang['block'] = array($script[0],$scrip[0],$scrip[1]);
            $debug[] = '--->object sub: '.$script[1];
            break;
          case 'GMJP':                                         //Für GMs reservierte JPs
            if($char->job=='gm0' || $char->job=='gm1'){
              $lang['gmjp'] = array($script[0],$scrip[0],$scrip[1],$scrip[2]);
              $debug[] = '--->object sub: '.$script[1];
            }
            else{
              $lang['pic'] = 'blank.gif';
            }
            break;
          case 'SAVE_NPC':                                    // Speicher NPC
            $lang['save_npc'] = array($script[0],$scrip[0],$scrip[1],$scrip[2],$scrip[3]);
            $debug[] = '--->object sub: '.$script[1];
            break;
          case 'SIGN':                                      // NPCs
            $scrip[0] = ($scrip[0] == '') ? 0 : $scrip[0];
            $scrip[1] = ($scrip[1] == '') ? 0 : $scrip[1];
            $scrip[2] = ($scrip[2] == '') ? 'clear_npc.gif' : $scrip[2];
            $scrip[3] = ($scrip[3] == '') ? 0 : $scrip[3];
//            $scrip[3] = ($scrip[3] == '') ? 'clear_npc.gif' : $scrip[3];
            if($scrip[0] == 1)$lang['block'] = array('BLOCK',1,1);
            $lang['sign'] = array($script[0],$lang['title'],$scrip[1],$scrip[2],$scrip[3]);
            $debug[] = '--->object sub: '.$script[1];
            break;
          case 'HEAL':                                    // Heilt LP / SP
            $lang['heal'] = array($script[0],$scrip[0],$scrip[1],$scrip[2],$lang['title']);
            $debug[] = '--->object sub: '.$script[1];
            break;

          case 'MIX':                                    // Tauscht Items gegen Items und Moku
            $lang['mix'] = array($script[0],$scrip[0],$scrip[1],$lang['title']);
            $debug[] = '--->object sub: '.$script[1];
            break;

          case 'JOBQUEST':
            $lang['jobquest'] = array($script[0],$scrip[0],$scrip[1],$lang['title'],$scrip[2]);
            $debug[] = '--->object sub: '.$script[1];
            break;

          case 'EVENT':                  // Setzt Event-ajax
            $lang['event'] = array($script[0],$scrip[0],$scrip[1],$scrip[2],$lang['title']);
            $debug[] = '--->object sub: '.$script[1];
            break;
          case 'SWARP':                   // Jetzt spezielle JPs (Einbahnstraße)
//            $sqlm = "SELECT text FROM {$table_prefix}rpg_text WHERE text_id = {$scrip[1]}";
  //          if (!($resultm = $db->sql_query($sqlm))) { message_die(GENERAL_MESSAGE, 'Die Feuerstelle ist aus... ganz erloschen...');}
    //        $rowm = $db->sql_fetchrow($resultm);
      //      $texts = explode('$$',$rowm['text']);
            $lang['swarp'] = array($script[0],$scrip[0],$scrip[1],$lang['title'],$scrip[2]);

            $debug[] = '--->object sub: '.$script[1];
            break;
          default:
            $debug[] = 'FEHLER IN OBJECT! prüfe ->'.$script[0].'<-';
            break;
        }
      }
    }
  }
  $lang['gmjp_script'] = "new Array('{$lang['gmjp'][0]}', '{$lang['gmjp'][1]}', '{$lang['gmjp'][2]}', '{$lang['gmjp'][3]}')";
  $lang['block_script'] = "new Array('{$lang['block'][0]}', '{$lang['block'][1]}', '{$lang['block'][2]}')";
  $lang['save_script'] = "new Array('{$lang['save_npc'][0]}', '{$lang['save_npc'][1]}', '{$lang['save_npc'][2]}','{$lang['save_npc'][3]}','{$lang['save_npc'][4]}')";
  $lang['sign_script'] = "new Array('{$lang['sign'][0]}','{$lang['sign'][1]}','{$lang['sign'][2]}','{$lang['sign'][3]}','{$lang['sign'][4]}')";
  $lang['heal_script'] = "new Array('{$lang['heal'][0]}','{$lang['heal'][1]}','{$lang['heal'][2]}','{$lang['heal'][3]}','{$lang['heal'][4]}')";
  $lang['mix_script'] = "new Array('{$lang['mix'][0]}','{$lang['mix'][1]}','{$lang['mix'][2]}','{$lang['mix'][3]}')";
  $lang['jobquest_script'] = "new Array('{$lang['jobquest'][0]}','{$lang['jobquest'][1]}','{$lang['jobquest'][2]}','{$lang['jobquest'][3]}','{$lang['jobquest'][4]}')";
  $lang['event_script'] = "new Array('{$lang['event'][0]}','{$lang['event'][1]}','{$lang['event'][2]}','{$lang['event'][3]}','{$lang['event'][4]}')";
  $lang['swarp_script'] = "new Array('{$lang['swarp'][0]}','{$lang['swarp'][1]}','{$lang['swarp'][2]}','{$lang['swarp'][3]}','{$lang['swarp'][4]}')";

  $debug[] = 'gmjp ==> '.$lang['gmjp_script'];
  $debug[] = 'save_npc ==> '.$lang['save_script'];
  $debug[] = 'block ==> '.$lang['block_script'];
  $debug[] = 'sign ==> '.$lang['sign_script'];
  $debug[] = 'heal ==> '.$lang['heal_script'];
  $debug[] = 'mix ==> '.$lang['mix_script'];
  $debug[] = 'jobquest ==> '.$lang['jobquest_script'];
  $debug[] = 'event ==> '.$lang['event_script'];
  $debug[] = 'swarp ==> '.$lang['swarp_script'];
return $lang;}

/*INFOS

GMJP:map,x,y;           <-- Zielmap, Ziel_x, Ziel_y
BLOCK:x,y;              <-- breite, höhe des gesetzten Feldtyps
SIGN:typ,id1,id2,pic;   <-- Blocken? ja=1 nein=0, Text1 (innen), Text2 (außen), Bild
MIX:TEXT_ID,ID,pic;        <-- Kesselnpc, Begrüßungstext, Kesselnummer, Bild
*/

function getCharProfile($save_id){
	global $db,$table_prefix,$userdata,$db_job_name,$phpbb_root_path;
	$userdata['user_save'] = $save_id; 		// Wir wollen ja nicht den Char des Betrachters.
	$char = new Character($userdata);

	$var = 'user'.$userdata['user_id'];
	$$var = true;

	$name = $char->name;
	$char_image = '<img src="'.$phpbb_root_path.'images/avatars/chara_'.$save_id.'.png">';
	$stat_image = '<img src="rpg/rpg_image_stats_v2.php?charid='.$save_id.'&w=150&h=150&rad=50&type=2&skin='.$userdata['user_style'].'" />';

		$bio = $char->bio;
		$level = $char->base_level.'/'.$char->job_level;
		$job = $db_job_name[$char->user_lang_short][$char->job];
		if($char->guild){
			$guild = new guild($char->guild);
			$name = '['.$guild->tag.'] '.$name;
			$guild_img = (file_exists('rpg/guilds/emblems/'.$guild->id.'.png')) 
											? '<img src="rpg/guilds/emblems/'.$guild->id.'.png">'
											: '<img src="images/spacer.gif" width="30" height="30" />';
			$guild_info = '<table border="0" cellspacing="0" cellpadding="0" wdith="100%">
			    	<tr> 
		        	<td>'.$guild_img.'</td>
		          <td colspan="3">
								<a href="'.$phpbb_root_path.'rpg_portal.php?s=guild_page&gid='.$guild->id.'">'.$guild->name.'</a><br />
							 	'.$guild->ranks[$char->guild_rank]->title.'
							</td>
		      	</tr>
					</table>';
		}

		$hp_bar = $char->getHPBar(50);
		$sp_bar = $char->getSPBar(50);
		
		$template = '
		<table cellspacing="0" cellpadding="0" width="100%%">
			<tr class="gen row1">
				<td rowspan="3">%2$s</td>
				<td>%4$s</td>
				<td rowspan="3">%3$s</td>
			</tr>
			<tr class="gen row1">
				<td>%5$s</td>
			</tr>
			<tr class="gen row1">
				<td>HP: %6$s<br>SP: %7$s</td>
			</tr>
			<tr class="gen row2">
				<td colspan="3">%8$s <img src="http://www.last-anixile.de/forum/rpg/images/sell.gif"></td>
			</tr>
			<tr class="gen row2">
				<td colspan="3">%9$s</td>
			</tr>
		</table>';
		return sprintf($template,$name,$char_image,$stat_image,$guild_info,$level.' '.$job,$hp_bar,$sp_bar,$char->moku,$char->bio);
}

/**
 * @param $fieldValue
 * @return int
 */
function getTileId($fieldValue){
        global $tilesetsByName, $stats;

        $field = explode('/', str_replace('.','/', $fieldValue));

        $setName = $field[0];
        $localTileId = $field[1];

        if(empty($stats['tilesetUsage'][$setName])){
                $stats['tilesetUsage'][$setName] = 1;
        }
        else{
                $stats['tilesetUsage'][$setName]++;
        }


        if(isset($tilesetsByName[$setName])){
                $tileset = $tilesetsByName[$setName];
        }
        elseif(isset($tilesetsByName[strtolower($setName)])){
                $tileset = $tilesetsByName[strtolower($setName)];
        }
        elseif(isset($tilesetsByName[ucfirst(strtolower($setName))])){
                $tileset = $tilesetsByName[ucfirst(strtolower($setName))];
        }
        else {
                echo " <b>Tileset not found: $setName</b> ";
                return 0;
        }

        $tileId = (int) $localTileId + $tileset['firstgid'] - 1;

        return $tileId;

}
