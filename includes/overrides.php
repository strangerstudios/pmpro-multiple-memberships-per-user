<?php

// This file is where we override the default PMPro functionality & pages as needed. (Which is a lot.)

// if a list of level ids is passed to checkout, pull out the first as the main level and save the rest
function pmprommpu_init_checkout_levels() {		
	//update and save pmpro checkout levels
	if(!is_admin() && !empty($_REQUEST['level']) && $_REQUEST['level'] != 'all') {
		global $pmpro_checkout_level_ids, $pmpro_checkout_levels;
		
		//convert spaces back to +
		$_REQUEST['level'] = str_replace(array(' ', '%20'), '+', $_REQUEST['level']);
		
		//get the ids
		$pmpro_checkout_level_ids = array_map('intval', explode("+", preg_replace("[^0-9\+]", "", $_REQUEST['level'])));		

		//setup pmpro_checkout_levels global
		$pmpro_checkout_levels = array();
		foreach($pmpro_checkout_level_ids as $level_id) {
			$pmpro_checkout_levels[] = pmpro_getLevelAtCheckout($level_id);
		}
		
		//update default request vars to only point to one (main) level
		$_REQUEST['level'] = $pmpro_checkout_level_ids[0];
		$_GET['level'] = $_REQUEST['level'];
		$_POST['level'] = $_POST['level'];
	}
	
	//update and save pmpro checkout deleted levels
	if(!is_admin() && !empty($_REQUEST['dellevels'])) {
		global $pmpro_checkout_del_level_ids;
		
		//convert spaces back to +
		$_REQUEST['dellevels'] = str_replace(array(' ', '%20'), '+', $_REQUEST['dellevels']);
		
		//get the ids
		$pmpro_checkout_del_level_ids = array();		
		$pmpro_checkout_del_level_ids = array_map('intval', explode("+", preg_replace("[^0-9\+]", "", $_REQUEST['dellevels'])));	
	}	
}
add_action('init', 'pmprommpu_init_checkout_levels', 100);

// trying to add a level that is already had? Sorry, Charlie.
function pmprommpu_template_redirect_dupe_level_check() {
	global $pmpro_checkout_level_ids;
	
	//on the checkout page?
	if(!empty($pmpro_checkout_level_ids) && !is_admin() && !empty($_REQUEST['level']) && !is_page($pmpro_pages['cancel'])) {
		$oktoproceed = true;
		$currentlevels = pmpro_getMembershipLevelsForUser();
		foreach($currentlevels as $curcurlevel) {
			if(in_array($curcurlevel->ID, $pmpro_checkout_level_ids)) { $oktoproceed = false; }
		}
		if(! $oktoproceed) {
			wp_redirect(pmpro_url("levels"));
			exit;
		}
	}
}

// the user pages - the actual function is in functions.php
add_filter( 'pmpro_pages_custom_template_path', 'pmprommpu_override_user_pages', 10, 5 );

// let's make sure jQuery UI Dialog is present on the admin side.
function pmprommpu_addin_jquery_dialog($pagehook) {
	if(strpos($pagehook, "pmpro-membershiplevels") !== FALSE) { // only add the overhead on the membership levels page.
		wp_enqueue_script('jquery-ui-dialog');
	}
}
add_action( 'admin_enqueue_scripts', 'pmprommpu_addin_jquery_dialog' );

