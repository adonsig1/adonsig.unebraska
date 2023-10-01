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
	
	function get_conflicts_for_instructor_class($instr_id, $class) {
		$conflicts = array();
		// none, canc, and stat do not have time conflicts, so only check others
                if ( ($instr_id != 'none') && ($instr_id != 'canc') && ($instr_id != 'stat') ) {
			$days_overlap = 'FALSE';
			$len = strlen($class->days);
			for ($i=0; $i < $len; $i++) {
				$c = substr($class->days, $i, 1);
                        	if ($c <> ' ') {
					$days_overlap .= " OR LOCATE('$c', days) > 0";
				}
			}
			$query =
		  	"SELECT 
		    	*
		  	FROM classes
		  	WHERE
		  		id != '$class->id '
		    	    AND instructor = '$instr_id' AND session = '$class->session'
		    	    AND (endtime > '$class->starttime' AND starttime < '$class->endtime')
        		    AND ( $days_overlap )";
			$rs = mysql_query($query);
			while ($x = mysql_fetch_object($rs)) {
				$conflicts[] = $x;
			}
                }
		return $conflicts;
	}

	function get_conflicts_for_instructor_attend($instr_id, $class) {
		$conflicts = array();
		// none, canc, and stat do not attend classes, so we treat all instructors the same //
		$days_overlap = 'FALSE';
		$len = strlen($class->days);
		for ($i=0; $i < $len; $i++) {
			$c = substr($class->days, $i, 1);
                       	if ($c <> ' ') {
				$days_overlap .= " OR LOCATE('$c', cl.days) > 0";
			}
		}
		$query =
		"SELECT 
			*
		FROM attending a INNER JOIN classes cl ON a.class = cl.id AND a.session = cl.session
		WHERE a.instructor = '$instr_id' AND a.session = '$class->session'
	    	    AND (cl.endtime > '$class->starttime' AND cl.starttime < '$class->endtime')
        	    AND ( $days_overlap )";
		$rs = mysql_query($query);
		while ($x = mysql_fetch_object($rs)) {
			$conflicts[] = $x;
		}
		return $conflicts;
	}

?>
?>
