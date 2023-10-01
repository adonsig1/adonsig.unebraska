<?php
	require_once 'common.php';
	require_once 'instructor_lib.php';

	db_connect();

	$user = getUser(ADMIN_PRIV);
	if (! $user) {
		return;
	}

	$action = $_REQUEST['action'];
	if ($action == 'view_instructor_roles') {
		$env = doAjaxViewInstructor();
		sendResponse($env);
	} else if ($action == 'add_instructor_role') {
		$env = doAjaxAddRole();
		sendResponse($env);
	} else if ($action == 'delete_instructor_role') {
		$env = doAjaxDeleteRole();
		sendResponse($env);
	}

	function sendResponse($env) {
		header("Content-Type: text/xml");
		
		$response = '<?xml version="1.0" ?>';
		$response .= '<root><success>';
		$response .= $env['success'];
		$response .= '</success><message>';
		$response .= $env['message'];
		$response .= '</message></root>';
		echo $response;
		exit();
	}

	function doAjaxViewInstructor() {
		$instructor_id = $_REQUEST['id'];

		// instructor ID should be lower case
		$instructor_id = strtolower(mysql_real_escape_string($instructor_id));
		// Load the instructor from the database
		$rs = mysql_query(
			"SELECT ir.ln AS ln, ir.fn AS fn, ir.role AS role, ir.session AS session
			FROM instructor_roles ir
			WHERE ir.id = '$instructor_id'");
		if (mysql_num_rows($rs) == 0) {
			return array(
				'success' => false,
				'message' => "Unknown instructor id '$instructor_id'"
			);
		}
		// else, return JSON grid data
		$JSON_grid_data = "{ rows:[";
		// insert row data. Format: { id:_, data:["_",...,"_"]}
		$j = 1;
		while ($x = mysql_fetch_object($rs)) {
			if ($j == 1) {
				$JSON_grid_data .= "{ ";
			} else {
				$JSON_grid_data .= ",{ ";
			}
			$JSON_grid_data .= "id:" . $j;
			$j++;
			$JSON_grid_data .= ", data: [";
			$JSON_grid_data .= "\"" . $x->ln ."\",";
			$JSON_grid_data .= "\"" . $x->fn ."\",";
			$JSON_grid_data .= "\"" . $x->role ."\",";
			$JSON_grid_data .= "\"" . $x->session ."\"";
			// now add blank for delete button 
			$JSON_grid_data .= ",\" \"]}";
		}
		$JSON_grid_data .= "]}";
		return array(
			'success' => true,
			'message' => $JSON_grid_data
		);
	}

	function update_instructors_table($instructor_id) {
		// update instructor table with most recent role
		$query = "UPDATE instructors i
			SET role = (SELECT ir.role
				FROM instructor_roles ir
				WHERE ir.id = '$instructor_id' AND ir.Session_order = (SELECT MAX(ir2.Session_order) FROM instructor_roles ir2 WHERE ir2.id = ir.id GROUP BY ir2.id))
			WHERE id = '$instructor_id'";
		mysql_query($query);
	}

	function doAjaxAddRole() {
		// add new row
		$instructor_id = $_REQUEST['id'];
		$role = $_REQUEST['role'];
		$session = $_REQUEST['session'];

		// instructor ID should be lower case
		$instructor_id = strtolower(mysql_real_escape_string($instructor_id));

		// Get missing values for id from instructors table
		$rs = mysql_query(
			"SELECT i.ln AS ln, i.fn AS fn, s.id AS session, s.name AS sessionName, s.order AS sessionOrder
			FROM instructors i JOIN sessions s
			WHERE i.id = '$instructor_id' AND s.id = '$session'");

		if (!$rs || mysql_num_rows($rs) == 0) {
			return array(
				'success' => false,
				'message' => "Unknown instructor '$instructor_id' or session '$session'."
			);
		}
		// should return only one entry
		$x = mysql_fetch_object($rs);
		$ln = $x->ln;
		$fn = $x->fn;
		$name = $x->sessionName;
		$order = $x->sessionOrder;

		// now write query to insert new row	
		$rs = mysql_query("INSERT INTO instructor_roles 
			VALUES ('$instructor_id','$ln','$fn','$role','$name','$session','$order','NULL')");
		if (!$rs) {
			return array(
				'success' => false,
				'message' => "Update failed."
			);
		}

		// Also, update instructors table.
		update_instructors_table($instructor_id);
			
		return doAjaxViewInstructor();
	}

	function doAjaxDeleteRole() {
		$instructor_id = $_REQUEST['id'];
		$session_name = $_REQUEST['session'];
		// instructor ID should be lower case
		$instructor_id = strtolower(mysql_real_escape_string($instructor_id));
		
		$rs = mysql_query("DELETE FROM instructor_roles
			WHERE id='$instructor_id' AND session='$session_name'");
		if (!$rs) {
			return array(
				'success' => false,
				'message' => "Update failed."
			);
		}

		// update instructors table
		update_instructors_table($instructor_id);

		return doAjaxViewInstructor();
	}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html14/strict.dtd">
<html>
	<head>
		<title>Manage instructor roles</title>
		<style type="text/css">
			@import url(css/main.css);
		</style>
		<link rel="stylesheet" type="text/css" href="js/dhtmlxSuite_v50_std/codebase/fonts/font_roboto/roboto.css"/>
		<link rel="stylesheet" type="text/css" href="js/dhtmlxSuite_v50_std/codebase/dhtmlx.css">
		<script type="text/javascript" src="js/dhtmlxSuite_v50_std/codebase/dhtmlx.js"></script>
		<script type="text/javascript">
	
			var myGrid;			
	
			var id;
	
			var sessions = [];

			var deleteButtons = [];

			function query_instructor_roles() {
				id = document.getElementById('userIdInput').value;
				// now query for instructor
				var url = "edit_instructor_roles.php?action=view_instructor_roles&id=" + id;
				sendQuery(url);
			}
			
			function add_new_role() {
				var newRole = prompt("New role","role");
				if (!newRole) { return;}
				var newStarted = prompt("Session role started","session");	
				if (!newStarted) { return; }

				var url = "edit_instructor_roles.php?action=add_instructor_role&id=" + id + "&role=" + newRole + "&session=" + newStarted;
				sendQuery(url);
			}

			function delete_role(i) {
				if (myGrid.getRowsNum() == 1) {
					// to delete all records of an instructor from instructor_roles, do delete in edit_instructors.php
					alert("Cannot delete record. This instructor has only one role. Visit edit_instructors.php to delete all records of this instructor.");
				} else {
					var url = "edit_instructor_roles.php?action=delete_instructor_role&id=" + id + "&session=" + sessions[i];
					sendQuery(url);
				}
			}

			function sendQuery(url) {
				window.dhx.ajax.get(url, function(r){
					var xml = r.xmlDoc.responseXML;

					if(xml == null) {
						alert('error parsing xml');
					} else {
						generateGrid(xml);
					}
				});
			}

			function generateGrid(xml) {
				var root = xml.getElementsByTagName("root")[0];
				var success = getText(root.getElementsByTagName("success")[0]);
				var message = getText(root.getElementsByTagName("message")[0]);
				if(success) {
					// build grid
					myGrid = new dhtmlXGridObject('gridBox');
					myGrid.setImagePath("js/dhtmlxSuite_v50_std/codebase/imgs/");
					myGrid.setHeader("ln,fn,role,role started, ");
					myGrid.setInitWidths("125px,125px,125px,125px,100px");
					myGrid.setColAlign("left,left,left,left,left");
					myGrid.setColSorting("str,str,str,str,str");
					myGrid.setColTypes("ro,ro,ro,ro,ro");
					myGrid.init();

					myGrid.parse(message,"json");
					myGrid.setStyle("font-size:12px;", "font-size:12px; row-height:10px;", "", "");
					// insert delete buttons
					for (var i = 0; i < myGrid.getRowsNum(); i++) {
						var j = i + 1;
						deleteButtons[i] = "<button id='delete_" + j + "' onclick='delete_role(" + i + ")'>Delete</button>";
						sessions[i] = myGrid.cells(j.toString(),3).getValue();
						myGrid.cells(j.toString(),4).setValue(deleteButtons[i]);
					}
					// reveal add role button
					document.getElementById("addRole").style.display = "block";
				} else {
					alert(message);
				}
			}

			function getText(node) {
				return node.firstChild == null ? "" : node.firstChild.data;
			}

		</script>
	</head>
	<body>
		<?php include 'std_header.php'; ?>
		View instructor roles:	
		<input type="text" id="userIdInput">
		<button onclick="query_instructor_roles()">Find Roles</button>
		<br><br>
		<div style="width:500px; height:50px;">
		<button onclick="add_new_role()" id="addRole" style="display: none">Add new role</button>
		</div>
		<div id="gridBox" style="width:600px; height:250px"></div>

		<?php 
			mysql_close();
			include 'std_footer.php'; ?>
	</body>
</html>
