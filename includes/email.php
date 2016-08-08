<?php

//	Functions to modify core PMPro e-mail behaviors.

function pmprommpu_send_checkout_emails($user_id, $checkout_statuses) {
	global $wpdb, $pmpro_levels;

	// first, let's get the checkout ID for the group.
	$checkout_id = -1;
	foreach($checkout_statuses as $mystatus) { // loop through all, just in case the first one doesn't have an ID
		if($mystatus['order']->checkout_id>0) {
			$checkout_id = $mystatus['order']->checkout_id;
		}
	}
	
	if($checkout_id>0) {
		// then we'll get a combo invoice
		$invoice = new MemberInvoice();
		if(! $invoice->getLastMemberInvoice($user_id)) {
			$invoice = null;
		}
		$levelids = explode(',', $invoice->membership_id);
		$pmpro_levels = pmpro_getAllLevels();
		$levelnames = array();
		$levels = array();
		foreach($levelids as $curid) {
			if(array_key_exists($curid, $pmpro_levels)) {
				$levelnames[] = $pmpro_levels[$curid]->name;
				$levels[] = $pmpro_levels[$curid];
			}
		}
		$auser = get_user_by( 'id', $user_id);
		if(! $auser) {
			return false;
		}
		
		// then we'll put the fields together for the user checkout e-mail
		$pmproemail = new PMProEmail();
		$pmproemail->email = $auser->user_email;
		$pmproemail->subject = sprintf(__("Your membership confirmation for %s", "pmpro"), get_option("blogname"));
		
		$pmproemail->data = array(
								"subject" => $pmproemail->subject, 
								"name" => $auser->display_name, 
								"user_login" => $auser->user_login,
								"sitename" => get_option("blogname"),
								"siteemail" => pmpro_getOption("from_email"),
								"login_link" => wp_login_url(pmpro_url("account")),
								"display_name" => $auser->display_name,
								"user_email" => $auser->user_email,								
							);
		$pmproemail->data['membership_id'] = $invoice->membership_id;
		$pmproemail->data['membership_level_name'] = pmprommpu_join_with_and($levelnames);
		$pmproemail->data['membership_cost'] = pmpro_getLevelsCost($levels);
	
		if(!empty($invoice) && !pmpro_areLevelsFree($levels))
		{									
			if($invoice->gateway == "paypalexpress")
				$pmproemail->template = "checkout_express";
			elseif($invoice->gateway == "check")
			{
				$pmproemail->template = "checkout_check";
				$pmproemail->data["instructions"] = wpautop(pmpro_getOption("instructions"));
			}
// 			elseif(pmpro_isLevelTrial($user->membership_level))
// 				$pmproemail->template = "checkout_trial";
			else
				$pmproemail->template = "checkout_paid";

			$pmproemail->data["invoice_id"] = $invoice->code;
			$pmproemail->data["invoice_total"] = pmpro_formatPrice($invoice->total);
			$pmproemail->data["invoice_date"] = date_i18n(get_option('date_format'), $invoice->timestamp);
			$pmproemail->data["billing_name"] = $invoice->billing->name;
			$pmproemail->data["billing_street"] = $invoice->billing->street;
			$pmproemail->data["billing_city"] = $invoice->billing->city;
			$pmproemail->data["billing_state"] = $invoice->billing->state;
			$pmproemail->data["billing_zip"] = $invoice->billing->zip;
			$pmproemail->data["billing_country"] = $invoice->billing->country;
			$pmproemail->data["billing_phone"] = $invoice->billing->phone;
			$pmproemail->data["cardtype"] = $invoice->cardtype;
			$pmproemail->data["accountnumber"] = hideCardNumber($invoice->accountnumber);
			$pmproemail->data["expirationmonth"] = $invoice->expirationmonth;
			$pmproemail->data["expirationyear"] = $invoice->expirationyear;
			$pmproemail->data["billing_address"] = pmpro_formatAddress($invoice->billing->name,
																 $invoice->billing->street,
																 "", //address 2
																 $invoice->billing->city,
																 $invoice->billing->state,
																 $invoice->billing->zip,
																 $invoice->billing->country,
																 $invoice->billing->phone);
			
			if($invoice->getDiscountCode())
				$pmproemail->data["discount_code"] = "<p>" . __("Discount Code", "pmpro") . ": " . $invoice->discount_code->code . "</p>\n";
			else
				$pmproemail->data["discount_code"] = "";
		}
		elseif(pmpro_areLevelsFree($levels))
		{
			$pmproemail->template = "checkout_free";		
			global $discount_code;
			if(!empty($discount_code))
				$pmproemail->data["discount_code"] = "<p>" . __("Discount Code", "pmpro") . ": " . $discount_code . "</p>\n";		
			else
				$pmproemail->data["discount_code"] = "";		
		}						
		else
		{
			$pmproemail->template = "checkout_freetrial";
			global $discount_code;
			if(!empty($discount_code))
				$pmproemail->data["discount_code"] = "<p>" . __("Discount Code", "pmpro") . ": " . $discount_code . "</p>\n";		
			else
				$pmproemail->data["discount_code"] = "";	
		}
		
		$pmproemail->data['membership_expiration'] = pmpro_getLevelsExpiration($levels);

		// ...and send it...
		$pmproemail->sendEmail();
	
		// and we'll modify the fields for the admin checkout e-mail
		$send = pmpro_getOption("email_admin_checkout");
		if(empty($send)) { return true; }

		$pmproemail->email = get_bloginfo("admin_email");
		$pmproemail->subject = sprintf(__("Member Checkout at %s", "pmpro"), get_option("blogname"));
		$pmproemail->data['subject'] = $pmproemail->subject;
		
		if(!empty($invoice) && !pmpro_areLevelsFree($levels))
		{									
			if($invoice->gateway == "paypalexpress")
				$pmproemail->template = "checkout_express_admin";
			elseif($invoice->gateway == "check")
			{
				$pmproemail->template = "checkout_check_admin";
			}
// 			elseif(pmpro_isLevelTrial($user->membership_level))
// 				$pmproemail->template = "checkout_trial";
			else
				$pmproemail->template = "checkout_paid_admin";
		}

		// ...and send it...
		return $pmproemail->sendEmail();
	}
}

add_action( 'pmpro_after_all_checkouts', 'pmprommpu_send_checkout_emails', 10, 2);

?>