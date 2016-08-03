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

/**
 * Change membership levels admin page to show groups.
 */
function pmprommpu_pmpro_membership_levels_table($original_html, $reordered_levels) {
	ob_start();
	?>
	<script>
		jQuery( document ).ready(function() {
				jQuery('#add-new-group').insertAfter( "h2 .add-new-h2" );
		});
	</script>
	<a id="add-new-group" class="add-new-h2" href="#">Add New Group</a>
	<style>
		tbody.membership-level-groups th {background: #FAFAFA; border-right: 1px solid #CCC; border-top: 5px solid #AAA; }
		tbody.membership-level-groups tr:nth-child(even) td {background: #FAFAFA; }
		.membership-level-groups tr:first-child td {border-top: 5px solid #AAA; }
	</style>
	<table class="widefat membership-levels">
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
		<!-- 
			Repeat tbody here for each group present.
		-->
		<tbody id="membership-level-group-1" class="membership-level-groups">
		<?php
			$count = 0;
			?>
			<tr class="<?php if($count++ % 2 == 1) { ?>alternate<?php } ?> <?php if(!$level->allow_signups) { ?>pmpro_gray<?php } ?> <?php if(!pmpro_checkLevelForStripeCompatibility($level) || !pmpro_checkLevelForBraintreeCompatibility($level) || !pmpro_checkLevelForPayflowCompatibility($level) || !pmpro_checkLevelForTwoCheckoutCompatibility($level)) { ?>pmpro_error<?php } ?>">
				<th rowspan="<?php echo count($reordered_levels); ?>" scope="rowgroup" valign="top">
					<h2>Default Group</h2>
					<p><em>Users can only choose one level from this group.</em></p>
					<p><a title="<?php _e('edit','pmpro'); ?>" href="admin.php?page=pmpro-membershiplevels&edit=<?php echo $level->id?>" class="button-primary"><?php _e('edit','pmpro'); ?></a>&nbsp;<a title="<?php _e('delete','pmpro'); ?>" href="javascript: askfirst('<?php echo str_replace("'", "\'", sprintf(__("Are you sure you want to delete membership level %s? All subscriptions will be cancelled.", "pmpro"), $level->name));?>','admin.php?page=pmpro-membershiplevels&action=delete_membership_level&deleteid=<?php echo $level->id?>'); void(0);" class="button-secondary"><?php _e('delete','pmpro'); ?></a></p>
				</th>
			<?php
				foreach($reordered_levels as $level)
				{
			?>
				<td scope="row"><?php echo $level->id?></td>
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
			?>
		</tbody>
	</table>

	<?php

	$table_html = ob_get_clean();

	return $table_html;
}
add_filter('pmpro_membership_levels_table', 'pmprommpu_pmpro_membership_levels_table', 10, 2);

/**
 * Add + new group button to membership levels page
 */

/**
 * Change text of drag/drop message.
 */
