<?php global $name, $height, $width, $main_bg, $field_bg, $field_layer1, $field_layer2, $field_layer4 ?>
<script language="JavaScript" type="text/javascript" src="/includes/js_rpg_adm.js"></script>

<script language="JavaScript" type="text/javascript">
    var edit_existing_map = <?php if($edit_existing_map){ echo 'true'; } else{ echo 'false'; } ?>;
    var value_bg = [];
    var old_x = 1;
    var old_y = 1;
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
                <div id="big_map" style="min-width:640px; min-height:640px; overflow:scroll;">
                    <div id="tbl_map" style="position:relative; display:inline; width: <? echo $width*32; ?>px; height: <? echo $height*32; ?>px;" align="center">
	                    <span id="bg_layer" style="position:relative; display:table; width: <? echo $width*32; ?>px; height: <? echo $height*32; ?>px;">
                        <?php
                        for($y = 0; $y < $height; $y++) {
                            // Y
                            for($x = 0; $x < $width; $x++) {
                                // X
                                $t = 32 * $y;
                                $l = 32 * $x;
                                $bg = isset($field_bg[$y][$x]) ? $field_bg[$y][$x] : $main_bg;

                                $field_layer1[$y][$x] = isset($field_layer1[$y][$x]) ? $field_layer1[$y][$x] : 'spacer.gif';
                                $field_layer2[$y][$x] = isset($field_layer2[$y][$x]) ? $field_layer2[$y][$x] : 'spacer.gif';
                                $field_layer4[$y][$x] = isset($field_layer4[$y][$x]) ? $field_layer4[$y][$x] : 'spacer.gif';

                                ?>
                                <span onClick="changeField(<?=$x?>,<?=$y?>);" title="<?=$x?>,<?=$y?>">
                                    <span id="bg_<?=$x?>x<?=$y?>" style="position: absolute; z-index: 1; top: <?=$t?>px; left: <?=$l?>px;">
                                        <img src="/images/tiles/<?=$bg?>">
                                    </span>
                                    <span id="bg_<?=$x?>x<?=$y?>_lay_1" style="position: absolute; z-index: 2; top: <?=$t?>px; left: <?=$l?>px;">
                                        <img src="/images/tiles/<?=$field_layer1[$y][$x]?>">
                                    </span>
                                    <span id="bg_<?=$x?>x<?=$y?>_lay_2" style="position: absolute; z-index: 3; top: <?=$t?>px; left: <?=$l?>px;">
                                        <img src="/images/tiles/<?=$field_layer2[$y][$x]?>">
                                    </span>
                                    <span id="bg_<?=$x?>x<?=$y?>_lay_4" style="position: absolute; z-index: 4; top: <?=$t?>px; left: <?=$l?>px;">
                                        <img src="/images/tiles/<?=$field_layer4[$y][$x]?>">
                                    </span>
                                </span>
                                <?
                            }
                        } ?>
	                    </span>
                    </div>
                </div>
            </td>
            <td width="30%"> <!-- Tiles -->
                <table>
                    <tr>
                        <td>
                            <select id="sel_tileset" onChange="switchTileSet();">
                                <? foreach($availableTilesets as $t){ ?>
                                <option value="<?=$t?>" <?php if($t == $stdTS) { echo 'selected'; } ?>><?=$t?></option>
                                <? } ?>
                            </select>
                            <span id="tiles_from_this_tileset">&nbsp;</span>
                        </td>
                    </tr>
                    <tr>
                        <td id="td_tileset" valign="top">
                            <iframe id="iframe_tileset" width="100%" height="600" src="<? echo '/images/tiles/'.$stdTS.'/'.$stdTS.'.html'; ?>"></iframe>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                Kartenname: <input type="text" id="name" name="name" value="<?  echo $name; ?>">
                <input type="hidden" id="file_name" name="file_name" value="<? echo $_POST['file_name']; ?>">
                <input type="hidden" id="stage" name="stage" value="2">
                <input type="hidden" id="main_bg" name="main_bg" value="<? global $main_bg; echo $main_bg; ?>">
                <input type="hidden" id="width" name="width" value="<? global $width; echo $width; ?>">
                <input type="hidden" id="height" name="height" value="<? global $height; echo $height; ?>">
                <input type="hidden" id="edit" name="edit" value="<? echo $_POST['edit']; ?>">
                <div id="div_fields">
                    <?php for($y = 0; $y < $height; $y++) {
                        // Y
                        for($x = 0; $x < $width; $x++){
                            // X
                            $t = 32 * $y;
                            $l = 32 * $x;
                            $bg = (($field_bg[$y][$x]) ? $field_bg[$y][$x] : $main_bg);

                            if($field_layer1[$y][$x]){
                                ?><input id="field_<?=$x?>x<?=$y?>_layer_1" name="field_<?=$x?>x<?=$y?>_layer_1" value="<?=$field_layer1[$y][$x]?>" type="hidden"><?
                            }
                            if($field_layer2[$y][$x]){
                                ?><input id="field_<?=$x?>x<?=$y?>_layer_2" name="field_<?=$x?>x<?=$y?>_layer_2" value="<?=$field_layer2[$y][$x]?>" type="hidden"><?
                            }
                            if($field_layer4[$y][$x]){
                                ?><input id="field_<?=$x?>x<?=$y?>_layer_4" name="field_<?=$x?>x<?=$y?>_layer_4" value="<?=$field_layer4[$y][$x]?>" type="hidden"><?
                            }


                            ?><input id="field_<?=$x?>x<?=$y?>_bg" name="field_<?=$x?>x<?=$y?>_bg" value="<?=$bg?>" type="hidden"><?
                        }
                    } ?>
                </div>

                <input type="submit" value="Fertig.">
</form>
<div id="div_debug"></div>