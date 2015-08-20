
<script language="JavaScript" type="text/javascript">
    function showImage(){
        var tile_set = document.getElementById('tileset').value;
        var file = document.getElementById("main_bg").value;
        document.getElementById("main_bg_img").innerHTML = '<img src="rpg/images/tiles/'+tile_set+'/'+file+'">';
    }

    function clickTile(tile_name){
        //tile_set = document.getElementById('tileset').value;
        document.getElementById('main_bg').value = tile_name;
        showImage();
    }

    function switchTileSet(){
        var newTS = document.getElementById('tileset').value;
        document.getElementById('iframe_tileset').src = 'rpg/images/tiles/'+newTS+'/'+newTS+'.html';
    }
</script>
<fieldset>
    <legend>Neue Karte erstellen</legend>
    <p>Gib bitte Name, H&ouml;he und Breite der neuen Karte an:</p>
    <form action="" method="post">
        <input type="hidden" id="stage" name="stage" value="edit_map">
        <p>Name:<input type="text" id="name" name="name" maxlength="50" size="30"></p>
        <p>Dateiname:<input type="text" id="file_name" name="file_name" maxlength="20" size="25"> <i>(kurz, keine Umlaute, Sonderzeichen oder Leerzeichen)</i></p>
        <p>Tileset:
            <select name="tileset" id="tileset" onChange="switchTileSet();">
                <?php foreach($tilesets as $tileset){ ?>
                    <option value="<?=$tileset?>"><?=$tileset?></option>
                <?php } ?>
            </select>
            <br><iframe id="iframe_tileset" src="" width="<? echo (32+8)*8; ?>" height="<? echo (32+8)*6; ?>"></iframe>
        </p>
        <p>Haupt-Hintergrund:<input type="text" name="main_bg" id="main_bg" onChange="showImage();"><span id="main_bg_img">&nbsp;</span> <i>(in den tiles anklicken)</i></p>
        <p>H&ouml;he:<input type="text" id="height" name="height" maxlength="3" size="5" value="20"></p>
        <p>Breite:<input type="text" id="width" name="width" maxlength="3" size="5" value="20"></p>
        <input type="submit" value="Weiter">
    </form>
</fieldset>
<hr>
<fieldset><legend>Alte Karte bearbeiten (Hintergrund)</legend>
    <form action="" method="post">
        <input type="hidden" id="stage" name="stage" value="edit_map">
        <input type="hidden" id="edit" name="edit" value="1">
        <input type="hidden" id="name" name="name" value="1">
        <input type="hidden" id="width" name="width" value="1">
        <input type="hidden" id="height" name="height" value="1">
        <select id="file_name" name="file_name">
            <?
            $directory = "rpg/maps";
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
                    elseif($user_202 &&  (strpos($file,'[p]') === false && strpos($file,'[l]') === false && strpos($file,'[t]') === false)){
                    }
                    elseif($user_1075 && (strpos($file,'[i]') === false)){
                    }
                    else{
                        $file_array[] = $file;
                    }
                }
            }
            chdir("../../");
            $d->close();

            natcasesort($file_array);
            foreach($file_array as $file){
                echo "<option value=\"$file\">$file</option>";
            }

            ?>
        </select>
        <input type="submit" value="Weiter">
    </form>
</fieldset>
<hr>
<fieldset><legend>Alte Karte bearbeiten (Feldtypen)</legend>
    <form action="" method="post">
        <input type="hidden" id="stage" name="stage" value="edit_ft">
        <select id="file_name" name="file_name">
            <?

            foreach($file_array as $file){
                echo "<option value=\"$file\">$file</option>";
            }
            ?>
        </select>
        <input type="submit" value="Weiter">
    </form>
</fieldset>