// Filter the text on the checkout page that tells the user what levels they're getting
function pmprommpu_checkout_level_text($intext, $levelids_adding, $levelids_deleting) {	
	$levelarr = pmpro_getAllLevels(true, true);	
	$outstring = '<p>' . _n('You have selected the following level', 'You have selected the following levels', count($levelids_adding), 'pmprommpu') . ':</p>';
	foreach($levelids_adding as $curlevelid) {
		$outstring .= "<p class='levellist'><strong><span class='levelnametext'>".$levelarr[$curlevelid]->name."</span></strong>";
		if(! empty($levelarr[$curlevelid]->description)) {
			$outstring .= "<br /><span class='leveldesctext'>".stripslashes($levelarr[$curlevelid]->description)."</span>";
		}
		$outstring .= "</p>";
	}
	if(count($levelids_deleting)>0) {
		$outstring .= '<p>' . _n('You are removing the following level', 'You are removing the following levels', count($levelids_deleting), 'pmprommpu') . ':</p>';			
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

// Ensure than when a membership level is changed, it doesn't delete the old one or unsubscribe them at the gateway. 
// We'll handle both later in the process.
function pmprommpu_pmpro_deactivate_old_levels($insetting) {
	global $pmpro_pages;
	
	//don't deactivate other levels, unless we're on the cancel page and set to cancel all
	if(!is_page($pmpro_pages['cancel']) && (empty($_REQUEST['level']) || $_REQUEST['level'] != 'all'))
		$insetting = false;
	
	return $insetting;
}
add_filter( 'pmpro_deactivate_old_levels', 'pmprommpu_pmpro_deactivate_old_levels', 10, 1);

function pmprommpu_pmpro_cancel_previous_subscriptions($insetting) {
	global $pmpro_pages;
	
	//don't cancel other subscriptions, unless we're on the cancel page and set to cancel all
	if(!is_page($pmpro_pages['cancel']) && (empty($_REQUEST['level']) || $_REQUEST['level'] != 'all'))
		$insetting = false;
		
	return $insetting;
}
add_filter( 'pmpro_cancel_previous_subscriptions', 'pmprommpu_pmpro_cancel_previous_subscriptions', 10, 1);

// Called after the checkout process, we are going to do three things here:
// First, process any extra levels that need to be charged/subbed for 
// Then, any unsubscriptions that the user opted for (whose level ids are in $_REQUEST['dellevels']) will be dropped.
// Then, any remaining conflicts will be dropped.
function pmprommpu_pmpro_after_checkout($user_id, $checkout_statuses) {
	global $wpdb, $current_user, $gateway, $discount_code, $discount_code_id, $pmpro_msg, $pmpro_msgt, $pmpro_level, $pmpro_checkout_levels, $pmpro_checkout_del_level_ids, $pmpro_checkout_id;
	
	//make sure we only call this once
	remove_action( 'pmpro_after_checkout', 'pmprommpu_pmpro_after_checkout', 99, 2);
		
	//process extra checkouts
	if(!empty($pmpro_checkout_levels)) {		
		global $username, $password, $password2, $bfirstname, $blastname, $baddress1, $baddress2, $bcity, $bstate, $bzipcode, $bcountry, $bphone, $bemail, $bconfirmemail, $CardType, $AccountNumber, $ExpirationMonth, $ExpirationYear;
				
		foreach($pmpro_checkout_levels as $level) {			
			//skip the "main" level we already processed
			if($level->id == $pmpro_level->id)
				continue;
				
			//process payment unless free
			if(!pmpro_isLevelFree($level)) {
				$morder                   = new MemberOrder();
				$morder->membership_id    = $level->id;
				$morder->membership_name  = $level->name;
				$morder->discount_code    = $discount_code;
				$morder->InitialPayment   = $level->initial_payment;
				$morder->PaymentAmount    = $level->billing_amount;
				$morder->ProfileStartDate = date( "Y-m-d", current_time( "timestamp" ) ) . "T0:0:0";
				$morder->BillingPeriod    = $level->cycle_period;
				$morder->BillingFrequency = $level->cycle_number;

				if($level->billing_limit ) {
					$morder->TotalBillingCycles = $level->billing_limit;
				}

				if(pmpro_isLevelTrial($level)) {
					$morder->TrialBillingPeriod    = $level->cycle_period;
					$morder->TrialBillingFrequency = $level->cycle_number;
					$morder->TrialBillingCycles    = $level->trial_limit;
					$morder->TrialAmount           = $level->trial_amount;
				}

				//credit card values
				$morder->cardtype              = $CardType;
				$morder->accountnumber         = $AccountNumber;
				$morder->expirationmonth       = $ExpirationMonth;
				$morder->expirationyear        = $ExpirationYear;
				$morder->ExpirationDate        = $ExpirationMonth . $ExpirationYear;
				$morder->ExpirationDate_YdashM = $ExpirationYear . "-" . $ExpirationMonth;
				$morder->CVV2                  = $CVV;

				//not saving email in order table, but the sites need it
				$morder->Email = $bemail;

				//sometimes we need these split up
				$morder->FirstName = $bfirstname;
				$morder->LastName  = $blastname;
				$morder->Address1  = $baddress1;
				$morder->Address2  = $baddress2;

				//other values
				$morder->billing          = new stdClass();
				$morder->billing->name    = $bfirstname . " " . $blastname;
				$morder->billing->street  = trim( $baddress1 . " " . $baddress2 );
				$morder->billing->city    = $bcity;
				$morder->billing->state   = $bstate;
				$morder->billing->country = $bcountry;
				$morder->billing->zip     = $bzipcode;
				$morder->billing->phone   = $bphone;

				//$gateway = pmpro_getOption("gateway");
				$morder->gateway = $gateway;
				$morder->setGateway();

				//setup level var
				$morder->getMembershipLevel();
				$morder->membership_level = apply_filters( "pmpro_checkout_level", $morder->membership_level );

				//tax
				$morder->subtotal = $morder->InitialPayment;
				$morder->getTax();

				//filter for order, since v1.8
				$morder = apply_filters( "pmpro_checkout_order", $morder );

				$pmpro_processed = $morder->process();
				
				if ( ! empty( $pmpro_processed ) ) {
					$pmpro_msg       = __( "Payment accepted.", "pmpro" );
					$pmpro_msgt      = "pmpro_success";
					$pmpro_confirmed = true;
				} else {
					//Payment failed. We need to backout this order and all previous orders.
					
					//give the user back the levels they had when they started.
					

					//find all orders for this checkout, refund and cancel them
					$checkout_orders = pmpro_getMemberOrdersByCheckoutID($morder->checkout_id);					
					foreach($checkout_orders as $checkout_order) {
						if($checkout_order->status != 'error')
							$checkout_order->cancel();
					}
					
					//set the error message
					$pmpro_msg = __( "ERROR: This checkout included several payments. Some of them were processed successfully and some failed. We have refunded the payments made. You should contact the site owner to resolve this issue.", "pmprommpu" );

					if(!empty($morder->error))
						$pmpro_msg .= " " . __("More information:", "pmprommpu") . " " . $morder->error;					
					$pmpro_msgt = "pmpro_error";

					//don't send an email
					add_filter('pmpro_send_checkout_emails', '__return_false');

					//don't redirect
					add_filter('pmpro_confirmation_url', '__return_false');

					//bail from this function
					return;
				}
			} else {
				//empty order for free levels
				$morder                 = new MemberOrder();
				$morder->InitialPayment = 0;
				$morder->Email          = $bemail;
				$morder->gateway        = "free";

				$morder = apply_filters( "pmpro_checkout_order_free", $morder );
			}

			//change level and save order
			do_action( 'pmpro_checkout_before_change_membership_level', $user_id, $morder );

			//start date is NOW() but filterable below
			$startdate = current_time( "mysql" );			
			$startdate = apply_filters( "pmpro_checkout_start_date", $startdate, $user_id, $level );

			//calculate the end date
			if ( ! empty( $level->expiration_number ) ) {
				$enddate =  date( "Y-m-d", strtotime( "+ " . $level->expiration_number . " " . $level->expiration_period, current_time( "timestamp" ) ) );
			} else {
				$enddate = "NULL";
			}
			
			$enddate = apply_filters( "pmpro_checkout_end_date", $enddate, $user_id, $level, $startdate );

			//check code before adding it to the order
			$code_check = pmpro_checkDiscountCode( $discount_code, $level->id, true );
			if ( $code_check[0] == false ) {
				//error
				$pmpro_msg  = $code_check[1];
				$pmpro_msgt = "pmpro_error";

				//don't use this code
				$use_discount_code = false;
			} else {
				//all okay
				$use_discount_code = false;
			}
			
			//update membership_user table.	
			//(NOTE: we can avoid some DB calls by using the global $discount_code_id, but the core preheaders/checkout.php may have blanked it)	
			if ( ! empty( $discount_code ) && ! empty( $use_discount_code ) ) {
				$discount_code_id = $wpdb->get_var( "SELECT id FROM $wpdb->pmpro_discount_codes WHERE code = '" . esc_sql( $discount_code ) . "' LIMIT 1" );
			} else {
				$discount_code_id = "";
			}

			$custom_level = array(
				'user_id'         => $user_id,
				'membership_id'   => $level->id,
				'code_id'         => $discount_code_id,
				'initial_payment' => $level->initial_payment,
				'billing_amount'  => $level->billing_amount,
				'cycle_number'    => $level->cycle_number,
				'cycle_period'    => $level->cycle_period,
				'billing_limit'   => $level->billing_limit,
				'trial_amount'    => $level->trial_amount,
				'trial_limit'     => $level->trial_limit,
				'startdate'       => $startdate,
				'enddate'         => $enddate
			);

			if ( pmpro_changeMembershipLevel( $custom_level, $user_id, 'changed' ) ) {
				//we're good				

				//add an item to the history table, cancel old subscriptions
				if ( ! empty( $morder ) ) {
					$morder->user_id       = $user_id;
					$morder->membership_id = $level->id;
					$morder->saveOrder();
				}
			}						
		}
	}
	
	pmprommpu_send_checkout_emails($user_id, $pmpro_checkout_id);

	//remove levels to be removed
	if(!empty($pmpro_checkout_del_level_ids)) {		
		foreach($pmpro_checkout_del_level_ids as $idtodel) {
			pmpro_cancelMembershipLevel($idtodel, $user_id, 'cancelled');
		}
	}

	// OK, levels are added, levels are removed. Let's check once more for any conflict, and resolve them - with extreme prejudice.
	$currentlevels = pmpro_getMembershipLevelsForUser($user_id);
	$currentlevelids = array();
	if(is_array($currentlevels)) {
		foreach($currentlevels as $curlevel) {
			$currentlevelids[] = $curlevel->id;
		}
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
			foreach($curgroup as $idtodel) {
				pmpro_cancelMembershipLevel($idtodel, $user_id, 'change');
			}			
		}
	}	
}
add_action( 'pmpro_after_checkout', 'pmprommpu_pmpro_after_checkout', 99, 2);

// Now, on to the admin pages. Let's start with membership levels...

/**
 * Change membership levels admin page to show groups.
 */
function pmprommpu_pmpro_membership_levels_table($intablehtml, $inlevelarr) {
	$groupsnlevels = pmprommpu_get_levels_and_groups_in_order(true);
	$allgroups = pmprommpu_get_groups();
	$alllevels = pmpro_getAllLevels(true, true);
	$gateway = pmpro_getOption("gateway");
	
	ob_start(); 
	
	if($gateway == "paypalexpress" || $gateway == "paypalstandard") { // doing this manually for now; should do it via a setting in the gateway class.
	?>
		<div id="message" class="error"><p><?php echo __("Multiple Memberships Per User (MMPU) does not work with PayPal Express or PayPal Standard. Please disable the MMPU plug-in or change gateways to continue.", "mmpu"); ?></p></div>
	<?php 
		$rethtml = ob_get_clean();
	
		return $rethtml;
	}
	
	?>
	
	<script>
		jQuery( document ).ready(function() {
				jQuery('#add-new-group').insertAfter( "h2 .add-new-h2" );
		});
	</script>
<!-- 
	<a id="add-new-group" class="add-new-h2" href="admin.php?page=pmpro-level-groups&edit=-1"><?php _e('Add New Group', 'pmprommpu'); ?></a>
 -->
	<a id="add-new-group" class="add-new-h2" href="#"><?php _e('Add New Group', 'pmprommpu'); ?></a>
	
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
						<a data-groupid="<?=$curgroup ?>" title="<?php _e('edit','pmpro'); ?>" href="#" class="editgrpbutt button-primary"><?php _e('edit','pmpro'); ?></a>						
<!-- 
						<a data-groupid="<?=$curgroup ?>" title="<?php _e('edit','pmpro'); ?>" href="admin.php?page=pmpro-membershiplevels&edit=<?php echo $level->id?>" class="editgrpbutt button-primary"><?php _e('edit','pmpro'); ?></a>						
 -->
						<?php if(count($itslevels)==0) { ?>
							<a title="<?php _e('delete','pmpro'); ?>" data-groupid="<?=$curgroup ?>" href="javascript: void(0);" class="delgroupbutt button-secondary"><?php _e('delete','pmpro'); ?></a>
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
	Delete group data when a level is deleted
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

function pmprommpu_stop_default_checkout_emails($inflag) {
	return false;
}
add_filter( 'pmpro_send_checkout_emails', 'pmprommpu_stop_default_checkout_emails', 10, 1);

function pmprommpu_show_multiple_levels_in_memlist($inuser) {
	$allmylevels = pmpro_getMembershipLevelsForUser($inuser->ID);
	$memlevels = array();
	foreach($allmylevels as $curlevel) {
		$memlevels[] = $curlevel->name;
	}
	
	$inuser->membership = implode(', ', $memlevels);
	
	return $inuser;
}
add_filter( 'pmpro_members_list_user', 'pmprommpu_show_multiple_levels_in_memlist', 10, 1);

function pmprommpu_set_checkout_id($inorder) {
	global $pmpro_checkout_id;
	
	if(! empty($pmpro_checkout_id)) {
		$inorder->checkout_id = $pmpro_checkout_id;
	}

	return $inorder;
}
add_filter( 'pmpro_checkout_order', 'pmprommpu_set_checkout_id', 10, 1);
add_filter( 'pmpro_checkout_order_free', 'pmprommpu_set_checkout_id', 10, 1);
