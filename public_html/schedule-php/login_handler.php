<?php	
	require_once 'common.php';
	
	db_connect();
	session_start();
	
	$login = $_REQUEST["login"];
	$passwd = $_REQUEST["passwd"];
	$login = mysqli_real_escape_string($newconn, $login);
	$passwd = mysqli_real_escape_string($newconn, $passwd);
	
	$query = 
		"SELECT * FROM instructors 
		WHERE (id = '$login' OR email = '$login') AND passwd = MD5('$passwd')";
	$resultset = mysqli_query($newconn, $query);
	/* $tt = mysqli_num_rows($resultset); */
        $tt = strval(10+mysqli_num_rows($resultset));
	
	if (mysqli_num_rows($resultset) > 0) {
		$row = mysqli_fetch_assoc($resultset);
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
		$_SESSION["errm"] = "Unknown login/password combination. Please try again.". $query;
		
		header("Location: login.php");
	}
		
	mysqli_close($newconn);
?>
