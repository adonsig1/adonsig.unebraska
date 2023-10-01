<?php
	require_once 'common.php';
	require_once 'instructor_lib.php';
	
	db_connect();
	
	$user = getUser(ADMIN_PRIV);
	if (! $user) {
		return; 
	}
	
	$errstr = null;
	
	$session = get_academic_session();
	
	$instrId = mysql_real_escape_string($_REQUEST["instrId"]);
	$instructor = get_instructor_object($instrId);
	
	$action = $_REQUEST["action"];
	if ($action == 'list_instructors_ajax') {
		do_list_instructors_ajax($session); // At bottom of file
		return;
	} else if ($action == 'list_classes_ajax') {
		do_list_classes_ajax($session); // At bottom of file
		return;
	} else if ($action == 'delete_class') {
		do_delete_class(mysql_real_escape_string($_REQUEST["classId"]));
	} else if ($action == 'assign_class') {
		do_assign_class(mysql_real_escape_string($_REQUEST["classId"]));
	}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<title>Instructor Assignment</title>
		<link rel="stylesheet" type="text/css" href="css/main.css">
		<link rel="stylesheet" type="text/css" href="css/autosuggest_inquisitor.css">
		<style type="text/css">
			TD OL {
				margin: 0px; 
				padding-left: 1.3em;
			}
			TD {
				vertical-align: top;
			}
		</style>
		<script type="text/javascript" src="js/bsn.AutoSuggest_2.1.3.js"></script>
		<script type="text/javascript">
			function onLoad() {	    	
				/*
	    	 * Initialize the autosuggest feature.
		     */
				var options = {
						script: "/<?php echo $ROOT ?>/instructors.php?action=list_instructors_ajax&session=<?php echo $session ?>&",
						varname: "idLeft",
						json: false,
						maxresults: 10
					};
					var as = new bsn.AutoSuggest('instrId', options);

					options = {
							script: "/<?php echo $ROOT ?>/instructors.php?action=list_classes_ajax&session=<?php echo $session ?>&",
							varname: "idLeft",
							json: false,
							maxresults: 10
						};
					as = new bsn.AutoSuggest('classId', options);
					
					/*
					 * Set event handlers for input fields
					 */
					var oldOnKeyPress = document.mainForm.instrId.onkeypress; // Set by AutoSuggest
					document.mainForm.instrId.onkeypress = function (event) {
						oldOnKeyPress(event); // Perform the AutoSuggest binding
						var key = (window.event) ? window.event.keyCode : event.keyCode;
						if (key == 13) {
							document.mainForm.submit();
							return false;
						} else {
							var instrDataDiv = document.getElementById("instrDataDiv");
							// instrDataDiv.style.visibility = "hidden";
							instrDataDiv.innerHTML = "";
							return true;
						}
					}
			}
			
			function deleteAssignment(classId) {
				if (confirm("Really delete  " + classId + "?")) {
					window.location = 
						"instructors.php?action=delete_class" 
						+ "&classId=" + classId + "&instrId=<?php echo $instrId ?>&session=<?php echo $session ?>";
				}
			}
			
			function addAssignment() {
			  document.mainForm.action.value = 'assign_class';
				document.mainForm.submit();
			}
		</script>
	</head>
	<body onLoad="onLoad()">
		<?php
			// $toplinks = array("Hours" => "hours_assigned.php","Query" => "query_requests.php"); 
			include 'std_header.php'; 
		?>
		
		<form method="post" name="mainForm">
			<input type="hidden" name="action" value="submit">
			
			<div class="session_select">
				<?php make_session_select($session, "") ?>		
			</div>
			
			<?php if ($errstr): ?>
				<div class="error_message" style="margin: 10px 0;">
					<?php echo $errstr ?>
				</div>
			<?php endif; ?>
			
			<label for="instrId">Instructor:</label>
			<input type="text"
			       value="<?php echo $_REQUEST["instrId"] ?>" 
			       id="instrId" 
			       name="instrId" 
			       size="12" 
			       maxlength="4" 
			       onClick="select()">
			<?php make_button("OK", "tick.png", "document.mainForm.submit()"); ?>
			
			<div id="instrDataDiv">
			<?php 
				if ($instructor) :
			?>
			
				<div class="displaybox"  style="margin: 10px auto;">
					<?php echo $instructor->fn ?> <?php echo $instructor->ln ?>, 
					<?php
						if ($instructor->email) {
							echo "<a href=\"mailto:$instructor->email\">$instructor->email</a>, "; 
						}
					?>
					<?php 
						$crhr = get_credit_hours($instrId, $session); 
						$assigned_crhr =  get_assigned_credit_hours($instrId, $session);
						if ($crhr == $assigned_crhr) {
							$crhr_fit = "match";
						} else if ($crhr > $assigned_crhr) {
							$crhr_fit = "under-assigned";
						} else {
							$crhr_fit = "over-assigned";
						}
					?>
					<?php echo $crhr ?> cr hr, assigned <?php echo $assigned_crhr ?> cr hr, <?php echo $crhr_fit ?>
					<?
						$comments = get_teaching_comments($instrId, $session);
						if ($comments) {
							echo "($comments)";
						}
					?>
				</div>
				
				<label for="classId">Class:</label>
				<input type="text"
				       value="<?php echo $_REQUEST["classId"] ?>" 
				       id="classId" 
				       name="classId" 
				       size="12" 
				       maxlength="12" 
				       onClick="select()">
				<?php make_button("Assign", "tick.png", "addAssignment()"); ?>

				<h2>Teaching Preferences</h2>

				<?
					$query = 
						"SELECT 
								cl.id AS id, cl.course AS course, cl.sectnum AS section, 
								CONCAT(TIME_FORMAT(cl.starttime, '%h:%i%p'), ' - ', TIME_FORMAT(cl.endtime, '%h:%i%p')) AS time,
								CONCAT(TIME_FORMAT(cl.starttime2, '%h:%i%p'), ' - ', TIME_FORMAT(cl.endtime2, '%h:%i%p')) AS time2,
								cl.days AS days, cl.days2 AS days2, cl.room AS room, co.name AS name, co.crhr AS crhr
							FROM classes cl INNER JOIN courses co ON cl.course = co.id
							WHERE cl.instructor = '$instrId' AND cl.session = '$session'";
					$rs = mysql_query($query);
				?>
				<table class="display" style="margin: 10px auto;">
					<?php 
						while ($r = mysql_fetch_assoc($rs)) {
							$classId = $r["id"];
							echo '<tr>';
							echo '<td>' . $r["course"] . '</td>';
							echo '<td>' . $r["section"] . '</td>';
							if (empty($r["days2"])) {
								echo '<td>' . $r["time"] . '</td>';
								echo '<td>' . $r["days"] . '</td>';
							} else {
								echo '<td>' . $r["time"] . '<br>' . $r["time2"] . '</td>';
								echo '<td>' . $r["days"] . '<br>' . $r["days2"] . '</td>';
							}
							echo '<td>' . $r["room"] . '</td>';
							echo '<td>' . $r["name"] . '</td>';
							echo '<td>' . $r["crhr"] . '</td>';
							echo "<td><a href=\"javascript:void(0)\" onClick=\"deleteAssignment('$classId')\">delete</a></td>";
							echo '</tr>';
						}
					?>
				</table>
				
				<table class="display" style="width: 90%; margin: 10px auto;">
					<tr>
						<th>Good Classes</th>
						<th>Bad Classes</th>
						<th>Good Times</th>
						<th>Bad Times</th>
					</tr>
					<tr>
						<td style="height:60px;"><?php echo get_good_course_list($instrId, $session) ?></td>
						<td><?php echo get_bad_course_list($instrId, $session) ?></td>
						<td><?php echo get_good_time_list($instrId, $session) ?></td>
						<td><?php echo get_bad_time_list($instrId, $session) ?></td>
					</tr>
					<?php	if ($attending = get_attending($instrId, $session)) :	?>
					<tr>
						<td colspan="4"><?php echo $attending ?></td>
					</tr>
					<?php	endif; ?>
					<?php	if ($prefs_comments = get_prefs_comments($instrId, $session)) :	?>
					<tr>
						<td colspan="4">
							<?php echo $prefs_comments ?>
						</td>
					</tr>
					<?php endif; ?>
				</table>
				
				<h2>Previous Classes Taught</h2>
				
				<?
					$query = 
						"select co.id as course_id, co.name as course, s.name as session, cl.eval as eval from
						   classes cl inner join courses co on cl.course = co.id
						   inner join sessions s on cl.session = s.id
						 where cl.instructor = '$instrId'
						 order by s.order asc";
					$rs = mysql_query($query);
					
					if (mysql_num_rows($rs) > 0):
				?>				
					<table class="display">
						<tr>
							<th>ID</th>
							<th>Class Name</th>
							<th>Session</th>
							<th>Eval</th>
						</tr>
						<?php
							while ($r = mysql_fetch_assoc($rs)) {
								echo '<tr>';
								echo '<td>' . $r['course_id'] . '</td>';
								echo '<td>' . $r['course'] . '</td>';
								echo '<td>' . $r['session'] . '</td>';
								echo '<td>' . $r['eval'] . '</td>';
								echo '</tr>';
							}
						?>
					</table>
				<?php else: ?>
					<p>No previous courses taught.</p>
				<?php  endif ?>
						
			<?php 
				endif;
			?>
			</div>
			
		</form>
		
		<?php include 'std_footer.php'; ?>
	</body>
