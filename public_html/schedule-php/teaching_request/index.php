<?php	
	require_once '../common.php';	
	
	$MAX_PREF = 16; // The "good prefs" are numbered decreasing from this 
	
	$user = getUser(INSTR_PRIV);
	if (! $user) {
		return; 
	}
	
	db_connect();
	$current_session = mysqli_real_escape_string($newconn, $_REQUEST['session']);
	$current_session = $current_session ? $current_session : "spr09";
	$current_session_name = get_current_session_name($current_session);
	
	function make_time_select($id) {
		global $newconn, $current_session, $user;
		$instructor = $user["login"];

                $times = array(
			"7:30AM","8:00AM","8:30AM","9:00AM","9:30AM","10:00AM","10:30AM",
			"11:00AM","11:30AM","12:00PM","12:30PM","1:00PM","1:30PM",
			"2:00PM","2:30PM","3:00PM","3:30PM","4:00PM","4:30PM", "5:00PM", "5:30PM", "6:00PM"
		);
		
		if ($id == 'good_times') {
			$pref_crit = 'pref > 0';
			$pref_order = 'DESC';
		} else {
			$pref_crit = 'pref < 0';
			$pref_order = 'ASC';
		}
		
		$query =
			"SELECT
				CONCAT(TIME_FORMAT(start, '%h:%i%p'), ' - ', TIME_FORMAT(end, '%h:%i%p'), ' ', days) AS text
			FROM pref_times
			WHERE instructor = '$instructor' AND session = '$current_session' AND $pref_crit
			ORDER BY pref $pref_order";
		
		include 'time_select.php';
	}


        /*
         * Get instructor's role
         */
        function get_instructor_role($id) {
                global $newconn; 
                $query = <<<SQL
                        SELECT inn.role AS role 
                        FROM
                                instructors inn 
                        WHERE
                                inn.id = '$id'
SQL;
                $rs = mysqli_query($newconn, $query);
                if (mysqli_num_rows($rs) > 0) {
                        $r = mysqli_fetch_array($rs);
                        return $r["role"];
                } else {
                        return "null";
                }
        }

	
	function make_course_select($id,$current_session,$instructor) {
		global $newconn;
	
		if ($id == 'good_courses') {
			$pref_crit = 'pr.pref < 0';
		} else {
			$pref_crit = 'pr.pref > 0';
		}


                if ( get_instructor_role($instructor)=='gta') {
			$gtaflag='co.GTAteach >= 1';
		} else {
			$gtaflag='co.GTAteach >= 0';
		}
		
		$query_non = 
			"SELECT DISTINCT
			   CONCAT('Math ', co.id, ', ', co.name) AS text,
			   co.id AS value
			FROM
				courses co INNER JOIN classes cl ON co.id = cl.course 
				LEFT OUTER JOIN pref_courses pr ON pr.course = co.id
			WHERE 
				$gtaflag
				AND 
				cl.session = '$current_session' 
				AND (
					pr.instructor IS NULL 
					OR (
						pr.instructor <> '$instructor' OR $pref_crit
					)
				)
			ORDER BY text";
		
		if ($id == 'good_courses') {
			$pref_crit = 'pr.pref > 0';
			$pref_order = 'DESC';
		} else {
			$pref_crit = 'pr.pref < 0';
			$pref_order = 'ASC';
		}
		
		# we don't restricte selected courses by GTA preferences
		$query_sel =
			"SELECT
			   CONCAT('Math ', co.id, ', ', co.name) AS text,
			   co.id AS value
			FROM 
				pref_courses pr INNER JOIN courses co ON pr.course = co.id
			WHERE 
				pr.instructor = '$instructor' AND pr.session = '$current_session' AND $pref_crit
			ORDER BY pref $pref_order";
		
		include 'course_select.php';
	}
	
	function make_class_select($id,$current_session,$instructor) {
		global $newconn;
		
		$query_non = 
			// this selection should really include the second meeting time, but I'm skipping it, since I don't think
			// grad classes will ever have such a schedule  APD March 27, 2014
			"SELECT
				CONCAT(
					'Math ', co.id, '-', cl.sectnum, ', ', co.name, ' ', 
					TIME_FORMAT(cl.starttime, '%h%i'), '-', TIME_FORMAT(cl.endtime,'%h%i%p'),
			 		' ', cl.days
			 	) AS text,
			 	cl.id AS value
			FROM 
				courses co INNER JOIN classes cl ON co.id = cl.course 
				LEFT OUTER JOIN attending a ON a.class = cl.id AND a.session = cl.session AND a.instructor='$instructor'
			WHERE 
				cl.session = '$current_session' 
				AND (a.instructor IS NULL OR a.instructor <> '$instructor')
				AND (LEFT(co.id, 1) = '8'
					 OR LEFT(co.id, 1) = '9' 
					 OR (LEFT(co.id, 1) = '4' AND LENGTH(co.id) > 4 AND SUBSTRING(co.id, 5, 1) = '8') )
                               AND ( length(cl.days) > 0 )
                        ORDER BY
                                co.id";
		
		$query_sel =
			// this selection should really include the second meeting time, but I'm skipping it, since I don't think
			// grad classes will ever have such a schedule  APD March 27, 2014
			"SELECT
				CONCAT(
					'Math ', co.id, '-', cl.sectnum, ', ', co.name, ' ', 
					TIME_FORMAT(cl.starttime, '%h%i'), '-', TIME_FORMAT(cl.endtime,'%h%i%p'),
			 		' ', cl.days
			 	) AS text,
			 	cl.id AS value
			FROM 
				courses co INNER JOIN classes cl ON co.id = cl.course 
				INNER JOIN attending a ON a.class = cl.id AND a.session = cl.session
			WHERE 
				cl.session = '$current_session'
				AND a.instructor = '$instructor'
                        ORDER BY
                                co.id";
		
		$unordered_list = 1;
		include 'course_select.php';
	}
	
	function get_comments() {
		global $newconn, $current_session, $user;
		$instructor = $user["login"];
		
		$query = 
			"SELECT comment 
			FROM pref_comments 
			WHERE instructor = '$instructor' AND session = '$current_session'";
		$rs = mysqli_query($newconn, $query);
		if ($r = mysqli_fetch_assoc($rs)) {
			return $r["comment"];
		}
	}
	
	function get_current_session_name($curr_session) {
                global $newconn;
		$query = "SELECT name FROM sessions WHERE id = '$curr_session'";
		$rs = mysqli_query($newconn, $query);
		if (mysqli_num_rows($rs) > 0) {
			$r = mysqli_fetch_assoc($rs);
			return $r["name"];
		} else {
			return "{$current_session}";
		}
	}
	
	function do_submit_request($current_session,$id,$MAX_PREF) {
		global $newconn;
		
		$query = "DELETE FROM pref_courses WHERE instructor = '$id' AND session = '$current_session'";
		mysqli_query($newconn, $query) or die(mysqli_error($newconn));
		read_course_prefs(TRUE);
		read_course_prefs(FALSE);
		
		$query = "DELETE FROM pref_times WHERE instructor = '$id' AND session = '$current_session'";
		mysqli_query($newconn, $query) or die(mysqli_error($newconn));
		read_time_prefs(TRUE);
		read_time_prefs(FALSE);
		
		read_bad_classes();
		read_comments();
	}
	
	function read_course_prefs($good) {
		global $newconn, $current_session, $user, $MAX_PREF;
		
		$id = $user["login"];
		if ( $good ) {
		      if ( isset( $_REQUEST["good_courses"] ) ) {
		            $courses = $_REQUEST["good_courses"];
		      } else {
		            return;
		      }
		} else {
		      if ( isset( $_REQUEST["bad_courses"] ) ) {
		            $courses = $_REQUEST["bad_courses"];
		      } else {
		            return;
		      }
		}
		// $courses = $_REQUEST[$good ? "good_courses" : "bad_courses"];		
		$num = count($courses);
		for ($i=0; $i<$num; $i++) {
			$course = mysqli_real_escape_string($newconn, $courses[$i]);
			$pref = $good ? $MAX_PREF - $i : $i - $MAX_PREF;
			$query = 
				"INSERT INTO pref_courses 
					(instructor, session, course, pref) 
				VALUES 
					('$id', '$current_session', '$course', $pref)";
			mysqli_query($newconn, $query) or die(mysqli_error($newconn));
		}
	}
	
	function read_time_prefs($good) {
		global $newconn, $current_session, $user, $MAX_PREF;
		
		$id = $user["login"];
		if ( $good ) {
		      if ( isset( $_REQUEST["good_times"] ) ) {
		            $times = $_REQUEST["good_times"];
		      } else {
		            return;
		      }
		} else {
		      if ( isset( $_REQUEST["bad_times"] ) ) {
		            $times = $_REQUEST["bad_times"];
		      } else {
		            return;
		      }
		}
		// $times = $_REQUEST[$good ? "good_times" : "bad_times"];
		
		$num = count($times);
		for ($i=0; $i<$num; $i++) {
			$time_range = mysqli_real_escape_string($newconn, $times[$i]);
			preg_match("/^(.*) - (.*) (.*)$/", $time_range, $a);
			$start = $a[1];
			$end = $a[2];
			$days = $a[3];
			$pref = $good ? $MAX_PREF - $i : $i - $MAX_PREF;
			$query = 
				"INSERT INTO pref_times 
					(instructor, session, start, end, days, pref) 
				VALUES 
					(
						'$id', '$current_session', 
						str_to_date('$start', '%h:%i%p'), 
						str_to_date('$end', '%h:%i%p'), 
						'$days', $pref
					)";
			mysqli_query($newconn, $query) or die(mysqli_error($newconn));
		}
	}
	
	function read_bad_classes() {
		global $newconn,$current_session, $user, $MAX_PREF;
		$id = $user["login"];
		if ( isset($_REQUEST["bad_classes"]) ) {   
		        $classes = $_REQUEST["bad_classes"];
                 } else {
                         return;
                 }
		
		$query = 
			"DELETE FROM attending WHERE instructor = '$id' AND session = '$current_session'";
		mysqli_query($newconn, $query) or die(mysqli_error($newconn));
		
		$num = count($classes);
		for ($i=0; $i<$num; $i++) {
			$class = $classes[$i];
			$query =
				"INSERT INTO attending
					(instructor, class, session)
				VALUES
					('$id', '$class', '$current_session')";
			mysqli_query($newconn, $query) or die(mysqli_error($newconn));
		}
	}
	
	function read_comments() {
		global $newconn, $current_session, $user;
		
		$id = $user["login"];
		$comments = mysqli_real_escape_string($newconn, $_REQUEST["comments"]);
		
		$query = 
			"DELETE FROM pref_comments WHERE instructor = '$id' AND session = '$current_session'";
		mysqli_query($newconn, $query) or die(mysqli_error($newconn));
		
		$query = 
			"INSERT INTO pref_comments
				(instructor, session, comment)
			VALUES
				('$id', '$current_session', '$comments')";
		mysqli_query($newconn, $query) or die(mysqli_error($newconn));
	}
	
	function send_summary_email() {
		global $ROOT, $user, $current_session_name;
		
		$email = $user["email"];
		
		if (! $email) {
			return FALSE;
		}
		
		$fn = $user["fn"];
		$ln = $user["ln"];
		$list = get_summary_list();
		$message = <<<MAIL
Dear $fn,

Thank you for completing the on-line teaching preferences form
for $current_session_name at http://www.math.unl.edu/$ROOT/ 

The following information has been recorded:

$list

If you want to make any changes to this information, simply
return to the site and log in with the same ID and password.

Thanks!
		
MAIL;
		return mail(
			$email, 
			"Teaching preferences: Confirmation",
			$message,
			"Reply-To: aradcliffe1@math.unl.edu"
		);
		/*
		return mail(
			"aradcliffe1@math.unl.edu", 
			"BACKUP: $fn $ln",
			$message,
			"Reply-To: aradcliffe1@math.unl.edu"
		);
		*/
		
	}
	
	function get_summary_list() {
		$text = "";
		$text .= "Preferred Courses:\n";
		if ( isset( $_REQUEST["good_courses"] ) ) {
		      $text .= list_array($_REQUEST["good_courses"]);
		}
		$text .= "\n";
		$text .= "Courses to Avoid:\n";
		if ( isset( $_REQUEST["bad_courses"] ) ) {
		       $text .= list_array($_REQUEST["bad_courses"]);
		}
		$text .= "\n";
		$text .= "Preferred Times:\n";
		if ( isset( $_REQUEST["good_times"] ) ) {
		     $text .= list_array($_REQUEST["good_times"]);
		}
		$text .= "\n";
		$text .= "Times to Avoid:\n";
		if ( isset( $_REQUEST["bad_times"] ) ) {
		       $text .= list_array($_REQUEST["bad_times"]);
		}
		$text .= "\n";
		$text .= "Classes Attending:\n";
		if ( isset( $_REQUEST["bad_classes"] ) ) {
		       $text .= list_array($_REQUEST["bad_classes"]);
		}
		$text .= "\n";
		$text .= "Other Comments:\n";
		if ( isset( $_REQUEST["comments"] ) ) {
		       $text .= $_REQUEST["comments"];
		}
		
		return $text;
	}
	
	function list_array($a) {
		$s = "";
		$n = count($a);
		for ($i=0; $i<$n; $i++) {
			$s .= " " . ($i + 1) . ") " . $a[$i] . "\n";
		}
		return $s;
	}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<title>Teaching Request Form</title>
		<script type="text/javascript" src="scripts.js"></script>
		<script type="text/javascript">
			function doCancel() {
				if (confirm("Do you want to cancel your teaching request without sending it?")) {
					window.location = "/<?php echo $ROOT ?>";
				}
			}
		</script>

		<link rel="stylesheet" href="../css/main.css">
		<link rel="stylesheet" href="style.css">
	</head>
	<body>
		<?php 
			include '../std_header.php';
			
			$action_done = 0;
			if ( isset($_REQUEST["action"]) && ( $_REQUEST["action"] == "submit_request") ) {
				do_submit_request($current_session,$user["login"],$MAX_PREF);
				$email_status = send_summary_email();
				$action_done = 1;
			}
			
			if ($action_done):  
		?>
		
			<h1>Confirmation</h1>
			
			<p>
				Thank you for completing the on-line teaching preferences form
				for <?php echo $current_session_name ?>.
				
				<?php if ($email_status) : ?> 
					A confirmation email containing the following information
					has been sent to you.
				<?php else : ?>
					The following information has been recorded for you.
					Please save or print this page for your records.
				<?php endif;?>
			</p>
			
			<div class="summary_list"><?php echo str_replace("\n", "<br>\n", get_summary_list()) ?></div>
			
			<p>
				If you would like to make changes to this data, you
				can log in to this page again, and edit your choices.
			</p>
		
		<?php 
			else : // proceed to the end of the form...
		?>
					
		<form name="main_form" method="post" action="index.php">
			<input type="hidden" name="action" value="submit_request">
			<input type="hidden" name="session" value="<?php echo $current_session ?>">

		<h1>Teaching Preferences Form for <?php echo $current_session_name ?></h1>
			
			<p>
				Welcome, <?php echo $user["fn"] ?> <?php echo $user["ln"] ?>. 
				Please use this form to indicate your teaching preferences for
				<b><?php echo $current_session_name ?></b>. 
 				If you strongly prefer not to use this web form, you can send an email with your teaching preferences to
				<a href="mailto:adonsig1@unl.edu">Allan Donsig</a>.
			</p>
                        <p style="color:red">
		        	<b> Spring 2023 classes will be in-person unless alternative arrangements have been made with the vice chair.</b>
		       	</p>
			
			<?php 
				$credit_hours = get_credit_hours($user["login"], $current_session);
				
				if ($credit_hours) :
			?>
			
			<div class="displaybox">
				<b>Note:</b>
				My records are that you are planning to teach
				<b><?php echo $credit_hours ?> credit hours</b> this semester.
				If this is not correct, please contact the vice chair, 
				<a href="mailto:adonsig1@unl.edu">Allan Donsig</a>
			</div>
			
			<?php 
				endif;
			?>
			
			<h2>Course Preferences</h2>
			
			<p class="instruction">
				<span class="count">1</span>
				Please select the courses which you would most <b>like</b> to teach,
				with your highest preference at the <b>top</b> of the list.
				<?php $role = get_instructor_role($user["login"]);
				if ( ($role=='fac') OR ($role=='pd') ) { ?>
				Include in this list any courses which it was agreed you would teach
				at one of the course planning meetings:
				<?php } ?>
			</p>
			
			<?php make_course_select("good_courses",$current_session,$user["login"]); ?>
			
			<p class="instruction">
				<span class="count">2</span>
				Please select any courses which you would like to <b>avoid</b> teaching,
				with the course you <b>least</b> want to teach at the <b>top</b>:
			</p>
			
			<?php make_course_select("bad_courses",$current_session,$user["login"]); ?>
			
			<h2>Time Preferences</h2>
			
			<p class="instruction">
				<span class="count">3</span>
				Please select blocks of time at which you <b>prefer</b> to teach.
			</p>
			
			<?php make_time_select("good_times"); ?>
		
			<p class="instruction">
				<span class="count">4</span>
				Please select blocks of time at which you <b>don't want</b> to teach.
				If the conflict is a math class you are taking, please select the class from the 
				list below <b>instead</b> of listing the time here.  If you absolutely cannot teach
				at a certain time or have a non-math class at that time, please explain in the comments.  
			</p>
			
			<?php make_time_select("bad_times"); ?>
		
			<p class="instruction">
				<span class="count">5</span>
				Please select any classes which you are <b>attending</b> as a student:
			</p>
			
			<?php make_class_select("bad_classes",$current_session,$user["login"]); ?>

			<p class="instruction">
				<span class="count">6</span>
				Enter any other information related to your teaching preferences, such as back-to-back classes, TR vs MWF vs five-days-a-week schedules.  If you plan to graduate this year, please tell me that.
			</p>
			
			<textarea name="comments" rows="8" cols="60" class="extra_info"><?php echo get_comments();	?></textarea>

			<p class="instruction">
				<span class="count">7</span>
				Please review your entries and then click <i>Submit</i> to
				send your request.
			</p>
			
			<center>
			<?php 
				make_button("Submit request", "../images/tick.png", "doSubmit()");
				echo "&nbsp;";
				make_button("Cancel", "../images/cross.png", "doCancel()");
			?>
			</center>
			
		</form>
		
		<?php 
			endif;
		?>
		
		<?php 
			include '../std_footer.php';
		?>
	</body>
</html>
