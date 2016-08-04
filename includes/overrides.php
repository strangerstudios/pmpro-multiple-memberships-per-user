<?php

// This file is where we override the default PMPro functionality & pages as needed. (Which is a lot.)

// First, the user pages - the actual function is in functions.php
add_filter( 'pmpro_pages_custom_template_path', 'pmprommpu_override_user_pages', 10, 5 );

// Next, let's make sure jQuery UI Dialog is present on the admin side.
function pmprommpu_addin_jquery_dialog($pagehook) {
	if(strpos($pagehook, "pmpro-membershiplevels") !== FALSE) { // only add the overhead on the membership levels page.
		wp_enqueue_script('jquery-ui-dialog');
	}
}
add_action( 'admin_enqueue_scripts', 'pmprommpu_addin_jquery_dialog' );

// Filter the text on the checkout page that tells the user what levels they're getting
function pmprommpu_checkout_level_text($intext, $levelids_adding, $levelids_deleting) {
	$levelarr = pmpro_getAllLevels(true);
	$outstring = "<p>You have selected the following level";
	if(count($levelids_adding)>1) { $outstring .= "s"; }
	$outstring .= ":</p>";
	foreach($levelids_adding as $curlevelid) {
		$outstring .= "<p class='levellist'><strong><span class='levelnametext'>".$levelarr[$curlevelid]->name."</span></strong>";
		if(! empty($levelarr[$curlevelid]->description)) {
			$outstring .= "<br /><span class='leveldesctext'>".stripslashes($levelarr[$curlevelid]->description)."</span>";
		}
		$outstring .= "</p>";
	}
	if(count($levelids_deleting)>0) {
		$outstring .= print_r($levelids_deleting); // delete me 
		$outstring .= "<p>You are removing the following level";
		if(count($levelids_deleting)>1) { $outstring .= "s"; }
		$outstring .= ":</p>";
		foreach($levelids_deleting as $curlevelid) {
			$outstring .= "<p class='levellist'><strong><span class='levelnametext'>".$levelarr[$curlevelid]->name."</span></strong>";
			if(! empty($levelarr[$curlevelid]->description)) {
				$outstring .= "<br /><span class='leveldesctext'>".stripslashes($levelarr[$curlevelid]->description)."</span>";
			}
			$outstring .= "</p>";
		}
	}
	return $outstring;
}
add_filter( 'pmprommpu_checkout_level_text', 'pmprommpu_checkout_level_text', 10, 3);

// Ensure than when a membership level is changed, it doesn't delete the old one or unsubscribe them at the gateway. We'll 
// handle both later in the process.
function pmprommpu_pmpro_deactivate_old_levels($insetting) {
	return false;
}
add_filter( 'pmpro_deactivate_old_levels', 'pmprommpu_pmpro_deactivate_old_levels', 10, 1);

function pmprommpu_pmpro_cancel_previous_subscriptions($insetting) {
	return false;
}
add_filter( 'pmpro_cancel_previous_subscriptions', 'pmprommpu_pmpro_cancel_previous_subscriptions', 10, 1);

