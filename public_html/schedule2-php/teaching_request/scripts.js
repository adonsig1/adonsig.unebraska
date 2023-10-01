function moveOption(fromId, toId, isTargetSorted) {
	var fromSelect = document.getElementById(fromId);
	var toSelect = document.getElementById(toId);

	if (fromSelect.selectedIndex < 0) {
		return;
	}
	
	var fromSelectedIndex = fromSelect.selectedIndex;
	var selectedOption = fromSelect.options[fromSelect.selectedIndex];

	try {
		toSelect.add(new Option(selectedOption.text, selectedOption.value), null);
	} catch (ex) {
		toSelect.add(new Option(selectedOption.text, selectedOption.value));
	}
	fromSelect.remove(fromSelectedIndex);
	
	if (isTargetSorted) {
		var size = toSelect.options.length;
		var a = new Array(size);
		for (var i=0; i < size; i++) {
			var opt = toSelect.options[i];
			a[i] = new Option(opt.text, opt.value);
		}
		var addedOpt = a[a.length - 1];
		var toSelectedIndex = size - 1;
		
		a.sort(compareOptionText);

		for (i=0; i<size; i++) {
			var opt = a[i];
			toSelect.options[i] = opt;
			if (opt == addedOpt) {
				toSelectedIndex = i;
			}
		}
		toSelect.selectedIndex = toSelectedIndex;
	} else {
		toSelect.selectedIndex = toSelect.options.length - 1;
	}

	if (fromSelectedIndex < fromSelect.options.length) {
		fromSelect.selectedIndex = fromSelectedIndex;
	} else if (fromSelectedIndex > 0) {
		fromSelect.selectedIndex = fromSelectedIndex - 1;
	} // The final alternative is that the list is empty
}

function compareOptionText(opt1, opt2) {
	var x = opt1.text.toLowerCase();
	var y = opt2.text.toLowerCase();
	if (x > y) {
		return 1;
	} else if (x < y) {
		return -1;
	} else {
		return 0;
	}
}

function addTimes(id) {
	var startSelect = document.getElementById(id + "_start");
	var endSelect = document.getElementById(id + "_end"); 
	var daysSelect = document.getElementById(id + "_days"); 
	var listSelect = document.getElementById(id);
	
	if (startSelect.selectedIndex >= endSelect.selectedIndex) {
		alert("The end time must be after the start time.");
		return;
	}
	
	var str = startSelect.options[startSelect.selectedIndex].text + " - "
		+ endSelect.options[endSelect.selectedIndex].text + " " 
		+ daysSelect.options[daysSelect.selectedIndex].text;
	
	for (var i=0; i<listSelect.options.length; i++) {
		if (listSelect.options[i].text == str) {
			alert("The range \"" + str + "\" has already been chosen.");
			return;
		}
	}
		
	try {
		listSelect.add(new Option(str), null);
	} catch (ex) {
		listSelect.add(new Option(str));
	}
}

function removeTimes(id) {
	var listSelect = document.getElementById(id);
	var k = listSelect.selectedIndex;
	if (k < 0) {
		alert("Please select a time range to remove from the list");
		return;
	}
	listSelect.remove(k);
}

function moveUp(id) {
	var sel = document.getElementById(id);
	var k = sel.selectedIndex;
	
	if (k < 1) {
		return;
	}
	
	var oldOpt = sel.options[k];
	var newOpt = new Option(oldOpt.text, oldOpt.value);
	sel.remove(k);
	try {
		sel.add(newOpt, sel.options[k-1]);
	} catch (ex) {
		sel.add(newOpt, k-1);
	}
	
	sel.selectedIndex = k-1;
}

function moveDown(id) {
	var sel = document.getElementById(id);
	var k = sel.selectedIndex;
	var length = sel.options.length;
	
	if (k < 0 || k >= length-1) {
		return;
	}
	
	var oldOpt = sel.options[k];
	var newOpt = new Option(oldOpt.text, oldOpt.value);
	sel.remove(k);
	try {
		sel.add(newOpt, sel.options[k+1]);
	} catch (ex) {
		sel.add(newOpt, k+1);
	}
	
	sel.selectedIndex = k+1;
}

function doSubmit() {
	selectAll(document.main_form.elements["good_courses[]"]);
	selectAll(document.main_form.elements["bad_courses[]"]);
	selectAll(document.main_form.elements["good_times[]"]);
	selectAll(document.main_form.elements["bad_times[]"]);
	selectAll(document.main_form.elements["bad_classes[]"]);
	document.main_form.submit();
}

function selectAll(sel) {
	// sel.multiple = true;
	var opts = sel.options;
	for (var i=0; i<opts.length; i++) {
		opts[i].selected = true;
	}
}