<?

define('IN_RPG', true);
include('includes/rpg_config.php');

?>
<script language="JavaScript" type="text/javascript">
// <!--
function changeTrans(){
	if(document.getElementById('trans_loc').value == 'clr'){
		document.getElementById('trans').disabled = 0;
	}
	else{
		document.getElementById('trans').disabled = 1;
	}
}
// -->
</script>
<?
function hex2int($hex) {
        return array( 'r' => hexdec(substr($hex, 0, 2)), // 1st pair of digits
                      'g' => hexdec(substr($hex, 2, 2)), // 2nd pair
                      'b' => hexdec(substr($hex, 4, 2))  // 3rd pair
                    );
}

if(!empty($_REQUEST['file'])){
	$srcSet = 'export/static/images/tilesets/'.$_REQUEST['file'];
	$prefix = $_REQUEST['prefix'];
	$TileSize = ($_REQUEST['size']) ? $_REQUEST['size'] : 32;
	
	$html_file_dir = 'export/static/images/tiles/'.$prefix.'/';
	// Verzeichnis erstellen falls noch nicht vorhanden
	if(!is_dir($html_file_dir)){
		mkdir($html_file_dir,0777);
	}
	
	
	$imgSet = ImageCreateFromPNG($srcSet) or die ('failed to create TileSet');
	$setW = ImageSX($imgSet);
	$setH = ImageSY($imgSet);
	$default = false;
	switch($_POST['trans_loc']){
		case 'lu':	
			$tx = 0;
			$ty = 0;	
			echo '<br>Farbe von Links Oben als Transparenz.';
			break;
		case 'ld':	
			$tx = 0;
			$ty = $setH - 1;	
			echo '<br>Farbe von Links Unten als Transparenz.';
			break;
		case 'ru':		
			$tx = $setW - 1;
			$ty = 0;
			echo '<br>Farbe von Rechts Oben als Transparenz.';
			break;
		case 'rd':		
			$tx = $setW - 1;
			$ty = $setH - 1;
			echo '<br>Farbe von Rechts Unten als Transparenz.';
			break;
		case 'clr':
		default:
			$transparentColor = ($_REQUEST['trans']) ? hex2int($_REQUEST['trans']) : hex2int('FFFFFF');
			$default = true;
			$trans = ImageColorAllocate($imgSet, $transparentColor['r'], $transparentColor['g'], $transparentColor['b']); 
			$none = ImageColorTransparent($imgSet,$trans);
			$transC = $transparentColor;
			echo '<br>Farbe #'.($_REQUEST['trans'] ? $_REQUEST['trans'] : 'FFFFFF' ).' als Transparenz.';
			break;
	}
	
	if(!$default){
		$none = ImageColorAt($imgSet,$tx,$ty);
		$transparentColor = ImageColorsForIndex($imgSet, $none);
		echo "<br>$transparentColor, $tx, $ty";
		echo "<br>$transparentColor[red], $transparentColor[green]x, $transparentColor[blue]";
		$trans = ImageColorAllocate($imgSet, $transparentColor['red'], $transparentColor['green'], $transparentColor['blue']); 
		$none = ImageColorTransparent($imgSet,$trans);
		$transC = $transparentColor;
		$transC['r'] = $transC['red'];
		$transC['g'] = $transC['green'];
		$transC['b'] = $transC['blue'];
	}
	//$transC = hex2int('669999');
	/*if($transC['red']){
		echo "<br>red => r<br>";
		$transC['r'] = $transC['red'];
		$transC['g'] = $transC['green'];
		$transC['b'] = $transC['blue'];
	}
	$trans = ImageColorAllocate($imgSet, $transC['r'], $transC['g'], $transC['b']); 
	$none = ImageColorTransparent($imgSet,$trans);
	*/
	echo "<br>Transparente Farbe:<br>R:{$transC['r']},G:{$transC['g']},B:{$transC['b']}";
	//echo "<br>Transparente Farbe:<br>R:{$transC['red']},G:{$transC['green']},B:{$transC['blue']}";
	//$trans = ImageColorAllocate($imgSet, $transparentColor[r], $transparentColor[g], $transparentColor[b]);
	//$none = ImageColorTransparent($imgSet,$trans);
	//$none = ImageColorTransparent($imgSet,$transparentColor);
	
	//if($setW % $TileSize != 0)
	//	die('')
	
	// Schattenfarbe
	if($_POST['shadow_clr'] != "" || ($_POST['shadow_row'] != "" && $_POST['shadow_col'] != "")){
		if($_POST['shadow_clr'] != ""){
			$shadow = hex2int($_POST['shadow_clr']);
			$shadowColor = ImageColorsForIndex($imgSet, $shadow);
			//echo "<br>$shadowColor[red], $shadowColor[green]x, $shadowColor[blue]";
		}
		else{
			$s = ImageColorAt($imgSet, ($_POST['shadow_col']*32)+1, ($_POST['shadow_row']*32)+1);
			$shadowColor = ImageColorsForIndex($imgSet, $none);
			//echo "<br>$shadowColor, $_POST['shadow_col'], $_POST['shadow_row']";
			//echo "<br>$shadowColor[red], $shadowColor[green]x, $shadowColor[blue]";	
		}
		$shdw = ImageColorAllocateAlpha($imgSet, $shadowColor['red'], $shadowColor['green'], $shadowColor['blue'], 75);
		$bShadow = true;
	}
	
	$numCols = $setW / $TileSize;
	$numRows = $setH / $TileSize;
	
	$tileX = 0;
	$tileY = 0;
	$counter = 0;
	$htmlImages = '<table bgcolor="#FF00CC">';
	for($i = 1; $i <= $numRows; $i++){
		$tileY += ($i > 1) ? $TileSize : 0; 
		$htmlImages .= "\n<tr>";
		for($j = 1; $j <= $numCols; $j++){
			//do{
				$counter++;
			//}while(file_exists($pathTiles.$prefix.$counter.'.png'));
			$file_name = $counter.'.png';
			$full_file_name = $html_file_dir.$file_name;
			
			$tileX += ($j > 1) ? $TileSize : 0;
			$newTile = ImageCreate($TileSize,$TileSize);

			$trans = ImageColorAllocate($newTile, $transC['r'], $transC['g'], $transC['b']); 
			$none = ImageColorTransparent($newTile,$trans);
			
			if($bShadow){
				$shdw = ImageColorAllocateAlpha($newTile, $shadowColor['red'], $shadowColor['green'], $shadowColor['blue'], 63); 
			}
			
			ImageCopyMerge($newTile, $imgSet, 0, 0, $tileX, $tileY, $TileSize, $TileSize, 100);
			ImagePNG($newTile,$full_file_name);
			$htmlImages .= '<td><img src="'.$file_name.'" title="'.$file_name.'" alt="'.$file_name.'" onClick="top.clickTile(\''.$file_name.'\')"></td>';
		}
		$htmlImages .= '</tr>';
		$tileX = 0;
	}
	$htmlImages .= "\n</table>";
	
	// Bilder in HTMl Compilation speichern
	$html_file_name = $html_file_dir.$prefix.'.html';
	$fp = fopen("$html_file_name",'w');
	if ($fp){
		flock($fp,2);
		echo "<br>'$html_file_name' wurde f�r andere User gesperrt.";
		fputs ($fp, "\n<!-- Datei: $html_file_name -->");
		fputs ($fp, "\n<!-- Liste der Bilder des Tilesets: $prefix -->");
		fputs ($fp, "$htmlImages");
		echo "<br>Der Code wurde in '$html_file_name' gespeichert.";
		flock($fp,3);
		echo "<br>'$html_file_name' wurde wieder entsperrt.";
		fclose($fp);
	}
	else{
		echo "<br>Datei konnte nicht zum  Schreiben ge�ffnet werden";
	}
	
	echo "<br><b>New Tiles created from Tileset $srcSet</b>
	<br>
	<br><iframe src=\"$html_file_name\" height=\"300\" width=\"150\"></iframe>
	</BODY></HTML>";
	
}
else{
	?>
<form name="sendTilesSet" method="post" action="">
  <br>Datei: <input type="text" name="file" id="file"> (aus dem Ordner '/images/tilesets')
  <br>Tileset Name: <input type="text" name="prefix" id="prefix" value="-"> <i>(wird dann in gleichnamigen Ordner gespeichert) (keine Umlaute oder Sonderzeichen verwenden!)</i>
  <br>Tile Size/ Gr&ouml;&szlig;e der Felder: <input type="text" name="size" id="size" value="32">
  <br><fieldset>
		<legend>Schattenfarbe:</legend>
  	Schattenfarbe: <input type="text" name="shadow_clr" id="shadow_clr" size="7" maxlength="6" value=""><br>
  	(keine Schatten? => leer lassen)<br />
		oder Tile das in der Schattenfarbe ist:
		Reihe (0-y): <input type="text" name="shadow_row" size="3" value="" /><br />
		Spalte (0-x): <input type="text" name="shadow_col" size="3" value="" /><br />
  </fieldset>
  <br><fieldset><legend>Transparenz:</legend>
  Transparente Farbe: <input type="text" name="trans" id="trans" value="FFFFFF" size="8" maxlength="6"><br>
  oder Ort an dem die Transparente Farbe ist: <br>
  <input type="radio" id="trans_loc" name="trans_loc" value="clr" onChange="changeTrans();" checked>
  über Farbwert w&auml;hlen <br>
  <input type="radio" id="trans_loc" name="trans_loc" value="lu" onChange="changeTrans();">
  Links Oben <br>
  <input type="radio" id="trans_loc" name="trans_loc" value="ru" onChange="changeTrans();">
  Rechts Oben<br>
  <input type="radio" id="trans_loc" name="trans_loc" value="ld" onChange="changeTrans();">
  Links Unten<br>
  <input type="radio" id="trans_loc" name="trans_loc" value="rd" onChange="changeTrans();">
  Rechts Unten 
  </fieldset>
  <br><input type="submit" value="Weiter">
</form>
<?
}


