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