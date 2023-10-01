<?php 
	include "common.php";
	
	session_start();
	
	# changed initial statements to avoid error messages about undefined indexes, APD Sept 24, 2021
        if ( isset($_SESSION["login"]) ) {
   		$login = $_SESSION["login"];
		unset($_SESSION["login"]);
        } else {
               $login = "";
        }
        if ( isset($_SESSION["errm"]) ) {
		$errm = $_SESSION["errm"];
		unset($_SESSION["errm"]);
        } else {
                $errm = 0;
        }
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<title>Login</title>
		<link rel="stylesheet" type="text/css" href="css/main.css">
		<style type="text/css">
			label {
				white-space: nowrap;
			}
			
			DIV.entry {
				margin-left: 5em;
				margin-top: 0.5em;
				margin-bottom: 0.5em;
			}
		
			.submit {
				margin-left: 5em;
			}
		
			fieldset {
				padding: 1em;
				width: 30em;
			}
		</style>
		<script type="text/javascript">
			function onLoad() {
				// Hitting enter in the password field submits the form
				document.login_form.passwd.onkeypress = function (event) {
					var key = (window.event) ? window.event.keyCode : event.keyCode;
					if (key == 13) {
						document.login_form.submit();
						return false;
					}
					return true;
				}
				// Hitting enter in the login field moves focus to passwd field
				document.login_form.login.onkeypress = function (event) {
					var key = (window.event) ? window.event.keyCode : event.keyCode;
					if (key == 13) {
						document.login_form.passwd.focus();
						return false;
					}
					return true;
				}
			
				document.getElementById("login").focus();
			}
		</script>
	</head>
	<body onLoad="onLoad();">
		<?php 
			include 'std_header.php';

			if ($errm) {
				echo "<div class='errm'>$errm</div>";
			}
		?>
		<form action="login_handler.php" name="login_form" method="post">
			<fieldset>
				<legend>Please sign in</legend>
				<div class="entry">
					<label for="login">Email address (or User ID):</label><br>
					<input type="text" name="login" id="login" value="<?php echo $login; ?>">
				</div>
				<div class="entry">
					<label for="password">Password:</label><br>
					<input type="password" name="passwd" id="password">
				</div>
				<div class="submit">
					<?php make_button("Sign In", "tick.png", "document.login_form.submit()") ?>
				</div>
				<p style="text-align: right;">
					<a href="reset_password.php">Email me my user ID and password...</a>
				</p>
			</fieldset>
		</form>
		
		<?php include 'std_footer.php'; ?>
	</body>
</html>