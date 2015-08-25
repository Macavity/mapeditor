<?php

class Map{
	var $id;
	var $name;
	var $width;
	var $height;
	var $mob = array();
	var $mob_all_per;
	var $mob_rate;
	var $bg_image;
	var $npc = array();
	
	function Map($map_id){
		global $db,$table_prefix;
		$this->id = $map_id;
		$sql = "SELECT * FROM {$table_prefix}rpg_maps WHERE map_id = $map_id";
		if(!$result = $db->sql_query($sql)){
			echo $sql;
			message_die(GENERAL_MESSAGE,'Tot...alle...sie sind alle tot...*heul*');
		}
		$row = $db->sql_fetchrow($result);
		$this->name = $row['map_name'];
		$this->width = $row['map_width'];
		$this->height = $row['map_height'];
		$this->bg_image = $row['map_bg_image'];
		
		$npc_counter = 0;
		// Suche nach Shops
		$sql = "SELECT * FROM {$table_prefix}shops2 WHERE map_id = $map_id";
		if(!$result = $db->sql_query($sql)){
			message_die(GENERAL_MESSAGE,'Tot...alle...sie sind alle tot...*flenn*');
		}
		while($shop_row = $db->sql_fetchrow($result)){
			$shop_id = $shop_row['id'];
			$shop_name = $shop_row['name'];
			$shop_image = ($shop_row['icon'] != '-1') ? $shop_row['icon'] : 'sell.gif';
			
			$this->npc[$npc_counter] = new NPC($map_id,$shop_row['map_x'],$shop_row['map_y']);
			$this->npc[$npc_counter]->type = 'Shop';
			$this->npc[$npc_counter]->name = $shop_name;
			$this->npc[$npc_counter]->image = $shop_image;
			$this->npc[$npc_counter]->link = "shop_inventory.php?action=shoplist&shop=$shop_id";
			$npc_counter++;
		}
		// Suche nach Sprungpunkten.
		$sql = "SELECT * FROM {$table_prefix}rpg_npcs WHERE npc_map = $map_id";
		if(!$result = $db->sql_query($sql)){
			message_die(GENERAL_MESSAGE,'Tot...alle...sie sind alle tot...*jammer*');
		}
		while($npc_row = $db->sql_fetchrow($result)){
			if($npc_row['npc_type'] == 'JumpPoint'){
				$script = explode('#',$npc_row['npc_script']);
				$jp_map = $script[0];
				$jp_x = $script[1];
				$jp_y = $script[2];
				$this->npc[$npc_counter] = new JumpPoint($map_id,$npc_row['npc_map_x'],$npc_row['npc_map_y'],$jp_map,$jp_x,$jp_y);
				$this->npc[$npc_counter]->id = $npc_row['npc_id'];
				$this->npc[$npc_counter]->name = $npc_row['npc_title'];
			}
			$npc_counter++;
		}
		
		// Suche nach Monstern
		$mob_counter = 0;
		$sql = "SELECT s.spawn_id,s.spawn_map,s.spawn_mob_id,s.spawn_mob_number,m.aggro,m.name_eng,m.name_ger 
				FROM {$table_prefix}rpg_spawns AS s, {$table_prefix}mob_db as m 
				WHERE s.spawn_map = $map_id AND s.spawn_mob_id = m.id ORDER BY spawn_mob_number ASC";
		if(!$result = $db->sql_query($sql)){
			message_die(GENERAL_MESSAGE,'Tot...alle...sie sind alle tot...*wimmer*');
		}
		$all_num = 0;
		while($spawn_row = $db->sql_fetchrow($result)){
			$all_num += $spawn_row['spawn_mob_number'];
			$name = (strlen($spawn_row['name_ger'])>3) ? $spawn_row['name_ger'] : $spawn_row['name_eng'];
			$this->mob[$mob_counter] = new Monster($spawn_row['spawn_mob_id'],$all_num,$spawn_row['aggro'],$name);
			$mob_counter++;
		}
		$this->mob_all_per = $all_num;		// Maximum für Monster Check
		$this->mob_rate = $row['map_mob_rate'];	
	}
	
	function getNPC($x,$y){
		for($i = 0; $i < count($this->npc); $i++){
			if($this->npc[$i]->x == $x && $this->npc[$i]->y == $y){
				return $i;
			}
		}
		return -1;
	}
}

class MapMonster{
	var $id;
	var $name;
	var $chance;
	var $aggro;
	
	function Monster($id,$chance,$aggro,$name){
		$this->id = $id;
		$this->name = $name;
		$this->chance = $chance;
		$this->aggro = $aggro;
	}
}

class Point{
	var $map_id;
	var $x;
	var $y;
	
	function Point($map_id,$x,$y){
		$this->map_id = $map_id;
		$this->x = $x;
		$this->y = $y;
	}
}

class NPC extends Point{
	var $type;
	var $image;
	var $name;
	var $link;
	
	function NPC($map_id,$x,$y){
		$this->map_id = $map_id;
		$this->x = $x;
		$this->y = $y;
	}
}

class JumpPoint extends NPC{
	var $id;
	var $name;
	var $target;
	
	function JumpPoint($map_id,$x,$y,$jp_map = 1,$jp_x = 1,$jp_y = 1){
		$this->map_id = $map_id;
		$this->x = $x;
		$this->y = $y;
		
		$this->type = "JumpPoint";
		$this->target = new NPC($jp_map,$jp_x,$jp_y);
		$this->image = 'jump.png';
	}
}
