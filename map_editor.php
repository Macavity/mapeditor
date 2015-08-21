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


$userId = 104;
$user = $users[$userId];


define('IN_RPG_ADMIN', true);

// List of possible Tilesets

$availableTilesets = array();

$directory = "images/tiles";
$d = dir($directory);
chdir($directory);
while($file = $d->read()) {
	if(is_dir($file) && $file != '.' && $file != '..'){
		$availableTilesets[] = $file;
	}
}
chdir("../../");
$d->close();

natcasesort($availableTilesets);


/*
 * Available Maps for this user
 */
$directory = "maps";
$d = dir($directory);
chdir($directory);

$availableMaps = array();
while($file = $d->read()){
	if( (is_file($file)) && strpos ($file, ".js") && strpos ($file, "_ft") === false ){

		if($user['rank'] == 'intern' && strpos($file, $user['prefix']) === false){
			continue;
		}

		$availableMaps[] = $file;
	}
}
chdir("../");
$d->close();

natcasesort($availableMaps);
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
	$edit_existing_map = ($_POST['edit'] == 1) ? true : false;
	$editFileName = empty($_POST['file_name']) ? false : 'maps/'.$_POST['file_name'];


	/**
	 * Edit Map
	 */
	if( $editFileName && file_exists($editFileName)){

		$mapSource = file_get_contents($editFileName);

		$mapSource = preg_replace("/var name = '([^']+)'/", "\$name = '$1';", $mapSource);

		$mapSource = str_replace('var ', '$', $mapSource);
		$mapSource = str_replace('new Array','array',$mapSource);
		$mapSource = str_replace('field_', '$field_', $mapSource);
		$mapSource = str_replace('$$','$',$mapSource);

		eval($mapSource);
	}
	else {
		echo "<h3>Achtung: Map $editFileName wurde nicht gefunden.</h3>";
	}
	$tiles_dir = '/images/tiles/';
	$stdTS = empty($_POST['tileset']) ? '001-Grassland01' : $_POST['tileset'];

	if($edit_existing_map){
	}
	else {
		$name = $_POST['name'];
		$main_bg = $_POST['tileset'].'/'.$_POST['main_bg'];
		$width = $_POST['width'];
		$height = $_POST['height'];
	}

	include('views/edit_map.php');

}
elseif($stage == "2"){//{{{
	/**
	 * Save Map file
	 */
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
elseif($_POST['stage'] == 'edit_ft'){
	/**
	 * Edit Field types of Map
	 */
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
	include("views/edit_fieldtype.php");
}
elseif($_POST['stage'] == 'edit_ft2'){
	/**
	 * Save field types
	 */
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