// Called after the checkout process, we are going to do two things here:
// First, any unsubscriptions that the user opted for (whose level ids are in $_REQUEST['dellevels']) will be dropped.
// Then, any remaining conflicts will be dropped.
function pmprommpu_unsub_after_all_checkouts($user_id, $checkout_statuses) {
	global $wpdb;
	
	if(array_key_exists('dellevels', $_REQUEST) && strlen($_REQUEST['dellevels'])>0) {
		$dellevelids = explode(',', $_REQUEST['dellevels']);
		$dellevelids = array_map('intval', $dellevelids); // these should always be integers
		foreach($dellevelids as $idtodel) {
			$sql = "UPDATE $wpdb->pmpro_memberships_users SET `status`='changed', `enddate`='" . current_time('mysql') . "' WHERE `user_id` = '". $user_id . "' AND `status`='active' AND `membership_id`=".$idtodel;
			$wpdb->query($sql);
			
			$order_ids_todel = $wpdb->get_col("SELECT id FROM $wpdb->pmpro_membership_orders WHERE user_id = '" . $user_id . "' AND membership_id = " . $idtodel . " AND status = 'success' ORDER BY id DESC");
			foreach($order_ids_todel as $order_id)
			{
				$c_order = new MemberOrder($order_id);
				$c_order->cancel();
			}
		}
	}

	// OK, levels are added, levels are removed. Let's check once more for any conflict, and resolve them - with extreme prejudice.
	$currentlevels = pmpro_getMembershipLevelsForUser($user_id);
	$currentlevelids = array();
	foreach($currentlevels as $curlevel) {
		$currentlevelids[] = $curlevel->id;
	}
	$levelsandgroups = pmprommpu_get_levels_and_groups_in_order();
	$allgroups = pmprommpu_get_groups();
	
	$levelgroupstoprune = array();
	foreach($levelsandgroups as $curgp => $gplevels) {
		if(array_key_exists($curgp, $allgroups) && $allgroups[$curgp]->allow_multiple_selections == 0) { // we only care about groups that restrict to one level within it
			$conflictlevels = array();
			foreach($gplevels as $curlevel) {
				if(in_array($curlevel->id, $currentlevelids)) { $conflictlevels[] = $curlevel->id; }
			}
			if(count($conflictlevels)>1) { $levelgroupstoprune[] = $conflictlevels; }
		}
	}
	if(count($levelgroupstoprune)>0) { // we've got some resolutions to do.
		foreach($levelgroupstoprune as $curgroup) {
			$groupstring = implode(',', $curgroup);

			$sql = "UPDATE $wpdb->pmpro_memberships_users SET `status`='changed', `enddate`='" . current_time('mysql') . "' WHERE `user_id` = '". $user_id . "' AND `status`='active' AND `membership_id` IN (".$groupstring.")";
			$wpdb->query($sql);

			$order_ids = $wpdb->get_col("SELECT id FROM $wpdb->pmpro_membership_orders WHERE user_id = '" . $user_id . "' AND membership_id IN (" . $groupstring . ") AND status = 'success' ORDER BY id DESC");
			array_shift($order_ids); // keep the newest one
			foreach($order_ids_todel as $order_id)
			{
				$c_order = new MemberOrder($order_id);
				$c_order->cancel();
			}
		}
	}
}
add_action( 'pmpro_after_all_checkouts', 'pmprommpu_unsub_after_all_checkouts', 10, 2);

// Now, on to the admin pages. Let's start with membership levels...

/**
 * Change membership levels admin page to show groups.
 */
