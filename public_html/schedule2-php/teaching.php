<?php
	require_once 'common.php';
	db_connect();
	
	$user = getUser(ADMIN_PRIV);

	if (! $user) {
		return; 
	}
	
	# Set these two vars to determine the current AY
	$acyear = get_academic_year();
	$fall = $acyear->fall;
	$spr = $acyear->spr;
	
	$role = mysql_real_escape_string($_REQUEST['role']);
	$role = (is_null($role) || $role == '') ? 'fac' : $role;
	
	if ($_REQUEST['action'] == 'ajax_update') {
		do_ajax_update();	
		return;
	}

	// The 'INNER JOIN' before 'WHERE' incorporates time-dependent roles.
	// Role is based on role in fall session.	
	$query = "SELECT 
			i.id, i.fn, i.ln, 
			t1.crhr as fall_crhr, t1.comments AS fall_comments,
			t2.crhr as spr_crhr, t2.comments AS spr_comments
		FROM 
			instructors i
				LEFT OUTER JOIN (
				  SELECT instructor, crhr, comments
				  FROM teaching WHERE session = '$fall' 
				) AS t1 ON i.id = t1.instructor
				LEFT OUTER JOIN (
				  SELECT instructor, crhr, comments
				  FROM teaching WHERE session = '$spr' 
				) AS t2 ON i.id = t2.instructor
			INNER JOIN instructor_roles ir ON ir.id = i.id AND ir.Session_order = (SELECT MAX(ir2.Session_order) FROM instructor_roles ir2 JOIN sessions s2 WHERE s2.id = '$fall' AND ir2.Session_order <= s2.order AND ir2.id = ir.id)
		WHERE 
			ir.role = '$role'
		ORDER BY i.ln, i.fn;";
	$rs = mysql_query($query);
			
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<title>Teaching assignment</title>
		<link rel="stylesheet" type="text/css" href="css/main.css">
		<style type="text/css">
		  TABLE.list {
		  	border-collapse: collapse;
		  }
		  TABLE.list TD {
		  	padding: 1px 4px;
		  }
		  TABLE.list TD.instr_name {
		  	white-space: nowrap;
		  }
			TR.hilight {
				background-color: #ddddbb;
			}
			TR.hilight INPUT[type="text"] {
				background-color: #f8f8d8;
			}
			#top_head TH {
				border-top: solid black 1px;
				border-left: solid black 1px;
				border-right: solid black 1px;
				background-color: #eeeecc;
			}
			#bottom_head TH {
				background-color: #f8f8d8;
			}
			#bottom_head TH.crhr {
				border-left: solid black 1px;
			}
			#bottom_head TH.left {
				border-left: solid black 1px;
			}
			#bottom_head TH.right {
				border-right: solid black 1px;
			}
		</style>
		<script src="js/fader.js" type="text/javascript"></script>
		<script type="text/javascript">

			var data = {
				<?php
					
					function js_escape($s) {
						return str_replace($s, "'", "\'");
					}
				
					mysql_data_seek($rs, 0);
					while ($x = mysql_fetch_object($rs)) {
						echo 
						  "'$x->id':  {
						                'modified': false,
						                'fall_crhr': '" . js_escape($x->fall_crhr) . "',
						                'fall_comments': '" . js_escape($x->fall_comments) . "',
						                'spr_crhr': '" . js_escape($x->spr_crhr) . "',
						                'spr_comments': '" . js_escape($x->spr_comments) . "'
					                },";
					}
				?>
			};
		
			function isNumeric(x) {
				var pat = /\d{1,2}/;
				return x.match(pat); 
			}
						
			function doUpdate(id) {
				var f = document.theForm;
				var fall_crhr = f["fall_crhr_" + id].value;
				var fall_comments = f["fall_comments_" + id].value;
				var spr_crhr = f["spr_crhr_" + id].value;
				var spr_comments = f["spr_comments_" + id].value;
				var acyear = f.acyear.options[f.acyear.selectedIndex].value;
				var action = "ajax_update";
				
				if (! (isNumeric(fall_crhr) && isNumeric(spr_crhr))) {
					alert("Credit hours must be a number between 0 and 99");
					return;
				} 
				
				var client = new XMLHttpRequest();
				client.open('POST', 'teaching.php');
				client.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
				client.onreadystatechange = function () { 
				
				  if (client.readyState == 4) {
					  
					  if (client.status != 200 || client.responseText != 'OK') {
					  	alert("Update failed. Please try again.");
					  	return;
					  }
					  
						f["submit_" + id].disabled = true;
						data[id].modified = false;
					  
					  var tr = document.getElementById("row_" + id);
					  var fdr = new Fader(tr, new Color(0,255,0), Color.WHITE);
					  fdr.doLast = function () { 
					  	tr.style.backgroundColor = null; 
						  tr.className = "";
					  }
					  fdr.start();
						
					}
				};
				
				var sendStr = 
					'action=' + escape(action) + '&id=' + escape(id) + '&acyear=' + escape(acyear) 
					+ '&fall_crhr=' + escape(fall_crhr)	+ '&fall_comments=' + escape(fall_comments)
					+ '&spr_crhr=' + escape(spr_crhr)	+ '&spr_comments=' + escape(spr_comments); 
				client.send(sendStr);
			}
			
			function doFocus(id) {
				var f = document.theForm;
				
				for (var i in data) {
					if (i != id && data[i].modified) {
						if (confirm("Data modified - save changes?")) {
							doUpdate(i);
							return;
						} else {
							var d = data[i];
							f['fall_crhr_' + i].value = d.fall_crhr;
							f['fall_comments_' + i].value = d.fall_comments;
							f['spr_crhr_' + i].value = d.spr_crhr;
							f['spr_comments_' + i].value = d.spr_comments;
							d.modified = false;
						}
					}
					f["submit_" + i].disabled = true;
					document.getElementById("row_" + i).className = "";
				}
				
				f["submit_" + id].disabled = false;
				document.getElementById("row_" + id).className = "hilight";
			}
			
			function doOnChange(id) {
				data[id].modified = true;
			}
			
		</script>
	</head>
	<body>
		<?php include 'std_header.php'; ?>
		
		<form name="theForm" method="post">
		
			<input type="hidden" name="id" value="">
			<input type="hidden" name="fall_crhr" value="">
			<input type="hidden" name="fall_comments" value="">
			<input type="hidden" name="spr_crhr" value="">
			<input type="hidden" name="spr_comments" value="">
			<input type="hidden" name="action" value="none">
			
			<div class="session_select">
				<?php make_role_select($role, "document.theForm.submit()") ?>
			</div>
			<div class="session_select">
				<?php make_acyear_select("document.theForm.submit()") ?>
			</div>
			
			<table class="list">
				<tr id='top_head'>
					<th>Name</th>
					<th colspan="2">Fall</th>
					<th colspan="2">Spring</th>
				</tr>
				<tr id='bottom_head'>
					<th class="left"></th>
					<th class="crhr">CrHr</th>
					<th>Comments</th>
					<th class="crhr">CrHr</th>
					<th class="right">Comments</th>
				</tr>
			<?php
				mysql_data_seek($rs, 0);
				while ($x = mysql_fetch_object($rs)) {
					echo "<tr id='row_$x->id'>";
					echo "<td class='instr_name'>$x->fn $x->ln</td>";
					echo "<td><input name='fall_crhr_$x->id' type='text' style='width:4ex;' maxlength='3' value='$x->fall_crhr' onfocus='doFocus(\"$x->id\")' onchange='doOnChange(\"$x->id\")'>";
					echo "<td><input name='fall_comments_$x->id' type='text' style='width:32ex;' value='$x->fall_comments' onfocus='doFocus(\"$x->id\")' onchange='doOnChange(\"$x->id\")'></td>";
					echo "<td><input name='spr_crhr_$x->id' type='text' style='width:4ex;' maxlength='3' value='$x->spr_crhr' onfocus='doFocus(\"$x->id\")' onchange='doOnChange(\"$x->id\")'></td>";
					echo "<td><input name='spr_comments_$x->id' type='text' style='width:32ex;' value='$x->spr_comments' onfocus='doFocus(\"$x->id\")' onchange='doOnChange(\"$x->id\")'></td>";
					echo "<td><input type='button' name='submit_$x->id' value='Update' onclick='doUpdate(\"$x->id\")' disabled='true'></td>";					
					echo '</tr>';
				}
			?>
			</table>
			
		</form>
		
		<?php
			mysql_close(); 
			include 'std_footer.php'; 
		?>
	</body>
