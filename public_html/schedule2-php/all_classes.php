<?php
	
	handle_request();

	/*
	 * Main control section. All request dispatching happens here.
	 */
	function handle_request() {
		$AJAX_UPDATE_INSTRUCTOR_SUCCESS_XML = 'all_classes/ajax_update_instructor_success.php';
		$AJAX_UPDATE_INSTRUCTOR_FAILURE_XML = 'all_classes/ajax_update_instructor_failure.php';
		$AJAX_SUCCESS_XML = 'all_classes/ajax_success.php';
		$AJAX_FAILURE_XML = 'all_classes/ajax_failure.php';
		
		require_once 'common.php';
		require_once 'instructor_lib.php';
		
		db_connect();
		
		$user = getUser(ADMIN_PRIV);
		if (! $user) {
			return; 
		}
		
		$action = $_REQUEST['action'];
		if ($action == "ajax_update_instructor") {
			$env = doAjaxUpdateInstructor();
			if ($env['success']) {
				include ($AJAX_UPDATE_INSTRUCTOR_SUCCESS_XML);
			} else {
				include($AJAX_UPDATE_INSTRUCTOR_FAILURE_XML);
			}
			return;
		} else if ($action == 'ajax_update_room') {
			$env = doAjaxUpdateRoom();
			if ($env['success']) {
				include ($AJAX_SUCCESS_XML);
			} else {
				include($AJAX_FAILURE_XML);
			}
		}else {
			$session = get_academic_session();
			$class_list = getClassList($session);
			include('all_classes/view.php');
		}
	}
		
	/*
	 * Prepare a list of all the course sections, ordered in 
	 * roughy the style Lori uses. Return an array of objects.
	 */
	function getClassList($session) {
		$class_list_query = <<<SQL
			SELECT 
  			cl.callnum, cl.course, cl.sectnum, co.name AS courseName, cl.subtitle,
  			CONCAT(time_format(cl.starttime, '%h%i'), '-', time_format(cl.endtime,'%h%i%p')) AS time, 
  			cl.days, 
  			CONCAT(time_format(cl.starttime2, '%h%i'), '-', time_format(cl.endtime2,'%h%i%p')) AS time2, 
  			cl.days2, cl.room, 
  			i.id AS instructor_id, i.ln, i.fn, 
  			r.name AS roleName,
  			cl.id AS class_id,
  			if(conv.instructor is null, '', '[C]') AS convenor,
  			cl.starttime,
  			DATE_FORMAT(cl.modified, '%Y%m%d') AS modified
  		FROM 
			classes cl LEFT OUTER JOIN courses co ON cl.course = co.id
			# The subquery pulls time dependent role from instructor_roles #
			# Written 4-26-17 TBR #
  				LEFT OUTER JOIN (instructors i INNER JOIN instructor_roles ir ON ir.id  = i.id AND ir.Session_order = (SELECT MAX(ir2.Session_order) FROM instructor_roles ir2 JOIN sessions s2 WHERE s2.id = '$session' AND ir2.Session_order <= s2.order AND ir2.id = ir.id)) ON cl.instructor = i.id
  				LEFT OUTER JOIN role_types r ON ir.role = r.id
  				LEFT OUTER JOIN convenors conv 
  					ON co.id = conv.course AND cl.instructor = conv.instructor AND cl.session = conv.session
  		WHERE cl.session = '$session'
  		##
  		# This ordering is totally ad hoc but it comes close to the 
		# ordering Lori uses in her spreadsheet
		# MODIFIED by APD on Aug 7, 2012 to use days to group MWF/TR classes together (mostly for 100A/101)
			# 
			ORDER BY cl.course, left(cl.sectnum, 1), left(cl.days, 1), cl.starttime, cl.sectnum, cl.id
SQL;
		$rs = mysql_query($class_list_query);
		while ($x = mysql_fetch_object($rs)) {
			$a[] = $x;
		}
		return $a;
	}

	/*
	 * Prepare a JSON list of the latest modification time for each
	 * class. Used by JS in the view to highlight the sections modified
	 * after a given date.   
	 */
	function getModifiedTimesList($class_list) {
		// line below added 3-10-17 for debugging - TBR
		$s = "";
		foreach ($class_list as $x) {
			if ($s) $s .= ',';
			$s .= $x->modified;
		}
		return $s;
	}
	
	function doAjaxUpdateInstructor() {

		header("Content-Type: text/xml");
		
		$spanId = $_REQUEST["span_id"];
		$instructor_id = $_REQUEST["value"];
		$class_id = $_REQUEST["class_id"];
		$session = $_REQUEST["session"];
		$overrideConflicts = $_REQUEST["overrideConflicts"];

		/* put instructor id in lower case */
		$instructor_id = strtolower(mysql_real_escape_string($instructor_id));
		$class_id = mysql_real_escape_string($class_id);
		$session = mysql_real_escape_string($session);
		
		// If the instructor id is empty then set the instructor to NULL
		if ($instructor_id == '') {
			$update = mysql_query(
				"UPDATE classes
				 SET instructor=NULL
				 WHERE id = '$class_id' AND session = '$session'"
			);
			
			if (! $update) {
				return array (
					'success' => false,
					'spanId' => $spanId,
					'message' => "Error updating the database.",
				        'conflicts' => false	
				);
			}
			
			return array (
				'success' => true,
				'spanId' => $spanId,
				'fn' => '',
				'ln' => '',
				'role' => ''
			);
		}
		
		// Load the instructor from the database
		$rs = mysql_query(
			"SELECT i.fn AS fn, i.ln AS ln, r.name AS role
			 FROM instructors i LEFT OUTER JOIN role_types r ON i.role = r.id 
			 WHERE i.id ='$instructor_id'");
		if (mysql_num_rows($rs) == 0) {
			return array (
				'success' => false,
				'spanId' => $spanId,
				'message' => "Unknown instructor '$instructor_id'",
			        'conflicts' => false	
			);
		}
		$instructor_row = mysql_fetch_assoc($rs);
		$fn = $instructor_row["fn"];
		$ln = $instructor_row["ln"];
		$role = $instructor_row["role"];
		
		// Check for conflicting assignments
		// only non-fillers have conflicts
		if ($role !== 'Fillers') {
		    $class = get_class_object($class_id, $session);
		    $conflicts = get_conflicts_for_instructor_class($instructor_id, $class);
		    if (count($conflicts) > 0 && !$overrideConflicts) {
			$errstr = $role . "  There are conflicts with the following assignment(s):\n";
			foreach ($conflicts as $c) {
				$errstr .= "    Math $c->course Section $c->sectnum\n";
			}
			$conflicts = get_conflicts_for_instructor_attend($instructor_id, $class);
			if (count($conflicts) > 0 && !$overrideConflicts) {
				$errstr .= "  There are conflicts with the following class(es) attended:\n";
				foreach ($conflicts as $c) {
					$errstr .= "    Math $c->course Section $c->sectnum\n";
				}
			}
			$errstr .= "Do you want to override conflicts?\n";
			$errstr .= "Press \"OK\" to override conficts.";

			return array (
				'success' => false,
				'spanId' => $spanId,
				'message' => $errstr,
			        'conflicts' => true	
			);
		    }
		    $conflicts = get_conflicts_for_instructor_attend($instructor_id, $class);
		    if (count($conflicts) > 0 && !$overrideConflicts) {
			    $errstr = $role . "  There are conflicts with the following class(es) attended:\n";
			    foreach ($conflicts as $c) {
				    $errstr .= "    Math $c->course Section $c->sectnum\n";
			    }
			    $errstr .= "Do you want to override conflicts?\n";
			    return array (
				    'success' => false,
				    'spanId' => $spanId,
				    'message' => $errstr,
				    'conflicts' => true 
			    );
		    }
		}
		$update = mysql_query(
			"UPDATE classes 
			 SET instructor = '$instructor_id' 
			 WHERE id = '$class_id' AND session = '$session'");
			 
		if (! $update) {
			return array (
				'success' => false,
				'spanId' => $spanId,
				'message' => "Error updating the database.",
			        'conflicts' => false	
			);
		}
		
		return array (
			'success' => true,
			'spanId' => $spanId,
			'fn' => $fn,
			'ln' => $ln,
			'role' => $role
		);
	}
	
	function doAjaxUpdateRoom() {
		// next line added 3-13-2017, TBR.
		header("Content-Type: text/xml");

		$room = mysql_real_escape_string($_REQUEST["value"]);
		$class_id = mysql_real_escape_string($_REQUEST["class_id"]);
		$session = mysql_real_escape_string($_REQUEST["session"]);
		
		$update = mysql_query(
			"UPDATE classes 
			 SET room = '$room' 
			 WHERE id = '$class_id' AND session = '$session'");
			 
		if (! $update) {
			return array (
				'success' => false,
				'message' => "Error updating the database." 
			);
		}
		
		return array ('success' => true);
	}
?>
