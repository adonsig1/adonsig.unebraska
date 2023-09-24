<?php
	require_once 'common.php';	
	
	$user = getUser(INSTR_PRIV);
	if (! $user) {
		return; 
	}
	
	db_connect();
	
	function do_update() {
		global $user;
		
		$id = $user["login"];
		$email = mysql_real_escape_string($_REQUEST["email"]);
		$query = "UPDATE instructors set email = '$email' WHERE id = '$id'\n\n";
		mysql_query($query);
		$user["email"] = $email;
		$_SESSION["user"] = $user;
		
		if ($_REQUEST["pw_change"]) {
			$pw = mysql_real_escape_string($_REQUEST["password1"]);
			$query = "UPDATE instructors set passwd = MD5('$pw') WHERE id = '$id'";
			mysql_query($query);
		}
	}
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<title>Set User Preferences</title>
		<link rel="stylesheet" type="text/css" href="css/main.css">
		<style type="text/css">
			DIV.entry {
				white-space: nowrap;
				margin-bottom: 1em;
			}
			DIV.entry label {
				float: left;
				width: 10em;
				text-align: right;
				margin-right: 0.5em;
			}
			DIV.submit {
				margin-left: 10.5em;
			}
			input:FOCUS {
				background-color: #ffffa0;
			}
			
			#pw1_label, #pw1_help, #pw2_label {
				color: gray;
			}
		</style>
		<script type="text/javascript">
			function setPasswordState(state) {
				var pw1 = document.getElementById("password1");
				var pw2 = document.getElementById("password2");
				
				pw1.disabled = ! state;
				pw2.disabled = ! state;
				pw1.value = "";
				pw2.value = "";

				var color = state ? "black" : "gray";
				document.getElementById("pw1_label").style.color = color;
				document.getElementById("pw1_help").style.color = color;
				document.getElementById("pw2_label").style.color = color;
			}

			function doSubmit() {
				var emailPttrn = /^[A-Z0-9._%+-]+@(?:[A-Z0-9-]+\.)+[A-Z]{2,4}$/i;
				var email = document.getElementById("email").value;
				var pw1 = document.getElementById("password1").value;
				var pw2 = document.getElementById("password2").value;
				var pwChange = document.getElementById("pw_change").checked;

				if (! email.match(emailPttrn)) {
					alert("You do not seem to have entered a valid email address.");
					return;
				}
				
				if (pwChange && (pw1.length < 4 || pw1.length > 16)) {
					alert("The password must be 4 - 16 characters.");
					return;
				}
				
				if (pwChange && pw1 != pw2) {
					alert("The two password entries are not the same.");
					return;
				}
				
				document.theForm.submit();
			}
			
		</script>
	</head>
	<body>
		<?php 
			include 'std_header.php'; 
			
			if ($_REQUEST["action"] == 'update') :
		?>
		
		<?php 
			do_update();
		?>
		
		<h1>Preferences Updated</h1>
		
		Your user preferences have been updated. 
		
		<?php else : ?>
		<h1>Set User Preferences</h1>
		
		<form method="post" action="prefs.php" name="theForm">
			<input type="hidden" name="action" value="update">
			<div class="entry">
				<label for="email">Email address:</label>
				<input type="text" id="email" name="email" value="<?php echo $user["email"] ?>">
			</div>
			<div class="entry">
				<label for="pw_change">Change password:</label>
				<input type="checkbox" id="pw_change" name="pw_change" onClick="setPasswordState(this.checked)">
				(Click here to enable a password change)
			</div>
			<div class="entry">
				<label for="password1" id="pw1_label">Password:</label>
				<input type="password" id="password1" name="password1" disabled="true">
				<span id="pw1_help">(Enter 4 - 16 characters)</span>
			</div>
			<div class="entry">
				<label for="password2" id="pw2_label">Retype password:</label>
				<input type="password" id="password2" name="password2" disabled="true">
			</div>	
			<div class="submit">
				<?php make_button("Update", "tick.png", "doSubmit()") ?>
				<?php make_button("Cancel", "cross.png", "window.location='/$ROOT'") ?>
			</div>
		</form>
				
		<?php endif; ?>
		<?php include 'std_footer.php'; ?>
	</body>
</html>