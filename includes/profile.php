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

	if ( ! function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
		return false;
	}

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
		$levelsandgroups = pmprommpu_get_levels_and_groups_in_order(true);
		$allgroups = pmprommpu_get_groups();

		//some other vars
		$current_day = date("j", current_time('timestamp'));
		$current_month = date("M", current_time('timestamp'));
		$current_year = date("Y", current_time('timestamp'));

		ob_start();
		?>
		<tr id="new_levels_tr_template" class="new_levels_tr">
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
				<?php
					//default enddate values
					$end_date = false;
					$selected_expires_day = $current_day;
					$selected_expires_month = date("m");
					$selected_expires_year = (int)$current_year + 1;
				?>
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
							<option value="<?php echo $i?>" <?php if($i == $selected_expires_month) { ?>selected="selected"<?php } ?>><?php echo date("M", strtotime($i . "/15/" . $current_year, current_time("timestamp")))?></option>
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
		<?php
		$new_level_template_html = preg_replace('/\n\t+/', '', ob_get_contents());
		ob_end_clean();

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
								echo $level->name;
							?>
							<input class="membership_level_id" type="hidden" name="membership_levels[]" value="<?php echo esc_attr($level->id);?>" />
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
										<option value="<?php echo $i?>" <?php if($i == $selected_expires_month) { ?>selected="selected"<?php } ?>><?php echo date("M", strtotime($i . "/15/" . $current_year, current_time("timestamp")))?></option>
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
					<tr class="old_levels_delsettings_tr_template remove_level">
						<td></td>
						<td colspan="3">
							<label for="send_admin_change_email"><input value="1" id="send_admin_change_email" name="send_admin_change_email[]" type="checkbox"> Send the user an email about this change.</label><br>
			                <label for="cancel_subscription"><input value="1" id="cancel_subscription" name="cancel_subscription[]" type="checkbox"> Cancel this user's subscription at the gateway.</label>
						</td>
					</tr>
					<?php
					}
				}
			}
		}
	?>
	<tr>
		<td colspan="4"><a href="javascript:void(0);" class="add_level">+ <?php _e('Add Level', 'pmprommpu');?></a></td>
	</tr>
	</tbody>
	</table>
	<script type="text/javascript">
		//vars with levels and groups
		var alllevels = <?php echo json_encode($alllevels);?>;
		var allgroups = <?php echo json_encode($allgroups);?>;
		var levelsandgroups = <?php echo json_encode($levelsandgroups);?>;
		var delsettingsrow = jQuery(".old_levels_delsettings_tr_template").first().detach();
		jQuery(".old_levels_delsettings_tr_template").detach();

		var new_level_template_html = '<?php echo $new_level_template_html; ?>';

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
				levelselect.append('<option value="">-- ' + <?php echo json_encode(__('Choose a Level', 'pmprommpu'));?> + ' --</option>');
				for(item in levelsandgroups[group_id]) {
					levelselect.append('<option value="'+alllevels[levelsandgroups[group_id][item]].id+'">'+alllevels[levelsandgroups[group_id][item]].name+'</option>');
				}
			} else {
				leveltd.html('<em>' + <?php echo json_encode(__('Choose a group first.', 'pmprommpu'));?> + '</em>');
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
				removetr.nextAll('.old_levels_delsettings_tr_template').first().remove();
			} else {
				//existing level? red it out and add to be removed
				removetr.addClass('remove_level');
				removelink.html(<?php echo json_encode(__('Undo', 'pmprommpu'));?>);
				var olevelid = removelink.closest('tr').find('input.membership_level_id').val();
				jQuery('<input type="hidden" name="remove_levels_id[]" value="'+olevelid+'">').insertAfter(removelink);
				removetr.after(delsettingsrow.clone());
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
				var newleveltr = jQuery('a.add_level').closest('tbody').append(new_level_template_html);
				pmprommpu_updateBindings();
			});
		}

		//on load
		jQuery(document).ready(function() {
			pmprommpu_updateBindings();
		});
	</script>
	<?php
	do_action("pmpro_after_membership_level_profile_fields", $user);
	}
}

