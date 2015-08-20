<?php

define('IN_RPG', true);

include('includes/rpg_config.php');

/**
 * 104 = Macavity
 * 75 = shizo
 * 54 = Morpheusz
 * 57 = Kadaj
 * 638 = Deval
 * 202 = Pietro
 * 1066 = Lunk
 * 1110 = Tsunade
 * 1075 = Istvan
 */
/*
if( !in_array($userdata['user_id'],array(104,75,57,638,202,1066,1110,1075))){
	message_die(GENERAL_MESSAGE, $lang['Not_Authorised']);
}*/

// since we don't have a phpbb frame anymore, let's make some fake arrays.
$users = array(
	  54 => array( 'name' => 'Morpheusz', 	'rank' => 'dev' ),
	  57 => array( 'name' => 'Kadaj', 		'rank' => 'dev' ),
  	  75 => array( 'name' => 'shizo', 		'rank' => 'dev' ),
	 104 => array( 'name' => 'Macavity', 	'rank' => 'admin' ),
	 638 => array( 'name' => 'Deval', 		'rank' => 'intern', 'prefix' => '[d]' ),
	 713 => array( 'name' => 'Santana', 	'rank' => 'editor', 'prefix' => '[s]' ),
	 202 => array( 'name' => 'Pietro', 		'rank' => 'editor', 'prefix' => '[p]' ),
	1066 => array( 'name' => 'Lunk', 		'rank' => 'editor', 'prefix' => '[l]' ),
	1110 => array( 'name' => 'Tsunade', 	'rank' => 'intern', 'prefix' => '[t]' ),
	1075 => array( 'name' => 'Istvan', 		'rank' => 'editor', 'prefix' => '[i]' ),
);

$userId = 104;
$user = $users[$userId];


define('IN_RPG_ADMIN', true);

// List of possible Tilesets

$tilesets = array();

$directory = "images/tiles";
$d = dir($directory);
chdir($directory);
while($file = $d->read()) {
	if(is_dir($file) && $file != '.' && $file != '..'){
		$tilesets[] = $file;
	}
}
chdir("../../../");
$d->close();

natcasesort($tilesets);

foreach($tilesets as $t){
	$list_tilesets .= "<option value=\"$t\">$t</option>";
}

/*
 * Available Maps for this user
 */
$directory = "maps";
$d = dir($directory);
chdir($directory);

$file_array = array();
while($file = $d->read()){
	if( (is_file($file)) && strpos ($file, ".js") && strpos ($file, "_ft") === false ){

		if($user_713 && strpos($file,'[s]') === false){
		}
		elseif($user_1110 && (strpos($file,'[t]') === false && strpos($file,'[l]') === false && strpos($file,'[p]') === false)){
		}
		elseif($user_1066 && (strpos($file,'[l]') === false && strpos($file,'[p]') === false && strpos($file,'[t]') === false)){
		}
		elseif($user_202 &&  (strpos($file,'[p]') === false && strpos($file,'[l]') === false && strpos($file,'[t]') === false && strpos($file,'[i]') === false && strpos($file,'[m]') === false)){
		}
		elseif($user_1075 && (strpos($file,'[i]') === false)){
		}
		else{
			$file_array[] = $file;
		}
	}
}
chdir("../../../");
$d->close();

natcasesort($file_array);
?>
<style type="text/css">
  body {
    color: black; background-color: white;
    font-size: 100.01%;
    font-family: Helvetica,Arial,sans-serif;
    margin: 0; padding: 1em;
  }

  ul#TileSet {
    float: right; width: 20em;
    margin: 0 0 1.2em; padding: 0;
    border: 1px dashed silver;
  }

  div#Map {
    /*margin: 0 12em 1em 16em;*/
    padding: 0 1em;
    border: 1px dashed silver;
  }
  * html div#Inhalt {
    height: 1em;  /* Workaround gegen den 3-Pixel-Bug des Internet Explorers */
  }

</style>
<?php

$stage = empty($_POST['stage']) ? 'create' : $_POST['stage'];

