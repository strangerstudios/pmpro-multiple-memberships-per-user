<?php

// This file has miscellaneous functions to help things run smoothly.

//set up wpdb for the tables we need
function pmprommpu_setDBTables()
{
	global $wpdb;
	$wpdb->hide_errors();
	$wpdb->pmpro_groups = $wpdb->prefix . 'pmpro_groups';
	$wpdb->pmpro_membership_levels_groups = $wpdb->prefix . 'pmpro_membership_levels_groups';
}
pmprommpu_setDBTables();

function pmprommpu_is_loaded() { // If you can run this, we're loaded.
	return true;
}

function pmprommpu_plugin_dir() {
	return PMPROMMPU_DIR;
}

// Return an array of all level groups, with the key being the level group id.
// Groups have an id, name, displayorder, and flag for allow_multiple_selections
function pmprommpu_get_groups() {
	global $wpdb;
	
	$allgroups = $wpdb->get_results("SELECT * FROM $wpdb->pmpro_groups ORDER BY id");
	$grouparr = array();
	foreach($allgroups as $curgroup) {
		$grouparr[$curgroup->id] = $curgroup;
	}
	
	return $grouparr;
}

// Given a name and a true/false flag about whether it allows multiple selections, create a level group.
function pmprommpu_create_group($inname, $inallowmult = true) {
	global $wpdb;
	
	$allowmult = intval($inallowmult);
	$result = $wpdb->insert($wpdb->pmpro_groups, array('name' => $inname, 'allow_multiple_selections' => $allowmult), array('%s', '%d'));
	
	if($result) { return $wpdb->insert_id; } else { return false; }
}

// Set (or move) a membership level into a level group
function pmprommpu_set_level_for_group($levelid, $groupid) {
	global $wpdb;
	
	$levelid = intval($levelid);
	$groupid = intval($groupid); // just to be safe
	
	$wpdb->query("DELETE FROM $wpdb->pmpro_membership_levels_groups WHERE level=$levelid");
	$wpdb->insert($wpdb->pmpro_membership_levels_groups, array('level' => $levelid, 'group' => $groupid), array('%d', '%d'));
}

// Return an array of the groups and levels in display order - keys are group ID, and values are their levels, in display order
function pmprommpu_get_levels_and_groups_in_order($includehidden = false) {
	global $wpdb;
	
	$retarray = array();
	
	$pmpro_levels = pmpro_getAllLevels($includehidden, true);
	$pmpro_level_order = pmpro_getOption('level_order');

	$order = array();
	if(! empty($pmpro_level_order)) { $order = explode(',', $pmpro_level_order); }

	$grouplist = $wpdb->get_col("SELECT id FROM $wpdb->pmpro_groups ORDER BY displayorder,id ASC");
	if($grouplist) {
		foreach($grouplist as $curgroup) {
			$curgroup = intval($curgroup);
			$levelsingroup = $wpdb->get_col("SELECT level FROM $wpdb->pmpro_membership_levels_groups mlg WHERE mlg.group=$curgroup ORDER BY level ASC");
			if(count($order)>0) {
				$mylevels = array();
				foreach($order as $level_id) {
					if(in_array($level_id, $levelsingroup)) { $mylevels[] = $level_id; }
				}
				$retarray[$curgroup] = $mylevels;
			} else {
				$retarray[$curgroup] = $levelsingroup;
			}

		}
	}
	return $retarray;
}

// Called as a filter by pmpro_pages_custom_template_path to add our path to the search path for user pages.
function pmprommpu_override_user_pages($templates, $page_name, $type, $where, $ext) {

	if(file_exists(PMPROMMPU_DIR . "/pages/{$page_name}.{$ext}")) {
		// We add our path as the second in the array - after core, but before user locations.
		// The array is reversed later, so this means user templates come first, then us, then core.
		array_splice($templates, 1, 0, PMPROMMPU_DIR . "/pages/{$page_name}.{$ext}");
	}
	return $templates;
}

// Given a level ID, this function returns the group ID it belongs to.
function pmprommpu_get_group_for_level($levelid) {
	global $wpdb;
	
	$levelid = intval($levelid); // just to be safe
	
	$groupid = $wpdb->get_var("SELECT mlg.group FROM $wpdb->pmpro_membership_levels_groups mlg WHERE level=$levelid");
	if($groupid) {
		$groupid = intval($groupid);
	} else {
		$groupid = -1;
	}
	return $groupid;
}

// Given a level ID and new group ID, this function sets the group ID for a level. Returns a success flag (true/false).
function pmprommpu_set_group_for_level($levelid, $groupid) {
	global $wpdb;
	
	$levelid = intval($levelid); // just to be safe
	$groupid = intval($groupid); // just to be safe

	$wpdb->query("DELETE FROM $wpdb->pmpro_membership_levels_groups WHERE level=$levelid");
	$success = $wpdb->query("INSERT INTO $wpdb->pmpro_membership_levels_groups (`group`,`level`) VALUES($groupid,$levelid)");
	if($success>0) {
		return true;
	} else {
		return false;
	}
}

