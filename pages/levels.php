<?php 
global $wpdb, $pmpro_msg, $pmpro_msgt, $current_user;

$current_levels = array();
$current_user->membership_levels = pmpro_getMembershipLevelsForUser($current_user->ID);
if(!empty($current_user->membership_levels)) {
	foreach($current_user->membership_levels as $level) {
		$current_levels[] = $level->id;
	}
}

$pmpro_levels = pmpro_getAllLevels(false, true);
$pmpro_groups = pmprommpu_get_groups();

$incoming_levels = pmpro_getMembershipLevelsForUser();

$displayorder = pmprommpu_get_levels_and_groups_in_order();

$pmpro_levels = apply_filters("pmpro_levels_array", $pmpro_levels);
if($pmpro_msg)
{
?>
<div class="pmpro_message <?php echo $pmpro_msgt?>"><?php echo $pmpro_msg?></div>
<?php
}
?>
<div id="pmpro_mmpu_levels">
	<div id="pmpro_mmpu_groups">
	<?php	
		//$count = 0;
		foreach($displayorder as $group => $grouplevels) 
		{
			?>
			<div id="pmpro_mmpu_group-<?php echo $pmpro_groups[$group]->id; ?>" class="pmpro_mmpu_group">
				<h2 class="pmpro_mmpu_group-name"><?php echo $pmpro_groups[$group]->name ?></h2>
				<p class="pmpro_mmpu_group-type">
					<?php 
						if($pmpro_groups[$group]->allow_multiple_selections > 0) 
							_e('You can only choose one level from this group.', 'pmprommpu');
						else
							_e('You can choose Choose multiple levels from this group.', 'pmprommpu');
					?>
				</p>
				<?php
					foreach($grouplevels as $level)
					{
					?>
					<div id="pmpro_mmpu_level-<?php echo $pmpro_levels[$level]->id?>" class="pmpro_mmpu_level group<?php echo $group; ?> <?php if($pmpro_groups[$group]->allow_multiple_selections>0) { echo 'selectone'; } ?>">
						<div class="pmpro_level-info">
							<h3 class="pmpro_level-name"><?php echo $pmpro_levels[$level]->name?></h3>
							<p class="pmpro_level-price">
								<?php 
									if(pmpro_isLevelFree($pmpro_levels[$level]))
										_e("Free", "pmpro");
									else
										echo pmpro_getLevelCost($pmpro_levels[$level], true, true);
								?>
							</p> <!-- end pmpro_level-price -->
							<?php
								$expiration_text = pmpro_getLevelExpiration($pmpro_levels[$level]);
								if(!empty($expiration_text))
								{
									?>
									<p class="pmpro_level-expiration">
										<?php echo $expiration_text; ?>
									</p> <!-- end pmpro_level-expiration -->
									<?php
								}
							?>
						</div> <!-- end pmpro_level-info -->
						<div class="pmpro_level-action">
						<?php 
							if($pmpro_groups[$group]->allow_multiple_selections > 0)
							{
								?>
								<!-- change message class wrap to success for selected or error if removing -->
								<label class="pmpro_level-select pmpro_message pmpro_default" for="level-<?php echo $pmpro_levels[$level]->id ?>"><input type="checkbox" id="level-<?php echo $pmpro_levels[$level]->id ?>" data-groupid="<?php echo $group ?>" <?php checked(in_array($pmpro_levels[$level]->id, $current_levels), 1);?>>&nbsp;&nbsp;<?php _e('Select', 'pmprommpu'); ?></label>
								<?php
							}
							else
							{
								?>
								<!-- change message class wrap to success for selected or error if removing -->
								<label class="pmpro_level-select pmpro_message pmpro_default" for="level-<?php echo $pmpro_levels[$level]->id ?>"><input type="radio" id="level-<?php echo $pmpro_levels[$level]->id ?>" name="group-<?php echo $group; ?>" data-groupid="<?php echo $group; ?>" <?php checked(in_array($pmpro_levels[$level]->id, $current_levels), 1);?>>&nbsp;&nbsp;<?php _e('Select', 'pmprommpu'); ?></label>
								<?php
							}
						?>
						</div> <!-- end pmpro_level-action -->
					</div> <!-- end pmpro_mmpu_level-ID -->
					<?php
					}
				?>
			</div> <!-- end pmpro_mmpu_group-ID -->
			<?php
		}
	?>
		<div class="pmpro_mmpu_checkout">
			<div class="pmpro_mmpu_level">
				<div class="pmpro_level-info"></div> <!-- end pmpro_level-info -->
				<div class="pmpro_level-action">	
					<input class="pmpro_mmpu_checkout-button" id="mmpu_checkout" type="button" value="Checkout" disabled>
				</div> <!-- end pmpro_level-action -->
			</div> <!-- end pmpro_mmpu_level -->
		</div> <!-- end pmpro_mmpu_checkout -->
		
	</div> <!-- end pmpro_mmpu_groups -->
	<div id="pmpro_mmpu_level_selections">
		<aside class="widget">
			<h3 class="widget-title"><?php _e('Membership Selections', 'pmprommpu'); ?></h3>
			<div id="pmpro_mmpu_level_summary"><?php _e('Select levels to complete checkout.', 'pmprommpu'); ?></div>
			<p><input class="pmpro_mmpu_checkout-button" id="mmpu_checkout" type="button" value="Checkout" disabled></p>
		</aside> 
	</div> <!-- end pmpro_mmpu_level_selections -->	
