<?php	
	require_once 'common.php';
	
	db_connect();
	session_start();
	
	$login = $_REQUEST["login"];
	$passwd = $_REQUEST["passwd"];
	$login = mysql_real_escape_string($login);
	$passwd = mysql_real_escape_string($passwd);
	
	$query = 
		"SELECT * FROM instructors 
		WHERE (id = '$login' OR email = '$login') AND passwd = MD5('$passwd')";
	$resultset = mysql_query($query);
	
	if (mysql_num_rows($resultset) > 0) {
		$row = mysql_fetch_assoc($resultset);
		$user = array(
			"login" => $row['id'],
			"fn" => $row["fn"],
			"ln" => $row["ln"],
			"priv" => $row["priv"],
			"email" => $row["email"]
		);
		
		$_SESSION["user"] = $user;
		
		$loc = $_SESSION["orig_request_uri"];
		unset($_SESSION["orig_request_uri"]);
		if (! $loc) {
			$loc = "index.php";
		}
		header("Location: $loc");		
	} else {
		$_SESSION["login"] = $login;
		$_SESSION["errm"] = "Unknown login/password combination. Please try again."; 
		
		header("Location: login.php");
	}
		
	mysql_close();
?>