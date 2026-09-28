<?php
/*
 * Template: Checkout
 * Version: 2.0.2

 License:

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
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	global $gateway, $pmpro_review, $skip_account_fields, $pmpro_paypal_token, $wpdb, $current_user, $pmpro_msg, $pmpro_msgt, $pmpro_requirebilling, $pmpro_level, $pmpro_levels, $tospage, $pmpro_show_discount_code, $pmpro_error_fields;
	global $discount_code, $username, $password, $password2, $bfirstname, $blastname, $baddress1, $baddress2, $bcity, $bstate, $bzipcode, $bcountry, $bphone, $bemail, $bconfirmemail, $CardType, $AccountNumber, $ExpirationMonth,$ExpirationYear;
	global $pmpro_checkout_levels, $pmpro_checkout_level_ids, $pmpro_checkout_del_level_ids;	

	/**
	 * Filter to set if PMPro uses email or text as the type for email field inputs.
	 *
	 * @since 1.8.4.5
	 *
	 * @param bool $use_email_type, true to use email type, false to use text type
	 */
	$pmpro_email_field_type = apply_filters('pmpro_email_field_type', true);

	// Set the wrapping class for the checkout div based on the default gateway;
	$default_gateway = pmpro_getOption( 'gateway' );
	if ( empty( $default_gateway ) ) {
		$pmpro_checkout_gateway_class = 'pmpro_checkout_gateway-none';
	} else {
		$pmpro_checkout_gateway_class = 'pmpro_checkout_gateway-' . $default_gateway;
	}

    // Make sure we have a value for this.
    if ( empty( $pmpro_checkout_levels ) && ! empty( $pmpro_level ) ) {
        $pmpro_checkout_levels = array( $pmpro_level );
    }
    if ( empty( $pmpro_checkout_level_ids ) && ! empty( $pmpro_level ) ) {
        $pmpro_checkout_level_ids = array( $pmpro_level->id );
    }
