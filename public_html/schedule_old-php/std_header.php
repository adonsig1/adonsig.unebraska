<?php 
	require_once('common.php');
	session_start();
	$user = $_SESSION["user"];
?>
<div class="banner">
	<a href="/<?php echo $ROOT ?>"><img src="/<?php echo $ROOT ?>/images/UNLMathHeader.png" border="0"></a>
</div>

<div class="top_name_bar">
	<?php 
		if (!($user)) {
			echo "<a href=\"/$ROOT/login.php\">Sign in</a> | ";
			echo "<a href=\"/$ROOT/index.php\">Home</a>";
		} elseif ($user["priv"]==ADMIN_PRIV) { 
		        echo "<a href=\"/$ROOT/index.php\">Home</a> | ";
                        echo "<a href=\"/$ROOT/all_classes.php\">List</a> | ";
                        echo "<a href=\"/$ROOT/instructors.php\">Individual Request</a> | ";
                        echo "<a href=\"/$ROOT/all_requests.php\">All Requests</a> | ";
                        echo "<a href=\"/$ROOT/query_requests.php\">Query</a> | ";
                        echo "<a href=\"/$ROOT/teaching.php\">Load</a> | ";
                        echo "<a href=\"/$ROOT/edit_instructors.php\">Profiles</a> | ";
                        echo "<a href=\"/$ROOT/hours_assigned.php\">Hours</a> | ";
                        echo "<a href=\"/$ROOT/useful_statistics.php\">Balance</a> | ";
			echo "<a href=\"/$ROOT/logout_handler.php\">Sign out</a>";
		} else {
			echo "Welcome, " . $user["fn"] . " ". $user["ln"] . " ";
			echo "<a href=\"/$ROOT/index.php\">Home</a> | ";
			echo "<a href=\"/$ROOT/prefs.php\">User prefs</a> | ";
			echo "<a href=\"/$ROOT/logout_handler.php\">Sign out</a>";
		}
	?>

</div>
