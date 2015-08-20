<?php
/***************************************************************************
 *                                        rpg_map.php
 *                                ------------------------
 *        begin                    : 01/01/2006
 *        copyright                : Macavity
 *
 *
 ***************************************************************************/

/***************************************************************************
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 *
 ***************************************************************************/

define('IN_PHPBB', true);
define('IN_RPG', true);
$phpbb_root_path = './';
include($phpbb_root_path . 'extension.inc');
include($phpbb_root_path . 'common.'.$phpEx);

require "includes/rpg_functions.php";
require "includes/rpg_config.php";
require "includes/class_map.php";
//require "includes/class_character.php";

$filename = "rpg_char.php";
$rpglang = "eng";

$debug = array();
//
// Start session management
$userdata = session_pagestart($user_ip, PAGE_INDEX);
init_userprefs($userdata);

if( !$userdata['session_logged_in'] || ($userdata['user_id'] != 104 && $userdata['user_id'] != 75)){
	header('Location: ' . append_sid("login.$phpEx?redirect=rpg_map.$phpEx", true));
}
// End session management
//

function generateTable($map_id){
	global $db, $table_prefix;
	// Create Table Field
	$map = new Map($map_id);
	$table_field .= '<table width="100%" border="1" background="" style="border:px solid; padding:4px 5px;background-color:white;" cellspacing="0" cellpadding="0">';

	for($i = 1; $i <= $map->height; $i++){	// Y
		$table_field .= '
		<tr>';
		for($j = 1; $j <= $map->width; $j++){	// X
			$npc_n = $map->getNPC($j,$i);
			if($npc_n != -1){
				if($map->npc[$npc_n]->type == 'Shop'){
					$debug[] = "Shop found";
					$image = '<img src="rpg/images/'.$map->npc[$npc_n]->image.'" alt="'.$map->npc[$npc_n]->name.'" title="'.$map->npc[$npc_n]->name.'">';
				}
				elseif($map->npc[$npc_n]->type == 'JumpPoint'){
					$image = '<img src="rpg/images/'.$map->npc[$npc_n]->image.'" onClick="window.location.replace(\'adm_edit_map.php?map='.$map->npc[$npc_n]->target->map_id.'\');" alt="'.$map->npc[$npc_n]->name.'" title="'.$map->npc[$npc_n]->name.'">';
				}
				$table_field .= '
		<td width="32" height="32" id="pos_'.$j.'x'.$i.'" name="pos_'.$j.'x'.$i.'" align="center" background="rpg/images/tiles/'.$map->bg_image.'">'.$image.'</td>';
			}
			else{
				$table_field .= '
		<td width="32" height="32" id="pos_'.$j.'x'.$i.'" name="pos_'.$j.'x'.$i.'" align="center" background="rpg/images/tiles/'.$map->bg_image.'" onClick="newNPC('.$j.','.$i.')">&nbsp;</td>';
		
			}
		}
		$table_field .= '
		</tr>';
	}
	$table_field .= '</table>';
	return $table_field;
}

function getMapOptions($not_map){
	global $db, $table_prefix;
	// Get Maps
	$sql = "SELECT * FROM {$table_prefix}rpg_maps WHERE map_id <> $not_map";
	if(!$result = $db->sql_query($sql)){
		message_die(GENERAL_MESSAGE,'Tot...alle...sie sind alle tot...*wimmer*');
	}
	while($row = $db->sql_fetchrow($result)){
		$opts .= '
		<option value="'.$row['map_id'].'">ID:'.$row['map_id'].', '.$row['map_name'].' ('.$row['map_comment'].')</option>';
	}
	return $opts;
}

// Aktionen ausführen
if($_POST['act']){
	$act = $_POST['act'];
	if($act == 'new_jp'){
		$table_field = generateTable($_POST['target_map']);
		
	?>
	<script language="JavaScript" type="text/javascript">
	//<!--
	function newNPC(x,y){
		document.getElementById('to_x').value = x;
		document.getElementById('to_y').value = y;
		document.getElementById('new_jp').style.display = 'inline';
	}
	//-->
	</script>
	<?  global $table_field; echo $table_field; ?>              			
	<br>Von Map ID: <? echo $_POST['map_id']; ?> X: <? echo $_POST['var_x']; ?> Y: <? echo $_POST['var_y']; ?><br>
	<div id="new_jp" style="display:none">
	<form action="adm_edit_map.php" method="post">
	<input type="hidden" id="act" name="act" value="new_jp2">
	<input type="hidden" id="from_map_id" name="from_map_id" value="<? echo $_POST['map_id']; ?>">
	<input type="hidden" id="from_x" name="from_x" value="<? echo $_POST['var_x']; ?>">
	<input type="hidden" id="from_y" name="from_y" value="<? echo $_POST['var_y']; ?>">
	<input type="hidden" id="from_name" name="from_name" value="<? echo $_POST['var_name']; ?>">
	<input type="hidden" id="target_map" name="target_map" value="<? echo $_POST['target_map']; ?>">
	<br>X:<input type="text" id="to_x" name="to_x" value="0">
	<br>Y:<input type="text" id="to_y" name="to_y" value="0">
	<br><input type="submit" value="Absenden">
	</form>
	</div> 
	<?
	}
	elseif($act == 'new_jp2'){
		$script = "{$_POST['target_map']}#{$_POST['to_x']}#{$_POST['to_y']}";
		$sql = "INSERT INTO {$table_prefix}rpg_npcs (npc_map,npc_type,npc_map_x,npc_map_y,npc_title,npc_script) 
				VALUES (".$_POST['from_map_id'].",'JumpPoint',".$_POST['from_x'].",".$_POST['from_y'].",'".addslashes($_POST['from_name'])."','$script')";
		if(!$result = $db->sql_query($sql)){
			message_die(GENERAL_MESSAGE,'Tot...alle...sie sind alle tot...*jammer*');
		}
		echo "SQL: <br>$sql<br>Sprungpunkt eingetragen.";
	}
}
else{
	$map_id = ($_GET['map']) ? $_GET['map'] : 1;
	$table_field = generateTable($map_id);
	$opts_map = getMapOptions($map_id);
	?>
	<script language="JavaScript" type="text/javascript">
	//<!--
	function newNPC(x,y){
		document.getElementById('var_x').value = x;
		document.getElementById('var_y').value = y;
		document.getElementById('new_jp').style.display = 'inline';
	}
	//-->
	</script>
	<? global $table_field; echo "$table_field"; ?>              			
	<div id="new_jp" style="display:none">
	<form action="adm_edit_map.php" method="post">
	<input type="hidden" id="act" name="act" value="new_jp">
	<input type="hidden" id="map_id" name="map_id" value="<? global $map_id; echo $map_id; ?>">
	<br>X:<input type="text" id="var_x" name="var_x" value="0">
	<br>Y:<input type="text" id="var_y" name="var_y" value="0">
	<br>Neuer Sprungpunkt nach:
	<select id="target_map" name="target_map"><? global $opts_map; echo $opts_map; ?></select>
	<br>Name:<input type="text" id="var_name" name="var_name" value="">
	<br><input type="submit" value="Absenden">
	</form>
	</div>
	<?
}


// Debug
foreach($debug as $w){
        $i++;
        $deb .= ($w == '-') ? "<hr>" : "<br>$i:$w";
}
$flag = getRates($user);
$debug = ($flag['debug']) ? $deb : '';

?>
