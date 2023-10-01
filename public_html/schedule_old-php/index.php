<?php
	require_once 'common.php';
	
	session_start();
	$user = $_SESSION["user"];


?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<title>Course Planning Homepage</title>
		<link rel="stylesheet" type="text/css" href="css/main.css" >
		<style type="text/css">
		  .centered_announcement {
		  	text-align: center;
		  	margin: 10px 0 20px;
		  }
		</style>
	</head>
	<body>
		<?php include 'std_header.php'?>

		<ul>
			<li>
				<b>If you have used this site before,</b> 
				follow this link to enter your preferences for the next semester:
				<div class="centered_announcement">
					<a href="teaching_request/?session=fall19">Teaching requests for Fall 2019</a>
				</div>
			</li>
			<li>
				<b>If you have NOT used this site before,</b>
				request an account from <a href="mailto:adonsig1@unl.edu">Allan Donsig</a>
			</li>
			<?php
				if ($user["priv"] == ADMIN_PRIV):
			?>
			<li><a href="all_classes.php">List/edit all classes</a></li>
			<li><a href="Lori_classes.php">List/edit all classes in Lori's ordering</a></li>
			<li><a href="Pub_classes.php">List publishable classes in Lori's ordering</a></li>
			<li><a href="instructors.php">Individual instructor assignments</a></li>
			<li><a href="all_requests.php">View all teaching requests</a></li>
			<li><a href="query_requests.php">Query teaching requests</a></li>
			<li><a href="teaching.php">Edit instructors' teaching load assignments</a></li>
			<li><a href="edit_instructors.php">Add and edit instructor profiles</a></li>
			<li><a href="hours_assigned.php">Tally of hours assigned to each instructor</a></li>
			<li><a href="grad_report.php">Report on GTAs, Lecturers & Postdocs</a></li>
			<li><a href="useful_statistics.php">Total capacity data</a></li>
			<?php
				endif;
			?>
		</ul>
			
		<?php include 'std_footer.php'; ?>
	</body>
</html>