</html>
<?php 

	function do_list_instructors_ajax($session) {
		$idLeft = mysql_real_escape_string($_REQUEST["idLeft"]);
		$idLeftLength = strlen($idLeft);
		$query = 
			"SELECT id, CONCAT(fn, ' ', ln) AS name
			FROM instructors
			WHERE LEFT(id, $idLeftLength) = '$idLeft'";
		do_ajax($query);
	}

	function do_list_classes_ajax($session) {
		$idLeft = mysql_real_escape_string($_REQUEST["idLeft"]);
		$idLeftLength = strlen($idLeft);
		$query = 
			"SELECT 
		     cl.id AS id,  
		      CONCAT(
		        co.name, ': ', cl.days, ' ',
		        TIME_FORMAT(cl.starttime, '%h%i'), '-', TIME_FORMAT(cl.endtime,'%h%i%p'), ' ',
		        IF(ISNULL(i.id), '(none)', CONCAT(i.fn, ' ', i.ln))
		      ) AS name
			FROM 
			  classes cl INNER JOIN courses co ON cl.course = co.id
			    LEFT OUTER JOIN instructors i ON cl.instructor = i.id
			WHERE cl.session = '$session' AND LEFT(cl.id, $idLeftLength) = '$idLeft'";
		do_ajax($query);
	}

	function do_ajax($query) {
		header("Content-Type: text/xml");
		echo '<?xml version="1.0" ?>';
		echo '<results>';
		
		$rs = mysql_query($query);
		while ($r = mysql_fetch_assoc($rs)) {
			$id = $r["id"];
			$name = htmlspecialchars($r["name"]);
			echo "<rs id='$id' info='$name'>$id</rs>";
		}
		
		echo '</results>';
	}

	function do_delete_class($classId) {
		global $session;
		$query = "UPDATE classes SET INSTRUCTOR = NULL WHERE id = '$classId' AND session = '$session'";
		mysql_query($query);
	}

	function do_assign_class($classId) {
		global $session, $instructor, $errstr;
		if ($instructor == null) {
			$errstr = "Unrecognized instructor. Please try again.";
			return;
		}
		$class = get_class_object($classId, $session);
		if ($class == null) {
			$errstr = "Unrecognized class ID. Please try again.";
			return;
		} 
		// only check conflictsfor non-fillers
		if ($instructor->role !== 'Fillers') {
		    $conflicts = get_conflicts_for_instructor_class($instructor->id, $class);
		    if (count($conflicts) > 0) {
			$errstr = 'There are conflicts with the following assignment(s):<br>';
			foreach ($conflicts as $c) {
				$errstr .= "    Math $c->course Section $c->sectnum<br>";
			}
			return;
		    }
		    $conflicts = get_conflicts_for_instructor_attend($instructor->id, $class);
		    if (count($conflicts) > 0) {
			$errstr = 'There are conflicts with the following class(es) attended:<br>';
			foreach ($conflicts as $c) {
				$errstr .= "    Math $c->course Section $c->sectnum<br>";
			}
			return;
		    }
		}
		
		$query = "UPDATE classes SET INSTRUCTOR = '$instructor->id' WHERE id = '$classId' AND session = '$session'";
		mysql_query($query);
	}
?>
