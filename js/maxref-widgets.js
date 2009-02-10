function change_display(display, number) {
	var thediv = document.getElementById('pages_div' + number);
	thediv.style.display = "none";
	
	var leveldiv = document.getElementById('levels_div' + number);
	leveldiv.style.display = "none";
	
	var hideemptydiv = document.getElementById('hideempty_div' + number);
	hideemptydiv.style.display = "none";

	if (display.match(/pages|categories\-\d+/)) {
		thediv.style.display = "block";
		leveldiv.style.display = "block";
	}
	
	if (display.match(/categories\-\d+/)) {
		hideemptydiv.style.display = "block";
	}
}

function titlelinktoggle(number) {
	var status = document.getElementById('mref_widget_' + number + '_titlelink').checked;
	
	if (status == true) {
		document.getElementById('mref_widget_' + number + '_titlelinkdiv').style.display = "block";
		document.getElementById('mref_widget_' + number + '_catrsslinks').checked = false;
	} else {
		document.getElementById('mref_widget_' + number + '_titlelinkdiv').style.display = "none";
	}
}

function showitemdates(number) {
	var status = document.getElementById('mref_widget_' + number + '_itemdates').checked;
	
	if (status == true) {
		document.getElementById('mref_widget_' + number + '_itemdatesY').style.display = "block";
	} else {
		document.getElementById('mref_widget_' + number + '_itemdatesY').style.display = "none";
	}
}