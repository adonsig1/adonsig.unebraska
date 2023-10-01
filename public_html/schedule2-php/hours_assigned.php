<?php 

	require_once 'common.php';
	db_connect();
	
	$user = getUser(ADMIN_PRIV);
	if (! $user) {
		return; 
	}
	
	$session = get_academic_session();

	function get_hours_list($session) {
		$query = 
			"SELECT 
			  i.id AS id, i.ln AS ln, i.fn AS fn, i.role AS role,
			  IFNULL(sum(co.crhr), 0) AS assigned, 
			  t.crhr AS hours, (t.crhr - IFNULL(sum(co.crhr), 0)) AS surplus, 
			  GROUP_CONCAT(co.name SEPARATOR ' | ') AS classes,
			  t.comments AS comments
			FROM 
			  (
			  	(instructors i INNER JOIN teaching t ON i.id = t.instructor) 
			  		LEFT OUTER JOIN classes cl ON i.id = cl.instructor AND t.session = cl.session
			  ) LEFT OUTER JOIN courses co ON co.id = cl.course
			WHERE t.session = '$session' AND i.role <> 'gone'
			GROUP BY i.id
			ORDER BY surplus DESC, i.ln, i.fn";
		$rs = mysql_query($query);
		while ($x = mysql_fetch_object($rs)) {
			$a[] = $x;
		}
		return $a;
	}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<link rel="stylesheet" type="text/css" href="css/main.css">
		<style type="text/css">
			TR.over_assigned {
				background-color: #f0fff8;
			}
			TR.under_assigned {
				background-color: #fff0f0;
			}
			TABLE.display TD {
				white-space: nowrap;
			}
		</style>
	</head>
	<body>
		<?php include 'std_header.php'; ?>
		
		<form name="theForm">
			<div class="session_select">
				<?php make_session_select($session, "document.theForm.submit()") ?>
			</div>
		</form>
		
		<table class="display">
			<?php
				$list = get_hours_list($session);
				foreach ($list as $x) {
					if ($x->surplus > 0) {
						echo '<tr class="over_assigned">';
					} else if ($x->surplus < 0) {
						echo '<tr class="under_assigned">';
					} else {
						echo '<tr>';
					}
					echo ($x->role == 'fac' || $x->role == 'pd') ? '<td style="font-weight: bold;">' : '<td>';
					echo "<a href=\"instructors.php?action=submit&instrId=$x->id\">";
					echo "$x->ln, $x->fn</a></td>";
					echo "<td>$x->assigned</td>";
					echo "<td>$x->hours</td>";
					echo "<td>$x->surplus</td>";
					echo "<td>$x->classes</td>";
					echo "<td>$x->comments</td>";
					echo '</tr>';
				}
			?>
		</table>
	
		<?php include 'std_footer.php'; ?>
	</body>
</html>
