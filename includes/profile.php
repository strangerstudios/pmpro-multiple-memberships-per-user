<?php
/**
 * Removed default PMPro edit profile functionality and add our own.
 *
 * NOTE: Stripe "updates" are not compatible with MMPU
 */
function pmprommpu_init_profile_hooks() {
	//remove default pmpro hooks
	remove_action( 'show_user_profile', 'pmpro_membership_level_profile_fields' );
	remove_action( 'edit_user_profile', 'pmpro_membership_level_profile_fields' );
	remove_action( 'personal_options_update', 'pmpro_membership_level_profile_fields_update' );
	remove_action( 'edit_user_profile_update', 'pmpro_membership_level_profile_fields_update' );
	
	//add our own
	add_action( 'show_user_profile', 'pmprommpu_membership_level_profile_fields' );
	add_action( 'edit_user_profile', 'pmprommpu_membership_level_profile_fields' );
	add_action( 'personal_options_update', 'pmprommpu_membership_level_profile_fields_update' );
	add_action( 'edit_user_profile_update', 'pmprommpu_membership_level_profile_fields_update' );
}
add_action('init', 'pmprommpu_init_profile_hooks');

/**
 * Show the membership levels section
 *  add_action( 'show_user_profile', 'pmprommpu_membership_level_profile_fields' );
 *  add_action( 'edit_user_profile', 'pmprommpu_membership_level_profile_fields' );
 */
