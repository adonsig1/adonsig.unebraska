<?php
	require_once 'common.php';
	db_connect();
	
	$user = getUser(ADMIN_PRIV);
	if (! $user) {
		return; 
	}
	
	$session = get_academic_session();
			
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<title>Useful statistics</title>
		<link rel="stylesheet" type="text/css" href="css/main.css">
		<style type="text/css">
		</style>
		<script type="text/javascript">
		</script>
	</head>
	<body>
		<?php include 'std_header.php'; ?>
		
		<form name="theForm">
		
			<div class="session_select">
				<?php make_session_select($session, "document.theForm.submit()") ?>
			</div>
		
		</form>

		<h2>Total hours available</h2>
		<table class="display">		
		<?php
			/*
			 * Select the total hours available by each role of instructor
			 */
			$query =
				"SELECT  r.name, sum(t.crhr) 
				FROM
				  teaching t INNER JOIN instructor_roles ir ON t.instructor = ir.id
				    INNER JOIN role_types r ON ir.role = r.id
				WHERE
				  t.session = '$session'
				  # Finds time-dependent role TBR #
				  AND ir.Session_order = (SELECT MAX(ir2.Session_order) FROM instructor_roles ir2 JOIN sessions s2 WHERE s2.id = '$session' AND ir2.Session_order <= s2.order AND ir2.id = ir.id)
				GROUP BY ir.role";
			$rs = mysql_query($query);
                        $sum = 0;
			while ($row = mysql_fetch_row($rs)) {
				echo '<tr>';
				echo '<td>' . $row[0] . '</td><td class="right">' . $row[1] . '</td>';
				echo '</tr>';
				$sum += $row[1];
			}
			echo '<tr>';
			echo '<td>Total</td><td class="right">' . $sum . '</td>';
			echo '</tr>';
		?>
		</table>
		
		<h2>total course hours</h2>
		<?php 
			/*
			 * Select the total number of credit hours of unassigned courses
			 */
			$query =
				"SELECT sum(co.crhr)
				FROM 
				  classes c INNER JOIN courses co ON c.course = co.id 
				WHERE 
				  c.session='$session'";
			$rs = mysql_query($query);
			$row = mysql_fetch_row($rs);
			$TotalCourseHours = $row[0]
		?>

		<p>
			Total # course credit hours: <?php echo $TotalCourseHours ?>  
		</p>

		<h2>Unassigned hours to be covered</h2>
		<?php 
			/*
			 * Select the total number of credit hours of unassigned courses
			 */
			$query =
				"SELECT sum(co.crhr)
				FROM 
				  classes c INNER JOIN courses co ON c.course = co.id 
				WHERE 
				  c.session='$session' AND (ISNULL(c.instructor) OR c.instructor = '')";
			$rs = mysql_query($query);
			$row = mysql_fetch_row($rs);
			$unassignedCourseHours = $row[0]
		?>
		<p>
			Total # course credit hours with no instructor assigned yet: <?php echo $unassignedCourseHours ?> 
		</p>
		
		<?php 
			/*
			 * Select the total number of instructor credit hours not yet assigned
			 */
			$query =
				"SELECT SUM(IF(net < 0, 0, net)) 
				 FROM (
  		     SELECT 
    	       t.instructor, t.crhr-if(isnull(sum(co.crhr)), 0, sum(co.crhr)) AS net
    	     FROM 
			       teaching t 
			         LEFT OUTER JOIN classes cl ON cl.instructor = t.instructor AND cl.session = t.session 
			         LEFT OUTER JOIN courses co ON cl.course = co.id
					 WHERE t.session='$session'
			     GROUP BY t.instructor, t.crhr
			   ) AS a";
			$rs = mysql_query($query);
			$row = mysql_fetch_row($rs);
			$unassignedInstructorHours = $row[0];
		?>
		<p>
			Total # instructor credit hours not yet assigned: <?php echo $unassignedInstructorHours ?> 
		</p>
		<p>
			Surplus: <?php echo $unassignedInstructorHours - $unassignedCourseHours ?> 
		</p>
		<?php
			mysql_close(); 
			include 'std_footer.php'; 
		?>
	</body>
</html>