?>
<div id="pmpro_level-mmpu" class="<?php echo esc_attr( $pmpro_checkout_gateway_class ); ?>">
<form id="pmpro_form" class="pmpro_form" action="<?php if(!empty($_REQUEST['review'])) echo esc_url( pmpro_url("checkout", "?level=" . $pmpro_checkout_level_ids) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display flag. ?>" method="post">
	<input type="hidden" id="level" name="level" value="<?php echo esc_attr( implode('+', $pmpro_checkout_level_ids) ); ?>" />
	<input type="hidden" id="levelstodel" name="levelstodel" value="<?php echo ( isset($_REQUEST['dellevels']) ? esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['dellevels'] ) ) ) : null); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only; re-displays the requested levels in a hidden field. ?>" />
	<input type="hidden" id="checkjavascript" name="checkjavascript" value="1" />

	<?php if ( ! empty( $pmpro_msg ) )
		{
	?>
		<div id="pmpro_message" class="pmpro_message <?php echo esc_attr( $pmpro_msgt ); ?>"><?php echo wp_kses_post( $pmpro_msg ); ?></div>
	<?php
		}
		else
		{
	?>
		<div id="pmpro_message" class="pmpro_message" style="display: none;"></div>
	<?php
		}
	?>

	<?php if ( ! empty( $pmpro_review ) ) {  ?>
 	 	<p><?php echo wp_kses_post( __('Almost done. Review the membership information and pricing below then <strong>click the "Complete Payment" button</strong> to finish your order.', 'pmpro-multiple-memberships-per-user') );?></p>
	<?php } ?>

	<div id="pmpro_pricing_fields" class="pmpro_checkout">
		<h3>
			<?php if ( ! empty( $pmpro_checkout_level_ids ) && count( $pmpro_checkout_level_ids ) > 1 ) { ?>
				<span class="pmpro_checkout-h3-name"><?php esc_html_e('Membership Levels', 'pmpro-multiple-memberships-per-user');?></span>
			<?php } else { ?>
				<span class="pmpro_checkout-h3-name"><?php esc_html_e('Membership Level', 'pmpro-multiple-memberships-per-user');?></span>
			<?php } ?>
			<?php if ( ! empty( $pmpro_levels ) && count( $pmpro_levels ) > 1 ) { ?><span class="pmpro_checkout-h3-msg"><a href="<?php echo esc_url( pmpro_url("levels") ); ?>"><?php esc_html_e('change', 'pmpro-multiple-memberships-per-user');?></a></span><?php } ?>
		</h3>
		<div class="pmpro_checkout-fields">
			<?php 
				$defaultstring = "<p>".sprintf(__('You have selected the <strong>%s</strong> membership level.', 'pmpro-multiple-memberships-per-user'), $pmpro_level->name)."</p>";
				if( ! empty( $pmpro_checkout_level_ids ) && count( $pmpro_checkout_level_ids ) < 2 && !empty($pmpro_level->description)) {
					$defaultstring .= apply_filters("the_content", stripslashes($pmpro_level->description));
				}
				echo wp_kses_post( apply_filters("pmprommpu_checkout_level_text", $defaultstring, $pmpro_checkout_level_ids, $pmpro_checkout_del_level_ids) );
			?>
			<div id="pmpro_level_cost">
				<?php if ( ! empty( $discount_code ) && pmpro_checkDiscountCode( $discount_code ) ) { ?>
					<?php printf(wp_kses_post( __('<p class="pmpro_level_discount_applied">The <strong>%s</strong> code has been applied to your order.</p>', 'pmpro-multiple-memberships-per-user') ), esc_html( $discount_code ));?>
				<?php } ?>
				<?php 
					if ( count( $pmpro_checkout_levels ) > 1 ) {
						echo wp_kses_post( wpautop( pmpro_getLevelsCost( $pmpro_checkout_levels ) ) );
						echo wp_kses_post( wpautop( pmpro_getLevelsExpiration( $pmpro_checkout_levels ) ) );
					} else {
						echo wp_kses_post( wpautop( pmpro_getLevelCost( $pmpro_level ) ) );
						echo wp_kses_post( wpautop( pmpro_getLevelExpiration( $pmpro_level ) ) );
					}
				?>
			</div>

			<?php do_action("pmpro_checkout_after_level_cost"); ?>

			<?php if ( ! empty( $pmpro_show_discount_code ) ) { ?>

				<?php if($discount_code && !$pmpro_review) { ?>
					<p id="other_discount_code_p" class="pmpro_small"><a id="other_discount_code_a" href="#discount_code"><?php esc_html_e('Click here to change your discount code', 'pmpro-multiple-memberships-per-user');?></a>.</p>
				<?php } elseif(!$pmpro_review) { ?>
					<p id="other_discount_code_p" class="pmpro_small"><?php esc_html_e('Do you have a discount code?', 'pmpro-multiple-memberships-per-user');?> <a id="other_discount_code_a" href="#discount_code"><?php esc_html_e('Click here to enter your discount code', 'pmpro-multiple-memberships-per-user');?></a>.</p>
				<?php } elseif($pmpro_review && $discount_code) { ?>
					<p><strong><?php esc_html_e('Discount Code', 'pmpro-multiple-memberships-per-user');?>:</strong> <?php echo esc_html( $discount_code ); ?></p>
				<?php } ?>

			<?php } ?>

			<?php if ( ! empty( $pmpro_show_discount_code ) ) { ?>
			<div id="other_discount_code_tr" style="display: none;">
				<label for="other_discount_code"><?php esc_html_e('Discount Code', 'pmpro-multiple-memberships-per-user');?></label>
				<input id="other_discount_code" name="other_discount_code" type="text" class="input <?php echo esc_attr( pmpro_getClassForField("other_discount_code") ); ?>" size="20" value="<?php echo esc_attr($discount_code)?>" />
				<input type="button" name="other_discount_code_button" id="other_discount_code_button" value="<?php esc_attr_e('Apply', 'pmpro-multiple-memberships-per-user');?>" />
			</div>
			<?php } ?>
		</div> <!-- end pmpro_checkout-fields -->
	</div> <!-- end pmpro_pricing_fields -->

	<!-- Moved embedded JS to own pmprommu-checkout.js file -->

	<?php
		do_action('pmpro_checkout_after_pricing_fields');

	if ( is_array( $pmpro_checkout_level_ids) ) {
		$checkout_levels = implode( ',', $pmpro_checkout_level_ids);
	} else {
		$checkout_levels = $pmpro_checkout_level_ids;
	}
	?>

	<?php if( ! $skip_account_fields && ! $pmpro_review ) { ?>
	<div id="pmpro_user_fields" class="pmpro_checkout">
		<hr />
		<h3>
			<span class="pmpro_checkout-h3-name"><?php esc_html_e('Account Information', 'pmpro-multiple-memberships-per-user');?></span>
			<span class="pmpro_checkout-h3-msg"><?php esc_html_e('Already have an account?', 'pmpro-multiple-memberships-per-user');?> <a href="<?php echo esc_url( wp_login_url(pmpro_url("checkout", "?level=" . $checkout_levels)) ); ?>"><?php esc_html_e('Log in here', 'pmpro-multiple-memberships-per-user');?></a></span>
		</h3>
		<div class="pmpro_checkout-fields">
			<div class="pmpro_checkout-field pmpro_checkout-field-username">
				<label for="username"><?php esc_html_e('Username', 'pmpro-multiple-memberships-per-user');?></label>
				<input id="username" name="username" type="text" class="input <?php echo esc_attr( pmpro_getClassForField("username") ); ?>" size="30" value="<?php echo esc_attr($username)?>" />
			</div> <!-- end pmpro_checkout-field-username -->

			<?php
				do_action('pmpro_checkout_after_username');
			?>

			<div class="pmpro_checkout-field pmpro_checkout-field-password">
				<label for="password"><?php esc_html_e('Password', 'pmpro-multiple-memberships-per-user');?></label>
				<input id="password" name="password" type="password" class="input <?php echo esc_attr( pmpro_getClassForField("password") ); ?>" size="30" value="<?php echo esc_attr($password)?>" />
			</div> <!-- end pmpro_checkout-field-password -->
			
			<?php
				$pmpro_checkout_confirm_password = apply_filters("pmpro_checkout_confirm_password", true);
				if ( ! empty( $pmpro_checkout_confirm_password ) )
				{
				?>
				<div class="pmpro_checkout-field pmpro_checkout-field-password2">
					<label for="password2"><?php esc_html_e('Confirm Password', 'pmpro-multiple-memberships-per-user');?></label>
					<input id="password2" name="password2" type="password" class="input <?php echo esc_attr( pmpro_getClassForField("password2") ); ?>" size="30" value="<?php echo esc_attr($password2)?>" />
				</div> <!-- end pmpro_checkout-field-password2 -->
				<?php
				}
				else
				{
				?>
				<input type="hidden" name="password2_copy" value="1" />
				<?php
				}
			?>

			<?php
				do_action('pmpro_checkout_after_password');
			?>

			<div class="pmpro_checkout-field pmpro_checkout-field-bemail">
				<label for="bemail"><?php esc_html_e('E-mail Address', 'pmpro-multiple-memberships-per-user');?></label>
				<input id="bemail" name="bemail" type="<?php echo ($pmpro_email_field_type ? 'email' : 'text'); ?>" class="input <?php echo esc_attr( pmpro_getClassForField("bemail") ); ?>" size="30" value="<?php echo esc_attr($bemail)?>" />
			</div> <!-- end pmpro_checkout-field-bemail -->

			<?php
				$pmpro_checkout_confirm_email = apply_filters("pmpro_checkout_confirm_email", true);
				if($pmpro_checkout_confirm_email)
				{
				?>
				<div class="pmpro_checkout-field pmpro_checkout-field-bconfirmemail">
					<label for="bconfirmemail"><?php esc_html_e('Confirm E-mail Address', 'pmpro-multiple-memberships-per-user');?></label>
					<input id="bconfirmemail" name="bconfirmemail" type="<?php echo ($pmpro_email_field_type ? 'email' : 'text'); ?>" class="input <?php echo esc_attr( pmpro_getClassForField("bconfirmemail") ); ?>" size="30" value="<?php echo esc_attr($bconfirmemail)?>" />
				</div> <!-- end pmpro_checkout-field-bconfirmemail -->
				<?php
				}
				else
				{
				?>
				<input type="hidden" name="bconfirmemail_copy" value="1" />
				<?php
				}
			?>

			<?php
				do_action('pmpro_checkout_after_email');
			?>

			<div class="pmpro_hidden">
				<label for="fullname"><?php esc_html_e('Full Name', 'pmpro-multiple-memberships-per-user');?></label>
				<input id="fullname" name="fullname" type="text" class="input <?php echo esc_attr( pmpro_getClassForField("fullname") ); ?>" size="30" value="" /> <strong><?php esc_html_e('LEAVE THIS BLANK', 'pmpro-multiple-memberships-per-user');?></strong>
			</div> <!-- end pmpro_hidden -->

			

			<?php
				do_action('pmpro_checkout_after_captcha');
			?>
		</div>  <!-- end pmpro_checkout-fields -->
	</div> <!-- end pmpro_user_fields -->
	<?php } elseif($current_user->ID && !$pmpro_review) { ?>
		<div id="pmpro_account_loggedin" class="pmpro_message pmpro_alert">
			<?php printf(wp_kses_post( __('You are logged in as <strong>%s</strong>. If you would like to use a different account for this membership, <a href="%s">log out now</a>.', 'pmpro-multiple-memberships-per-user') ), esc_html( $current_user->user_login ), esc_url( wp_logout_url( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ) ) ); ?>
		</div> <!-- end pmpro_account_loggedin -->
	<?php } ?>

	<?php
		do_action('pmpro_checkout_after_user_fields');
	?>

	<?php
		do_action('pmpro_checkout_boxes');
	?>

	<?php if(pmpro_getGateway() == "paypal" && empty($pmpro_review)) { ?>
		<div id="pmpro_payment_method" class="pmpro_checkout" <?php if(!$pmpro_requirebilling) { ?>style="display: none;"<?php } ?>>
			<hr />
			<h3>
				<span class="pmpro_checkout-h3-name"><?php esc_html_e('Choose your Payment Method', 'pmpro-multiple-memberships-per-user');?></span>
			</h3>
			<div class="pmpro_checkout-fields">
				<span class="gateway_paypal">
					<input type="radio" name="gateway" value="paypal" <?php if(!$gateway || $gateway == "paypal") { ?>checked="checked"<?php } ?> />
					<a href="javascript:void(0);" class="pmpro_radio"><?php esc_html_e('Check Out with a Credit Card Here', 'pmpro-multiple-memberships-per-user');?></a>
				</span>
				<span class="gateway_paypalexpress">
					<input type="radio" name="gateway" value="paypalexpress" <?php if($gateway == "paypalexpress") { ?>checked="checked"<?php } ?> />
					<a href="javascript:void(0);" class="pmpro_radio"><?php esc_html_e('Check Out with PayPal', 'pmpro-multiple-memberships-per-user');?></a>
				</span>
			</div> <!-- end pmpro_checkout-fields -->
		</div> <!-- end pmpro_payment_method -->
	<?php } ?>

	<?php
		$pmpro_include_billing_address_fields = apply_filters('pmpro_include_billing_address_fields', true);
		if($pmpro_include_billing_address_fields)
		{
	?>
	<div id="pmpro_billing_address_fields" class="pmpro_checkout" <?php if(!$pmpro_requirebilling || apply_filters("pmpro_hide_billing_address_fields", false) ){ ?>style="display: none;"<?php } ?>>
		<hr />
		<h3>
			<span class="pmpro_checkout-h3-name"><?php esc_html_e('Billing Address', 'pmpro-multiple-memberships-per-user');?></span>
		</h3>
		<div class="pmpro_checkout-fields">
			<div class="pmpro_checkout-field pmpro_checkout-field-bfirstname">
				<label for="bfirstname"><?php esc_html_e('First Name', 'pmpro-multiple-memberships-per-user');?></label>
				<input id="bfirstname" name="bfirstname" type="text" class="input <?php echo esc_attr( pmpro_getClassForField("bfirstname") ); ?>" size="30" value="<?php echo esc_attr($bfirstname)?>" />
			</div> <!-- end pmpro_checkout-field-bfirstname -->
			<div class="pmpro_checkout-field pmpro_checkout-field-blastname">
				<label for="blastname"><?php esc_html_e('Last Name', 'pmpro-multiple-memberships-per-user');?></label>
				<input id="blastname" name="blastname" type="text" class="input <?php echo esc_attr( pmpro_getClassForField("blastname") ); ?>" size="30" value="<?php echo esc_attr($blastname)?>" />
			</div> <!-- end pmpro_checkout-field-blastname -->
			<div class="pmpro_checkout-field pmpro_checkout-field-baddress1">
				<label for="baddress1"><?php esc_html_e('Address 1', 'pmpro-multiple-memberships-per-user');?></label>
				<input id="baddress1" name="baddress1" type="text" class="input <?php echo esc_attr( pmpro_getClassForField("baddress1") ); ?>" size="30" value="<?php echo esc_attr($baddress1)?>" />
			</div> <!-- end pmpro_checkout-field-baddress1 -->
			<div class="pmpro_checkout-field pmpro_checkout-field-baddress2">
				<label for="baddress2"><?php esc_html_e('Address 2', 'pmpro-multiple-memberships-per-user');?></label>
				<input id="baddress2" name="baddress2" type="text" class="input <?php echo esc_attr( pmpro_getClassForField("baddress2") ); ?>" size="30" value="<?php echo esc_attr($baddress2)?>" />
			</div> <!-- end pmpro_checkout-field-baddress2 -->
			<?php
				$longform_address = apply_filters("pmpro_longform_address", true);
				if($longform_address)
				{
			?>
				<div class="pmpro_checkout-field pmpro_checkout-field-bcity">
					<label for="bcity"><?php esc_html_e('City', 'pmpro-multiple-memberships-per-user');?></label>
					<input id="bcity" name="bcity" type="text" class="input <?php echo esc_attr( pmpro_getClassForField("bcity") ); ?>" size="30" value="<?php echo esc_attr($bcity)?>" />
				</div> <!-- end pmpro_checkout-field-bcity -->
				<div class="pmpro_checkout-field pmpro_checkout-field-bstate">
					<label for="bstate"><?php esc_html_e('State', 'pmpro-multiple-memberships-per-user');?></label>
					<input id="bstate" name="bstate" type="text" class="input <?php echo esc_attr( pmpro_getClassForField("bstate") ); ?>" size="30" value="<?php echo esc_attr($bstate)?>" />
				</div> <!-- end pmpro_checkout-field-bstate -->
				<div class="pmpro_checkout-field pmpro_checkout-field-bzipcode">
					<label for="bzipcode"><?php esc_html_e('Postal Code', 'pmpro-multiple-memberships-per-user');?></label>
					<input id="bzipcode" name="bzipcode" type="text" class="input <?php echo esc_attr( pmpro_getClassForField("bzipcode") ); ?>" size="30" value="<?php echo esc_attr($bzipcode)?>" />
				</div> <!-- end pmpro_checkout-field-bzipcode -->
			<?php
				}
				else
				{
				?>
				<div class="pmpro_checkout-field pmpro_checkout-field-bcity_state_zip">
					<label for="bcity_state_zip"><?php esc_html_e('City, State Zip', 'pmpro-multiple-memberships-per-user');?></label>
					<input id="bcity" name="bcity" type="text" class="input <?php echo esc_attr( pmpro_getClassForField("bcity") ); ?>" size="14" value="<?php echo esc_attr($bcity)?>" />,
					<?php
						$state_dropdowns = apply_filters("pmpro_state_dropdowns", false);
						if($state_dropdowns === true || $state_dropdowns == "names")
						{
							global $pmpro_states;
						?>
						<select name="bstate" class=" <?php echo esc_attr( pmpro_getClassForField("bstate") ); ?>">
							<option value="">--</option>
							<?php
								foreach($pmpro_states as $ab => $st)
								{
							?>
								<option value="<?php echo esc_attr($ab);?>" <?php if($ab == $bstate) { ?>selected="selected"<?php } ?>><?php echo esc_html( $st );?></option>
							<?php } ?>
						</select>
						<?php
						}
						elseif($state_dropdowns == "abbreviations")
						{
							global $pmpro_states_abbreviations;
						?>
							<select name="bstate" class=" <?php echo esc_attr( pmpro_getClassForField("bstate") ); ?>">
								<option value="">--</option>
								<?php
									foreach($pmpro_states_abbreviations as $ab)
									{
								?>
									<option value="<?php echo esc_attr($ab);?>" <?php if($ab == $bstate) { ?>selected="selected"<?php } ?>><?php echo esc_html( $ab );?></option>
								<?php } ?>
							</select>
						<?php
						}
						else
						{
						?>
						<input id="bstate" name="bstate" type="text" class="input <?php echo esc_attr( pmpro_getClassForField("bstate") ); ?>" size="2" value="<?php echo esc_attr($bstate)?>" />
						<?php
						}
					?>
					<input id="bzipcode" name="bzipcode" type="text" class="input <?php echo esc_attr( pmpro_getClassForField("bzipcode") ); ?>" size="5" value="<?php echo esc_attr($bzipcode)?>" />
				</div> <!-- end pmpro_checkout-field-bcity_state_zip -->
				<?php
				}
			?>

			<?php
				$show_country = apply_filters("pmpro_international_addresses", true);
				if($show_country)
				{
			?>
			<div class="pmpro_checkout-field pmpro_checkout-field-bcountry">
				<label for="bcountry"><?php esc_html_e('Country', 'pmpro-multiple-memberships-per-user');?></label>
				<select name="bcountry" id="bcountry" class=" <?php echo esc_attr( pmpro_getClassForField("bcountry") ); ?>">
					<?php
						global $pmpro_countries, $pmpro_default_country;
						if(!$bcountry)
							$bcountry = $pmpro_default_country;
						foreach($pmpro_countries as $abbr => $country)
						{
						?>
						<option value="<?php echo esc_attr( $abbr ); ?>" <?php if($abbr == $bcountry) { ?>selected="selected"<?php } ?>><?php echo esc_html( $country ); ?></option>
						<?php
						}
					?>
				</select>
			</div> <!-- end pmpro_checkout-field-bcountry -->
			<?php
				}
				else
				{
				?>
					<input type="hidden" name="bcountry" id="bcountry" value="US" />
				<?php
				}
			?>
			<div class="pmpro_checkout-field pmpro_checkout-field-bphone">
				<label for="bphone"><?php esc_html_e('Phone', 'pmpro-multiple-memberships-per-user');?></label>
				<input id="bphone" name="bphone" type="text" class="input <?php echo esc_attr( pmpro_getClassForField("bphone") ); ?>" size="30" value="<?php echo esc_attr(formatPhone($bphone))?>" />
			</div> <!-- end pmpro_checkout-field-bphone -->
			<?php if($skip_account_fields) { ?>
			<?php
				if($current_user->ID)
				{
					if(!$bemail && $current_user->user_email)
						$bemail = $current_user->user_email;
					if(!$bconfirmemail && $current_user->user_email)
						$bconfirmemail = $current_user->user_email;
				}
			?>
			<div class="pmpro_checkout-field pmpro_checkout-field-bemail">
				<label for="bemail"><?php esc_html_e('E-mail Address', 'pmpro-multiple-memberships-per-user');?></label>
				<input id="bemail" name="bemail" type="<?php echo ($pmpro_email_field_type ? 'email' : 'text'); ?>" class="input <?php echo esc_attr( pmpro_getClassForField("bemail") ); ?>" size="30" value="<?php echo esc_attr($bemail)?>" />
			</div> <!-- end pmpro_checkout-field-bemail -->
			<?php
				$pmpro_checkout_confirm_email = apply_filters("pmpro_checkout_confirm_email", true);
				if($pmpro_checkout_confirm_email)
				{
				?>
				<div class="pmpro_checkout-field pmpro_checkout-field-bconfirmemail">
					<label for="bconfirmemail"><?php esc_html_e('Confirm E-mail', 'pmpro-multiple-memberships-per-user');?></label>
					<input id="bconfirmemail" name="bconfirmemail" type="<?php echo ($pmpro_email_field_type ? 'email' : 'text'); ?>" class="input <?php echo esc_attr( pmpro_getClassForField("bconfirmemail") ); ?>" size="30" value="<?php echo esc_attr($bconfirmemail)?>" />
				</div> <!-- end pmpro_checkout-field-bconfirmemail -->
				<?php
					}
					else
					{
				?>
				<input type="hidden" name="bconfirmemail_copy" value="1" />
				<?php
					}
				?>
			<?php } ?>
		</div> <!-- end pmpro_checkout-fields -->
	</div> <!--end pmpro_billing_address_fields -->
	<?php } ?>

	<?php do_action("pmpro_checkout_after_billing_fields"); ?>

	<?php
		$pmpro_accepted_credit_cards = pmpro_getOption("accepted_credit_cards");
		$pmpro_accepted_credit_cards = explode(",", $pmpro_accepted_credit_cards);
		$pmpro_accepted_credit_cards_string = pmpro_implodeToEnglish($pmpro_accepted_credit_cards);
	?>

	<?php
		$pmpro_include_payment_information_fields = apply_filters("pmpro_include_payment_information_fields", true);
		if ( ! empty( $pmpro_include_payment_information_fields ) )
		{
		?>
		<div id="pmpro_payment_information_fields" class="pmpro_checkout" <?php if(!$pmpro_requirebilling || apply_filters("pmpro_hide_payment_information_fields", false) ) { ?>style="display: none;"<?php } ?>>
			<h3>
				<span class="pmpro_checkout-h3-name"><?php esc_html_e('Payment Information', 'pmpro-multiple-memberships-per-user');?></span>
				<span class="pmpro_checkout-h3-msg"><?php printf(esc_html__('We Accept %s', 'pmpro-multiple-memberships-per-user'), esc_html( $pmpro_accepted_credit_cards_string ));?></span>
			</h3>
			<?php $sslseal = pmpro_getOption("sslseal"); ?>
			<?php if(!empty($sslseal)) { ?>
				<div class="pmpro_checkout-fields-display-seal">
			<?php } ?>
			<div class="pmpro_checkout-fields">
			<?php
				$pmpro_include_cardtype_field = apply_filters('pmpro_include_cardtype_field', false);
				if ( ! empty( $pmpro_include_cardtype_field ) ) {
				?>
				<div class="pmpro_checkout-field pmpro_payment-card-type">
					<label for="CardType"><?php esc_html_e('Card Type', 'pmpro-multiple-memberships-per-user');?></label>
					<select id="CardType" name="CardType" class=" <?php echo esc_attr( pmpro_getClassForField("CardType") ); ?>">
						<?php foreach($pmpro_accepted_credit_cards as $cc) { ?>
							<option value="<?php echo esc_attr( $cc ); ?>" <?php if($CardType == $cc) { ?>selected="selected"<?php } ?>><?php echo esc_html( $cc ); ?></option>
						<?php } ?>
					</select>
				</div> <!-- end pmpro_payment-card-type -->
				<?php
				}
				else
				{
				?>
					<input type="hidden" id="CardType" name="CardType" value="<?php echo esc_attr($CardType);?>" />
					<!-- Moved embedded JS to own pmprommu-checkout.js file -->
					<?php
					}
				?>
				<div class="pmpro_checkout-field pmpro_payment-account-number">
					<label for="AccountNumber"><?php esc_html_e('Card Number', 'pmpro-multiple-memberships-per-user');?></label>
					<input id="AccountNumber" name="AccountNumber" class="input <?php echo esc_attr( pmpro_getClassForField("AccountNumber") ); ?>" type="text" size="25" value="<?php echo esc_attr($AccountNumber)?>" data-encrypted-name="number" autocomplete="off" />
				</div> <!-- end pmpro_payment-account-number -->
				<div class="pmpro_checkout-field pmpro_payment-expiration">
					<label for="ExpirationMonth"><?php esc_html_e('Expiration Date', 'pmpro-multiple-memberships-per-user');?></label>
					<select id="ExpirationMonth" name="ExpirationMonth" class=" <?php echo esc_attr( pmpro_getClassForField("ExpirationMonth") ); ?>">
						<option value="01" <?php if($ExpirationMonth == "01") { ?>selected="selected"<?php } ?>>01</option>
						<option value="02" <?php if($ExpirationMonth == "02") { ?>selected="selected"<?php } ?>>02</option>
						<option value="03" <?php if($ExpirationMonth == "03") { ?>selected="selected"<?php } ?>>03</option>
						<option value="04" <?php if($ExpirationMonth == "04") { ?>selected="selected"<?php } ?>>04</option>
						<option value="05" <?php if($ExpirationMonth == "05") { ?>selected="selected"<?php } ?>>05</option>
						<option value="06" <?php if($ExpirationMonth == "06") { ?>selected="selected"<?php } ?>>06</option>
						<option value="07" <?php if($ExpirationMonth == "07") { ?>selected="selected"<?php } ?>>07</option>
						<option value="08" <?php if($ExpirationMonth == "08") { ?>selected="selected"<?php } ?>>08</option>
						<option value="09" <?php if($ExpirationMonth == "09") { ?>selected="selected"<?php } ?>>09</option>
						<option value="10" <?php if($ExpirationMonth == "10") { ?>selected="selected"<?php } ?>>10</option>
						<option value="11" <?php if($ExpirationMonth == "11") { ?>selected="selected"<?php } ?>>11</option>
						<option value="12" <?php if($ExpirationMonth == "12") { ?>selected="selected"<?php } ?>>12</option>
					</select>/<select id="ExpirationYear" name="ExpirationYear" class=" <?php echo esc_attr( pmpro_getClassForField("ExpirationYear") ); ?>">
						<?php
							for($i = date("Y"); $i < date("Y") + 10; $i++)
							{
						?>
							<option value="<?php echo esc_attr( $i ); ?>" <?php if($ExpirationYear == $i) { ?>selected="selected"<?php } ?>><?php echo esc_html( $i ); ?></option>
						<?php
							}
						?>
					</select>
				</div> <!-- end pmpro_payment-expiration -->
				<?php
					$pmpro_show_cvv = apply_filters("pmpro_show_cvv", true);
					if ( ! empty( $pmpro_show_cvv ) ) { ?>
				<div class="pmpro_checkout-field pmpro_payment-cvv">
					<label for="CVV"><?php esc_html_e('CVV', 'pmpro-multiple-memberships-per-user');?></label>
					<input class="input" id="CVV" name="CVV" type="text" size="4" value="<?php if(!empty($_REQUEST['CVV'])) { echo esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['CVV'] ) ) ); } // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only; re-displays the submitted CVV. ?>" class=" <?php echo esc_attr( pmpro_getClassForField("CVV") ); ?>" />  <small>(<a href="javascript:void(0);" onclick="javascript:window.open('<?php echo esc_url( pmpro_https_filter(PMPRO_URL) ); ?>/pages/popup-cvv.html','cvv','toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=600, height=475');"><?php esc_html_e("what's this?", 'pmpro-multiple-memberships-per-user');?></a>)</small>
				</div> <!-- end pmpro_payment-cvv -->
				<?php } ?>

				<?php if ( ! empty( $pmpro_show_discount_code ) ) { ?>
				<div class="pmpro_checkout-field pmpro_payment-discount-code">
					<label for="discount_code"><?php esc_html_e('Discount Code', 'pmpro-multiple-memberships-per-user');?></label>
					<input class="input <?php echo esc_attr( pmpro_getClassForField("discount_code") ); ?>" id="discount_code" name="discount_code" type="text" size="20" value="<?php echo esc_attr($discount_code)?>" />
					<input type="button" id="discount_code_button" name="discount_code_button" value="<?php esc_attr_e('Apply', 'pmpro-multiple-memberships-per-user');?>" />
					<p id="discount_code_message" class="pmpro_message" style="display: none;"></p>
				</div> <!-- end pmpro_payment-discount-code -->
			<?php } ?>
			</div> <!-- end pmpro_checkout-fields -->
			<?php if(!empty($sslseal)) { ?>
				<div class="pmpro_checkout-fields-rightcol pmpro_sslseal"><?php echo stripslashes($sslseal); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Admin-entered SSL seal markup, which usually includes a provider script. ?></div>
			</div> <!-- end pmpro_checkout-fields-display-seal -->
			<?php } ?>
		</div> <!-- end pmpro_payment_information_fields -->
	<?php } ?>

	<!-- Moved embedded JS to own pmprommu-checkout.js file -->

	<?php do_action('pmpro_checkout_after_payment_information_fields'); ?>

	<?php
		if ( ! empty( $tospage ) &&  empty( $pmpro_review ) )
		{
		?>
		<div id="pmpro_tos_fields" class="pmpro_checkout">
			<hr />
			<h3>
				<span class="pmpro_checkout-h3-name"><?php echo esc_html( $tospage->post_title ); ?></span>
			</h3>
			<div class="pmpro_checkout-fields">
				<div id="pmpro_license" class="pmpro_checkout-field">
<?php echo wpautop(do_shortcode($tospage->post_content)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Admin-authored TOS page content, rendered like post content. ?>
				</div> <!-- end pmpro_license -->
				<?php
					if ( isset( $_REQUEST['tos'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only; re-checks the TOS box on redisplay.
						$tos = intval( $_REQUEST['tos'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only; re-checks the TOS box on redisplay.
					} else {
						$tos = "";
					}
				?>
				<input type="checkbox" name="tos" value="1" id="tos" <?php checked( 1, $tos ); ?> /> <label class="pmpro_label-inline pmpro_clickable" for="tos"><?php printf(esc_html__('I agree to the %s', 'pmpro-multiple-memberships-per-user'), esc_html( $tospage->post_title ));?></label>
			</div> <!-- end pmpro_checkout-fields -->
		</div> <!-- end pmpro_tos_fields -->
		<?php
		}
	?>

	<?php do_action("pmpro_checkout_after_tos_fields"); ?>

	<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_checkout-field pmpro_captcha', 'pmpro_captcha' ) ); ?>">
	<?php
		global $recaptcha, $recaptcha_publickey;
		if ( $recaptcha == 2 || ( $recaptcha == 1 && pmpro_isLevelFree( $pmpro_level ) ) ) {
			echo pmpro_recaptcha_get_html($recaptcha_publickey, NULL, true); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- PMPro core prints its own escaped reCAPTCHA markup.
		}
	?>
	</div> <!-- end pmpro_captcha -->

	<?php do_action("pmpro_checkout_before_submit_button"); ?>

	<div class="pmpro_submit">
		<hr />
		<?php if ( $pmpro_msg ) { ?>
			<div id="pmpro_message_bottom" class="pmpro_message <?php echo esc_attr( $pmpro_msgt ); ?>"><?php echo wp_kses_post( $pmpro_msg ); ?></div>
		<?php } else { ?>
			<div id="pmpro_message_bottom" class="pmpro_message" style="display: none;"></div>
		<?php } ?>
		
		<?php if( ! empty( $pmpro_review ) ) { ?>

			<span id="pmpro_submit_span">
				<input type="hidden" name="confirm" value="1" />
				<input type="hidden" name="token" value="<?php echo esc_attr($pmpro_paypal_token)?>" />
				<input type="hidden" name="gateway" value="<?php echo esc_attr($gateway); ?>" />
				<input id="pmpro_btn-submit" type="submit" class="pmpro_btn pmpro_btn-submit-checkout" value="<?php esc_attr_e('Complete Payment', 'pmpro-multiple-memberships-per-user');?> &raquo;" />
			</span>

		<?php } else { ?>

			<?php
				$pmpro_checkout_default_submit_button = apply_filters('pmpro_checkout_default_submit_button', true);
				if ( ! empty( $pmpro_checkout_default_submit_button ) )
				{
				?>
				<span id="pmpro_submit_span">
					<input type="hidden" name="submit-checkout" value="1" />
					<input id="pmpro_btn-submit" type="submit" class="pmpro_btn pmpro_btn-submit-checkout" value="<?php if($pmpro_requirebilling) { esc_attr_e('Submit and Check Out', 'pmpro-multiple-memberships-per-user'); } else { esc_attr_e('Submit and Confirm', 'pmpro-multiple-memberships-per-user');}?> &raquo;" />
				</span>
				<?php
				}
			?>

		<?php } ?>

		<span id="pmpro_processing_message" style="visibility: hidden;">
			<?php
				$processing_message = apply_filters("pmpro_processing_message", __("Processing...", 'pmpro-multiple-memberships-per-user'));
				echo wp_kses_post( $processing_message );
			?>
		</span>
	</div> <!-- end pmpro_submit -->

</form>

<?php do_action('pmpro_checkout_after_form'); ?>

</div> <!-- end pmpro_level-ID -->