/**
 * Handle updates
 *  add_action( 'personal_options_update', 'pmprommpu_membership_level_profile_fields_update' );
 *  add_action( 'edit_user_profile_update', 'pmprommpu_membership_level_profile_fields_update' );
*/
function pmprommpu_membership_level_profile_fields_update() {
	//get the user id
	global $wpdb, $current_user;
	wp_get_current_user();

	if(!empty($_REQUEST['user_id'])) {
		$user_id = $_REQUEST['user_id'];
	} else {
		$user_id = $current_user->ID;
	}

	$membership_level_capability = apply_filters("pmpro_edit_member_capability", "manage_options");
	if(!current_user_can($membership_level_capability))
		return false;

	// OK. First, we're going to remove them from any levels that they should be dropped from - and keep an array of the levels we're dropping (so we don't adjust expiration later)
	$droppedlevels = array();
	$old_levels = pmpro_getMembershipLevelsForUser($user_id);
	if(array_key_exists('remove_levels_id', $_REQUEST)) {
		foreach($_REQUEST['remove_levels_id'] as $arraykey => $leveltodel) {
// 			$subscription_id = -1;
// 			foreach($old_levels as $checklevel) {
// 				if($checklevel->id == $leveltodel) {
// 					$subscription_id = $checklevel->subscription_id;
// 					break;
// 				}
// 			}
// 			$wpdb->query("UPDATE $wpdb->pmpro_memberships_users SET status ='admin_cancelled', enddate ='".current_time('mysql')."' WHERE id=$subscription_id");
// 			if(is_array($_REQUEST['cancel_subscription']) && array_key_exists($arraykey, $_REQUEST['cancel_subscription']) && !empty($_REQUEST['cancel_subscription'][$arraykey])) {
// 				$other_order_ids = $wpdb->get_col("SELECT id FROM $wpdb->pmpro_membership_orders WHERE user_id = '" . $user_id . "' AND status = 'success' AND membership_id = $leveltodel ORDER BY id DESC");
//
// 				foreach($other_order_ids as $order_id)
// 				{
// 					$c_order = new MemberOrder($order_id);
// 					$c_order->cancel();
// 				}
// 			}
// 			//email to admin
// 			$pmproemail = new PMProEmail();
// 			$pmproemail->sendAdminChangeAdminEmail(get_userdata($user_id));
//
// 			//send email
// 			if(is_array($_REQUEST['send_admin_change_email']) && array_key_exists($arraykey, $_REQUEST['send_admin_change_email']) && !empty($_REQUEST['send_admin_change_email'][$arraykey])) {
// 				//email to member
// 				$pmproemail = new PMProEmail();
// 				$pmproemail->sendAdminChangeEmail(get_userdata($user_id));
// 			}
			pmpro_cancelMembershipLevel($leveltodel, $user_id, 'admin_cancelled');
			$droppedlevels[] = $leveltodel;
		}
	}

	// Next, let's update the expiration on any existing levels - as long as the level isn't in one of the ones we dropped them from.
	if(array_key_exists('expires', $_REQUEST)) {
		foreach($_REQUEST['expires'] as $expkey => $doesitexpire) {
			$thislevel = $_REQUEST['membership_levels'][$expkey];
			if(!in_array($thislevel, $droppedlevels)) { // we don't change expiry for a level we've dropped.
				if(!empty($doesitexpire)) { // we're going to expire.
					//update the expiration date
					$expiration_date = intval($_REQUEST['expires_year'][$expkey]) . "-" . str_pad(intval($_REQUEST['expires_month'][$expkey]), 2, "0", STR_PAD_LEFT) . "-" . str_pad(intval($_REQUEST['expires_day'][$expkey]), 2, "0", STR_PAD_LEFT);

					$wpdb->update(
						$wpdb->pmpro_memberships_users,
						array( 'enddate' => $expiration_date ),
						array(
							'status' => 'active',
							'membership_id' => $thislevel,
							'user_id' => $user_id ), // Where clause
						array( '%s' ),  // format for data
						array( '%s', '%d', '%d' ) // format for where clause
					);

					// $wpdb->query("UPDATE $wpdb->pmpro_memberships_users SET enddate = '" . $expiration_date . "' WHERE status = 'active' AND membership_id = '" . intval($thislevel) . "' AND user_id = '" . $user_id . "' LIMIT 1");
				} else { // No expiration for me!
					$wpdb->update(
						$wpdb->pmpro_memberships_users,
						array( 'enddate' => NULL ),
						array(
							'status' => 'active',
							'membership_id' => $thislevel,
							'user_id' => $user_id
						),
						array( NULL ),
						array( '%s', '%d', '%d' )
					);

					// $wpdb->query("UPDATE $wpdb->pmpro_memberships_users SET enddate = NULL WHERE status = 'active' AND membership_id = '" . intval($thislevel) . "' AND user_id = '" . $user_id . "' LIMIT 1");
				}
			}
		}
	}
	// Finally, we'll add any new levels requested. First, we'll try it without forcing, and then if need be, we'll force it (but then we'll know to give a warning about it.)
	if(array_key_exists('new_levels_level', $_REQUEST)) {
		$hadtoforce = false;
		$curlevels = pmpro_getMembershipLevelsForUser($user_id); // have to do it again, because we've made changes since above.
		$curlevids = array();
		foreach($curlevels as $thelev) { $curlevids[] = $thelev->ID; }
		foreach($_REQUEST['new_levels_level'] as $newkey => $leveltoadd) {
			if(! in_array($leveltoadd, $curlevids)) {
				$result = pmprommpu_addMembershipLevel($leveltoadd, $user_id, false);
				if(! $result) {
					pmprommpu_addMembershipLevel($leveltoadd, $user_id, true);
					$hadtoforce = true;
				}
				$doweexpire = $_REQUEST['new_levels_expires'][$newkey];
				if(!empty($doweexpire)) { // we're going to expire.
					//update the expiration date
					$expiration_date = intval($_REQUEST['new_levels_expires_year'][$newkey]) . "-" . str_pad(intval($_REQUEST['new_levels_expires_month'][$newkey]), 2, "0", STR_PAD_LEFT) . "-" . str_pad(intval($_REQUEST['new_levels_expires_day'][$newkey]), 2, "0", STR_PAD_LEFT);

					$wpdb->update(
						$wpdb->pmpro_memberships_users,
						array( 'enddate' => $expiration_date ),
						array(
							'status' => 'active',
							'membership_id' => $leveltoadd,
							'user_id' => $user_id ), // Where clause
						array( '%s' ),  // format for data
						array( '%s', '%d', '%d' ) // format for where clause
					);

					// $wpdb->query("UPDATE $wpdb->pmpro_memberships_users SET enddate = '" . $expiration_date . "' WHERE status = 'active' AND membership_id = '" . intval($leveltoadd) . "' AND user_id = '" . $user_id . "' LIMIT 1");
				} else { // No expiration for me!
					$wpdb->update(
						$wpdb->pmpro_memberships_users,
						array( 'enddate' => NULL ),
						array(
							'status' => 'active',
							'membership_id' => $leveltoadd,
							'user_id' => $user_id
						),
						array( NULL ),
						array( '%s', '%d', '%d' )
					);

					// $wpdb->query("UPDATE $wpdb->pmpro_memberships_users SET enddate = NULL WHERE status = 'active' AND membership_id = '" . intval($leveltoadd) . "' AND user_id = '" . $user_id . "' LIMIT 1");
				}
			}
		}
		if($hadtoforce) {
			// TODO: Should flag some kind of message to alert the admin that we had to force it (and the consequences of that).
		}
	}
	wp_cache_delete( 'user_' . $user_id . '_levels', 'pmpro' );
}
