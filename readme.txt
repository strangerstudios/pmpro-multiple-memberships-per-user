=== Paid Memberships Pro: Multiple Memberships per User ===
Contributors: strangerstudios
Tags: pmpro, paid memberships pro, members, memberships, mmpu
Requires at least: 4.8
Tested up to: 5.0.3
Stable tag: .5.1

Updates the core Paid Memberships Pro plugin to allow users to have multiple memberships at the same time.

== Description ==

IMPORTANT: This plugin is considered to be in "beta" status. Use at your own risk. In particular, almost all of the
other PMPro add ons that adjust the checkout process, membership pricing, user subscriptions, or perform actions when
users change membership levels will NOT currently work as you might expect with this plugin active.

Specifically, most PMPro add ons assume that users will only have one membership level at a time and
don't "know what to do" when users have multiple levels or cancel multiple levels at once. We will be updating
our add ons over time to support MMPU and will add notices here and on our website when we do.

== Installation ==

1. Upload the `pmpro-multiple-memberships-per-user` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.

== Changelog ==

= .5.1 =
* BUG FIX: Fixed bug when setting expiration dates for new levels added on the profile page.

= .5 =
* BUG FIX: Fixed fatal errors when running PMPro 2.0+.
* BUG FIX: Fixed fatal errors when PMPro is deactivated.
* BUG FIX/ENHANCEMENT: Added a check for "orphaned" levels with no group. If found, they are inserted into the first available group.
* ENHANCEMENT: Updated membership levels table styles to match PMPro 2.0.
* NOTE: There are known issues with dragging levels from one group to another. We are working on a fix. You can still edit a level's group by editing the level.

= .4.1 =
* BUG FIX: Updated pmprommpu_get_levels_from_latest_checkout() in a couple places to also consider orders with "pending" status. This fixes issues with MMPU and the Pay by Check addon.

= .4 =
* NOTE: Skipped up to version .4 to match version increments from before we added the readme.
* BUG FIX: Fixed some warnings.
* BUG FIX: pmprommpu_addMembershipLevel() now accepts level arrays as well as objects.
* BUG FIX: Removed unnecessary backticks from SQL that would break the query on some MySQL setups.
* BUG FIX: Fixed broken delete button.

= .1.1 =
* BUG/FIX: Fixed warnings when adding a level to a user through the edit user page in the dashboard.

= .1 =
* First version.
