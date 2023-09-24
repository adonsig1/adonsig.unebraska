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
		</style>
		<script type="text/javascript" src="js/editable_text.js"></script>
		<script type="text/javascript" src="js/calendar.js"></script>
		<script type="text/javascript" src="js/calendar-en.js"></script>
		<script type="text/javascript" src="js/calendar-setup.js"></script>
		<script type="text/javascript">
			var cellModified = [ <?php echo getModifiedTimesList($class_list) ?> ];
			var selectedDate = null;
				
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
					updateTable();
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
			function updateTable() {
				for (var i = 0; i < cellModified.length; i++) {
					var tr = document.getElementById("tr_" + i); 
					if (selectedDate && cellModified[i] >= selectedDate) {
						if (tr.className != "highlit") tr.className = "highlit";
					} else {
						if (tr.className != "") tr.className = "";
					}
				}
			}
			
			/*
			 * Called when the "highlight changes since <date>" field is cleared.
			 */
			function clearDate() {
				document.getElementById('date_field').value='';
				selectedDate = null;
				updateTable();
			}
		</script>
	</head>
	<body onLoad="initEditableTextNodes()">
		<?php include 'std_header.php'; ?>
		
		<form name="theForm">
			<div class="session_select">
				<?php make_session_select($session, "document.theForm.submit()") ?>
			</div>
			Highlight changes since:
			<input type="text" id="date_field" disabled="true">
			<button id="calendar_button" type="button">...</button>
			<button type="button" onclick="clearDate()">Clear</button>
		</form>
		<script type="text/javascript">
			Calendar.setup(
				{
					inputField : "date_field",
					ifFormat   : "%m/%d/%Y",
					button     : "calendar_button",
					onUpdate   : calUpdate
				}
			);
		</script>
		
		<table id="class_list" class="class_list">
			<?php 
				for ($i=0; $i < count($class_list); $i++) {
					$x = 	$class_list[$i];
					$thisCourse = $x->course;
					if ($thisCourse != $lastCourse) {
						echo "<tr><td style=\"height: 0.5ex;\"></td></tr>";
					}
					echo "<tr id='tr_$i'>\n";
					echo "<td>$x->callnum</td>";
					echo "<td>$thisCourse</td>";
					echo "<td>$x->sectnum</td>";
					echo "<td>$x->courseName</td>";
					echo "<td>$x->time</td>";
					echo "<td>$x->days</td>";
					
					$url = "all_classes.php?action=ajax_update_room&class_id=$x->class_id&session=$session";
					echo "<td><a href=\"$url\" class=\"update_room\">$x->room</a></td>";
					
					echo "<td><span id='class_$i-ln'>$x->ln</span></td>";
					echo "<td><span id='class_$i-fn'>$x->fn</span></td>";
					echo "<td>$x->convenor</td>";
					echo "<td><span id='class_$i-role'>$x->roleName</span></td>";
					
					$url = "all_classes.php?action=ajax_update_instructor&span_id=class_$i&class_id=$x->class_id&session=$session";
					echo "<td><a href='$url' class='update_instructor'>$x->instructor_id</a></td>";
					
					echo "</tr>";
					if ($x->days2) {
						echo "<tr><td></td><td></td><td></td><td></td><td>$x->time2</td><td>$x->days2</td></tr>";
					}
					if ($x->subtitle) {
						echo "<tr><td></td><td></td><td></td><td>&nbsp;&nbsp;$x->subtitle</td></tr>";
					}
					$lastCourse = $thisCourse;
				}
			?>
		</table>
		
		<?php
			mysql_close(); 
			include 'std_footer.php'; 
		?>
	</body>
</html>
