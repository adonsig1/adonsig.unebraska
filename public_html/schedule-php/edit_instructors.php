<?php
	require_once 'common.php';
	require_once 'instructor_lib.php';
	db_connect();
	
	$user = getUser(ADMIN_PRIV);
	if (! $user) {
		return; 
	}
	
	$role = mysqli_real_escape_string($newconn, $_REQUEST["role"]);
	$role = (is_null($role) || $role == '') ? 'fac' : $role;
	
	$errstr = NULL;
	
	$action = $_REQUEST['action'];
	if ($action == 'update') {
		do_update();
	} else if ($action == 'add') {
		do_add();
	} else if ($action == 'delete') {
		do_delete();
	} else if ($action == 'ajax_update') {
		do_ajax_update();
		return;
	} else if ($action == 'ajax_query_instructor_links') {
		do_ajax_query_instructor_links();
		return;
	}

	$query = "SELECT * FROM instructors WHERE role='$role' ORDER BY ln ASC, fn ASC";
	$rs = mysqli_query($newconn, $query);

	?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<title>Manage instructors</title>
		<link rel="stylesheet" type="text/css" href="css/main.css">
		<style type="text/css">
		  TABLE.list {
		  	border-collapse: collapse;
		  }
		  TABLE.list TD {
		  	padding: 1px 4px;
		  }
			TR.hilight {
				background-color: #ddddbb;
			}
			TR.hilight INPUT[type="text"] {
				background-color: #f8f8d8;
			}
		</style>
		<script src="js/fader.js" type="text/javascript"></script>
		<script type="text/javascript">
			var data = {
				<?php
					$instr = new Instructor('_NEW');
					output_json_row($instr);
					
					mysqli_data_seek($rs, 0);
					while ($x = mysqli_fetch_object($rs, 'Instructor')) {
						output_json_row($x);
					}
				?>
			};
			
			var allIds = [<?php 
				$query = "SELECT id FROM instructors";
				$rs2 = mysqli_query($newconn, $query);
				while ($row = mysqli_fetch_array($rs2)) {
					$i = $row[0];
					echo "'$i',";
				}
			?>];
			
			function doFocus(id) {
				var f = document.dataForm;
				
				for (var i in data) {
					if (i != id && data[i].modified) {
						if (confirm("Data modified - save changes?")) {
							doUpdate(i);
							return;
						} else {
							var d = data[i];
							f['id_' + i].value = (i == '_NEW' ? '' : i);
							f['ln_' + i].value = d.ln;
							f['fn_' + i].value = d.fn;
							f['email_' + i].value = d.email;
							
							var start_list = f['started_' + i];
							var start_opts = start_list.options;
							start_list.selectedIndex = 0; 
							for (k in start_opts) {
								if (start_opts[k].text == d.started) {
									start_list.selectedIndex = k;
									break;
								}
							}
							
							d.modified = false;
						}
					}
					f["submit_" + i].disabled = true;
					
					var tdDelete = document.getElementById('td_delete_' + i);
					if (tdDelete != null) tdDelete.innerHTML = ""; 
					
					document.getElementById("row_" + i).className = "";
				}
				
				f["submit_" + id].disabled = false;
				
				var tdDelete = document.getElementById('td_delete_' + id);
				if (tdDelete != null) tdDelete.innerHTML = "<a onclick='doDelete(\"" + id + "\")' href='javascript:void(0)'>delete</a>"
				
				document.getElementById("row_" + id).className = "hilight";
			}
			
			function doOnChange(id) {
				data[id].modified = true;
			}
			
			function validateId(id) {
				for (var i in allIds) {
					if (id == allIds[i]) {
						alert("The id '" + id + "' has already been taken.");
						return false;
					}
				}
				
				if (! id.match(/^[a-z]{2,4}$/)) {
					alert("An id must be 2 - 4 lower-case letters");
					return false;
				}
				return true;
			}
			
			function validateName(name) {
				if (! name.match(/^[a-zA-Z]{1,16}$/)) {
					alert("A name must be 1 - 16 letters. '" + name + "' is not acceptable");
					return false;
				}
				return true;
			}
			
			function validateEmail(email) {
				if (email == '') return true;
				
				if (! email.match(/^[a-zA-Z0-9\.\-_]{1,20}@[a-zA-Z0-9\.\-_]{1,25}$/)) {
					alert("'" + email + "' is not an acceptable email address");
					return false;
				}
				return true;
			}
			
			function getProposedId(fn, ln) {
				if (fn.length == 0 || ln.length < 2) {
					return null;
				}
				return (fn.charAt(0) + (ln.length == 2 ? ln : ln.substring(0, 3))).toLowerCase();
			}
			
			function doAjaxSubmit(action, id, ln, fn, email, started) {
				var client = new XMLHttpRequest();
				client.open('POST', 'edit_instructors.php');
				client.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
				client.onreadystatechange = 
					function () { 
					  if (client.readyState == 4) {
					  
						  if (client.status != 200 || client.responseText != 'OK') {
						  	alert("Update failed. Please try again.");
						  	return;
						  }
						  
							var tdDelete = document.getElementById('td_delete_' + id);
							if (tdDelete != null) tdDelete.innerHTML = "";
							 
						  f["submit_" + id].disabled = true;
						  var tr = document.getElementById("row_" + id);
						  tr.className = "";
						  new Fader(tr, new Color(0,255,0), Color.WHITE).start();
						}
					}
				
				var sendStr = 
					'action=' + escape(action) + '&id=' + escape(id) + '&ln=' + escape(ln) 
					+ '&fn=' + escape(fn) + '&email=' + escape(email) + '&started=' + escape(started); 
				client.send(sendStr);
			}
			
			function doAdd(i) {
				var f = document.dataForm;
				var id = f['id_' + i].value;
				var ln = f['ln_' + i].value;
				var fn = f['fn_' + i].value;
				var email = f['email_' + i].value;
				var started_list = f['started_' + i];
				var started = started_list.options[started_list.selectedIndex].text;
				
				var proposedId = getProposedId(fn, ln);
				if (proposedId != null && proposedId != id) {
					if (confirm("It is strongly recommended that you adopt a\nconsistent naming pattern for ID's. Would\nyou like to use the ID '" + proposedId + "' here?")) {
						id = proposedId;
						f['id_' + i].value = proposedId;
					}
				}
				
				if (! validateId(id)) return;
				
				if (! validateName(ln) || ! validateName(fn) || ! validateEmail(email)) return;
				
				f = document.mainForm;
				f.id.value = id;
				f.ln.value = ln;
				f.fn.value = fn;
				f.email.value = email;
				f.started.value = started;
				f.action.value = 'add';
				f.submit();
			}
			
			function doUpdate(id) {
				var f = document.dataForm;
				var ln = f['ln_' + id].value;
				var fn = f['fn_' + id].value;
				var email = f['email_' + id].value;
				var started_list = f['started_' + id];
				var started = started_list.options[started_list.selectedIndex].text;
				
				if (! validateName(ln) || ! validateName(fn) || ! validateEmail(email)) return;
				
				f = document.mainForm;
				f.id.value = id;
				f.ln.value = ln;
				f.fn.value = fn;
				f.email.value = email;
				f.started.value = started;
				f.action.value = 'update';
				f.submit();

				// doAjaxSubmit('ajax_update', id, ln, fn, email, started);
			}
			
			function doDelete(id) {
				var f = document.dataForm;
				var ln = f['ln_' + id].value;
				var fn = f['fn_' + id].value;
				var email = f['email_' + id].value;
				var started_list = f['started_' + id];
				var started = started_list.options[started_list.selectedIndex].text;
				
				var client = new XMLHttpRequest();
				client.open('POST', 'edit_instructors.php');
				client.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
				client.onreadystatechange = 
					function () {
				
					  if (client.readyState != 4) {
					  	return;
					  }
					  
					  if (client.status != 200) {
					  	alert("Update failed. Please try again.");
					  	return;
					  }
					  
					  var numLinks = client.responseText;
					  
					  var conftxt;
					  if (numLinks > 0) {
					  	var classes = (numLinks == 1 ? "class" : "classes");
							conftxt =
								"WARNING!!\n\n"
								+ fn + " " + ln + " is listed as the instructor for " + numLinks + " " + classes + ".\n"
								+ "It is strongly recommended that you do not delete instructor records for\n"
								+ "people who have been assigned to classes.\n\n"
								+ "This action cannot be undone. Click OK to continue deleting.";
						} else {
							conftxt = 
								"Do you really want to permanently delete the records for " + fn + " " + ln + "?\n"
								+ "This action cannot be undone. Click OK to continue deleting.";
						}
						
						if (! confirm(conftxt)) return;
						
						conftxt =	"Last chance! Are you *really* sure?";
						
						if (! confirm(conftxt)) return;
						
						f = document.mainForm;
						f.id.value = id;
						f.action.value = 'delete';
						f.submit();
					}
				client.send('action=ajax_query_instructor_links&id=' + id);
			}
		</script>
	</head>
	<body>
		<?php include 'std_header.php'; ?>
		
		<form name="mainForm">
			<input type="hidden" name="action">
			<input type="hidden" name="id">
			<input type="hidden" name="ln">
			<input type="hidden" name="fn">
			<input type="hidden" name="email">
			<input type="hidden" name="started">

			<div class="session_select">
				<?php make_role_select($role, "document.mainForm.submit()") ?>
			</div>
		</form>
			
		<?php if ($errstr): ?>
			<div class="error_message" style="margin: 10px 0;">
				<?php echo $errstr ?>
			</div>
		<?php endif; ?>
		
		<form name="dataForm">
			<table class="list">
				<?php
					$instr = new Instructor('_NEW');
					output_row($instr);
					
					mysqli_data_seek($rs, 0);
					while ($instr = mysqli_fetch_object($rs, 'Instructor')) {
						output_row($instr);
					}
				?>
			</table>
		</form>
	</body>
