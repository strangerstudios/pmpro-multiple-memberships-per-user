<?php 
global $wpdb, $pmpro_msg, $pmpro_msgt, $current_user;

$pmpro_levels = pmpro_getAllLevels(false, true);
$pmpro_groups = mmpu_get_groups();

$incoming_levels = pmpro_getMembershipLevelsForUser();

$displayorder = mmpu_get_levels_and_groups_in_order();

$pmpro_levels = apply_filters("pmpro_levels_array", $pmpro_levels);
if($pmpro_msg)
{
?>
<div class="pmpro_message <?php echo $pmpro_msgt?>"><?php echo $pmpro_msg?></div>
<?php
}
?>
<div id="mmpu_level_summary">No levels selected.</div>
<table id="pmpro_levels_table" class="pmpro_checkout">
<thead>
  <tr>
	<th><?php _e('Group', 'mmpu');?></th>
	<th><?php _e('Level', 'pmpro');?></th>
	<th><?php _e('Price', 'pmpro');?></th>	
	<th><input class="checkoutbutt" id="mmpu_checkout" type="button" value="Checkout" disabled></th>
  </tr>
</thead>
<tbody>
	<?php	
	$count = 0;
	foreach($displayorder as $group => $grouplevels) {
		?>
		<tr>
			<td><?=$pmpro_groups[$group]->name ?></td>
			<td colspan=3>
			<?php if($pmpro_groups[$group]->allow_multiple_selections>0) { ?>
			<em>(Can select only one in this group.)</em>
			<? } ?>
			</td>
		</tr>
		<?php
		foreach($grouplevels as $level)
		{
		?>
		<tr class="group<?=$group ?> <?php if($pmpro_groups[$group]->allow_multiple_selections>0) { echo 'selectone'; } ?>">
			<td>&nbsp;</td>
			<td><?=$pmpro_levels[$level]->name?></td>
			<td>
				<?php 
					if(pmpro_isLevelFree($pmpro_levels[$level]))
						$cost_text = "<strong>" . __("Free", "pmpro") . "</strong>";
					else
						$cost_text = pmpro_getLevelCost($pmpro_levels[$level], true, true); 
					$expiration_text = pmpro_getLevelExpiration($pmpro_levels[$level]);
					if(!empty($cost_text) && !empty($expiration_text))
						echo $cost_text . "<br />" . $expiration_text;
					elseif(!empty($cost_text))
						echo $cost_text;
					elseif(!empty($expiration_text))
						echo $expiration_text;
				?>
			</td>
			<td>
				<input type="button" id="level<?=$pmpro_levels[$level]->id ?>" data-groupid="<?=$group ?>" class="pmpro_btn pmpro_btn-select" value="<?php _e('Select', 'pmpro');?>">
			</td>
		</tr>
		<?php
		}
	}
	?>
</tbody>
</table>
<nav id="nav-below" class="navigation" role="navigation">
	<div class="nav-previous alignleft">
		<?php if(!empty($current_user->membership_level->ID)) { ?>
			<a href="<?php echo pmpro_url("account")?>"><?php _e('&larr; Return to Your Account', 'pmpro');?></a>
		<?php } else { ?>
			<a href="<?php echo home_url()?>"><?php _e('&larr; Return to Home', 'pmpro');?></a>
		<?php } ?>
	</div>
