<?
	class Instructor {
		public $id;
		public $ln;
		public $fn;
		public $email;
		public $role;
		public $comments;
		public $started;
		public $passwd;
		public $priv; 
		
		public function Instructor($new_id = NULL) {
			if ($new_id != NULL) {
				$this->id = $new_id;
			}
		}
	}

	function get_instructor_object($id) {
		$rs = mysql_query("SELECT * FROM instructors WHERE id = '$id'");
		return mysql_fetch_object($rs);
	}
	
	function get_class_object($classId, $session) {
		$rs = mysql_query("SELECT * FROM classes WHERE id = '$classId' AND session = '$session'");
		return mysql_fetch_object($rs);
	}
	
	function get_list($query) {
		$rs = mysql_query($query);
		if (mysql_num_rows($rs) == 0) {
			return NULL;
		}
		
		$s = '<ol>';
		while ($r = mysql_fetch_array($rs)) {
			$s .= "<li>" . $r[0] . "</li>";
		}
		$s .= '</ol>';
		return $s;
	}
	
	function get_good_course_list($instrId, $session) {
		$query =
			"SELECT course FROM pref_courses 
			WHERE 
				instructor = '$instrId' 
				AND session='$session' 
				AND pref > 0 
			ORDER BY pref DESC";
		return get_list($query);
	}
	
	function get_bad_course_list($instrId, $session) {
		$query =
			"SELECT course FROM pref_courses 
			WHERE 
				instructor = '$instrId' 
				AND session='$session' 
				AND pref < 0 
			ORDER BY pref ASC";
		return get_list($query);
	}
	
	function get_good_time_list($instrId, $session) {
		$query =
			"SELECT 
				CONCAT(TIME_FORMAT(start, '%h:%i%p'), ' - ', TIME_FORMAT(end, '%h:%i%p'), ' ', days) 
			FROM pref_times 
			WHERE 
				instructor = '$instrId' 
				AND session='$session' 
				AND pref > 0 
			ORDER BY pref DESC";
		return get_list($query);
	}
	
	function get_bad_time_list($instrId, $session) {
		$query =
			"SELECT 
				CONCAT(TIME_FORMAT(start, '%h:%i%p'), ' - ', TIME_FORMAT(end, '%h:%i%p'), ' ', days) 
			FROM pref_times 
			WHERE 
				instructor = '$instrId' 
				AND session='$session' 
				AND pref < 0 
			ORDER BY pref ASC";
		return get_list($query);
	}
	
	function get_prefs_comments($id, $session) {
		$rs = mysql_query("SELECT comment FROM pref_comments WHERE instructor = '$id' AND session='$session'");
		if ($r = mysql_fetch_array($rs)) {
			return $r[0];
		} else {
			return '';
		}
	}
	
	function get_attending($id, $session) {
		$query =
			"SELECT 
				CONCAT(
					'Math ', cl.course, '-', cl.sectnum , ': ',
					TIME_FORMAT(cl.starttime, '%h:%i%p'), ' - ', TIME_FORMAT(cl.endtime, '%h:%i%p'), ' ',
					cl.days
				)
			FROM attending a INNER JOIN classes cl ON a.class = cl.id AND a.session = cl.session
			WHERE a.instructor = '$id' AND a.session = '$session'";
		return get_list($query);
	}
	
	// assumes intructor is not a filler
	function get_conflicts_for_instructor_class($instr_id, $class) {
		$conflicts = array();
		$conflicts = get_conflicts_class_time_slot($instr_id,$class->id,$class->session,$class->days,
			$class->starttime,$class->endtime);
		if (empty($conflicts)) {
			$conflicts = get_conflicts_class_time_slot($instr_id,$class->id,$class->session,$class->days2,
				$class->starttime2,$class->endtime2);
		}
		return $conflicts;
	}

        // added function to check for conflict with a particular time slot
	// rewrote function to deal with multiple class meeting times APD Mar 27, 2014 
	function get_conflicts_class_time_slot($instr_id, $id, $session, $days, $stime, $etime) {
		$days_overlap = 'FALSE';
		$len = strlen($days);
		for ($i=0; $i < $len; $i++) {
			$c = substr($days, $i, 1);
                       	if ($c <> ' ') {
				$days_overlap .= " OR LOCATE('$c', days) > 0";
			}
		}
		$query =
	  	"SELECT 
	    	*
	  	FROM classes
	  	WHERE
	  		id != '$id '
	    	    AND instructor = '$instr_id' AND session = '$session'
	    	    AND (endtime > '$stime' AND starttime < '$etime')
       		    AND ( $days_overlap )";
		$rs = mysql_query($query);
		// if no conflicts in first time slot of class list, check second
		if (mysql_num_rows($rs) == 0) {
			$days_overlap = 'FALSE';
			$len = strlen($days);
			for ($i=0; $i < $len; $i++) {
				$c = substr($days, $i, 1);
                       		if ($c <> ' ') {
					$days_overlap .= " OR LOCATE('$c', days2) > 0";
				}
			}
			$query =
	  		"SELECT 
	    		*
	  		FROM classes
	  		WHERE
	  			id != '$id '
	    	    	    AND instructor = '$instr_id' AND session = '$session'
	    	    	    AND (endtime2 > '$stime' AND starttime2 < '$etime')
       		    	    AND ( $days_overlap )";
			$rs = mysql_query($query);
		}
		while ($x = mysql_fetch_object($rs)) {
			$conflicts[] = $x;
		}
		return $conflicts;
	}

        
	// added function to check for conflicts with classes attended   APD Aug 2, 2013 
	// rewrote function to deal with multiple class meeting times APD Mar 27, 2014 
	function get_conflicts_for_instructor_attend($instr_id, $class) {
		$conflicts = array();
		// none, canc, and stat do not attend classes, so we treat all instructors the same 
		$conflicts = get_conflicts_attend_time_slot($instr_id,$class->id,$class->session,$class->days,
			$class->starttime,$class->endtime);
		if (empty($conflicts)) {
			$conflicts = get_conflicts_attend_time_slot($instr_id,$class->id,$class->session,$class->days2,
				$class->starttime2,$class->endtime2);
		}
		return $conflicts;
	}

	// added function to check for attendence conflict with one time slot of class APD Mar 25, 2014 //
	function get_conflicts_attend_time_slot($instr_id, $id, $session, $days, $stime, $etime) {
		$days_overlap = 'FALSE';
		$len = strlen($days);
		for ($i=0; $i < $len; $i++) {
			$c = substr($days, $i, 1);
                       	if ($c <> ' ') {
				$days_overlap .= " OR LOCATE('$c', cl.days) > 0";
			}
		}
		$query =
		"SELECT 
			*
		FROM attending a INNER JOIN classes cl ON a.class = cl.id AND a.session = cl.session
		WHERE a.instructor = '$instr_id' AND a.session = '$session'
	    	    AND (cl.endtime > '$stime' AND cl.starttime < '$etime')
        	    AND ( $days_overlap )";
		// if no conflicts in first time slot of class list, check second
		$rs = mysql_query($query);
		if (mysql_num_rows($rs) == 0) {
			$days_overlap = 'FALSE';
			$len = strlen($days);
			for ($i=0; $i < $len; $i++) {
				$c = substr($days, $i, 1);
                       		if ($c <> ' ') {
					$days_overlap .= " OR LOCATE('$c', cl.days2) > 0";
				}
			}
			$query =
			"SELECT 
				*
			FROM attending a INNER JOIN classes cl ON a.class = cl.id AND a.session = cl.session
			WHERE a.instructor = '$instr_id' AND a.session = '$session'
	    		    AND (cl.endtime2 > '$stime' AND cl.starttime2 < '$etime')
        		    AND ( $days_overlap )";
			$rs = mysql_query($query);
		}
		while ($x = mysql_fetch_object($rs)) {
			$conflicts[] = $x;
		}
		return $conflicts;
	}

?>
