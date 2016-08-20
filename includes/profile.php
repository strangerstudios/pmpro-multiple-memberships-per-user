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

	$levels = $wpdb->get_results( "SELECT * FROM {$wpdb->pmpro_membership_levels}", OBJECT );

	if(!$levels)
		return "";
?>
<h3><?php _e("Membership Levels", "pmprommpu"); ?></h3>
<?php
	$show_membership_level = true;
	$show_membership_level = apply_filters("pmpro_profile_show_membership_level", $show_membership_level, $user);
	if($show_membership_level)
	{
	?>
	<table class="wp-list-table widefat fixed" width="100%" cellpadding="0" cellspacing="0" border="0">
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
									echo $level->name;
								} else {
									//can select more than one level from this group, so show new style table
									echo $level->name;
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
							<select class="expires" name="expires">
								<option value="0" <?php if(!$end_date) { ?>selected="selected"<?php } ?>><?php _e("No", "pmpro");?></option>
								<option value="1" <?php if($end_date) { ?>selected="selected"<?php } ?>><?php _e("Yes", "pmpro");?></option>
							</select>
							<span class="expires_date" <?php if(!$end_date) { ?>style="display: none;"<?php } ?>>
								on
								<select name="expires_month">
									<?php																
										for($i = 1; $i < 13; $i++)
										{
										?>
										<option value="<?php echo $i?>" <?php if($i == $selected_expires_month) { ?>selected="selected"<?php } ?>><?php echo date("M", strtotime($i . "/1/" . $current_year, current_time("timestamp")))?></option>
										<?php
										}
									?>
								</select>
								<input name="expires_day" type="text" size="2" value="<?php echo $selected_expires_day?>" />
								<input name="expires_year" type="text" size="4" value="<?php echo $selected_expires_year?>" />
							</span>												
						</td>
						<td width="25%"><a href="#">Remove</a></td>
					</tr>
					<?php
					}
				}				
			}
		}				
	?>
	<tr>
		<td colspan="4"><a href="#">+ Add Level</a></td>
	</tr>
	</tbody>
	</table>
	<script>
		//hide/show expiration dates
		jQuery('select.expires').change(function() {
			if(jQuery(this).val() == 1)
				jQuery(this).next('span.expires_date').show();
			else
				jQuery(this).next('span.expires_date').hide();
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