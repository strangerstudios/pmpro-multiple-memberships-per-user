=== Paid Memberships Pro: Multiple Memberships per User ===
Contributors: strangerstudios
Tags: pmpro, paid memberships pro, members, memberships, mmpu
Requires at least: 5.2
Tested up to: 6.3
Stable tag: 0.8.4

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
= 0.8.4 - 2023-06-14 =
* BUG FIX: Added additional check to prevent JavaScript from loading on pages other than the checkout page.
* REFACTOR: Added version to Checkout page template to avoid outdated template warnings in PMPro v2.11+

= 0.8.3 - 2023-01-10 =
* ENHANCEMENT: Moved "Group" setting on "Edit Level" page to the "General Information" section.
* BUG FIX: Fixed issue where the "Group" setting was not being saved when creating a new level.
* BUG FIX: Fixed dragging and dropping levels on the "Levels" settings page.
* BUG FIX: Fixed issue where admin checkout emails would not be sent.

= 0.8.2 - 2022-12-02 =
* BUG FIX/ENHANCEMENT: Fixed cases where the wrong text domain was used, which should improve localization efforts.
* BUG FIX/ENHANCEMENT: Fixed notices on the confirmation page.
* BUG FIX/ENHANCEMENT: Fixed warning on the checkout page when no level was passed in.
* BUG FIX/ENHANCEMENT: Added back functionality for the "Send the user an email about this change" and "Cancel this user's subscription at the gateway" checkboxes when removing their membership level via the WP admin. There are still issues RE what's expected email-wise when changing many levels from the edit user page.
* BUG FIX/ENHANCEMENT: Updated to use the new cache system for pmpro_getAllLevels, which improves performance of some pages.
* BUG FIX: Fixed issue where checkout would fail when requiring billing fields at checkout with Stripe.
* BUG FIX: Fixed issue where levels were not showing up correctly when adding/editing/copying from the Memberships -> Settings -> Levels page in the dashboard.
* BUG FIX: Fixed issues where our template parsing REGEX was breaking JavaScript with some themes.

= 0.8.1 - 2021-09-24 =
* BUG FIX: Now showing the TOS consent history on the edit user page. This was hidden before when MMPU was active. (Thanks @femiyb on GitHub)
* BUG FIX: Fixed issues with using recaptcha while MMPU is active. (Thanks, Mehran Aziz)

= 0.8 - 2021-02-25 =
* FEATURE: Now supporting non-Stripe/Braintree gateways. You cannot checkout for multiple levels at once with these gateways, but you can checkout for one level at a time and have MMPU.
* ENHANCEMENT: Added a pmprommpu_disable_mmpu_levels_page filter. Set the callback to return true to use the default PMPro levels page template instead.
* BUG FIX/ENHANCEMENT: If only checking out for 1 level, the default level cost text and expiration functions are used. This avoids issues with some PMPro Add ons that affect the cost text.
* BUG FIX/ENHANCEMENT: Fixed the wording of the Save button in the edit group modal.
* BUG FIX: Fixed JS code that squeezes the Add Group button into place on the level settings page.
* BUG FIX: Fixed issues when selecting multiple levels from the MMPU levels page.

= 0.7 - 2020-09-01 =
* BUG FIX: Setting jQuery Migrate as a dependency to avoid issues with WP 5.5+.
* BUG FIX/ENHANCEMENT: The Members List table columns now show all levels and level ids.

= 0.6.5 - 2020-05-29 =
* BUG FIX: Fixed issues with cancelling individual levels.
* BUG FIX: Deleting the user levels cache after a user's levels are updated on the edit user page.
* BUG FIX/ENHANCEMENT: Added the "pmpro_after_membership_level_profile_fields" hook. Core PMPro has this and other add ons and code rely on it. (Thanks, Frank Fava)
* BUG FIX/ENHANCEMENT: Localized strings in JavaScript files and updated French translations.

= 0.6.4 - 2020-05-01 =
* BUG FIX: Fixed issue where expiration dates weren't set correctly when using PMPro WooCommerce.
* BUG FIX: Fixed a fatal error when PMPro was not activated.
* BUG FIX: Avoiding warnings when no level param is set on the checkout page.
* ENHANCEMENT: Adding a 0 to the front of the version number to avoid issues with version control tags/etc.

= .6.3 - 2019-11-22 =
* BUG FIX: Fixed issue where dates could save incorrectly with WP 5.3+.

= .6.2 - 2019-10-18 =
* BUG FIX: No filter pmpro_require_billing so payment fields will show up if the first level passed into the checkout page is free. We still need an update in core PMPro check the pmpro_require_billing filter instead of just the first level when deciding to enqueue gateway JavaScript.

= .6.1 - 2019-09-16 =
* BUG FIX: The TOS field will autocheck if it was checked before form submission. Also showing a second message box at the bottom of the form now.

= .6 - 2019-09-13 =
* BUG FIX/ENHANCEMENT: Checkout page updated to use table-free layout and support PMPro v2.1.
* BUG FIX: Now correctly sending the checkout_free_admin email for free checkouts.
* BUG FIX: Avoiding warnings during checkout due to how variables are not cast in PHP 7.1.

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
