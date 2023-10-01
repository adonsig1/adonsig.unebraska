<?php
	require_once 'common.php';
	require_once 'recaptchalib.php';
	
	db_connect();
	session_start();
	$vicechairemail = get_vicechair_email();
	$vicechairname = get_vicechair_name();
	
	$action = $_REQUEST["action"];
	if ($action == "editable_text_ajax") {
		doAjaxUpdate(); // Defined below all the html
		return;
	} else if ($action == "check_captcha") {
		$privatekey = '6LdwHQMAAAAAAJK_s7CQkn2lQoXdYfehdjUYyDva';
		$resp = recaptcha_check_answer(
		  $privatekey,
		  $_SERVER['REMOTE_ADDR'],
		  $_POST['recaptcha_challenge_field'],
		  $_POST['recaptcha_response_field']);
		  
		  if ($resp->is_valid) {
		  	$_SESSION["captcha"] = 1;
		  }
	}
	
	if (! $_SESSION["captcha"]) {
		doCaptchaPrompt(); // Defined below all the html
		return; 
	}
	
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<?php 
			$rs = mysql_query(
				"SELECT i.fn AS fn, i.ln AS ln, i.email AS email, i.id AS id, r.name AS role
				 FROM instructors i INNER JOIN role_types r ON i.role = r.id
				 ORDER BY ln, fn");
			$rs_size = mysql_num_rows($rs);
		?>
		<title>Register for Class Scheduling</title>
		<link rel="stylesheet" type="text/css" href="css/main.css">
		<link rel="stylesheet" type="text/css" href="css/editable_text.css">
		<style type="text/css">
			TABLE.class_list TD {
				border-color: #c0c0c0;
			}
		</style>
		<script type="text/javascript" src="js/editable_text.js"></script>
		<script type="text/javascript">
			function registerEditableText() {
				
				successHdlr = function(root) {
					alert(
						"Thank you!\n\n" +
						"An email has been sent to you with your user ID and password.\n" +
						"Once you receive this email, you can log in to the teaching\n" +
						"preferences page."
					);
				};
								
				failureHdlr = function (root) {
					alert("Update failed:\n" + root.getElementsByTagName("message")[0].firstChild.data);
				};
				
				confirmHdlr = function (value) {
					return confirm("Confirm:\n\nIs the email address '" + value + "' correct?");
				};

				EditableTextElement.registerByClass("edit_email", successHdlr, failureHdlr, confirmHdlr);
			}
		</script>
	</head>
	<body onLoad="registerEditableText()">
		<?php include 'std_header.php'; ?>
		
		<h1>Teaching Preferences Site Registration</h1>
		
		<p>
			Please locate your name on the list below.
		</p>
		
		<p>
			If your email address is <b>not</b> set, 
			please click on the field and enter your
			email address. After you do this, you will
			receive a confimation email giving your
			user ID and password for this site.
		</p>
		
		<p>
			If your email address <b>is</b> set,
			please check that it is correct. 
			If it is, you can get an email with your
			user ID and password by entering your email
			address on the 
			<a href="/<?php echo $ROOT ?>/reset_password.php">password reminder page</a>.
			If your email address is incorrect, please
			contact <a href="mailto:<?php echo $vicechairemail?>"><?php echo $vicechairname?></a>.
		</p>
		
		<table class="class_list">
			<?php 
				mysql_data_seek($rs, 0);
				for($i=0; $i<$rs_size; $i++) {
					$r = mysql_fetch_assoc($rs);
					$fn = $r["fn"];
					$ln = $r["ln"];
					$role = $r["role"];
					$email = $r["email"];
					echo "<tr>";
					echo "<td>$ln</td>";
					echo "<td>$fn</td>";
					echo "<td>$role</td>";
					if ($email) {
						echo "<td>$email</td>";
					} else {
						echo "<td><a class=\"edit_email\" href=\"register.php?action=editable_text_ajax&id=$r[id]\">Click to edit</a></td>";
					}
					echo "</tr>";
				}
			?>
		</table>
		<?php include 'std_footer.php'; ?>
	</body>
</html>
<?php 
	function doAjaxUpdate() {
		global $ROOT;
		header("Content-Type: text/xml");
		echo "<?xml version=\"1.0\" ?>";
		
		$id = mysql_real_escape_string($_REQUEST["id"]);
		$email = mysql_real_escape_string($_REQUEST["value"]);
		
		if (! preg_match("/^[A-Z0-9._%+-]+@(?:[A-Z0-9-]+\.)+[A-Z]{2,4}$/i", $email)) {
			ajaxError("'$email' does not appear to be a valid email address");
			return;
		}
		
		$rs = mysql_query("SELECT * FROM instructors WHERE id = '$id'");
		if (mysql_num_rows($rs) == 0) {
			ajaxError("Unknown instructor '$id'");
			return;		
		}
		
		$r = mysql_fetch_assoc($rs);
		if ($r["email"]) {
			ajaxError("The instructor email is already set");
			return;
		}
		
		$passwd = generatePassword();
		
		$update = 
			"UPDATE instructors SET email = '$email', passwd = MD5('$passwd') WHERE id = '$id'";
		mysql_query($update);
		
		$fn = $r["fn"];
		$message = <<<MAIL
Dear $fn,
		
This email is being sent because you registered
for a user ID and password on the Math Department 
teaching preferences site.

Here is the information you requested:

Email: $email
User ID: $id
Password: $passwd

Please visit http://www.math.unl.edu/$ROOT/
and log in with this ID and password. Once you have logged
in, you can complete the teaching preferences form. You
can also change your email and password settings, if
you wish.

Thanks!
MAIL;
		mail(
			$email, 
			"Teaching preferences: User ID and password",
			$message,
			"Reply-To: adonsig1@math.unl.edu"
		);
		
		echo
			"<root success=\"true\">
				<message></message>
			</root>";
	}
	
	function ajaxError($message) {
		echo "<root success=\"false\">
		        <message>$message</message>
		      </root>";
	}
	
	function doCaptchaPrompt() {
		global $ROOT;
		include 'captcha_prompt.php';
	}
?>