</div> <!-- end #pmpro_mmpu_levels -->
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
var lastselectedlevel;
var selectedlevels = [];
var currentlevels = {};
var removedlevels = {};
var addedlevels = {};
<?php if($incoming_levels) { // At this point, we're not disabling others in the group for initial selections, because if they're here, they probably want to change them.
	foreach($incoming_levels as $curlev) { ?>
		selectedlevels.push("level-<?php echo $curlev->id ?>");
		jQuery("input#level<?php echo $curlev->id ?>").prop('disabled', false).removeClass("unselected").addClass("selected").val('<?php _e('Selected', 'mmpu');?>');
		currentlevels[<?php echo $curlev->id ?>] = "<?php echo $curlev->name ?>";
	<?php } ?>
	updateLevelSummary();
<?php } ?>
var alllevels = {};
<?php foreach($pmpro_levels as $onelev) { ?>
alllevels[<?php echo $onelev->id ?>] = "<?php echo $onelev->name ?>";
<?php } ?>
jQuery(document).ready(function() {	
	jQuery(".pmpro_level-select input").bind('change', function() {
		if(this.type == 'radio') {
			//they clicked on a radio button
			var mygroup = jQuery(this).attr('data-groupid');
			var groupradio = jQuery('input[name=group-'+mygroup+']');
			var checkedradio = jQuery('input[name=group-'+mygroup+']:checked');
						
			//remove all levels from this group
			for (var item of groupradio) {
				var item_id = parseInt(jQuery(item).attr('id').replace(/\D/g,''));				
				jQuery('#level-'+item_id).removeClass('selected');
				delete selectedlevels[item_id];
				delete removedlevels[item_id];
				delete addedlevels[item_id];
			}
			
			//add the selected one
			selectedlevels.push(checkedradio.attr('id'));
			var newlevelid = parseInt(checkedradio.attr('id').replace(/\D/g,''));
			checkedradio.addClass("selected");
			//? change the wording of the label?
			delete removedlevels[newlevelid];
			if(currentlevels.hasOwnProperty(newlevelid)) {
				delete addedlevels[newlevelid];
			} else {
				addedlevels[newlevelid] = alllevels[newlevelid];
			}
		} else {
			//they clicked on a checkbox
			var checked = jQuery(this).is(':checked');			
			if(!checked) {
				var mygroup = jQuery(this).attr('data-groupid');
				// we are deselecting a level
				var newlevelid = parseInt(jQuery(this).attr('id').replace(/\D/g,''));
				selectedlevels = removeFromArray(jQuery(this).attr('id'), selectedlevels);
				jQuery(this).removeClass("selected");
				//? change wording of label?
				delete addedlevels[newlevelid];
				if(currentlevels.hasOwnProperty(newlevelid)) {
					removedlevels[newlevelid] = alllevels[newlevelid];
				}
				// if we've deselected a level in a one-level-only group, we need to enable the others in the group again.
				if(jQuery(this).parents("div.selectone").length>0) {
					jQuery("input[data-groupid='" + mygroup + "']").map(function() {
						jQuery(this).prop('disabled', false);
					});
				}		
			} else { 
				// we are selecting a level					
				selectedlevels.push(jQuery(this).attr('id'));
				var newlevelid = parseInt(jQuery(this).attr('id').replace(/\D/g,''));
				jQuery(this).addClass("selected");
				//? change the wording of the label?
				delete removedlevels[newlevelid];
				if(currentlevels.hasOwnProperty(newlevelid)) {
					delete addedlevels[newlevelid];
				} else {
					addedlevels[newlevelid] = alllevels[newlevelid];
				}
			}
		}
		updateLevelSummary();	
	});
	jQuery("#mmpu_checkout").click(function() {
		var addlevs = joinObjectKeys(",", addedlevels);
		var dellevs = joinObjectKeys(",", removedlevels);
		var newForm = jQuery('<form>', {
			'action': '<?php echo pmpro_url("checkout", "", "https"); ?>',
			'method': 'POST'
		}).append(jQuery('<input>', {
			'name': 'level',
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
			message += "<p class='mmpu_currentlevels'><label for='mmpu_currentlevels'>Current Levels</label>";
			message += joinObjectProps(", ", currentlevels);
			message += "</p>";
		} else {
			message += "<p class='mmpu_currentlevels'><label for='mmpu_currentlevels'>Current Levels</label>None.</p>";
		}
		if(numOfPropsInObject(addedlevels)>0) {
			message += "<p class='mmpu_addedlevels'><label for='mmpu_addedlevels'>Added Levels</label>";
			message += joinObjectProps(", ", addedlevels);
			message += "</p>";
			cancheckout = true;
		} else {
			message += "<p class='mmpu_addedlevels'><label for='mmpu_addedlevels'>Added Levels</label>None.</p>";
		}
		if(numOfPropsInObject(removedlevels)>0) {
			message += "<p class='mmpu_removedlevels'><label for='mmpu_removedlevels'>Removed Levels</label>";
			message += joinObjectProps(", ", removedlevels);
			message += "</p>";
			cancheckout = true;
		} else {
			message += "<p class='mmpu_removedlevels'><label for='mmpu_removedlevels'>Removed Levels</label>None.</p>";
		}
		
	}
	jQuery("#pmpro_mmpu_level_summary").html(message);
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
function joinObjectKeys(separator, object) {
	var result = "";
	for (var k in object) if (object.hasOwnProperty(k)) {
		if(result.length>0) { result += separator; }
		result += k;
	}
	return result;
}
</script>

