<?php
	/*
	 * Library of common functions for course scheduling
	 */

	$ROOT = '~adonsig1/Library/Public_html/schedule-php';

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
			static $conn;
			
			if (! $conn) {
				mysql_connect("localhost", "adonsig1", "Huskers18!");
				mysql_select_db("schedule");
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
		echo "<label for=\"session_select\">Session:</label> ";
		echo "<select name=\"session\" id=\"session_select\" onChange=\"$onchange\">";
		
		$rs = mysql_query("SELECT * FROM sessions ORDER BY sessions.order ASC");
		while ($r = mysql_fetch_assoc($rs)) {
			if ($session == $r["id"]) {
				printf("<option value=\"%s\" SELECTED>%s</option>", $r["id"], $r["name"]);
			} else {
				printf("<option value=\"%s\">%s</option>", $r["id"], $r["name"]);
			}
		}
		echo "</select>";
	}
	
	function make_acyear_select($onchange) {
		$acyear = get_academic_year();
		echo "<label for=\"acyear_select\">Academic Year:</label> ";
		echo "<select name=\"acyear\" id=\"acyear_select\" onChange=\"$onchange\">";
		
		$rs = mysql_query("SELECT * FROM acyears ORDER BY acyears.order ASC");
		while ($r = mysql_fetch_assoc($rs)) {
			if ($r["id"] == $acyear->id) {
				printf("<option value=\"%s\" SELECTED>%s</option>", $r["id"], $r["name"]);
			} else {
				printf("<option value=\"%s\">%s</option>", $r["id"], $r["name"]);
			}
		}
		echo "</select>";
	}
	
	function make_role_select($role, $onchange) {
		echo "<label for=\"role_select\">Role:</label> ";
		echo "<select name=\"role\" id=\"role_select\" onChange=\"$onchange\">";
		
		$rs = mysql_query("SELECT * FROM role_types");
		while ($r = mysql_fetch_assoc($rs)) {
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
		$query = <<<SQL
			SELECT t.crhr AS crhr
			FROM 
				teaching t
			WHERE
				t.instructor = '$id' AND t.session = '$session'
SQL;
		$rs = mysql_query($query);
		if (mysql_num_rows($rs) > 0) {
			$r = mysql_fetch_array($rs);
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
		$query = <<<SQL
			SELECT t.comments AS comments
			FROM 
				teaching t
			WHERE
				t.instructor = '$id' AND t.session = '$session'
SQL;
		$rs = mysql_query($query);
		if (mysql_num_rows($rs) > 0) {
			$r = mysql_fetch_array($rs);
			return $r['comments'];
		} else {
			return '';
		}
	}
	
	/*
	 * Get the number of credit hours assigned to an instructor
	 */
	function get_assigned_credit_hours($id, $session) {
		$query = <<<SQL
			SELECT sum(co.crhr) AS crhr
			FROM 
				classes cl INNER JOIN courses co ON cl.course = co.id
			WHERE
				cl.instructor = '$id' AND cl.session = '$session'
SQL;
		$rs = mysql_query($query);
		$r = mysql_fetch_array($rs);
		$crhr = $r["crhr"];
		return $crhr == NULL ? "0" : $crhr;
	}
	
	function get_academic_session() {
		$session = $_REQUEST["session"];
		if (is_null($session)) {
			$session = get_application_property('default_session'); 
		}
		return mysql_real_escape_string($session);
	}
	
	function get_academic_year() {
		$acyear = mysql_real_escape_string($_REQUEST["acyear"]);
		if ($acyear == '') {
			$acyear = get_application_property('default_acyear'); 
		}
		$query = "SELECT id, fall, spr FROM acyears WHERE id = '$acyear'";
		$rs = mysql_query($query);
		return mysql_fetch_object($rs);
	}

	function get_vicechair_email() {
		$vicechairemail = $_REQUEST["vicechair_email"];
		if (is_null($vicechairemail)) {
			$vicechairemail = get_application_property('vicechair_email'); 
		}
		return mysql_real_escape_string($vicechairemail);
	}
	
	function get_vicechair_name() {
		$vicechairname = $_REQUEST["vicechair_name"];
		if (is_null($vicechairname)) {
			$vicechairname = get_application_property('vicechair_name'); 
		}
		return mysql_real_escape_string($vicechairname);
	}
	
	function get_application_property($name) {
		$query = "SELECT value FROM properties WHERE name = '$name'";
		$rs = mysql_query($query);
		$r = mysql_fetch_array($rs);
		return $r['value'];
	}
?>
