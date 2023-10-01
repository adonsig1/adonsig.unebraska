<table class="time_select">
	<tr>
		<td>
			<select id='<?php echo $id ?>_start'>
			<?php
				foreach ($times as $t) {
					echo "<option>$t</option>\n";
				}
			?>
			</select>
			 - 
			<select id='<?php echo $id ?>_end'>
			<?php
				foreach ($times as $t) {
					echo "<option>$t</option>\n";
				}
			?>
			</select>
			<select id='<?php echo $id ?>_days'><option>MWF</option><option>TR</option><option>M</option><option>T</option><option>W</option><option>R</option><option>F</option></select>
		</td>
		<td>
			<select multiple name="<?php echo $id ?>[]" size='5' id='<?php echo $id ?>' style='width: 14em'>
				<?php
					$rs = mysqli_query($newconn, $query);
					while ($r = mysqli_fetch_assoc($rs)) {
						$text = $r["text"];
						echo "<option>$text</option>";
					}
				?>
			</select>
		</td>
	</tr>
	<tr>
		<td></td>
		<td style='text-align: center;'>
			<?php
				make_button("Add", "add.png", "addTimes('$id')");
				make_button("Remove", "delete.png", "removeTimes('$id')");
			?>
		</td>
	</tr>
</table>