function pmprommpu_membership_level_profile_fields($user) {
global $current_user;

	$membership_level_capability = apply_filters("pmpro_edit_member_capability", "manage_options");
	if(!current_user_can($membership_level_capability))
		return false;

	global $wpdb;
	/*$user->membership_level = $wpdb->get_row("SELECT l.id AS ID, l.name AS name
														FROM {$wpdb->pmpro_membership_levels} AS l
														JOIN {$wpdb->pmpro_memberships_users} AS mu ON (l.id = mu.membership_id)
														WHERE mu.user_id = " . $user->ID . "
														LIMIT 1");*/
	$user->membership_level = pmpro_getMembershipLevelForUser($user->ID);

	$alllevels = $wpdb->get_results( "SELECT * FROM {$wpdb->pmpro_membership_levels}", OBJECT_K );

	if(!$alllevels)
		return "";
?>
<h3><?php _e("Membership Levels", "pmprommpu"); ?></h3>
<?php
	$show_membership_level = true;
	$show_membership_level = apply_filters("pmpro_profile_show_membership_level", $show_membership_level, $user);
	if($show_membership_level)
	{
	?>
	<table class="wp-list-table widefat fixed pmprommpu_levels" width="100%" cellpadding="0" cellspacing="0" border="0">
	<thead>
		<tr>
			<th>Group</th>
			<th>Membership Level</th>
			<th>Expiration</th>
			<th>&nbsp;</th>
		</tr>
	</thead>
	<tbody>
	<?php
		//get levels and groups
		$currentlevels = pmpro_getMembershipLevelsForUser($user->ID);
		$levelsandgroups = pmprommpu_get_levels_and_groups_in_order();
		$allgroups = pmprommpu_get_groups();
		
		//some other vars
		$current_day = date("j", current_time('timestamp'));
		$current_month = date("M", current_time('timestamp'));
		$current_year = date("Y", current_time('timestamp'));
		
		//set group for each level
		for($i = 0; $i < count($currentlevels); $i++) {
			$currentlevels[$i]->group = pmprommpu_get_group_for_level($currentlevels[$i]->id);
		}
		
		//loop through all groups in order and show levels if the user has any currently
		foreach($levelsandgroups as $group_id => $levels) {
			if(pmprommpu_hasMembershipGroup($group_id, $user->ID)) {
				//user has at least one of these levels, so let's show them				
				foreach($currentlevels as $level) {
					if($level->group == $group_id) {
					?>
					<tr>
						<td width="25%"><?php echo $allgroups[$group_id]->name;?></td>
						<td width="25%">
							<?php
								if($allgroups[$group_id]->allow_multiple_selections == 0) {
									//can only select one level from this group, so show old style dropdown to change									
									?>
									<select class="membership_level_id" name="membership_levels[]">
										<option value="">-- <?php _e("None", "pmpro");?> --</option>
									<?php
										foreach($levelsandgroups[$level->group] as $glevel_id)
										{
											$glevel = pmpro_getLevel($glevel_id);
									?>
										<option value="<?php echo $glevel->id?>" <?php selected($glevel->id, $level->id);?> ><?php echo $glevel->name?></option>
									<?php
										}
									?>
									</select>
									<?php
								} else {
									//can select more than one level from this group, so show new style table
									echo $level->name;
									?>
									<input class="membership_level_id" type="hidden" name="membership_levels[]" value="<?php echo esc_attr($level->id);?>" />
									<?php
								}
							?>
						</td>
						<td width="25%">
						<?php
							$show_expiration = true;
							$show_expiration = apply_filters("pmpro_profile_show_expiration", $show_expiration, $user);
							if($show_expiration)
							{					
								//is there an end date?								
								$end_date = !empty($level->enddate);
								
								//some vars for the dates									
								if($end_date)
									$selected_expires_day = date("j", $level->enddate);
								else
									$selected_expires_day = $current_day;
																		
								if($end_date)
									$selected_expires_month = date("m", $level->enddate);
								else
									$selected_expires_month = date("m");
																	
								if($end_date)
									$selected_expires_year = date("Y", $level->enddate);
								else
									$selected_expires_year = (int)$current_year + 1;
							}
							?>
							<select class="expires" name="expires[]">
								<option value="0" <?php if(!$end_date) { ?>selected="selected"<?php } ?>><?php _e("No", "pmpro");?></option>
								<option value="1" <?php if($end_date) { ?>selected="selected"<?php } ?>><?php _e("Yes", "pmpro");?></option>
							</select>
							<span class="expires_date" <?php if(!$end_date) { ?>style="display: none;"<?php } ?>>
								on
								<select name="expires_month[]">
									<?php																
										for($i = 1; $i < 13; $i++)
										{
										?>
										<option value="<?php echo $i?>" <?php if($i == $selected_expires_month) { ?>selected="selected"<?php } ?>><?php echo date("M", strtotime($i . "/1/" . $current_year, current_time("timestamp")))?></option>
										<?php
										}
									?>
								</select>
								<input name="expires_day[]" type="text" size="2" value="<?php echo $selected_expires_day?>" />
								<input name="expires_year[]" type="text" size="4" value="<?php echo $selected_expires_year?>" />
							</span>												
						</td>
						<td width="25%"><a class="remove_level" href="javascript:void(0);"><?php _e('Remove', 'pmprommpu');?></a></td>
					</tr>
					<?php
					}
				}				
			}
		}				
	?>
	<tr id="new_levels_tr_template" class="new_levels_tr" style="display: none;">
		<td>
			<select class="new_levels_group" name="new_levels_group[]">
				<option value="">-- <?php _e("Choose a Group", "pmpro");?> --</option>
				<?php foreach($allgroups as $group) { ?>
					<option value="<?php echo $group->id;?>"><?php echo $group->name;?></option>
				<?php } ?>
			</select>
		</td>
		<td>
			<em><?php _e('Choose a group first.', 'pmprommpu');?></em>
		</td>
		<td>
			<select class="expires new_levels_expires" name="new_levels_expires[]">
				<option value="0" <?php if(!$end_date) { ?>selected="selected"<?php } ?>><?php _e("No", "pmpro");?></option>
				<option value="1" <?php if($end_date) { ?>selected="selected"<?php } ?>><?php _e("Yes", "pmpro");?></option>
			</select>
			<span class="expires_date new_levels_expires_date" <?php if(!$end_date) { ?>style="display: none;"<?php } ?>>
				on
				<select name="new_levels_expires_month[]">
					<?php																
						for($i = 1; $i < 13; $i++)
						{
						?>
						<option value="<?php echo $i?>" <?php if($i == $selected_expires_month) { ?>selected="selected"<?php } ?>><?php echo date("M", strtotime($i . "/1/" . $current_year, current_time("timestamp")))?></option>
						<?php
						}
					?>
				</select>
				<input name="new_levels_expires_day[]" type="text" size="2" value="<?php echo $selected_expires_day?>" />
				<input name="new_levels_expires_year[]" type="text" size="4" value="<?php echo $selected_expires_year?>" />
			</span>
		</td>
		<td><a class="remove_level" href="javascript:void(0);"><?php _e('Remove', 'pmprommpu');?></a></td>
	</tr>
	<tr>
		<td colspan="4"><a href="javascript:void(0);" class="add_level">+ <?php _e('Add Level', 'pmprommpu');?></a></td>
	</tr>
	</tbody>
	</table>
	<script>
		//vars with levels and groups
		var alllevels = <?php echo json_encode($alllevels);?>;
		var allgroups = <?php echo json_encode($allgroups);?>;
		var levelsandgroups = <?php echo json_encode($levelsandgroups);?>;
		
		//update levels when a group dropdown changes
		function updateLevelSelect(e) {
			var groupselect = jQuery(e.target);
			var leveltd = groupselect.parent().next('td');
			var group_id = groupselect.val();
			
			leveltd.html('');
			
			//group chosen?	
			if(group_id.length > 0) {
				//add level select				
				var levelselect = jQuery('<select class="new_levels_level" name="new_levels_level[]"></select>').appendTo(leveltd);				
				levelselect.append('<option value="">-- '+<?php echo json_encode(__('Choose a Level', 'pmprommpu'));?>+' --</option>');
				for(item in levelsandgroups[group_id]) {					
					levelselect.append('<option value="'+alllevels[levelsandgroups[group_id][item]].id+'">'+alllevels[levelsandgroups[group_id][item]].name+'</option>');
				}
			} else {
				leveltd.html('<em>'+<?php echo json_encode(__('Choose a group first.', 'pmprommpu'));?>+'</em>');
			}
		}
		
		//remove level
		function removeLevel(e) {
			var removelink = jQuery(e.target);
			var removetr = removelink.closest('tr');
						
			if(removetr.hasClass('new_levels_tr')) {
				//new level? just delete the row
				removetr.remove();
			} else if(removetr.hasClass('remove_level')) { 
				removetr.removeClass('remove_level');
				removelink.html(<?php echo json_encode(__('Remove', 'pmprommpu'));?>);
				removelink.next('input').remove();
			} else {
				//existing level? red it out and add to be removed
				removetr.addClass('remove_level');
				removelink.html(<?php echo json_encode(__('Undo', 'pmprommpu'));?>);
				var olevelid = removelink.closest('tr').find('input.membership_level_id').val();
				jQuery('<input type="hidden" name="remove_levels_id[]" value="'+olevelid+'">').insertAfter(removelink);
			}
		}
		
		//bindings
		function pmprommpu_updateBindings() {
			//hide/show expiration dates
			jQuery('select.expires').unbind('change.pmprommpu');
			jQuery('select.expires').bind('change.pmprommpu', function() {
				if(jQuery(this).val() == 1)
					jQuery(this).next('span.expires_date').show();
				else
					jQuery(this).next('span.expires_date').hide();
			});
			
			//update level selects when groups are updated
			jQuery('select.new_levels_group').unbind('change.pmprommpu');
			jQuery('select.new_levels_group').bind('change.pmprommpu', updateLevelSelect);
			
			//remove buttons
			jQuery('a.remove_level').unbind('click.pmprommpu');
			jQuery('a.remove_level').bind('click.pmprommpu', removeLevel);

			//clone new level tr
			jQuery('a.add_level').unbind('click.pmprommpu');
			jQuery('a.add_level').bind('click.pmprommpu', function() {				
				var newleveltr = jQuery('#new_levels_tr_template').clone().attr('id', '').css('display', '').insertBefore(jQuery('a.add_level').closest('tr'));
				pmprommpu_updateBindings();
			});				
		}
		
		//on load
		jQuery(document).ready(function() {
			pmprommpu_updateBindings();
		});
	</script>	
	<?php
	}
}

/**
 * Handle updates
 *  add_action( 'personal_options_update', 'pmprommpu_membership_level_profile_fields_update' );
 *  add_action( 'edit_user_profile_update', 'pmprommpu_membership_level_profile_fields_update' );
*/
function pmprommpu_membership_level_profile_fields_update() {

}