function pmprommpu_pmpro_membership_levels_table($intablehtml, $inlevelarr) {
	$groupsnlevels = pmprommpu_get_levels_and_groups_in_order(true);
	$allgroups = pmprommpu_get_groups();
	$alllevels = pmpro_getAllLevels(true, true);
	
	ob_start(); ?>
	
	<script>
		jQuery( document ).ready(function() {
				jQuery('#add-new-group').insertAfter( "h2 .add-new-h2" );
		});
	</script>
	<a id="add-new-group" class="add-new-h2" href="admin.php?page=pmpro-level-groups&edit=-1"><?php _e('Add New Group', 'pmprommpu'); ?></a>
	
    <table class="widefat mmpu-membership-levels">		
		<thead>
			<tr>
				<th width="20%"><?php _e('Group', 'pmpro');?></th>
				<th><?php _e('ID', 'pmpro');?></th>
				<th><?php _e('Name', 'pmpro');?></th>
				<th><?php _e('Billing Details', 'pmpro');?></th>
				<th><?php _e('Expiration', 'pmpro');?></th>
				<th><?php _e('Allow Signups', 'pmpro');?></th>
				<th width="15%"></th>
			</tr>
		</thead>
		<?php 
			$count = 0;
			foreach($groupsnlevels as $curgroup => $itslevels) { 
				$onerowclass = "";
				if(count($itslevels)==0) { $onerowclass = "onerow"; } else { $onerowclass = "toprow"; }
				$groupname = "Unnamed Group";
				$groupallowsmult = 0;
				if(array_key_exists($curgroup, $allgroups)) {
					$groupname = $allgroups[$curgroup]->name;
					$groupallowsmult = $allgroups[$curgroup]->allow_multiple_selections;
				}
			?>
			<tbody data-groupid="<?=$curgroup ?>" class="membership-level-groups">
			<tr class="grouprow <?=$onerowclass ?>">
				<th rowspan="<?php echo max(count($itslevels)+1,2); ?>" scope="rowgroup" valign="top">
					<h2><?php echo $groupname;?></h2>
					<?php if(! $groupallowsmult) { ?>
						<p><em><?php _e('Users can only choose one level from this group.', 'pmprommpu');?></em></p>
					<?php } ?>
					<p>
						<a data-groupid="<?=$curgroup ?>" title="<?php _e('edit','pmpro'); ?>" href="admin.php?page=pmpro-membershiplevels&edit=<?php echo $level->id?>" class="editgrpbutt button-primary"><?php _e('edit','pmpro'); ?></a>						
						<?php if(count($itslevels)==0) { ?>
							<a title="<?php _e('delete','pmpro'); ?>" href="javascript: void(0);" class="delgroupbutt button-secondary"><?php _e('delete','pmpro'); ?></a>
						<?php } ?>
					</p>
				</th>
				<?php /*
				<td colspan=4><span class="groupname"><?=$groupname ?></span> <button type="button" data-groupid="<?=$curgroup ?>" class="editgrpbutt">Edit Group</button>
					<span style="display: none;" id="group<?=$curgroup ?>name"><?=$groupname ?></span><span style="display: none;" id="group<?=$curgroup ?>allowmult"><?=$groupallowsmult ?></span>
					<?php if(count($itslevels)==0) { ?> <button type="button" data-groupid="<?=$curgroup ?>" class="delgroupbutt">Delete Group</button> <?php } ?>
					</td>
				<td colspan=3 style="text-align: right;">
					<?php if(! $groupallowsmult) { ?>
						<em>Users can only choose one level from this group.</em>
					<?php } else { echo ' &nbsp; '; } ?>
				</td>
				*/ ?>
			</tr>
			<?php if(count($itslevels)>0) { ?>				
					<?php foreach($itslevels as $curlevelid)
					{
						if(array_key_exists($curlevelid, $alllevels)) { // Just in case there's a level artifact in the groups table that wasn't removed - it won't show here.
							$level = $alllevels[$curlevelid];
					?>
							<tr class="<?php if($count++ % 2 == 1) { ?>alternate<?php } ?> levelrow <?php if(!$level->allow_signups) { ?>pmpro_gray<?php } ?> <?php if(!pmpro_checkLevelForStripeCompatibility($level) || !pmpro_checkLevelForBraintreeCompatibility($level) || !pmpro_checkLevelForPayflowCompatibility($level) || !pmpro_checkLevelForTwoCheckoutCompatibility($level)) { ?>pmpro_error<?php } ?>">			
								
								<td class="levelid"><?php echo $level->id?></td>
								<td class="level_name"><a href="admin.php?page=pmpro-membershiplevels&edit=<?php echo $level->id?>"><?php echo $level->name?></a></td>
								<td>
									<?php if(pmpro_isLevelFree($level)) { ?>
										<?php _e('FREE', 'pmpro');?>
									<?php } else { ?>
										<?php echo str_replace( 'The price for membership is', '', pmpro_getLevelCost($level)); ?>
									<?php } ?>
								</td>
								<td>
									<?php if(!pmpro_isLevelExpiring($level)) { ?>
										--
									<?php } else { ?>		
										<?php _e('After', 'pmpro');?> <?php echo $level->expiration_number?> <?php echo sornot($level->expiration_period,$level->expiration_number)?>
									<?php } ?>
								</td>
								<td><?php if($level->allow_signups) { ?><a href="<?php echo pmpro_url("checkout", "?level=" . $level->id);?>"><?php _e('Yes', 'pmpro');?></a><?php } else { ?><?php _e('No', 'pmpro');?><?php } ?></td>

								<td><a title="<?php _e('edit','pmpro'); ?>" href="admin.php?page=pmpro-membershiplevels&edit=<?php echo $level->id?>" class="button-primary"><?php _e('edit','pmpro'); ?></a>&nbsp;<a title="<?php _e('copy','pmpro'); ?>" href="admin.php?page=pmpro-membershiplevels&copy=<?php echo $level->id?>&edit=-1" class="button-secondary"><?php _e('copy','pmpro'); ?></a>&nbsp;<a title="<?php _e('delete','pmpro'); ?>" href="javascript: askfirst('<?php echo str_replace("'", "\'", sprintf(__("Are you sure you want to delete membership level %s? All subscriptions will be cancelled.", "pmpro"), $level->name));?>','admin.php?page=pmpro-membershiplevels&action=delete_membership_level&deleteid=<?php echo $level->id?>'); void(0);" class="button-secondary"><?php _e('delete','pmpro'); ?></a></td>
							</tr>
					<?php
						}
					}
				?>
			<?php } else { ?>
				<tr class="levelrow"><td colspan="6"></td></tr>					
			<?php } ?>
			</tbody>
		<?php } ?>
		</tbody>
	</table>
	<div id="addeditgroupdialog" style="display:none;">
		<p>Name<input type="text" size="30" id="groupname"></p>
		<p>Can users choose more than one level in this group? <input type="checkbox" id="groupallowmult" value="1"></p>
	</div>
	<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery("#add-new-group").click(function() {
			dialog = jQuery("#addeditgroupdialog").dialog({
				autoOpen: false,
				title: "Add Group",
				modal: true,
				buttons: {
					"Add": function() {
						if(jQuery("#groupname").val().length>0) {
							var groupname = jQuery("#groupname").val();
							var allowmult = 0;
							if(jQuery("#groupallowmult").attr("checked")) { allowmult = 1; }
							dialog.dialog("close");
							jQuery.post(ajaxurl, { action: "pmprommpu_add_group", name: groupname, mult: allowmult }, function() {
								window.location = "<?php echo admin_url('admin.php?page=pmpro-membershiplevels'); ?>";
							});
						}
					},
					"Cancel": function() {
						dialog.dialog("close");
					}
				}
			});
			dialog.dialog("open");
		});
		jQuery(".editgrpbutt").click(function() {
			var groupid = parseInt(jQuery(this).attr("data-groupid"), 10);
			if(groupid>0) {
				jQuery("#groupname").val(jQuery("#group" + groupid + "name").text());
				if(parseInt(jQuery("#group" + groupid + "allowmult").text(), 10)>0) {
					jQuery("#groupallowmult").attr('checked', true);
				} else {
					jQuery("#groupallowmult").attr('checked', false);
				}
				dialog = jQuery("#addeditgroupdialog").dialog({
					autoOpen: false,
					title: "Edit Group",
					modal: true,
					buttons: {
						"Edit": function() {
							if(jQuery("#groupname").val().length>0) {
								var groupname = jQuery("#groupname").val();
								var allowmult = 0;
								if(jQuery("#groupallowmult").attr("checked")) { allowmult = 1; }
								dialog.dialog("close");
								jQuery.post(ajaxurl, { action: "pmprommpu_edit_group", group: groupid, name: groupname, mult: allowmult }, function() {
									window.location = "<?php echo admin_url('admin.php?page=pmpro-membershiplevels'); ?>";
								});
							}
						},
						"Cancel": function() {
							dialog.dialog("close");
						}
					}
				});
				dialog.dialog("open");
			}
		});
		jQuery(".delgroupbutt").click(function() {
			var groupid = parseInt(jQuery(this).attr("data-groupid"), 10);
			if(groupid>0) {
				var answer = confirm("Are you sure you want to delete this group? It cannot be undone.");
				if(answer) {
					jQuery.post(ajaxurl, { action: "pmprommpu_del_group", group: groupid }, function() {
						window.location.reload(true);
					});
				}
			}
		});
		
        // Return a helper with preserved width of cells
		// from http://www.foliotek.com/devblog/make-table-rows-sortable-using-jquery-ui-sortable/
		var fixHelper = function(e, ui) {
			ui.children().each(function() {
				jQuery(this).width(jQuery(this).width());
			});
			return ui;
		};

		jQuery("table.mmpu-membership-levels").sortable({
			helper: fixHelper,
			update: update_level_and_group_order
		});
		
		jQuery("table.mmpu-membership-levels tbody").sortable({
			items: "tr.levelrow",
			helper: fixHelper,
			placeholder: 'testclass',
			forcePlaceholderSize: true,
			connectWith: "tbody",
			update: update_level_and_group_order
		});
		
		function update_level_and_group_order(event, ui) {
			var groupsnlevels = [];
			jQuery("tbody").each(function() {
				var groupid = jQuery(this).attr('data-groupid');
				curlevels = [];
				jQuery(this).children("tr.levelrow").each(function() {
					curlevels.push(parseInt(jQuery("td.levelid", this).text(), 10));
				});
				groupsnlevels.push({ group: groupid, levels: curlevels });
			});
			
			data = {	action:		'pmprommpu_update_level_and_group_order',
						neworder:	groupsnlevels
					};
			jQuery.post(ajaxurl, data, function(response) { });
		}
	});
	</script>
	<?php
	$rethtml = ob_get_clean();
	
	return $rethtml;
}
add_filter( 'pmpro_membership_levels_table', 'pmprommpu_pmpro_membership_levels_table', 10, 2);

