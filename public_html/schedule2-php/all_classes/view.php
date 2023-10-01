<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<title>All Class Assignments</title>
		<style type="text/css">
			@import url(css/main.css);
			@import url(css/editable_text.css);
			@import url(css/calendar-system.css);
			
			TABLE.class_list {
				border: 0pt;
				margin-left: 25px; 
			}
			
			TABLE.class_list TR.highlit {
				background-color: yellow; 
			}

			TABLE.class_list TD {
				font-size: 8pt;
				font-family: sans-serif;
				border: 0pt;
				white-space: nowrap;
			}

			TABLE.class_list TD input {
				font-size: 8pt;
				font-family: sans-serif;
				border-width: 1px;
				border-color: #808080;
				border-style: solid;
				background-color: #ffffC0;
				margin-right: 4px;
				margin-top: 0px;
				margin-bottom: 0px;
				height: 11pt;
			}
			
			div.input_box {
				display: inline-block;
				white-space: nowrap;
			}

			div.gridbox {
				white-space: nowrap;
			} 
		</style>
		<link rel="stylesheet" type="text/css" href="js/dhtmlxSuite_v50_std/codebase/fonts/font_roboto/roboto.css"/>
		<link rel="stylesheet" type="text/css" href="js/dhtmlxSuite_v50_std/codebase/dhtmlx.css"/>
		<script type="text/javascript" src="js/editable_text.js"></script>
		<script type="text/javascript" src="js/calendar.js"></script>
		<script type="text/javascript" src="js/calendar-en.js"></script>
		<script type="text/javascript" src="js/calendar-setup.js"></script>
		<script type="text/javascript" src="js/dhtmlxSuite_v50_std/codebase/dhtmlx.js"></script>
		<style>
			#calendar_input {
				border: 1px solid #dfdfdf;
				font-family: Roboto, Arial, Helvetica;
				font-size: 14px;
				color: #404040;
			}

			#calendar_icon {
				vertical-align: middle;
				cursor: pointer;
			}

			.not_m_line {
				display: inline-block;
				height: 25px;
				white-space:nowrap;
				overflow:hidden;
			}
		</style>
		<script type="text/javascript">
			var cellModified = [ <?php echo getModifiedTimesList($class_list) ?>]; 
			var selectedDate = null;
			
			var dhtmlxCal;

			var dhtmlxGrid;

			var myGrid;

			var instructorIDNodes = [];

			var roomNodes = [];

			/*
				*  Main "onLoad" function.
				*  Added 3-15-2017 by TBR.
			 */
			function doOnLoad() {
				initGrid();
				initCalendar();
				initEditableTextNodes();
				initEditableUserID();	
			}

			/*
				* Instantiates dhtmlx Grid (replaces table)
				* TBR
			*/
			function initGrid() {
				myGrid = new dhtmlXGridObject('gridbox');
				myGrid.setImagePath("js/dhtmlxSuite_v50_std/codebase/imgs/");
				myGrid.setHeader("Call#,Course#,Section#,Course,Time,Day,Room,,,LN,FN,[C],Role,ID,,");
				myGrid.setInitWidths("40,60,40,180,75,40,70,30,30,75,75,40,75,70,30,30");
				myGrid.setColAlign("left,left,left,left,left,left,left,left,left,left,left,left,left,left,left,left");
				myGrid.setColSorting("str,str,str,str,str,str,str,str,str,str,str,str,str,str,str,str");
				myGrid.setColTypes("ro,ro,ro,ro,ro,ro,ro,ro,ro,ro,ro,ro,ro,ro,ro,ro");
				myGrid.init();
			
				var the_data = <?php
					echo "{ rows:[";	
					// Added next line 3-13-17: TBR
					$lastCourse = 0;
					for ($i=0; $i < count($class_list); $i++) {
						$x = 	$class_list[$i];
						$thisCourse = $x->course;
						$j = $i + 1;
						if($lastCourse !== 0) {
							echo ",";
						}
						echo "{ id:$j, data: [";
						echo "\"$x->callnum\",";
						echo "\"$thisCourse\",";
						echo "\"$x->sectnum\",";
						echo "\"$x->courseName\",";
						echo "\"$x->time\",";
						echo "\"$x->days\",";
						
						$url = "all_classes.php?action=ajax_update_room&class_id=$x->class_id&session=$session";
						echo "\"<a href='$url' class='update_room' id='editRoom_$j'>$x->room</a>\",";
						
						echo " , ,\"$x->ln\",";
						echo "\"$x->fn\",";
						echo "\"$x->convenor\",";
						echo "\"$x->roleName\",";
						
						$url = "all_classes.php?action=ajax_pending_input&row=$j&id=$x->instructor_id";
						$java_link = "javascript:addInputBox($j)";
						if ($x->instructor_id == "") {
							echo "\"<a href='javascript:void(0)' id='editID_$j'><i>Click to edit</i></a>\"";
						} else {
							echo "\"<a href='javascript:void(0)' id='editID_$j'>$x->instructor_id</a>\"";
						}
						echo ", , ]}";
						if ($x->days2) {
							echo ",{ id:$j + 0.1, data: [ , , , ,\"$x->time2\",\"$x->days2\", , , , , , , , , , ]}";
						}
						if ($x->subtitle) {
							echo ",{ id:$j + 0.2, data: [ , , ,\"$x->subtitle\", , , , , , , , , , , , ]}";
						}
						$lastCourse = $thisCourse;
					}
					echo "]};";
				?>;
				myGrid.parse(the_data,"json");
				
				myGrid.setStyle("font-size:10px;", "font-size:10px; row-height:5px;", "", "");
			}

			/*
				* Instantiate DhtmlX grid
				* TBR
			*/
			function initCalendar() {
				dhtmlxCal = new dhtmlXCalendarObject({input: "calendar_input", button: "calendar_icon"});
				dhtmlxCal.setDateFormat("%m/%d/%Y");
				dhtmlxCal.attachEvent("onClick", function(){
					dhtmlxCalUpdate(dhtmlxCal);
				});
			}

			/*
				* Calls updateGrid upon date selection
				* TBR
			*/
			function dhtmlxCalUpdate(cal) {
				selectedDate = cal.getFormatedDate("%Y%m%d");
				updateGrid();
			}

			/*
				* Sets up clickable link inside grid
				* for editing instructors rooms.
				* TBR
			*/
			function initEditableUserID() {
				<?php
				for ($i = 0; $i < count($class_list); $i++) {
					$j = $i + 1;
					echo "instructorIDNodes[$i] = document.getElementById(\"editID_$j\");\n";
					$id = $class_list[$i]->instructor_id;
					$class_id = $class_list[$i]->class_id;
					echo "instructorIDNodes[$i].onclick = function() { return addInputBox($j,\"$id\",\"$class_id\",\"$session\"); };\n";
					$room = $class_list[$i]->room;
					echo "roomNodes[$i] = document.getElementById(\"editRoom_$j\");\n";
					echo "roomNodes[$i].onclick = function() { return addRoomInputBox($j,\"$room\",\"$class_id\",\"$session\"); };\n";
				} ?>
			}

			/*
				* When a user id is clicked in grid,
				* this function replaces the link with
				* a text box. TBR
			*/
			function addInputBox(j,input_id,class_id,session) {
				// replace user ID entry with text box
				if(input_id) {
					var inputBoxAsString = "<input id=\"input_box_" + j + "\" type=\"text\" size=\"4px\" value=\"" + input_id + "\" >";
					var button2AsString = "<img id=\"cancel_" + j + "\" src=\"images/cross.png\" border=\"0\" onclick=clearInputBox(" + j + ",\"" + class_id + "\",\"" + session + "\",\"" + input_id + "\")>";	
				} else {
					var inputBoxAsString = "<input id=\"input_box_" + j + "\" type=\"text\" size=\"4px\" >";
					var button2AsString = "<img id=\"cancel_" + j + "\" src=\"images/cross.png\" border=\"0\" onclick=clearInputBox(" + j + ",\"" + class_id + "\",\"" + session + "\")>";				
				}					
				var button1AsString = "<img id=\"check_" + j + "\" src=\"images/tick.png\" border=\"0\" onclick=editInstructor(" + j + ",\"" + class_id + "\",\"" + session + "\")>";
				myGrid.cells(j.toString(),13).setValue(inputBoxAsString);
				myGrid.cells(j.toString(),14).setValue(button1AsString);
				myGrid.cells(j.toString(),15).setValue(button2AsString);
			}

			/*
				* When a room is clicked, this adds replaces
				* the link with a text box. TBR
			*/
			function addRoomInputBox(j,room,class_id,session) {
				// do stuff
				if(room) {
					// set up check and cross
					// Link cross to room link
					var inputBoxAsString = "<input id=\"room_box_" + j + "\" type=\"text\" size=\"4px\" value=\"" + room + "\" >";
					var button2AsString = "<img src=\"images/cross.png\" border=\"0\" onclick=clearRoomInputBox(" + j + ",\"" + class_id + "\",\"" + session + "\",\"" + room + "\")>";	
				} else {
					// set up check and cross
					// link cross to "click to edit"
					var inputBoxAsString = "<input id=\"room_box_" + j + "\" type=\"text\" size=\"4px\" >";
					var button2AsString = "<img id=\"cancel_" + j + "\" src=\"images/cross.png\" border=\"0\" onclick=clearRoomInputBox(" + j + ",\"" + class_id + "\",\"" + session + "\")>";				
				}
				var button1AsString = "<img src=\"images/tick.png\" border=\"0\" onclick=editRoom(" + j + ",\"" + class_id + "\",\"" + session + "\")>";
				// update grid
				myGrid.cells(j.toString(),6).setValue(inputBoxAsString);
				myGrid.cells(j.toString(),7).setValue(button1AsString);
				myGrid.cells(j.toString(),8).setValue(button2AsString);

			}
			
			/*
				* Removes an input box when a new instructor
				* is assigned or the 'cross' is clicked.
				* TBR
			*/
			function clearInputBox(j,class_id,session,input_id) {
				if(input_id) {
					var inputAsText = "<a href='javascript:void(0)' id='editID_" + j + "'>" + input_id + "</a>";
				} else {
					var inputAsText = "<a href='javascript:void(0)' id='editID_" + j + "'><i>Click to edit</i></a>";
				}
				myGrid.cells(j.toString(),13).setValue(inputAsText);
				myGrid.cells(j.toString(),14).setValue("");
				myGrid.cells(j.toString(),15).setValue("");
				var i = j - 1;
				instructorIDNodes[i] = document.getElementById("editID_" + j);
				instructorIDNodes[i].onclick = function() { return addInputBox(j,input_id,class_id,session); };
			}

			/*
				* When a new room is selected or the 'cross'
				* is clicked, this removes the text box and
				* replaces it with a link. TBR
			*/
			function clearRoomInputBox(j,class_id,session,room) {
				if(room) {
					var inputAsText = "<a href='javascript:void(0)' id='editRoom_" + j + "'>" + room + "</a>";
				} else {
					var inputAsText = "<a href='javascript:void(0)' id='editRoom_" + j + "'><i>Click to edit</i></a>";
				}
				// update grid
				myGrid.cells(j.toString(),6).setValue(inputAsText);
				myGrid.cells(j.toString(),7).setValue("");
				myGrid.cells(j.toString(),8).setValue("");

				// update room nodes
				var i = j - 1;
				roomNodes[i] = document.getElementById("editRoom_" + j);
				roomNodes[i].onclick = function() { return addRoomInputBox(j,room,class_id,session); };

			}
			
			/*
				* Sends new instructor assignment to
				* all_classes.php and handles server-side
				* response. TBR
			*/
			function editInstructor(j,class_id,session,override_conflicts) {
				var i = j - 1;
				var inputId = "input_box_" + j;
				var value = document.getElementById(inputId).value;
				if (override_conflicts != 1) {
					override_conflicts = 0;
				}
				var url = "all_classes.php?action=ajax_update_instructor&span_id=class_" + i + "&class_id=" + class_id + "&session=" + session + "&overrideConflicts=" + override_conflicts + "&value=" + value;
				// Now do ajax query
				// if there are conflicts, ask user to override conflicts or not. If the user clicks override conflicts, resubmit query with overrideConflicts=1.
				window.dhx.ajax.get(url, function(r){
					var xml = r.xmlDoc.responseXML;
					if (xml != null) {
						var root = xml.getElementsByTagName("root")[0];
						var success = root.getAttribute("success");
						if(success == "true") {
							var fn = getText(root.getElementsByTagName("fn")[0]);
							var ln = getText(root.getElementsByTagName("ln")[0]);
							var role = getText(root.getElementsByTagName("role")[0]);
							myGrid.cells(j.toString(),9).setValue(ln);
							myGrid.cells(j.toString(),10).setValue(fn);
							myGrid.cells(j.toString(),12).setValue(role);
							if(value) {
								clearInputBox(j,class_id,session,value);
							} else {
								clearInputBox(j,class_id,session);
							}
						} else {
							var conflicts = getText(root.getElementsByTagName("conflicts")[0]);
							var message = getText(root.getElementsByTagName("message")[0]);
							if(conflicts == 1) {
								var override = confirm(message);
								if(override) {
									editInstructor(j,class_id,session,1);
								}
							} else {
								alert(message);
							}
						}
					} else {
						alert("Error parsing xml.");
					}
				});
			}
			
			/*
				* Sends new room assignment to
				* all_classes.php and handles
				* server-side response. TBR
			*/
			function editRoom(j,class_id,session) {
				// update room
				var i = j - 1;
				var inputId = "room_box_" + j;
				var value = document.getElementById(inputId).value;
				var url = "all_classes.php?action=ajax_update_room&class_id=" + class_id + "&session=" + session + "&value=" + value;
				window.dhx.ajax.get(url, function(r){
					var xml = r.xmlDoc.responseXML;
					if (xml != null) {
						var root = xml.getElementsByTagName("root")[0];
						var success = root.getAttribute("success");
						if(success == "true") {
							if(value) {
								clearRoomInputBox(j,class_id,session,value);
							} else {
								clearRoomInputBox(j,class_id,session);
							}
						} else {
							var message = getText(root.getElementsByTagName("message")[0]);
							alert(message);
						}
					} else {
						alert("Error parsing xml.");
					}
				});
				
			}

			/*
				* This code is now unused and obsolete.
				* I'm leaving it in case it is useful for
				* future updates. TBR
			*/
			function initEditableTextNodes() {
				
				var ajaxFailureHandler = function (root) {
					alert("Update failed:\n" + root.getElementsByTagName("message")[0].firstChild.data);
				};
				var ajaxSuccessHandler = function (root) {
					var spanId = getText(root.getElementsByTagName("spanId")[0]);
					var fn = getText(root.getElementsByTagName("fn")[0]);
					var ln = getText(root.getElementsByTagName("ln")[0]);
					var role = getText(root.getElementsByTagName("role")[0]);
					document.getElementById(spanId + "-fn").innerHTML = fn;
					document.getElementById(spanId + "-ln").innerHTML = ln;
					document.getElementById(spanId + "-role").innerHTML = role;
					
					// Insert the new modification time into the global list
					// of modification times and highlight (if appropriate).
					var k = spanId.substring(6);
					cellModified[k] = getFormattedNow();
					updateGrid();
				};
				EditableTextElement.registerByClass("update_instructor", ajaxSuccessHandler, ajaxFailureHandler);
				
				EditableTextElement.registerByClass("update_room", null, ajaxFailureHandler);
			}
			
			/*
			 * Format the current day's date as yyyymmdd for highlighting
			 * recent changes
			 */
			function getFormattedNow() {
				var now = new Date();
				var Y = now.getFullYear();
				var m = now.getMonth() + 1;
				var d = now.getDate();
				var s = "" + Y + (m < 10 ? "0" + m : "" + m) + (d < 10 ? "0" + d : "" + d);
				return s;
			}
			
			function getText(node) {
				return node.firstChild == null ? "" : node.firstChild.data;
			}
			
			/*
			 * Called by the calendar popup when a date has been selected.
			 * Set the global selectedDate field to the chosen date in yyyymmdd format
			 * and then update the table to highlight any rows.
			 */
			function calUpdate(cal) {
				selectedDate = cal.date.print("%Y%m%d");
				updateTable();
			}
			
			/*
			 * Highlight any rows which have a modified date more recent than
			 * the golbal selectedDate variable.  
			 */
			function updateGrid() {
				var count = <?php echo count($class_list); ?>;
				var this_row;
				for (var i = 0; i < count; i++) {
					if (selectedDate && cellModified[i] >= selectedDate) {
						myGrid.setRowColor(i + 1, "yellow");
					} else {
						myGrid.setRowColor(i + 1,"white");
					}
				}
			}

			/*
			 * Called when the "highlight changes since <date>" field is cleared.
			 */
			function clearDate() {
				selectedDate = null;
				updateGrid();
			}
		</script>
	</head>
	<body onload="doOnLoad()">
		<?php include 'std_header.php'; ?>
		<form name="theForm">
			<div class="session_select">
				<?php make_session_select($session, "document.theForm.submit()") ?>
			</div>
			Highlight changes since:
			<input type="text" id="calendar_input">
			<span><img id="calendar_icon" src="images/calendar.png" border="0"></span>
			<button onclick="clearDate()">Clear</button>
		</form>
		<br>
		<div id="gridbox" style="width:980px;height:800px;"></div>

		<?php
			mysql_close(); 
			include 'std_footer.php'; 
		?>
	</body>
</html>
