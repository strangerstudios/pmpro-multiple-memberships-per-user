<?php
/*
Plugin Name: Paid Memberships Pro - Multiple Memberships per User
Plugin URI: http://www.paidmembershipspro.com/pmpro-multiple-memberships-per-user/
Description: Update PMPro to allow users to checkout for and hold multiple memberships at the same time.
Version: .1
Author: Stranger Studios
Author URI: http://www.strangerstudios.com
*/

/*
	The Story
	* Add a new section to the edit membership level page, labeled "Level Group".
	* Can choose an existing group from a dropdown or add a new group with a text label.
	* Groups are not visible on the front end of the website by default and only used to handle how PMPro should handle members checking out for a level within the same group or across groups.
	* Below the option to set the group, is another option about how to handle checking out for that membership level. "When a Member Checks out for This Level":
	** Remove all other levels (Super Level)
	** Remove all other levels in the same group (Grouped Level)
	** Do not remove any other levels (Standalone Level)
	* Levels should default to the "General" group, with the "Super Level" option chosen. This is the behavior in core PMPro.
	* Some of this was coded up in the v2.0 branch. Here is a committ that removed some of the UI code: https://github.com/strangerstudios/paid-memberships-pro/commit/b1017dc28cbe28632ca550008c8d3818c8c2ba21
	* We will need to hook into the changeMembershipLevel code, perhaps via a new hook/filter to stop the old level from being cancelled.
	* The pmpro_hasMembershipLevel() and pmpro_has_membership_access() functions in core PMPro have already been updated to handle cases where users have more than one level. pmpro_hasMembershipLevel() will return true if the user has that level as one of their levels. pmpro_has_membership_access() will return true if any of the user's levels gives them access to the content.
	* Finally (but the real work), we will need to update all of the places in the core PMPro code or other required addons where the assumption is made that users have just one level. This is probably easiest to work out once users have multiple levels (or you can kind of hack it by adding an extra row into the pmpro_memberships_users table).
	** Any time pmpro_getMembershipLevelForUser() is called, it should probably be replaced with pmpro_getMembershipLevelsForUser() (with an s)
	** Any time pmpro_changeMembershipLevel() is called, you will have to make sure that it respects the new settings.
*/