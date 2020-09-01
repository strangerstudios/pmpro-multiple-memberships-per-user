<?php
/*
Plugin Name: Paid Memberships Pro - Multiple Memberships per User
Plugin URI: http://www.paidmembershipspro.com/pmpro-multiple-memberships-per-user/
Description: Update PMPro to allow users to checkout for and hold multiple memberships at the same time.
Version: 0.7
Author: Square Lines LLC and Stranger Studios
Author URI: http://www.square-lines.com
Text Domain: pmpro-multiple-memberships-per-user
Domain Path: /languages
*/

/*
 * License:

 Copyright 2016-2019 - Stranger Studios, LLC

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License, version 2, as
 published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/*
	The Story
	* Add a new section to the edit membership level page, labeled "Level Group".
	* Can choose an existing group from a dropdown or add a new group with a text label.
	* Groups are not visible on the front end of the website by default and only used to handle how PMPro should handle members checking out for a level within the same group or across groups.
	* Below the option to set the group, is another option about how to handle checking out for that membership level. "When a Member Checks out for This Level":
	** Remove all other levels (Super Level) -- not currently implemented.
	** Remove all other levels in the same group (Grouped Level)
	** Do not remove any other levels (Standalone Level)
	* Levels should default to the "General" group, with the "Grouped Level" option chosen. This is the behavior in core PMPro.
	* Some of this was coded up in the v2.0 branch. Here is a committ that removed some of the UI code: https://github.com/strangerstudios/paid-memberships-pro/commit/b1017dc28cbe28632ca550008c8d3818c8c2ba21
	* We will need to hook into the changeMembershipLevel code, perhaps via a new hook/filter to stop the old level from being cancelled.
	* The pmpro_hasMembershipLevel() and pmpro_has_membership_access() functions in core PMPro have already been updated to handle cases where users have more than one level. pmpro_hasMembershipLevel() will return true if the user has that level as one of their levels. pmpro_has_membership_access() will return true if any of the user's levels gives them access to the content.
	* Finally (but the real work), we will need to update all of the places in the core PMPro code or other required addons where the assumption is made that users have just one level. This is probably easiest to work out once users have multiple levels (or you can kind of hack it by adding an extra row into the pmpro_memberships_users table).
	** Any time pmpro_getMembershipLevelForUser() is called, it should probably be replaced with pmpro_getMembershipLevelsForUser() (with an s)
	** Any time pmpro_changeMembershipLevel() is called, you will have to make sure that it respects the new settings.
*/

define("PMPROMMPU_DIR", dirname(__FILE__)); // signals our presence to the mother ship, and other add-ons
define("PMPROMMPU_VER", "0.7"); // Version string to signal cache refresh during JS/CSS updates

require_once(PMPROMMPU_DIR . "/includes/upgrades.php");		// to handle upgrades and to do initial setup
require_once(PMPROMMPU_DIR . "/includes/functions.php");	// misc helper functions
require_once(PMPROMMPU_DIR . "/includes/overrides.php");	// to override the default PMPro functionality, and the admin pages
require_once(PMPROMMPU_DIR . "/includes/profile.php");		// updates to the edit user/profile page in the admin
require_once(PMPROMMPU_DIR . "/includes/invoice.php");		// to extend the MemberOrder class for multi-order invoices
require_once(PMPROMMPU_DIR . "/includes/email.php");		// functions to amend/replace the PMPro email functions

if(is_admin()) {
	pmprommpu_setup_and_upgrade();
}

/**
 * pmprommpu_load_plugin_text_domain
 *
 * @since 0.6.5
 */
function pmprommpu_load_plugin_text_domain() {
	load_plugin_textdomain( 'pmpro-multiple-memberships-per-user', false, basename( PMPROMMPU_DIR ) . '/languages' );
}
add_action( 'init', 'pmprommpu_load_plugin_text_domain' );

// On activation, set a wp_option and set up initial group of all current levels if there are no groups.
function pmprommpu_activation() {
	if ( !is_plugin_active( 'paid-memberships-pro/paid-memberships-pro.php' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( "Paid Memberships Pro must be active in order to activate the MMPU add-on.", 'Plugin dependency check', array( 'back_link' => true ) );
	}

	// No groups in the DB? Create one with all levels, to maintain backward-compatibility out of the box.
	$curgroups = pmprommpu_get_groups();
	if(count($curgroups)==0) {
		$newgroupid = pmprommpu_create_group("Main Group", false);

		$alllevels = pmpro_getAllLevels(true, true);
		foreach($alllevels as $levelid => $leveldetail) {
			pmprommpu_set_level_for_group($levelid, $newgroupid);
		}
	}

	update_option( 'pmprommpu_installed', 1, true);
}

function pmprommpu_deactivation() {
	delete_option( 'pmprommpu_installed');
}

register_activation_hook(__FILE__, 'pmprommpu_activation');
register_deactivation_hook(__FILE__, 'pmprommpu_deactivation');

// Include stylesheets.
function pmprommpu_init() {
	if(is_admin()) {
		$csspath = plugins_url("css/jquery-ui.min.css", __FILE__);
		wp_enqueue_style( 'pmprommpu_jquery_ui', $csspath, array(), PMPROMMPU_VER, "screen");
		$csspath = plugins_url("css/jquery-ui.structure.min.css", __FILE__);
		wp_enqueue_style( 'pmprommpu_jquery_ui_structure', $csspath, array(), PMPROMMPU_VER, "screen");
		$csspath = plugins_url("css/jquery-ui.theme.min.css", __FILE__);
		wp_enqueue_style( 'pmprommpu_jquery_ui_theme', $csspath, array(), PMPROMMPU_VER, "screen");

		$csspath = plugins_url("css/admin.css", __FILE__);
		wp_enqueue_style( 'pmprommpu_admin', $csspath, array(), PMPROMMPU_VER, "screen");
	} else {
		$csspath = plugins_url("css/frontend.css", __FILE__);
		wp_enqueue_style( 'pmprommpu_frontend', $csspath, array(), PMPROMMPU_VER, "screen");
	}
}
add_action( 'init', "pmprommpu_init");
