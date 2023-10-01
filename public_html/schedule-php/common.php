<?php
	/*
	 * Library of common functions for course scheduling
	 */

	$ROOT = '~adonsig1/schedule-php';

	define("INSTR_PRIV", 0);
	define("ADMIN_PRIV", 1);

	function getUser($priv_level = ADMIN_PRIV) {
		global $ROOT;
		session_start();
		$user = $_SESSION["user"];
		if (! $user) {
			$_SESSION["orig_request_uri"] = $_SERVER["REQUEST_URI"];
			header("Location: /$ROOT/login.php");
			return; 
		} else if ($user["priv"] < $priv_level) {
			unset($user);
			$_SESSION["orig_request_uri"] = $_SERVER["REQUEST_URI"];
			$_SESSION["errm"] = "Insufficient privileges for this user.";
			header("Location: /$ROOT/login.php");
		}
		return $user;
	}
	
	function db_connect() {
			global $newconn;
			
			if (! $newconn) {
				$newconn = mysqli_connect("localhost", "adonsig1", "Huskers18!", "schedule");
                                if (!$newconn) { 
                                        die("Connection failed: " . mysqli_connect_error()); 
                                } 
			}

	}
	
	function make_button($text, $icon, $action) {
		global $ROOT;
		echo 
			"<button type='button' onClick=\"$action\">
				<img src='/$ROOT/images/$icon'>
				$text
			</button>";
	}
	
	function make_session_select($session, $onchange) {
                global $newconn;

		echo "<label for=\"session_select\">Session:</label> ";
		echo "<select name=\"session\" id=\"session_select\" onChange=\"$onchange\">";
		
                /* $query = mysqli_real_esacpe_string($newconn, "SELECT * FROM sessions ORDER BY sessions.order ASC" );
		$rs = mysqli_query($newconn, $query); */
		$rs = mysqli_query($newconn, "SELECT * FROM sessions ORDER BY sessions.order ASC"); 
		while ($r = mysqli_fetch_assoc($rs)) {
			if ($session == $r["id"]) {
				printf("<option value=\"%s\" SELECTED>%s</option>", $r["id"], $r["name"]);
			} else {
				printf("<option value=\"%s\">%s</option>", $r["id"], $r["name"]);
			}
		}
		echo "</select>";
	}
	
	function make_acyear_select($onchange) {
                global $newconn;

		$acyear = get_academic_year();
		echo "<label for=\"acyear_select\">Academic Year:</label> ";
		echo "<select name=\"acyear\" id=\"acyear_select\" onChange=\"$onchange\">";
		
		$rs = mysqli_query($newconn, "SELECT * FROM acyears ORDER BY acyears.order ASC");
		while ($r = mysqli_fetch_assoc($rs)) {
			if ($r["id"] == $acyear->id) {
				printf("<option value=\"%s\" SELECTED>%s</option>", $r["id"], $r["name"]);
			} else {
				printf("<option value=\"%s\">%s</option>", $r["id"], $r["name"]);
			}
		}
		echo "</select>";
	}
	
	function make_role_select($role, $onchange) {
                global $newconn;

		echo "<label for=\"role_select\">Role:</label> ";
		echo "<select name=\"role\" id=\"role_select\" onChange=\"$onchange\">";
		
		$rs = mysqli_query($newconn, "SELECT * FROM role_types");
		while ($r = mysqli_fetch_assoc($rs)) {
			if ($r["id"] == $role) {
				printf("<option value=\"%s\" SELECTED>%s</option>", $r["id"], $r["name"]);
			} else {
				printf("<option value=\"%s\">%s</option>", $r["id"], $r["name"]);
			}
		}
		echo "</select>";
	}
	
	function generatePassword() {
		$vowels = "aeiouAE3U";
		$consonants = "bcdfghjkmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ256789";
		$vlen = strlen($vowels) - 1;
		$clen = strlen($consonants) - 1;
		
		$pw = '';
		$alt = rand(0, 1);
		$length = rand(6,8);
		for ($i=0; $i<$length; $i++) {
			switch ($alt) {
				case 0:
					$pw .= $vowels[rand(0, $vlen)];
					$alt = 1;
					break;
				case 1:
					$pw .= $consonants[rand(0, $clen)];
					$alt = 0;
					break;
			}
		}
		return $pw;
	}
	
	/*
	 * Get the number of credit hours an instructor is contracted
	 * to teach in the given session
	 */
	function get_credit_hours($id, $session) {
                global $newconn;

		$query = <<<SQL
			SELECT t.crhr AS crhr
			FROM 
				teaching t
			WHERE
				t.instructor = '$id' AND t.session = '$session'
SQL;
		$rs = mysqli_query($newconn, $query);
		if (mysqli_num_rows($rs) > 0) {
			$r = mysqli_fetch_array($rs);
			return $r["crhr"];
		} else {
			return "0";
		}
	}
	
	/*
	 * Get the number of credit hours an instructor is contracted
	 * to teach in the given session
	 */
	function get_teaching_comments($id, $session) {
                global $newconn;

		$query = <<<SQL
			SELECT t.comments AS comments
			FROM 
				teaching t
			WHERE
				t.instructor = '$id' AND t.session = '$session'
SQL;
		$rs = mysqli_query($newconn, $query);
		if (mysqli_num_rows($rs) > 0) {
			$r = mysqli_fetch_array($rs);
			return $r['comments'];
		} else {
			return '';
		}
	}
	
	/*
	 * Get the number of credit hours assigned to an instructor
	 */
	function get_assigned_credit_hours($id, $session) {
                global $newconn;

		$query = <<<SQL
			SELECT sum(co.crhr) AS crhr
			FROM 
				classes cl INNER JOIN courses co ON cl.course = co.id
			WHERE
				cl.instructor = '$id' AND cl.session = '$session'
SQL;
		$rs = mysqli_query($newconn, $query);
		$r = mysqli_fetch_array($rs);
		$crhr = $r["crhr"];
		return $crhr == NULL ? "0" : $crhr;
	}
	
	function get_academic_session() {
                global $newconn;

		if ( isset($_REQUEST["session"] ) ) {
		     $session = $_REQUEST["session"];
		} else {
		     $session = get_application_property('default_session'); 
		}
		// if (is_null($session)) {
		// 	$session = get_application_property('default_session'); 
		//} 
		return mysqli_real_escape_string($newconn, $session);
	}
	
	function get_academic_year() {
                global $newconn;

                if (isset( $_REQUEST["acyear"] ) ) {
		         $acyear = mysqli_real_escape_string($newconn, $_REQUEST["acyear"]);
	        } else {
			$acyear = get_application_property('default_acyear'); 
		}
		$query = "SELECT id, fall, spr FROM acyears WHERE id = '$acyear'";
		$rs = mysqli_query($newconn, $query);
		return mysqli_fetch_object($rs);
	}

	function get_vicechair_email() {
                global $newconn;

		$vicechairemail = $_REQUEST["vicechair_email"];
		if (is_null($vicechairemail)) {
			$vicechairemail = get_application_property('vicechair_email'); 
		}
		return mysqli_real_escape_string($newconn, $vicechairemail);
	}
	
	function get_vicechair_name() {
                global $newconn;

		$vicechairname = $_REQUEST["vicechair_name"];
		if (is_null($vicechairname)) {
			$vicechairname = get_application_property('vicechair_name'); 
		}
		return mysqli_real_escape_string($newconn, $vicechairname);
	}
	
	function get_application_property($name) {
                global $newconn;

		$query = "SELECT value FROM properties WHERE name = '$name'";
		$rs = mysqli_query($newconn, $query);
		$r = mysqli_fetch_array($rs);
		return $r['value'];
	}
?>
