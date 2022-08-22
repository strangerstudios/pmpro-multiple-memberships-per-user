<?php
/*
 * License:

 Copyright 2016 - Stranger Studios, LLC

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

global $wpdb, $pmpro_msg, $pmpro_msgt, $current_user;

global $pmpro_levels;

if ( !empty( $pmpro_levels ) ) {
	$pmpro_levels = pmpro_getAllLevels( false, true );
}

$pmpro_groups = pmprommpu_get_groups();

$incoming_levels = pmpro_getMembershipLevelsForUser();

$displayorder = pmprommpu_get_levels_and_groups_in_order();

$pmpro_levels = apply_filters( "pmpro_levels_array", $pmpro_levels );

if ( $pmpro_msg ) {
	?>
	<div class="pmpro_message <?php echo $pmpro_msgt ?>"><?php echo $pmpro_msg ?></div>
	<?php
}
?>
<div id="pmpro_mmpu_levels">
	<div id="pmpro_mmpu_groups">
		<?php
		//$count = 0;				
		foreach ( $displayorder as $group => $grouplevels ) {

			if ( !empty( $grouplevels )) {
			?>
			<div id="pmpro_mmpu_group-<?php echo $pmpro_groups[ $group ]->id; ?>"
			     class="pmpro_mmpu_group <?php if ( intval( $pmpro_groups[ $group ]->allow_multiple_selections ) == 0 ) { ?>selectone<?php } ?>">
				<h2 class="pmpro_mmpu_group-name"><?php echo $pmpro_groups[ $group ]->name ?></h2>
				<p class="pmpro_mmpu_group-type">
					<?php
					if ( intval( $pmpro_groups[ $group ]->allow_multiple_selections ) > 0 ) {
						_e( 'You can choose multiple levels from this group.', 'pmpro-multiple-memberships-per-user' );
					} else {
						_e( 'You can only choose one level from this group.', 'pmpro-multiple-memberships-per-user' );
					}
					?>
				</p>
				<?php

				foreach ( $grouplevels as $level ) {

					?>
					<div id="pmpro_mmpu_level-<?php echo $pmpro_levels[ $level ]->id; ?>"
					     class="pmpro_mmpu_level group<?php echo $group; ?> <?php if ( isset($pmpro_groups[ $group ]->allow_multiple_selections) && intval( $pmpro_groups[ $group ]->allow_multiple_selections ) == 0 ) {
						     echo 'selectone';
					     } ?>">
						<div class="pmpro_level-info">
							<h3 class="pmpro_level-name"><?php echo $pmpro_levels[ $level ]->name; ?></h3>
							<p class="pmpro_level-price">
								<?php
								if ( pmpro_isLevelFree( $pmpro_levels[ $level ] ) ) {
									_e( "Free", 'pmpro-multiple-memberships-per-user' );
								} else {
									echo pmpro_getLevelCost( $pmpro_levels[ $level ], true, true );
								}
								?>
							</p> <!-- end pmpro_level-price -->
							<?php
							$expiration_text = pmpro_getLevelExpiration( $pmpro_levels[ $level ] );
							if ( ! empty( $expiration_text ) ) {
								?>
								<p class="pmpro_level-expiration">
									<?php echo $expiration_text; ?>
								</p> <!-- end pmpro_level-expiration -->
								<?php
							}
							?>
						</div> <!-- end pmpro_level-info -->
						<div class="pmpro_level-action">
							<?php
							if ( $pmpro_groups[ $group ]->allow_multiple_selections > 0 ) {
								?>
								<!-- change message class wrap to success for selected or error if removing -->
								<label
									class="pmpro_level-select <?php if ( pmpro_hasMembershipLevel( $pmpro_levels[ $level ]->id ) ) {
										echo "pmpro_level-select-current";
									} ?>" for="level-<?php echo $pmpro_levels[ $level ]->id ?>"><input type="checkbox"
								                                                                       id="level-<?php echo $pmpro_levels[ $level ]->id ?>"
								                                                                       data-groupid="<?php echo $group ?>" <?php checked( pmpro_hasMembershipLevel( $pmpro_levels[ $level ]->id ), true ); ?>>&nbsp;&nbsp;<?php _e( 'Add', 'pmpro-multiple-memberships-per-user' ); ?>
								</label>
								<?php
							} else {
								?>
								<!-- change message class wrap to success for selected or error if removing -->
								<label
									class="pmpro_level-select <?php if ( pmpro_hasMembershipLevel( $pmpro_levels[ $level ]->id ) ) {
										echo "pmpro_level-select-current";
									} ?>" for="level-<?php echo $pmpro_levels[ $level ]->id ?>"><input type="checkbox"
								                                                                       id="level-<?php echo $pmpro_levels[ $level ]->id ?>"
								                                                                       data-groupid="<?php echo $group; ?>" <?php checked( pmpro_hasMembershipLevel( $pmpro_levels[ $level ]->id ), true ); ?>>&nbsp;&nbsp;<?php _e( 'Select', 'pmpro-multiple-memberships-per-user' ); ?>
								</label>
								<?php
							}
							?>
						</div> <!-- end pmpro_level-action -->
					</div> <!-- end pmpro_mmpu_level-ID -->
					<?php
				}
				?>
			</div> <!-- end pmpro_mmpu_group-ID -->
			<?php
			}
		}
		?>
		<div class="pmpro_mmpu_checkout">
			<div class="pmpro_mmpu_level">
				<div class="pmpro_level-info"></div> <!-- end pmpro_level-info -->
				<div class="pmpro_level-action">
					<input class="pmpro_mmpu_checkout-button" type="button" value="<?php esc_attr_e( 'Checkout', 'pmpro-multiple-memberships-per-user' ) ?>" disabled="disabled">
				</div> <!-- end pmpro_level-action -->
			</div> <!-- end pmpro_mmpu_level -->
		</div> <!-- end pmpro_mmpu_checkout -->

	</div> <!-- end pmpro_mmpu_groups -->
	<div id="pmpro_mmpu_level_selections">
		<aside class="widget">
			<h3 class="widget-title"><?php _e( 'Membership Selections', 'pmpro-multiple-memberships-per-user' ); ?></h3>
			<div id="pmpro_mmpu_level_summary"><?php _e( 'Select levels to complete checkout.', 'pmpro-multiple-memberships-per-user' ); ?></div>
			<p><input class="pmpro_mmpu_checkout-button" type="button" value="<?php esc_attr_e( 'Checkout', 'pmpro-multiple-memberships-per-user' ) ?>" disabled="disabled"></p>
		</aside>
	</div> <!-- end pmpro_mmpu_level_selections -->
</div> <!-- end #pmpro_mmpu_levels -->
<nav id="nav-below" class="navigation" role="navigation">
	<div class="nav-previous alignleft">
		<?php if ( ! empty( $current_user->membership_level->id ) ) { ?>
			<a href="<?php echo pmpro_url( "account" ) ?>"><?php _e( '&larr; Return to Your Account', 'pmpro-multiple-memberships-per-user' ); ?></a>
		<?php } else { ?>
			<a href="<?php echo home_url() ?>"><?php _e( '&larr; Return to Home', 'pmpro-multiple-memberships-per-user' ); ?></a>
		<?php } ?>
	</div>
</nav>
<style>
	input.selected {
		background-color: rgb(0, 122, 204);
		color: #000000;
	}
</style>