</html><?php
	function do_ajax_update() {
		global $fall, $spr;
		
		header("Content-Type: text/plain");
		
		$id = mysql_real_escape_string($_REQUEST['id']);
		$fall_crhr = mysql_real_escape_string($_REQUEST['fall_crhr']);
		$fall_comments = mysql_real_escape_string($_REQUEST['fall_comments']);
		$spr_crhr = mysql_real_escape_string($_REQUEST['spr_crhr']);
		$spr_comments = mysql_real_escape_string($_REQUEST['spr_comments']);

		mysql_query("BEGIN");
		
		$delete = "DELETE FROM teaching WHERE instructor = '$id' AND session = '$fall'";
		$insert = "INSERT INTO teaching (instructor, session, crhr, comments) VALUES ('$id', '$fall', '$fall_crhr', '$fall_comments')";
		
		mysql_query($delete);
		mysql_query($insert);
		
		$delete = "DELETE FROM teaching WHERE instructor = '$id' AND session = '$spr'";
		$insert = "INSERT INTO teaching (instructor, session, crhr, comments) VALUES ('$id', '$spr', '$spr_crhr', '$spr_comments')";
		
		mysql_query($delete);
		mysql_query($insert);

		mysql_query("COMMIT");
		
		echo "OK";
	}
?>

