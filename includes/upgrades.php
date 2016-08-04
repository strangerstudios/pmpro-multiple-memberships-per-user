<?php

//	These functions are run on startup if user is an admin. They check for upgrades -
//	and if it's a new install, everything is an upgrade!

function pmprommpu_setup_and_upgrade()
{
	$installed_version = get_option("pmprommpu_version");

	//if we can't find the DB tables, reset version to 0
	global $wpdb;
	$wpdb->hide_errors();
	$wpdb->pmpro_groups = $wpdb->prefix . 'pmpro_groups';
	$table_exists = $wpdb->query("SHOW TABLES LIKE '" . $wpdb->pmpro_groups . "'");
	if(!$table_exists)
		$installed_version = 0;

	//default options
	if(!$installed_version || $installed_version<1) {
		$installed_version = pmprommpu_setup_v1();
	}

}

function pmprommpu_db_delta() {
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	global $wpdb;
	$wpdb->hide_errors();
	$wpdb->pmpro_groups = $wpdb->prefix . 'pmpro_groups';
	$wpdb->pmpro_membership_levels_groups = $wpdb->prefix . 'pmpro_membership_levels_groups';
	
	
	$sqlQuery = "
		CREATE TABLE `" . $wpdb->pmpro_groups . "` (
		  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `name` varchar(255) NOT NULL,
		  `allow_multiple_selections` tinyint(4) NOT NULL DEFAULT '1',
		  `displayorder` int(11),
		  PRIMARY KEY (`id`),
		  KEY `name` (`name`)
		);
	";
	dbDelta($sqlQuery);

	$sqlQuery = "
		CREATE TABLE `" . $wpdb->pmpro_membership_levels_groups . "` (
		  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `level` int(11) unsigned NOT NULL DEFAULT '0',
		  `group` int(11) unsigned NOT NULL DEFAULT '0',
		  PRIMARY KEY (`id`),
		  KEY `level` (`level`),
		  KEY `group` (`group`)
		);
	";
	dbDelta($sqlQuery);
}

function pmprommpu_setup_v1() {
	// Set any additional default options here.

	// Set up the database.
	pmprommpu_db_delta();

	// Save the current version number, and return it to stop later updates (or not, as the case may be).
	update_option("pmprommpu_version", "1");
	return 1;
}