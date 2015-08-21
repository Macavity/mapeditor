// JavaScript Document
// Common Functions

// F�llt die vorher mit generateFields() erstellte Tabelle mit den passenden Hintergrundbildern.
function setFields(width,height,field_bg,field_layer1,field_layer2,field_layer4){
				
	for(y = 0; y < height; y++){			// Y
		for(x = 0; x < width; x++){		// X
			s = 'bg_'+x+'x'+y;
			s2 = 'span_bg_'+x+'x'+y;
			if(document.getElementById(s) && document.getElementById(s2)){
				td = document.getElementById(s);
				span = document.getElementById(s2);
				// BG
				if(field_bg[y] && field_bg[y][x]){
					name = 'field_'+x+'x'+y+'_bg';
    				setValue(name,field_bg[y][x]);
							
					td.style.backgroundImage = 'url(rpg/images/tiles/'+field_bg[y][x]+')';
					td.style.backgroundRepeat = 'repeat';
					td.style.backgroundColor = "";
				}
				// Layer 1 
				if(field_layer1[y] && field_layer1[y][x]){
					name = 'field_'+x+'x'+y+'_layer_1';
					setValue(name,field_layer1[y][x]);
					span.innerHTML += '<span id="bg_'+x+'x'+y+'_lay_1" style="position:absolute; z-index:1"><img src="rpg/images/tiles/'+field_layer1[y][x]+'"></span>';
				}
				// Layer 2 
				if(field_layer2[y] && field_layer2[y][x]){
					name = 'field_'+x+'x'+y+'_layer_2';
					setValue(name,field_layer2[y][x]);
					span.innerHTML += '<span id="bg_'+x+'x'+y+'_lay_2" style="position:absolute; z-index:2"><img src="rpg/images/tiles/'+field_layer2[y][x]+'"></span>';
				}
				// Layer 4
				if(field_layer4[y] && field_layer4[y][x]){
					name = 'field_'+x+'x'+y+'_layer_4';
					setValue(name,field_layer4[y][x]);
					span.innerHTML += '<span id="bg_'+x+'x'+y+'_lay_4" style="position:absolute; z-index:4"><img src="rpg/images/tiles/'+field_layer4[y][x]+'"></span>';
				}
			} 
		} // END: for x < width
	} // END: for y < height
}

function init(){
	generateFields(width,height,main_bg);
	document.getElementById('name').value = name;
	document.getElementById('main_bg').value = main_bg;
	document.getElementById('width').value = width;
	document.getElementById('height').value = height;
	//setFields(width,height,field_bg,field_layer1,field_layer2,field_layer4);
}

// Erstellt eine komplette HTML-Tabelle innerhalb des Elementes mit der ID 'big_map'
function generateFields(width,height,main_bg){
	//debug = document.getElementById('div_debug');
	var bigm = '';
	if(document.getElementById('big_map')){
		var map = document.getElementById('big_map');
		map.innerHTML = '';
		var bg = 'rpg/images/tiles/'+main_bg;
		for(var y = 0; y < height; y++){			// Y
			bigm += "\n"+'<tr>';
			//debug.innerHTML += '<br>';
			for(x = 0; x < width; x++){		// X
				//debug.innerHTML += '#'+x+','+y;
				bigm += '<td width="32" height="32" id="bg_'+x+'x'+y+'" name="bg_'+x+'x'+y+'" align="left" valign="top" background="'+bg+'" onClick="changeField('+x+','+y+')" style="border-width:0px" title="'+x+'x'+y+'" alt="'+x+'x'+y+'"><span id="span_bg_'+x+'x'+y+'" style="position:relative;">&nbsp;</span></td>';
			}
			bigm += '</tr>';
		}
	}
	bigm = '<table id="tbl_map" height="'+(32 * height)+'" width="'+(32 * width)+'" cellpadding="0" cellspacing="0" border="0">'+bigm+'</table>';
	map.innerHTML = bigm;
	//alert('Tabelle erstellt.');
}

function setValue(name,value){
	if(document.getElementById(name)){
		document.getElementById(name).value = value;
	}
	else{
		var new_hidden = '<input type="hidden" id="'+name+'" name="'+name+'" value="'+value+'">';
		document.getElementById('div_fields').innerHTML += new_hidden;
	}
}

function changeField(x,y){
	document.getElementById('edit_x').innerHTML = x;
	document.getElementById('edit_y').innerHTML = y;

	var tile_set = document.getElementById('sel_tileset').value;
	var tile = document.getElementById('sel_tile').value;

	var edit_type = document.getElementById('edit_et').value;
	var value = tile;

	if(edit_type == 'BG'){
		//Wert speichern
		var name = 'field_'+x+'x'+y+'_bg';
		setValue(name,value);
		document.getElementById('bg_'+x+'x'+y).innerHTML = '<img src="/images/tiles/'+value+'">';
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
	var x = document.getElementById('edit_x').innerHTML;
	var y = document.getElementById('edit_y').innerHTML;

	document.getElementById('bg_'+x+'x'+y).style.backgroundImage = 'url(/images/tiles/'+(document.getElementById('edit_bg').value)+');';

	//Wert speichern
	var value = document.getElementById('edit_bg').value;
	var name = 'field_'+x+'x'+y+'_bg';
	setValue(name,value);

}

function changeLayer(num,value){
	x = document.getElementById('edit_x').innerHTML;
	y = document.getElementById('edit_y').innerHTML;

	//Wert speichern
	var name = 'field_'+x+'x'+y+'_layer_'+num;
	setValue(name,value);

	if(document.getElementById('bg_'+x+'x'+y+'_lay_'+num)){
		if(value != ''){
			document.getElementById('bg_'+x+'x'+y+'_lay_'+num).innerHTML = '<img src="/images/tiles/'+value+'">';
		}
		else{
			document.getElementById('bg_'+x+'x'+y+'_lay_'+num).innerHTML = '';
		}
		//document.getElementById('div_debug').innerHTML = '<img src="/images/'+value+'">';
	}
	else{
		if(value != ''){
			var new_div = '<span id="bg_'+x+'x'+y+'_lay_'+num+'" style="position:absolute; z-index:'+num+'"><img src="/images/tiles/'+value+'"></span>';
			document.getElementById('span_bg_'+x+'x'+y).innerHTML += new_div;
		}
		else{
			document.getElementById('bg_'+x+'x'+y+'_lay_'+num).innerHTML = '';
		}
		//document.getElementById('div_debug').innerHTML = 'neu, '+num+','+new_div;
	}
}

function switchTileSet(){
	var newTS = document.getElementById('sel_tileset').value;
	document.getElementById('iframe_tileset').src = '/images/tiles/'+newTS+'/'+newTS+'.html';
}

function clickTile(tile_name){
	var tile_set = document.getElementById('sel_tileset').value;
	var value = tile_set+'/'+tile_name;
	document.getElementById('sel_tile').value = value;
	document.getElementById('img_sel_tile').innerHTML = '<img src="/images/tiles/'+value+'">';
}