</nav>
<style>
input.selected {
	background-color: rgb(0, 122, 204);
	color: #000000;
}
</style>
<script type="text/javascript">
var selectedlevels = [];
var currentlevels = {};
var removedlevels = {};
var addedlevels = {};
<?php if($incoming_levels) { // At this point, we're not disabling others in the group for initial selections, because if they're here, they probably want to change them.
	foreach($incoming_levels as $curlev) { ?>
		selectedlevels.push("level<?=$curlev->id ?>");
		jQuery("input#level<?=$curlev->id ?>").prop('disabled', false).addClass("selected").val('<?php _e('Selected', 'mmpu');?>');
		currentlevels[<?=$curlev->id ?>] = "<?=$curlev->name ?>";
	<?php } ?>
	updateLevelSummary();
<?php } ?>
var alllevels = {};
<?php foreach($pmpro_levels as $onelev) { ?>
alllevels[<?=$onelev->id ?>] = "<?=$onelev->name ?>";
<?php } ?>
jQuery(document).ready(function() {
	jQuery(".pmpro_btn-select").click(function() {
		if(jQuery(this).prop('disabled') !== true) {
			var mygroup = jQuery(this).attr('data-groupid');
			if(jQuery.inArray(jQuery(this).attr('id'),selectedlevels)>=0) { // we are deselecting a level
				var newlevelid = parseInt(jQuery(this).attr('id').substr(5), 10);
				selectedlevels = removeFromArray(jQuery(this).attr('id'), selectedlevels);
				jQuery(this).removeClass("selected");
				jQuery(this).val('<?php _e('Select', 'pmpro');?>');
				delete addedlevels[newlevelid];
				if(currentlevels.hasOwnProperty(newlevelid)) {
					removedlevels[newlevelid] = alllevels[newlevelid];
				}
				// if we've deselected a level in a one-level-only group, we need to enable the others in the group again.
				if(jQuery(this).parents("tr.selectone").length>0) {
					jQuery("input[data-groupid='" + mygroup + "']").map(function() {
						jQuery(this).prop('disabled', false);
					});
				}
			} else { // we are selecting a level
				// if we've selected a level in a one-level-only group, we need to deselect and dim the others in the group.
				if(jQuery(this).parents("tr.selectone").length>0) {
					jQuery("input[data-groupid='" + mygroup + "']").map(function() {
						var mylevelid = parseInt(jQuery(this).attr('id').substr(5), 10);
						if(jQuery.inArray(jQuery(this).attr('id'),selectedlevels)>=0) {
							selectedlevels = removeFromArray(jQuery(this).attr('id'), selectedlevels);
							delete addedlevels[mylevelid];
							if(currentlevels.hasOwnProperty(mylevelid)) {
								removedlevels[mylevelid] = alllevels[mylevelid];
							}
						}
						jQuery(this).prop('disabled', true);
					});
				}
				selectedlevels.push(jQuery(this).attr('id'));
				var newlevelid = parseInt(jQuery(this).attr('id').substr(5), 10);
				jQuery(this).prop('disabled', false).addClass("selected");
				jQuery(this).val('<?php _e('Selected', 'mmpu');?>');
				delete removedlevels[newlevelid];
				if(currentlevels.hasOwnProperty(newlevelid)) {
					delete addedlevels[newlevelid];
				} else {
					addedlevels[newlevelid] = alllevels[newlevelid];
				}
			}
			updateLevelSummary();
		}
	});
	jQuery("#mmpu_checkout").click(function() {
		var addlevs = joinObjectProps(",", addedlevels);
		var dellevs = joinObjectProps(",", removedlevels);
		var newForm = jQuery('<form>', {
			'action': '<?php echo pmpro_url("checkout", "", "https"); ?>',
			'method': 'POST'
		}).append(jQuery('<input>', {
			'name': 'addlevels',
			'value': addlevs,
			'type': 'hidden'
		})).append(jQuery('<input>', {
			'name': 'dellevels',
			'value': dellevs,
			'type': 'hidden'
		}));
		newForm.submit();
	});
});
function updateLevelSummary() {
	var message = "";
	var cancheckout = false;
	if(numOfPropsInObject(currentlevels)<1 && numOfPropsInObject(removedlevels)<1 && numOfPropsInObject(addedlevels)<1) {
		message = "No levels selected.";
	} else {
		if(numOfPropsInObject(currentlevels)>0) {
			message += "Current Levels: ";
			message += joinObjectProps(", ", currentlevels);
			message += "<br>";
		} else {
			message += "Current Levels: None.<br>";
		}
		if(numOfPropsInObject(addedlevels)>0) {
			message += "Added Levels: ";
			message += joinObjectProps(", ", addedlevels);
			message += "<br>";
			cancheckout = true;
		} else {
			message += "Added Levels: None.<br>";
		}
		if(numOfPropsInObject(removedlevels)>0) {
			message += "Removed Levels: ";
			message += joinObjectProps(", ", removedlevels);
			message += "<br>";
			cancheckout = true;
		} else {
			message += "Removed Levels: None.<br>";
		}
		
	}
	jQuery("#mmpu_level_summary").html(message);
	if(cancheckout) {
		jQuery("#mmpu_checkout").prop('disabled', false);
	} else {
		jQuery("#mmpu_checkout").prop('disabled', true);
	}
}
function removeFromArray(elemtoremove, array) {
	for(var arritem in array) {
		if(array[arritem] === elemtoremove) {
		   array.splice(arritem, 1);
		}
	}
	return array;
}
function numOfPropsInObject(object) {
	var count = 0;
	for (var k in object) if (object.hasOwnProperty(k)) ++count;
	return count;
}
function joinObjectProps(separator, object) {
	var result = "";
	for (var k in object) if (object.hasOwnProperty(k)) {
		if(result.length>0) { result += separator; }
		result += object[k];
	}
	return result;
}
</script>
