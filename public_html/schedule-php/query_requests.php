<?php

	require_once 'common.php';
	db_connect();
	
	$user = getUser(ADMIN_PRIV);
	if (! $user) {
		return; 
	}
	
	$session = get_academic_session();
	
	if ( isset($_REQUEST["courseId"]) ) {		
	          $courseId = mysqli_real_escape_string($newconn, $_REQUEST["courseId"]);
	} else {
	          $courseId = "";
        }
	
	if ( (isset($_REQUEST["action"]) ) && ($_REQUEST["action"] == 'list_courses') ) {
		do_list_courses_ajax($session); // At bottom of file
		return;
	}
	
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<title>Query Teaching Preferences</title>
		<link rel="stylesheet" type="text/css" href="css/main.css">
		<link rel="stylesheet" type="text/css" href="css/autosuggest_inquisitor.css">
		<style type="text/css">
			.good_times {
				color: green;
				font-size: 75%;
			}
			
			.bad_times {
				color: red;
				font-size: 75%;
			}
		</style>
		<script type="text/javascript" src="js/bsn.AutoSuggest_2.1.3.js"></script>
		<script type="text/javascript">
			function onLoad() {
				/*
	    	 * Initialize the autosuggest feature.
		     */
				var options = {
					script: "/<?php echo $ROOT ?>/query_requests.php?action=list_courses&session=<?php echo $session ?>&",
					varname: "idLeft",
					json: false,
					maxresults: 10
				};
				var as = new bsn.AutoSuggest('courseId', options);
			}
		</script>
	</head>
	<body onLoad="onLoad();">
		<?php include 'std_header.php'; ?>
		<form name="theForm">
			<input type="hidden" name="action" value="submit">
			<div class="session_select">
				<?php make_session_select($session, "document.theForm.submit()") ?>
			</div>
			<br>
			Find instructors who
			<select name="queryType">
				<option value="want"<?php if ( ( isset($query_type) ) && ($query_type == 'want') ) echo 'selected'?>>want to teach</option>
				<option value="not_want"<?php if ( ( isset($query_type) ) && ($query_type == 'not_want') ) echo 'selected'?>>don't want to teach</option>
			</select>
			<input type="text" id="courseId" name="courseId" value="<?php echo $courseId ?>" size="8" maxlength="10" onClick="select()">
			<?php make_button("OK", "tick.png", "document.theForm.submit()"); ?>
		</form>

		<br>

		<?php
			if ( (isset($_REQUEST["action"]) ) && ($_REQUEST["action"] == 'submit') ) :
		?>

		<table class="display">
			<tr>
				<th colspan="5">
					<?php echo get_course_name($courseId) ?>
				</th>
			</tr>
			<?php
				$rs = get_query_results($courseId,$session);
				if (mysqli_num_rows($rs) == 0) {
					echo '<tr>';
					echo '<td colspan="5"><i>No results!!</i></td>';
					echo '</tr>';
				} else {
					while ($r = mysqli_fetch_assoc($rs)) {
                                              $rrs = get_surplus_hours($session,$r["instrId"]);
                                              if (mysqli_num_rows($rrs) == 0) {
                                                    $assign = 'Error';
                                                    $surpl = 'Error';
                                              } else {
                                                    $rr = mysqli_fetch_assoc($rrs); 
                                                    $assign = $rr["assigned"];
                                                    $surpl = $rr["surplus"];
                                              }
						echo '<tr>';
						echo '<td>';
						echo '<a href="instructors.php?action=submit&instrId=' . $r["instrId"] . '">';
						echo $r['name'] . '</a></td>';
						echo '<td>' . $r['rnk'] . '</td>';
                                               echo '<td>' . $assign . '</td>';
                                               echo '<td>' . $surpl . '</td>';
						echo '<td>' . $r['times'] . '</td>';
						echo '<tr>';
					}
				}
			?>
		</table>
		
		<?php
			endif;
		?>
		
		<?php include 'std_footer.php'; ?>
	</body>
</html>
<?php 

	function do_list_courses_ajax($session) {
                global $newconn;
		
		$idLeft = mysqli_real_escape_string($newconn, $_REQUEST["idLeft"]);
		$idLeftLength = strlen($idLeft);
		
		header("Content-Type: text/xml");
		echo '<?xml version="1.0" ?>';
		echo '<results>';
		
		$query = 
			"SELECT DISTINCT co.id, co.name
			FROM classes cl INNER JOIN courses co ON cl.course = co.id
			WHERE cl.session = '$session' AND LEFT(cl.id, $idLeftLength) = '$idLeft'";
		$rs = mysqli_query($newconn, $query);
		while ($r = mysqli_fetch_assoc($rs)) {
			$id = $r["id"];
			$name = htmlentities($r["name"]);
			echo "<rs id=\"$id\" info=\"$name\">$id</rs>";
		}
		
		echo '</results>';
	}
	
	function get_course_name($id) {
 		global $newconn;

		$rs = mysqli_query($newconn,
			"SELECT CONCAT('Math ', id, ' - ', name) 
			FROM courses WHERE id = '$id'"
		);
		if ($r = mysqli_fetch_array($rs)) {
			return $r[0];
		}
		return "";
	}
	
	function get_query_results($courseId, $session) {
                global $newconn;
		
		$query_type = $_REQUEST['queryType']; 
	
		switch ($query_type) {
		case 'want':
		default:
			$query =
				"SELECT 
					CONCAT(i.fn, ' ', i.ln) AS name,
					17 - pc.pref AS rnk, 
					GROUP_CONCAT(
						CONCAT(
							IF(pt.pref > 0, '<span class=\"good_times\">', '<span class=\"bad_times\">'),
								pt.days, ' ', TIME_FORMAT(pt.start, '%l:%i'), '-', TIME_FORMAT(pt.end, '%l:%i%p'),
							'</span>'
						) 
						ORDER BY pt.pref DESC
						SEPARATOR '; '
					) AS times,
					i.id AS instrId
				FROM 
					pref_courses pc INNER JOIN instructors i ON pc.instructor = i.id
					LEFT JOIN pref_times pt ON pc.instructor = pt.instructor AND pc.session = pt.session
				WHERE pc.course = '$courseId' AND pc.session = '$session' AND pc.pref > 0
				GROUP BY name, instrId, rnk
				ORDER BY rnk ASC";
			break;
		case 'not_want':
			$query =
				"SELECT 
					CONCAT(i.fn, ' ', i.ln) AS name,
					pref + 17 AS rnk,
                                        i.id AS instrId
				FROM pref_courses p INNER JOIN instructors i ON p.instructor = i.id  
				WHERE p.course = '$courseId' AND p.session = '$session' AND pref < 0
				ORDER BY pref ASC";
			break;
		}	
			
		return mysqli_query($newconn, $query);
	}

        /* function to get available hours for a particular instructor */

	function get_surplus_hours($session,$instrId) {
                global $newconn;

        $query = "SELECT IFNULL(sum(co.crhr), 0) AS assigned,
                         (t.crhr - IFNULL(sum(co.crhr), 0)) AS surplus
                  FROM ( (instructors i INNER JOIN teaching t ON i.id = t.instructor)
                            LEFT OUTER JOIN classes cl ON i.id = cl.instructor AND t.session = cl.session )
                        LEFT OUTER JOIN courses co ON co.id = cl.course
                 WHERE t.session = '$session' AND i.id = '$instrId'
                 GROUP BY i.id";
        return mysqli_query($newconn, $query);

        }

?>
