<?php

	/*
	 * NOTE: This page uses *terrible* logic as far as scaling is concerned.
	 * It iterates over a list of instructors and does separate queries for
	 * each one. If this needed to scale we would *really* need to unify all
	 * these into a bounded list of queries. But the coding of the page would be
	 * harder, and we're about rapid deployment here...
	 */

	require_once 'common.php';
	require_once 'instructor_lib.php';
	db_connect();
	
	$user = getUser(ADMIN_PRIV);
	if (! $user) {
		return; 
	}
	
	$session = get_academic_session();
	
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<title>Instructor Assignment</title>
		<link rel="stylesheet" type="text/css" href="css/main.css">
		<style type="text/css">
			TABLE.disp {
				border-collapse: collapse;
			}
			
			TABLE.disp TR.odd {
				background-color: #f8f4ff;
			}
			
			TABLE.disp TR.even {
				background-color: #fff;
			}
			
			TABLE.disp TR.nothing {
				color: #aaa;
			}
			
			TABLE.disp TD,TH {
				padding: 5px;
				white-space: nowrap;
			}
			
			TABLE.disp TD.comments {
				white-space: normal;
			}
			
			TABLE.disp TD.name {
				font-weight: bold;
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
		
		<table class="disp">
			<tr>
				<th>Instructor</th>
				<th>Good Classes</th>
				<th>Bad Classes</th>
				<th>Good Times</th>
				<th>Bad Times</th>
			</tr>
			<?php
				$rs = mysqli_query($newconn, "SELECT * FROM instructors ORDER BY ln, fn");
				while ($r = mysqli_fetch_assoc($rs)) {
					$row_class = ($i % 2 == 0) ? "even" : "odd";
					$instrId = $r["id"];
                                        /* The following condition is a temporary patch - we should really be checking an instructor role of gone
                                         * and only listing instructors who are still active 
                                         */
                                       $crhr =  get_credit_hours($instrId, $session);
                                       if ( $crhr > 0 ) { 
					    $good_course_list = get_good_course_list($instrId, $session);
					    $bad_course_list = get_bad_course_list($instrId, $session);
					    $good_time_list = get_good_time_list($instrId, $session);
					    $bad_time_list = get_bad_time_list($instrId, $session);
					    if ($good_course_list != NULL || $bad_course_list != NULL 
					           || $good_time_list!= NULL || $bad_time_list != NULL) {
						echo "<tr class='$row_class'>";
						echo "<td rowspan=\"3\" class='name'>" . $r["ln"] . ", " . $r["fn"] . "</td>\n";
						echo "<td>" . $good_course_list . "</td>\n";
						echo "<td>" . $bad_course_list . "</td>\n";
						echo "<td>" . $good_time_list . "</td>\n";
						echo "<td>" . $bad_time_list . "</td>\n";
 						echo "<tr class='$row_class'>\n";
						echo "<td colspan=\"4\">" . get_attending($instrId, $session) . "</td>\n";
                                               echo " <td></td>\n";
						echo "</tr>\n";
						echo "<tr class='$row_class'>\n";
						echo "<td colspan=\"4\" class=\"comments\">" . get_prefs_comments($instrId, $session) . "</td>\n";
                                               echo " <td></td>\n";
						echo "</tr>\n";
 					    } else {
						echo "<tr class='$row_class'>";
						echo "<td class='name'>" . $r["ln"] . ", " . $r["fn"] . "</td>\n";
						echo "<td colspan=\"4\">Nothing requested</td>";
						echo "</tr>\n";
					    }
                                       }
					$i++;
				}
			?>
		</table>
		
		<?php include 'std_footer.php'; ?>
	</body>
</html>
