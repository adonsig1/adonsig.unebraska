<?php 
	$max_width = 0;
	
	$rs_sel = mysqli_query($newconn, $query_sel);
	$rs_non = mysqli_query($newconn, $query_non);
	
	while ($r = mysqli_fetch_assoc($rs_sel)) {
		$k = strlen($r["text"]);
		$max_width = ($k > $max_width) ? $k : $max_width; 
	}
	mysqli_data_seek($rs_sel, 0);
	
	while ($r = mysqli_fetch_assoc($rs_non)) {
		$k = strlen($r["text"]);
		$max_width = ($k > $max_width) ? $k : $max_width; 
	}
	mysqli_data_seek($rs_non, 0);
?>
<table class="course_select">
	<tr>
		<th>All Courses</th>
		<th>Selected Courses</th>
	</tr>
	<tr>
		<td rowspan="2">
			<select size="10" id="<?php echo $id ?>_list" style="width:<?php echo $max_width ?>ex;">
				<?php 
					while ($r = mysqli_fetch_assoc($rs_non)) {
						$text = $r["text"];
						$value = $r["value"];
						echo "<option value='$value'>";
						echo $r["text"];
						echo "</option>";
					}
				?>
			</select>
		</td>
		<td>
			<select multiple name="<?php echo $id ?>[]" size="5" id="<?php echo $id ?>_selected" style="width:<?php echo $max_width ?>ex;">
				<?php 
					while ($r = mysqli_fetch_assoc($rs_sel)) {
						$text = $r["text"];
						$value = $r["value"];
						echo "<option value='$value'>";
						echo $r["text"];
						echo "</option>";
					}
				?>
			</select>
			<?php 
			        if (!isset($unordered_list) || !$unordered_list):
			?>
			<div  class="small_instructions" style="width:<?php echo $max_width ?>ex;">
				This is an ordered listt, with highest priority
				given to the items at the top of the list.
			</div>
			<?php 
				endif;
			?>
		</td>
		<?php 
			if (!isset($unordered_list) || !$unordered_list):
		?>
		<td>
			<?php	make_button("", "../images/arrow_up.png", "moveUp('" . $id . "_selected')"); ?>
			<br>
			<?php make_button("", "../images/arrow_down.png", "moveDown('" . $id . "_selected')"); ?>
		</td>
		<?php 
			endif;
		?>
	</tr>
	<tr>
		<td style="text-align: center;">
			<?php
				make_button("Add", "add.png", "moveOption('" . $id . "_list', '" . $id . "_selected', false);");
				make_button("Remove", "delete.png", "moveOption('" . $id . "_selected', '" . $id . "_list', true);");
			?>
		</td>
	</tr>
</table>
