<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<title>Verification</title>
		<link rel="stylesheet" type="text/css" href="css/main.css">
	</head>
	<body>
		<?php include 'std_header.php'; ?>
		<h1>Security Question</h1>
		
		In order to keep robots out of this page,
		please type the two words shown into the 
		box below, and then click "OK".  
		<br>
		<br>
		<form method="POST">
			<center>
				<?php
					require_once('recaptchalib.php');
					$publickey = '6LdwHQMAAAAAAJfFMwPVn4e0LSjkwMdf5A_MaU5n';
					echo recaptcha_get_html($publickey);
				?>
				<input type="hidden" name="action" value="check_captcha">
				<input type="submit" value="OK" style="margin: 10px; padding: 7px 40px;">
			</center>
		</form>
		<?php include 'std_footer.php'; ?>
	</body>
</html>
