<script language="JavaScript" type="text/javascript">
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
                                foreach($availableTilesets as $t){
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