if( $stage == 'create' || empty($_POST['file_name']) ){
	/**
	 * Create Map
	 */
	include("views/create_map.php");
}
elseif( $stage == 'edit_map'){
	/**
	 * Edit Map
	 */
	if(file_exists('rpg/maps/'.$_POST['file_name'])){
		$map_file = file('rpg/maps/'.$_POST['file_name']);
		foreach($map_file as $line){
			$s = $line;
			if(substr_count($s,'var name')){
				$s = $s.';';
			}
			$s = str_replace('var ','$',$s);
			$s = str_replace('new Array','array',$s);
			$s = str_replace('field_','$field_',$s);
			$s = str_replace('$$','$',$s);
			$php_code .= $s;
			
		}
		eval($php_code);
	}
	$tiles_dir = 'rpg/images/tiles/';
	$stdTS = $_POST['tileset'];
	
	$edit_existing_map = ($_POST['edit'] == 1) ? true : false;	
	
	if($edit_existing_map){
		$stdTS = '001-Grassland01';
		?>
		<script language="JavaScript" type="text/javascript" src="includes/js_rpg_adm.js"></script>
		<script language="JavaScript" type="text/javascript" src="<? echo 'rpg/maps/'.$_POST['file_name']; ?>"></script>
		<script language="JavaScript" type="text/javascript">
			var edit_existing_map = true;
			
			function init(){
				generateFields(width,height,main_bg);
				document.getElementById('name').value = name;
				document.getElementById('main_bg').value = main_bg;
				document.getElementById('width').value = width;
				document.getElementById('height').value = height;
				//setFields(width,height,field_bg,field_layer1,field_layer2,field_layer4);
			}
			
		</script>
		<?	
	}
	else{
		$name = $_POST['name'];
		$main_bg = $_POST['tileset'].'/'.$_POST['main_bg'];	
 		$width = $_POST['width'];
		$height = $_POST['height'];
		?>
		<script language="JavaScript" type="text/javascript">
			var edit_existing_map = false;
		</script>
		<?	
	}
	
 for($y = 0; $y < $height; $y++){//{{{ 
 	// Y
	$var_bg_js = array();
	for($x = 0; $x < $width; $x++){//{{{
		// X
		$t = 32 * $y;
		$l = 32 * $x;
		$div_fields .= "\n";
		if($field_layer1[$y][$x]){
			$div_fields .= '<input id="field_'.$x.'x'.$y.'_layer_1" name="field_'.$x.'x'.$y.'_layer_1" value="'.$field_layer1[$y][$x].'" type="hidden">';
		}
		if($field_layer2[$y][$x]){
			$div_fields .= '<input id="field_'.$x.'x'.$y.'_layer_2" name="field_'.$x.'x'.$y.'_layer_2" value="'.$field_layer2[$y][$x].'" type="hidden">';
		}
		if($field_layer4[$y][$x]){
			$div_fields .= '<input id="field_'.$x.'x'.$y.'_layer_4" name="field_'.$x.'x'.$y.'_layer_4" value="'.$field_layer4[$y][$x].'" type="hidden">';
		}
		$bg = (($field_bg[$y][$x]) ? $field_bg[$y][$x] : $main_bg);
		$field_layer1[$y][$x] = ($field_layer1[$y][$x]) ? $field_layer1[$y][$x] : 'spacer.gif';
		$field_layer2[$y][$x] = ($field_layer2[$y][$x]) ? $field_layer2[$y][$x] : 'spacer.gif';
		$field_layer4[$y][$x] = ($field_layer4[$y][$x]) ? $field_layer4[$y][$x] : 'spacer.gif';
		$bg_div_field .= '
		<span onClick="changeField('.$x.','.$y.');" alt="'.$x.','.$y.'" title="'.$x.','.$y.'">
		<span id="bg_'.$x.'x'.$y.'" style="position: absolute; z-index: 1; top: '.$t.'px; left: '.$l.'px;">
			<img src="rpg/images/tiles/'.$bg.'">
		</span>
		<span id="bg_'.$x.'x'.$y.'_lay_1" style="position: absolute; z-index: 2; top: '.$t.'px; left: '.$l.'px;">
			<img src="rpg/images/tiles/'.$field_layer1[$y][$x].'">
		</span>
		<span id="bg_'.$x.'x'.$y.'_lay_2" style="position: absolute; z-index: 3; top: '.$t.'px; left: '.$l.'px;">
			<img src="rpg/images/tiles/'.$field_layer2[$y][$x].'">
		</span>
		<span id="bg_'.$x.'x'.$y.'_lay_4" style="position: absolute; z-index: 4; top: '.$t.'px; left: '.$l.'px;">
			<img src="rpg/images/tiles/'.$field_layer4[$y][$x].'">
		</span>
		</span>';
		$div_fields .= '<input id="field_'.$x.'x'.$y.'_bg" name="field_'.$x.'x'.$y.'_bg" value="'.$bg.'" type="hidden">';
	}
 }
 //$well_done = false;
?>
<script language="JavaScript" type="text/javascript"><!--{{{  -->

//<!--
var value_bg = new Array()
var old_x = 1;
var old_y = 1;

function setValue(name,value){
        if(document.getElementById(name)){
                document.getElementById(name).value = value;
        }
        else{
                new_hidden = '<input type="hidden" id="'+name+'" name="'+name+'" value="'+value+'">';
                document.getElementById('div_fields').innerHTML += new_hidden;
        }
}

function changeField(x,y){
	document.getElementById('edit_x').innerHTML = x;
	document.getElementById('edit_y').innerHTML = y;
	
	tile_set = document.getElementById('sel_tileset').value;
	tile = document.getElementById('sel_tile').value;
	
	edit_type = document.getElementById('edit_et').value;
	value = tile;
	
	if(edit_type == 'BG'){
    //Wert speichern
		name = 'field_'+x+'x'+y+'_bg';
    setValue(name,value);
		document.getElementById('bg_'+x+'x'+y).innerHTML = '<img src="rpg/images/tiles/'+value+'">';
		//document.getElementById('edit_img_bg').innerHTML = '<img src="rpg/images/tiles/'+value+'">';
	}
	else if(edit_type == 'L1'){
		changeLayer(1,value);
	}
	else if(edit_type == 'L2'){
		changeLayer(2,value);
	}
	else if(edit_type == 'L4'){
		changeLayer(4,value);
	}
	old_x = x;
	old_y = y;
}

function changeBG(){
	x = document.getElementById('edit_x').innerHTML;
	y = document.getElementById('edit_y').innerHTML;

	document.getElementById('bg_'+x+'x'+y).style.backgroundImage = 'url(rpg/images/tiles/'+(document.getElementById('edit_bg').value)+');';
    	
    //Wert speichern
    value = document.getElementById('edit_bg').value;
	name = 'field_'+x+'x'+y+'_bg';
    setValue(name,value);

}

function changeLayer(num,value){
	x = document.getElementById('edit_x').innerHTML;
	y = document.getElementById('edit_y').innerHTML;

	//Wert speichern
	name = 'field_'+x+'x'+y+'_layer_'+num;
	setValue(name,value);

	if(document.getElementById('bg_'+x+'x'+y+'_lay_'+num)){
    if(value != ''){
			document.getElementById('bg_'+x+'x'+y+'_lay_'+num).innerHTML = '<img src="rpg/images/tiles/'+value+'">';
    }
		else{
			document.getElementById('bg_'+x+'x'+y+'_lay_'+num).innerHTML = '';
    }
		//document.getElementById('div_debug').innerHTML = '<img src="rpg/images/'+value+'">';
    }
    else{
    	if(value != ''){
				new_div = '<span id="bg_'+x+'x'+y+'_lay_'+num+'" style="position:absolute; z-index:'+num+'"><img src="rpg/images/tiles/'+value+'"></span>';
        document.getElementById('span_bg_'+x+'x'+y).innerHTML += new_div;
    	}
			else{
				document.getElementById('bg_'+x+'x'+y+'_lay_'+num).innerHTML = '';
    	}
    	//document.getElementById('div_debug').innerHTML = 'neu, '+num+','+new_div;
    }
}

function switchTileSet(){
	newTS = document.getElementById('sel_tileset').value;
	document.getElementById('iframe_tileset').src = 'rpg/images/tiles/'+newTS+'/'+newTS+'.html';
}

function clickTile(tile_name){	
	tile_set = document.getElementById('sel_tileset').value;
	value = tile_set+'/'+tile_name;
	document.getElementById('sel_tile').value = value;
	document.getElementById('img_sel_tile').innerHTML = '<img src="rpg/images/tiles/'+value+'">';
}

//--> <!--}}}-->
</script>
<form action="" method="post">
<table width="100%">
 <tr> <!-- Edit Field -->
  <td colspan="2">
  <table width="100%">
   <tr>
    <td>
     <fieldset><legend>Feld:</legend>
		X: <span id="edit_x"></span><br>
		Y: <span id="edit_y"></span><br>
	 </fieldset>
    </td>
    <td>
     <fieldset><legend>Bearbeitungsebene Pinsel</legend>
    	<select name="edit_et" size="1" id="edit_et">
      		<option value="BG">Hintergrund</option>
      		<option value="L1">Ebene 1</option>
      		<option value="L2">Ebene 2</option>
      		<option value="L4">Ebene 4 (&uuml;ber dem Spieler)</option>
    	</select>
     </fieldset>
	</td>
    <td>
     <fieldset><legend>Bild Pinsel</legend>
    	<input type="hidden" id="sel_tile" value=""><span id="img_sel_tile">&nbsp;</span>	
    </fieldset>
    </td>
   </tr>
   <tr>
   <td colspan=3><input type="checkbox" name="backup" value="yes">Backup erstellen </td>
   </tr>
  </table>
  </td>
 </tr>
 <tr>
  <td width="70%"> <!-- Karte -->
   <div id="big_map" style="width:640px; height:640px; overflow:scroll;">
	 <div id="tbl_map" style="position:relative; display:inline; width: <? echo $width*32; ?>px; height: <? echo $height*32; ?>px;" align="center">
	<span id="bg_layer" style="position:relative; display:table; width: <? echo $width*32; ?>px; height: <? echo $height*32; ?>px;">
	<?
	global $bg_div_field; echo $bg_div_field;
	?>
	</span></div></div>
  </td>
  <td width="30%"> <!-- Tiles -->
   <table>
    <tr>
	 <td><select id="sel_tileset" onChange="switchTileSet();"><? 
	foreach($tilesets as $t){
		if($t == $stdTS)
			$list_tilesets .= "\n<option value=\"$t\" selected>$t</option>";
		else
			$list_tilesets .= "\n<option value=\"$t\">$t</option>";
	}
	echo $list_tilesets;
	?></select><span id="tiles_from_this_tileset">&nbsp;</span></td>
	</tr>
	<tr>
	<td id="td_tileset" valign="top"><iframe id="iframe_tileset" width="100%" height="600" src="<? echo 'rpg/images/tiles/'.$stdTS.'/'.$stdTS.'.html'; ?>"></iframe></td>
	</tr>
   </table>
  </td>
 </tr>
 <tr>
  <td colspan="2">
Kartenname: <input type="text" id="name" name="name" value="<? global $name; echo $name; ?>">
<input type="hidden" id="file_name" name="file_name" value="<? echo $_POST['file_name']; ?>">
<input type="hidden" id="stage" name="stage" value="2">
<input type="hidden" id="main_bg" name="main_bg" value="<? global $main_bg; echo $main_bg; ?>">
<input type="hidden" id="width" name="width" value="<? global $width; echo $width; ?>">
<input type="hidden" id="height" name="height" value="<? global $height; echo $height; ?>">
<input type="hidden" id="edit" name="edit" value="<? echo $_POST['edit']; ?>">
<div id="div_fields"><? global $div_fields; echo $div_fields; ?>;</div>

<input type="submit" value="Fertig.">
</form>
  </td>
 </tr>
</table>
<div id="div_debug"></div>
<? //}}}
	if($_POST['edit'] == 1){
	?>
	<script language="JavaScript" type="text/javascript">
		//init();
		//setFields();
	</script>
	<?
	}
}
// Zum Abschluss eine JS Datei erstellen/�berschreiben mit den Werten.
elseif($_POST['stage'] == "2"){//{{{ 
/********************************************************************************
 *													Kartendaten speichern
 *******************************************************************************/
echo '<form action="" method="post"><input type="submit" value="Weiter"></form>';

        $file_name = $_POST['file_name'];        
		$file_name = str_replace('.js','',$file_name);
		
		if($_POST['backup'] == 'yes'){		// Backup erstellen
			echo "<br>backup wurde ausgef�hrt.";
			$s = 'rpg/maps/'.$file_name.'.js';
			if(file_exists($s)){
				$counter = 0;
				do{
					$bak_file = 'rpg/maps/'.$file_name.'.bak'.$counter;
					$counter++;
				}while(file_exists($bak_file));
				rename($s,$bak_file);
			}
		}
		$width = $_POST['width'];
        $height = $_POST['height'];
        $output .= "\nvar name = '{$_POST['name']}'";
        $output .= "\nvar width = $width;";
        $output .= "\nvar height = $height;";
        $output .= "\nvar main_bg = '{$_POST['main_bg']}';";
        //$output .= "\nvar field_type = new Array();";
        $output .= "\nvar field_bg = new Array();";
        $output .= "\nvar field_layer1 = new Array();";
        $output .= "\nvar field_layer2 = new Array();";
        $output .= "\nvar field_layer4 = new Array();";
        for($i = 0; $i < $height; $i++){
			//$field_bg = array();
			$bool_field_bg = false;
			$bool_field_lay1 = false;
			$bool_field_lay2 = false;
			$bool_field_lay3 = false;
			$field_type = array();
			$field_bgs = '';
            for($j = 0; $j < $width; $j++){
            	$s1 = 'field_'.$j.'x'.$i.'_bg';
                $s2 = 'field_'.$j.'x'.$i.'_layer_1';
                $s3 = 'field_'.$j.'x'.$i.'_layer_2';
                $s4 = 'field_'.$j.'x'.$i.'_layer_4';
					  
				// Feld Hintergrund
				if($_POST[$s1]){
					//$field_bg[$j] = "'{$_POST[$s1]}'";
					$field_bgs .= "\n  field_bg[$i][$j] = '{$_POST[$s1]}';";
					$bool_field_bg = true;
				}
				
				// Layer
				if($_POST[$s2]){
					$field_bgs .= "\n  field_layer1[$i][$j] = '{$_POST[$s2]}';";
					$bool_field_lay1 = true;
				}
				if($_POST[$s3]){
					$field_bgs .= "\n  field_layer2[$i][$j] = '{$_POST[$s3]}';";
					$bool_field_lay2 = true;
				}
				if($_POST[$s4]){
					$field_bgs .= "\n  field_layer4[$i][$j] = '{$_POST[$s4]}';";
					$bool_field_lay4 = true;
				}
				
			}
			$output .= "\n// Reihe $i";
		   	if($bool_field_bg)	$output .= "\n\tfield_bg[$i] = new Array();";
		   	if($bool_field_lay1)	$output .= "\n\tfield_layer1[$i] = new Array();";
		   	if($bool_field_lay2)	$output .= "\n\tfield_layer2[$i] = new Array();";
		   	if($bool_field_lay4)	$output .= "\n\tfield_layer4[$i] = new Array();";
		   	//$output .= "\n field_bg[$i] = new Array(".(implode(',',$field_bg)).");";
		   	$output .= "$field_bgs";
		}	
	
		echo str_replace("\n","<br>",$output);
		$file_name = 'rpg/maps/'.$file_name.'.js';
		$fp = fopen("$file_name",'w');
		$date = date("l dS of F Y H:i:s");
		if ($fp){
			flock($fp,2);
			echo "<br>'$file_name' wurde f�r andere User gesperrt.";
			fputs ($fp, "\n// Datei: $file_name");
			fputs ($fp, "\n// Map: {$_POST['name']}");
			fputs ($fp, "\n// Editor: {$userdata['username']}");
			fputs ($fp, "\n// Datum: $date");
			fputs ($fp, "$output");
			echo "// Datei: $file_name
				<br>// Map: {$_POST['name']}
				<br>// Editor: {$userdata['username']}
				<br>// Datum: $date<br>Der Code wurde in '$file_name' gespeichert.";
			flock($fp,3);
			echo "<br>'$file_name' wurde wieder entsperrt.";
			fclose($fp);
		}
		else{
			echo "<br>Datei konnte nicht zum  Schreiben ge�ffnet werden";
		} //}}}
}
elseif($_POST['stage'] == 'edit_ft'){		// Feldtypen bearbeiten
/********************************************************************************
 *													Feldtypen bearbeiten
 *******************************************************************************/
	$map_name = 'rpg/maps/'.$_POST['file_name'];
	
	if(file_exists($map_name)){
		$map_file = file($map_name);	
		foreach($map_file as $line){
			$s = $line;
			if(substr_count($s,'var name =')){
				$s = $s.';';
			}
			$s = str_replace('var ','$',$s);
			$s = str_replace('new Array','array',$s);
			$s = str_replace('field_','$field_',$s);
			$s = str_replace('$$','$',$s);
			$php_code .= $s;
			
		}
	}
	
	$f = str_replace('.js','_ft.js',$_POST['file_name']);
	if(file_exists('rpg/maps/ft_defs/'.$f)){
		$fieldtype_data = 'rpg/maps/ft_defs/'.$f;
		$ft_file = file($fieldtype_data);
		foreach($ft_file as $line){
			if(!substr_count($line,'var name =') > 0){
				$s = $s.';';
			}
			$s = $line;
			$s = str_replace('var ','$',$s);
			$s = str_replace('new Array','array',$s);
			$s = str_replace('field_','$field_',$s);
			$s = str_replace('$$','$',$s);
			$php_code .= $s;
		}
	}
	eval($php_code);
	
	for($y = 0; $y < $height; $y++){//{{{ 
 	// Y
	$var_bg_js = array();
	for($x = 0; $x < $width; $x++){//{{{ 
		// X
		$t = 32 * $y;
		$l = 32 * $x;
		$div_fields .= "\n";
		if($field_layer1[$y][$x]){
			$div_fields .= '<input id="field_'.$x.'x'.$y.'_layer_1" name="field_'.$x.'x'.$y.'_layer_1" value="'.$field_layer1[$y][$x].'" type="hidden">';
		}
		if($field_layer2[$y][$x]){
			$div_fields .= '<input id="field_'.$x.'x'.$y.'_layer_2" name="field_'.$x.'x'.$y.'_layer_2" value="'.$field_layer2[$y][$x].'" type="hidden">';
		}
		if($field_layer4[$y][$x]){
			$div_fields .= '<input id="field_'.$x.'x'.$y.'_layer_4" name="field_'.$x.'x'.$y.'_layer_4" value="'.$field_layer4[$y][$x].'" type="hidden">';
		}
		$bg = (($field_bg[$y][$x]) ? $field_bg[$y][$x] : $main_bg);
		$field_layer1[$y][$x] = ($field_layer1[$y][$x]) ? $field_layer1[$y][$x] : 'spacer.gif';
		$field_layer2[$y][$x] = ($field_layer2[$y][$x]) ? $field_layer2[$y][$x] : 'spacer.gif';
		$field_layer4[$y][$x] = ($field_layer4[$y][$x]) ? $field_layer4[$y][$x] : 'spacer.gif';
		if($x == $map_default_x && $y == $map_default_y){
			$field_type[$y][$x] = ($field_type[$y][$x]) ? $field_type[$y][$x] : 1;
			$ft = 'd';
		}
		else{
			$field_type[$y][$x] = ($field_type[$y][$x]) ? $field_type[$y][$x] : 1;
			$ft = ($field_type[$y][$x]) ? $field_type[$y][$x] : 1;
		}
		$bg_div_field .= '
		<span onClick="changeFieldType('.$x.','.$y.');" alt="'.$x.','.$y.'" title="'.$x.','.$y.'">
		<span id="bg_'.$x.'x'.$y.'" style="position: absolute; z-index: 1; top: '.$t.'px; left: '.$l.'px;">
			<img src="rpg/images/tiles/'.$bg.'">
		</span>
		<span id="bg_'.$x.'x'.$y.'_lay_1" style="position: absolute; z-index: 2; top: '.$t.'px; left: '.$l.'px;">
			<img src="rpg/images/tiles/'.$field_layer1[$y][$x].'">
		</span>
		<span id="bg_'.$x.'x'.$y.'_lay_2" style="position: absolute; z-index: 3; top: '.$t.'px; left: '.$l.'px;">
			<img src="rpg/images/tiles/'.$field_layer2[$y][$x].'">
		</span>
		<span id="bg_'.$x.'x'.$y.'_lay_4" style="position: absolute; z-index: 4; top: '.$t.'px; left: '.$l.'px;">
			<img src="rpg/images/tiles/'.$field_layer4[$y][$x].'">
		</span>
		<span id="span_ft_'.$x.'x'.$y.'" style="position: absolute; z-index: 5; top: '.$t.'px; left: '.$l.'px;">
			<img src="rpg/images/icon_ft'.$ft.'.gif">
		</span>
		</span>';
		$div_fields .= '<input id="ft_'.$x.'x'.$y.'" name="ft_'.$x.'x'.$y.'" value="'.$field_type[$y][$x].'" type="hidden">';
	}
 }
	?>
<script language="JavaScript" type="text/javascript">
	<? 
	echo (file_exists('rpg/maps/ft_defs/'.$f)) ? 'var pre_defined = true;' :'var pre_defined = false;';
	?>
		function debug_echo(debug_text){
			if(document.getElementById('div_debug')){
				debug = document.getElementById('div_debug');
				debug.innerHTML += '<br>'+debug_text;
			}
		}

//<!--
var value_bg = new Array()
var old_x = 1;
var old_y = 1;

function setValue(name,value){
        if(document.getElementById(name)){
                document.getElementById(name).value = value;
        }
        else{
                new_hidden = '<input type="hidden" id="'+name+'" name="'+name+'" value="'+value+'">';
                document.getElementById('div_fields').innerHTML += new_hidden;
        }
}

function changeFieldType(x,y){
	//document.getElementById('edit_x').innerHTML = x;
	//document.getElementById('edit_y').innerHTML = y;
	
	field_type = document.getElementById('sel_ft').value;
	if(field_type == 'D'){
		old_def_x = document.getElementById('default_x').value;
		old_def_y = document.getElementById('default_y').value;
		// Eventuell vorigen Default Punkt auf Feldtyp 1 setzen
		if(old_def_x != '' && old_def_x != ''){
			document.getElementById('span_ft_'+old_def_x+'x'+old_def_y).innerHTML = '<img src="rpg/images/icon_ft1.gif">';
			setValue('ft_'+old_def_x+'x'+old_def_y,1);
		}
		if(document.getElementById('span_ft_'+x+'x'+y)){
			document.getElementById('span_ft_'+x+'x'+y).innerHTML = '<img src="rpg/images/icon_ftd.gif">';
		}
		document.getElementById('default_x').value = x;
		document.getElementById('default_y').value = y;
	}
	else{
		if(document.getElementById('span_ft_'+x+'x'+y)){
			document.getElementById('span_ft_'+x+'x'+y).innerHTML = '<img src="rpg/images/icon_ft'+ field_type +'.gif">';
		}
		name = 'ft_'+x+'x'+y;
		setValue(name,field_type);
	}
}
//-->
</script>
<form action="" method="post">
<table width="100%">
 <tr> <!-- Edit Field -->
  <td colspan="2"><div id="div_debug"></div>
  <table width="100%">
   <tr>
    <td>
     <fieldset><legend>Feldtyp Pinsel</legend>
    	<select name="sel_ft" id="sel_ft" size="1">
      		<option value="1">Begehbar (Monster)</option>
      		<option value="2">Begehbar (keine Monster)</option>
      		<option value="3">Nicht begehbar</option>
      		<option value="D">Default Punkt setzen</option>
    	</select>
    </fieldset>
    </td>
	<td>
     <fieldset><legend>Default Punkt</legend>
	 	X:<input type="text" id="default_x" name="default_x" value="<? global $map_default_x; echo $map_default_x; ?>"><br>
	 	Y:<input type="text" id="default_y" name="default_y" value="<? global $map_default_y; echo $map_default_y; ?>">
    </fieldset>
	</td>
	<td>
		<input type="submit" value="Fertig.">
	</td>
   </tr>
  </table>
  </td>
 </tr>
 <tr>
  <td> <!-- backup ? -->
  <input type="checkbox" name="backup" value="yes"> Backup erstellen
  </td>
 </tr>
 <tr>
  <td> <!-- Karte -->
   <span id="big_map" style="position:static;">
   	<!--span id="layer_ft" style="position:absolute; z-index:5;"></span-->
   	<div id="layer_map" style="position:relative; display:inline; width: <? echo $width*32; ?>px; height: <? echo $height*32; ?>px;" align="center">
	<span id="bg_layer" style="position:relative; display:table; width: <? echo $width*32; ?>px; height: <? echo $height*32; ?>px;">
	<?
	global $bg_div_field; echo $bg_div_field;
	?>
	</span></div>
   </span>
  </td>
 </tr>
 <tr>
  <td colspan="2">
<input type="hidden" id="stage" name="stage" value="edit_ft2">
<input type="hidden" id="name" name="name" value="<? global $name; echo $name; ?>">
<input type="hidden" id="file_name" name="file_name" value="<? echo $_POST['file_name']; ?>">
<input type="hidden" id="main_bg" name="main_bg" value="<? global $main_bg; echo $main_bg; ?>">
<input type="hidden" id="width" name="width" value="<? global $width; echo $width; ?>">
<input type="hidden" id="height" name="height" value="<? global $height; echo $height; ?>">
<div id="div_fields"><? global $div_fields; echo $div_fields; ?>;</div>
</form>
  </td>
 </tr>
</table>
<? //}}}
}
elseif($_POST['stage'] == 'edit_ft2'){
/********************************************************************************
 *													Feldtypen speichern
 *******************************************************************************/
	echo '<form action="" method="post"><input type="submit" value="Weiter"></form>';
 
	$file_name = $_POST['file_name'];        
	$file_name = str_replace('.js','',$file_name);
	$width = $_POST['width'];        
	$height = $_POST['height'];        
	
	$def_x = (is_numeric($_POST['default_x'])) ? $_POST['default_x'] : floor($width/2);
	$def_y = (is_numeric($_POST['default_y'])) ? $_POST['default_y'] : floor($height/2);
		
	// Backup erstellen
	if($_POST['backup'] == 'yes'){
		echo "<br>backup wurde ausgef&uuml;hrt.";
		$s = 'rpg/maps/ft_defs/'.$file_name.'_ft.js';
		if(file_exists($s)){
			$counter = 0;
			do{
				$bak_file = 'rpg/maps/ft_defs/'.$file_name.'_ft.bak'.$counter;
				$counter++;
			}while(file_exists($bak_file));
			rename($s,$bak_file);
		}
	}
	
	$output .= "\nvar field_type = new Array();";
    $output .= "\n// field_type[y] = new Array(x1,x2,x3,..xN)";
	for($i = 0; $i < $height; $i++){
		//$field_bg = array();
		$field_type = array();
		for($j = 0; $j < $width; $j++){
           	$s1 = 'ft_'.$j.'x'.$i;
        	// Feldtyp
			if($_POST[$s1]){
				$field_type[$j] = $_POST[$s1];
			}
			else{
				$field_type[$j] = 1;
			}

		}
		$output .= "\n\tfield_type[$i] = new Array(".(implode(',',$field_type)).");";
	}

	echo str_replace("\n","<br>",$output);
	$file_name = 'rpg/maps/ft_defs/'.$file_name.'_ft.js';
	$fp = fopen("$file_name",'w');
	$date = date("l dS of F Y H:i:s");
	if ($fp){
		flock($fp,2);
		echo "<br>'$file_name' wurde f&uuml;r andere User gesperrt.";
		fputs ($fp, "\n// Feldtyp Definition");
		fputs ($fp, "\n// Editor: {$userdata['username']}");
		fputs ($fp, "\n// Datum: $date");
		fputs ($fp, "\n// * 1: Begehbar (Monster)");
		fputs ($fp, "\n// * 2: Begehbar (keine Monster)");
		fputs ($fp, "\n// * 3: Nicht Begehbar");
		fputs ($fp, "\n\n// Default Punkt");
		fputs ($fp, "\nvar map_default_x = $def_x;");
		fputs ($fp, "\nvar map_default_y = $def_y;");
		fputs ($fp, "$output");
		echo "<br>Der Code wurde in '$file_name' gespeichert.";
		flock($fp,3);
		echo "<br>'$file_name' wurde wieder entsperrt.";
		fclose($fp);
	}
	else{
		echo "<br>Datei konnte nicht zum  Schreiben ge&ouml;ffnet werden";
	}
}

?>