</html><?php
	
	function output_row($instr) {
		$idTxt = $instr->id == '_NEW' ? '' : $instr->id;
		echo "<tr id='row_$instr->id'>";
		if ($instr->id == '_NEW') {
			echo "<td><input name='id_$instr->id' type='text' style='width:5ex;' maxlength='4' value='$idTxt' onfocus='doFocus(\"$instr->id\")' onchange='doOnChange(\"$instr->id\")'></td>";
		} else {
			echo "<td><input name='id_$instr->id' type='text' style='width:5ex;' maxlength='4' value='$idTxt' disabled='true'></td>";
		}
		echo "<td><input name='fn_$instr->id' type='text' style='width:16ex;' value='$instr->fn' onfocus='doFocus(\"$instr->id\")' onchange='doOnChange(\"$instr->id\")'></td>";
		echo "<td><input name='ln_$instr->id' type='text' style='width:16ex;' value='$instr->ln' onfocus='doFocus(\"$instr->id\")' onchange='doOnChange(\"$instr->id\")'></td>";
		echo "<td><input name='email_$instr->id' type='text' style='width:32ex;' value='$instr->email' onfocus='doFocus(\"$instr->id\")' onchange='doOnChange(\"$instr->id\")'></td>";
		echo '<td>';
		output_session_started($instr->id, $instr->started);
		echo '</td>';
		if ($instr->id == '_NEW') {
			echo "<td><input name='submit_$instr->id' type='button' value='Add' onclick='doAdd(\"$instr->id\")' disabled='true'></td>";					
		} else {
			echo "<td><input name='submit_$instr->id' type='button' value='Update' onclick='doUpdate(\"$instr->id\")' disabled='true'></td>";
			echo "<td id='td_delete_$instr->id'></td>";
		}
		echo '</tr>';
	}
	
	function output_json_row($instr) {
		echo
		  "'$instr->id':  {
		                'modified': false,
		                'ln': '$instr->ln',
		                'fn': '$instr->fn',
		                'email': '$instr->email',
		                'started': '$instr->started'
	                },";
	}
	
	function output_session_started($id, $started) {
		echo "<select name='started_$id' onchange='doOnChange(\"$id\")' onfocus='doFocus(\"$id\")'>";
		echo '<option></option>';
		
		$year = date('Y');
		$fallspr = array('Fall', 'Spr');
		for ($i=1; $i<7; $i++) {
			foreach ($fallspr as $season) {
				$session = $season . ' ' . $year;
				if ($started == $session) {
					echo "<option selected>$session</option>";	
				} else {
					echo "<option>$session</option>";
				}
			}
			--$year;
		}
		echo '</select>';
	}
	
	function validate_id($id) {
		global $errstr,$newconnn;
		
		$rs = mysqli_query($newconn, "SELECT * FROM instructors WHERE id = '$id'");
		if (mysqli_num_rows($rs) > 0) {
			$errstr = "The id '$id' has already been taken.";
			return false;
		}
		return validate_id_form($id);
	}
	
	function validate_id_form($id) {
		global $errstr;
		
		if (! preg_match('/^[a-z]{2,4}$/', $id)) {
			$errstr = 'An id must be 2 - 4 lower-case letters';
			return false;
		}
		return true;
	}
	
	function validate_name($name) {
		global $errstr;
		
		if (! preg_match('/^[a-zA-Z]{1,16}$/', $name)) {
			$errstr = "A name must be 1 - 16 letters. '$name' is not acceptable";
			return false;
		}
		return true;
	}
	
	function validate_email($email) {
		global $errstr;
		
		if ($email == '') return true;
		
		if (! preg_match('/^[a-zA-Z0-9\.\-_]{1,20}@[a-zA-Z0-9\.\-_]{1,25}$/', $email)) {
			$errstr = "'$email' is not an acceptable email";
			return false;
		}
		return true;
	}
	
	function validate_started($started) {
		global $errstr;
		
		if ($started == '') return true;
		
		if (! preg_match('/^(Fall|Spr) [0-9]{4}$/', $started)) {
			$errstr = "'$started' is not an acceptable start date.";
			return false;
		}
		return true;
	}
	
	function do_add() {
		global $role,$newconnn;
		$id = mysqli_real_escape_string($newconn, $_REQUEST['id']);
		$ln = mysqli_real_escape_string($newconn, $_REQUEST['ln']);
		$fn = mysqli_real_escape_string($newconn, $_REQUEST['fn']);
		$email = mysqli_real_escape_string($newconn, $_REQUEST['email']);
		$started = mysqli_real_escape_string($newconn, $_REQUEST['started']);
		
		if (! validate_id($id) || ! validate_name($fn) || ! validate_name($ln) || ! validate_email($email) || ! validate_started($started)) {
			return;
		}
		
		$query = "INSERT INTO instructors (id, ln, fn, email, role, started) VALUES ('$id', '$ln', '$fn', '$email', '$role', '$started')";
		mysqli_query($newconn, $query);
	}
	
	function do_update() {
		global $role,$newconnn;
		$id = mysqli_real_escape_string($newconn, $_REQUEST['id']);
		$ln = mysqli_real_escape_string($newconn, $_REQUEST['ln']);
		$fn = mysqli_real_escape_string($newconn, $_REQUEST['fn']);
		$email = mysqli_real_escape_string($newconn, $_REQUEST['email']);
		$started = mysqli_real_escape_string($newconn, $_REQUEST['started']);
		
		if (! validate_name($fn) || ! validate_name($ln) || ! validate_email($email) || ! validate_started($started)) {
			return;
		}
		
		$query = "UPDATE instructors SET id = '$id', ln = '$ln', fn = '$fn', email = '$email' , started = '$started' WHERE id = '$id'";
		mysqli_query($newconn, $query);
	}
	
	function do_delete() {
		global $role,$newconnn;
		$id = mysqli_real_escape_string($newconn, $_REQUEST['id']);
		
		if (! validate_id_form($id)) {
			return;
		}
		$query = "DELETE FROM instructors WHERE id = '$id'";
	 	mysqli_query($newconn, $query);
	}
	
	function do_ajax_query_instructor_links() {
		global $newconnn;
		$id = mysqli_real_escape_string($newconn, $_REQUEST['id']);
		$query = "SELECT count(id) FROM classes WHERE instructor = '$id'";
		$rs = mysqli_query($newconn, $query);
		$numLinks = mysqli_fetch_array($rs);
		
		header("Content-Type: text/plain");
		echo $numLinks[0];
	}
	
	function do_ajax_update() {
		header("Content-Type: text/plain");
		echo "OK";
	}
?>