// Called by AJAX to add a group from the admin-side Membership Levels and Groups page. Incoming parms are name and mult (can users sign up for multiple levels in this group - 0/1).
function pmprommpu_add_group() {
	global $wpdb;
	
	$displaynum = $wpdb->get_var("SELECT MAX(displayorder) FROM $wpdb->pmpro_groups");
	if(! $displaynum || intval($displaynum)<1) { $displaynum = 1; } else { $displaynum = intval($displaynum); $displaynum++; }
	
	if(array_key_exists("name", $_REQUEST)) {
		$allowmult = 0;
		if(array_key_exists("mult", $_REQUEST) && intval($_REQUEST["mult"])>0) { $allowmult = 1; }
		$wpdb->insert($wpdb->pmpro_groups,
			array(	'name' => $_REQUEST["name"],
					'allow_multiple_selections' => $allowmult,
					'displayorder' => $displaynum),
			array(	'%s',
					'%d',
					'%d')
			);
	}
	
	wp_die();
}

// Called by AJAX to edit a group from the admin-side Membership Levels and Groups page. Incoming parms are group (the ID #), name and mult (can users sign up for multiple levels in this group - 0/1).
function pmprommpu_edit_group() {
	global $wpdb;
	
	if(array_key_exists("name", $_REQUEST) && array_key_exists("group", $_REQUEST) && intval($_REQUEST["group"])>0) {
		$allowmult = 0;
		if(array_key_exists("mult", $_REQUEST) && intval($_REQUEST["mult"])>0) { $allowmult = 1; }
		$grouptoedit = intval($_REQUEST["group"]);
		$wpdb->update($wpdb->pmpro_groups,
			array(	'name' => $_REQUEST["name"],
					'allow_multiple_selections' => $allowmult),
			array(	'id' => $grouptoedit),
			array(	'%s',
					'%d',
					'%d'),
			array(	'%d')
			);
	}
	
	wp_die();
}

// Called by AJAX to delete an empty group from the admin-side Membership Levels and Groups page. Incoming parm is group (group ID #).
function pmprommpu_del_group() {
	global $wpdb;
	
	if(array_key_exists("group", $_REQUEST) && intval($_REQUEST["group"])>0) {
		$groupid = intval($_REQUEST["group"]);
		$wpdb->query("DELETE FROM $wpdb->pmpro_membership_levels_groups WHERE group=$groupid");
		$wpdb->query("DELETE FROM $wpdb->pmpro_groups WHERE id=$groupid");		
	}
	
	wp_die();
}

// Called by AJAX from the admin-facing levels page when the rows are reordered. Incoming parm (neworder) is an ordered array of objects (with two parms, group (scalar ID) and levels (ordered array of scalar level IDs))
function pmprommpu_update_level_and_group_order() {
	global $wpdb;
	
	$grouparr = array();
	$levelarr = array();
	
	if(array_key_exists("neworder", $_REQUEST) && is_array($_REQUEST["neworder"])) {
		foreach($_REQUEST["neworder"] as $curgroup) {
			$grouparr[] = $curgroup["group"];
			foreach($curgroup["levels"] as $curlevel) {
				$levelarr[] = $curlevel;
			}
		}
		$ctr = 1;
		foreach($grouparr as $orderedgroup) {
			$wpdb->query("UPDATE $wpdb->pmpro_groups SET displayorder=$ctr WHERE id=$orderedgroup");
			$ctr++;
		}
		pmpro_setOption('level_order', $levelarr);
	}
	
	wp_die();
}

// This function returns an array of the successfully-purchased levels from the most recent checkout
// for the user specified by user_id. If user_id isn't specified, the current user will be used.
// If there are no successful levels, or no checkout, will return an empty array.
function pmprommpu_get_levels_from_latest_checkout($user_id = NULL, $statuses_to_check = 'status') {
	global $wpdb;
	
	if(empty($user_id))
	{
		global $current_user;
		$user_id = $current_user->ID;
	}

	if(empty($user_id))
	{
		return false;
	}

	//make sure user id is int for security
	$user_id = intval($user_id);

	$retval = array();
	$all_levels = pmpro_getAllLevels(true);

	$checkoutid = $wpdb->get_var("SELECT MAX(checkout_id) FROM $wpdb->pmpro_membership_orders WHERE user_id=$user_id");
	if(empty($checkoutid) || intval($checkoutid)<1) { return $retval; }
	
	$querySql = "SELECT membership_id FROM $wpdb->pmpro_membership_orders WHERE checkout_id=$checkoutid AND gateway='free' OR ";
	if(!empty($statuses_to_check) && is_array($statuses_to_check)) {
		$querySql .= "status IN('" . implode("','", $statuses_to_check) . "') ";
	} elseif(!empty($statuses_to_check)) {
		$querySql .= "status = '" . esc_sql($statuses_to_check) . "' ";
	} else {
		$querySql .= "status = 'success'";
	}
		
	$levelids = $wpdb->get_col($querySql);
	foreach($levelids as $thelevel) {
		if(array_key_exists($thelevel, $all_levels)) {
			$retval[] = $all_levels[$thelevel];
		}
	}
	return $retval;
}

function pmprommpu_join_with_and($inarray) {
	$outstring = "";

	if(!is_array($inarray) || count($inarray)<1) { return $outstring; }
	
	$lastone = array_pop($inarray);
	if(count($inarray)>0) {
		$outstring .= implode(', ', $inarray);
		if(count($inarray)>1) { $outstring .= ', '; }
		$outstring .= "and ";
	}
	$outstring .= "$lastone";
	return $outstring;
}

?>