/*
	Add options to edit level page
*/
//add options
function pmprommpu_add_group_to_level_options() {
	$level = $_REQUEST['edit'];	
	$allgroups = pmprommpu_get_groups();
	$prevgroup = pmprommpu_get_group_for_level($level);
	?>
	<h3 class="topborder"><?php _e('Group', 'mmpu'); ?></h3>
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row" valign="top"><label><?php _e('Group', 'mmpu');?></label></th>
				<td><select name="groupid">
					<?php foreach($allgroups as $curgroup) { ?>
						<option value="<?=$curgroup->id ?>" <?php if($curgroup->id == $prevgroup) { echo "selected"; } ?>><?=$curgroup->name ?></option>
					<?php } ?>
				</select></td>
			</tr>
		</tbody>
	</table>
	
	<?php
}
add_action( 'pmpro_membership_level_after_other_settings', 'pmprommpu_add_group_to_level_options');

//save options
function pmprommpu_save_group_on_level_edit($levelid) {
	if(array_key_exists("groupid", $_REQUEST) && intval($_REQUEST["groupid"])>0) {
		pmprommpu_set_group_for_level($levelid, $_REQUEST["groupid"]);
	}
}
add_action( 'pmpro_save_membership_level', 'pmprommpu_save_group_on_level_edit' );

/*
	Delete group data when a level is delted
*/
function pmprommpu_on_del_level($levelid) {
	global $wpdb;
	$levelid = intval($levelid);
	
	$wpdb->query("DELETE FROM $wpdb->pmpro_membership_levels_groups WHERE `level`=$levelid");
}
add_action( 'pmpro_delete_membership_level', 'pmprommpu_on_del_level' );

// Actual functions are defined in functions.php.
add_action( 'wp_ajax_pmprommpu_add_group', 'pmprommpu_add_group' );
add_action( 'wp_ajax_pmprommpu_edit_group', 'pmprommpu_edit_group' );
add_action( 'wp_ajax_pmprommpu_del_group', 'pmprommpu_del_group' );
add_action( 'wp_ajax_pmprommpu_update_level_and_group_order', 'pmprommpu_update_level_and_group_order' );