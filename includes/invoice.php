<?php

if ( ! class_exists( 'MemberOrder' ) ) {
	return;
}

//	This extends the MemberOrder class in PMPro to add functions for multi-order invoices.
class MemberInvoice extends MemberOrder {
	// Returns an order object that represents one or more orders on an invoice (consolidating amounts, etc)
	// If there isn't a recent invoice, or the last one didn't have any matching statuses, then return false.
	function getLastMemberInvoice($user_id = NULL, $status = 'success') {
		global $current_user, $wpdb;

		if(!$user_id)
			$user_id = $current_user->ID;

		if(!$user_id)
			return false;

		//build query
		$this->sqlQuery = "SELECT MAX(checkout_id) FROM $wpdb->pmpro_membership_orders WHERE user_id = '" . $user_id . "' ";
		if(!empty($status) && is_array($status))
			$this->sqlQuery .= "AND (status IN('" . implode("','", $status) . "') ";
		elseif(!empty($status))
			$this->sqlQuery .= "AND (status = '" . esc_sql($status) . "' ";
		else
			$this->sqlQuery .= "AND (1=1 ";

		// need to add back in free orders, because they don't get a success status.
		$this->sqlQuery .= " OR gateway = 'free')";

		//get id
		$checkoutid = $wpdb->get_var($this->sqlQuery);
		if(empty($checkoutid)) { return false; }
		
		$this->sqlQuery = "SELECT id FROM $wpdb->pmpro_membership_orders WHERE checkout_id=$checkoutid ";
		if(!empty($status) && is_array($status))
			$this->sqlQuery .= "AND (status IN('" . implode("','", $status) . "') ";
		elseif(!empty($status))
			$this->sqlQuery .= "AND (status = '" . esc_sql($status) . "' ";
		else
			$this->sqlQuery .= "AND (1=1 ";

		// need to add back in free orders, because they don't get a success status.
		$this->sqlQuery .= " OR gateway = 'free')";

		$idarray = $wpdb->get_col($this->sqlQuery);
		$orderarray = array();
		$ordercount = 1;
		if(count($idarray)<1) { return false; }
		foreach($idarray as $curid) {
			$temporder = new MemberOrder();
			$temporder->getMemberOrderByID($curid);
			if($temporder) {
				if($ordercount == 1) { $this->id = $temporder->id; } else { $this->id = "Multiple"; }
				if($ordercount == 1) { $this->code = $temporder->code; } else { $this->code = "Multiple"; }
				$this->session_id = $temporder->session_id;
				$this->user_id = $temporder->user_id;
				if($ordercount == 1) { $this->membership_id = $temporder->membership_id; } else { $this->membership_id .= ",".$temporder->membership_id; }
				if($ordercount == 1) { $this->paypal_token = $temporder->paypal_token; } else { $this->paypal_token = "Multiple"; }
				$this->billing = new stdClass();
				$this->billing->name = $temporder->billing->name;
				$this->billing->street = $temporder->billing->street;
				$this->billing->city = $temporder->billing->city;
				$this->billing->state = $temporder->billing->state;
				$this->billing->zip = $temporder->billing->zip;
				$this->billing->country = $temporder->billing->country;
				$this->billing->phone = $temporder->billing->phone;

				//split up some values
				$nameparts = pnp_split_full_name($this->billing->name);

				if(!empty($nameparts['fname']))
					$this->FirstName = $nameparts['fname'];
				else
					$this->FirstName = "";
				if(!empty($nameparts['lname']))
					$this->LastName = $nameparts['lname'];
				else
					$this->LastName = "";

				$this->Address1 = $this->billing->street;

				//get email from user_id
				$this->Email = $wpdb->get_var("SELECT user_email FROM $wpdb->users WHERE ID = '" . $this->user_id . "' LIMIT 1");
				
				if($ordercount == 1) { $this->subtotal = $temporder->subtotal; } else { $this->subtotal = (double)$this->subtotal + (double)$temporder->subtotal; }
				if($ordercount == 1) { $this->tax = $temporder->tax; } else { $this->tax = (double)$this->tax + (double)$temporder->tax; }
				if($ordercount == 1) { $this->couponamount = $temporder->couponamount; } else { $this->couponamount = (double)$this->couponamount + (double)$temporder->couponamount; }
				if($ordercount == 1) { if(strlen($temporder->certificate_id)>0) { $this->certificate_id = $temporder->certificate_id; } else { $this->certificate_id = ""; }
					} elseif(strlen($temporder->certificate_id)>0 && strlen($this->certificate_id)>0) { $this->certificate_id = "Multiple"; }
				if($ordercount == 1) { $this->certificateamount = $temporder->certificateamount; } else { $this->certificateamount = (double)$this->certificateamount + (double)$temporder->certificateamount; }
				if($ordercount == 1) { $this->total = $temporder->total; } else { $this->total = (double)$this->total + (double)$temporder->total; }

				$this->payment_type = $temporder->payment_type;
				$this->cardtype = $temporder->cardtype;
				$this->accountnumber = trim($temporder->accountnumber);
				$this->expirationmonth = $temporder->expirationmonth;
				$this->expirationyear = $temporder->expirationyear;

				if($ordercount == 1) { $this->status = $temporder->status; } elseif($this->status != $temporder->status) { $this->status = "Multiple"; }
				$this->gateway = $temporder->gateway;
				$this->gateway_environment = $temporder->gateway_environment;
				if($ordercount == 1) { $this->payment_transaction_id = $temporder->payment_transaction_id; } else { $this->payment_transaction_id = "Multiple"; }
				if($ordercount == 1) { $this->subscription_transaction_id = $temporder->subscription_transaction_id; } else { $this->subscription_transaction_id = "Multiple"; }
				$this->timestamp = $temporder->timestamp;
				$this->affiliate_id = $temporder->affiliate_id;
				$this->affiliate_subid = $temporder->affiliate_subid;

				$this->notes = $temporder->notes;
				$this->checkout_id = $temporder->checkout_id;

				//date formats sometimes useful
				$this->ExpirationDate = $this->expirationmonth . $this->expirationyear;
				$this->ExpirationDate_YdashM = $this->expirationyear . "-" . $this->expirationmonth;

				//reset the gateway
				if(empty($this->nogateway))
					$this->setGateway();

				$ordercount++;
			}
		}
		$this->membership_levels = pmprommpu_get_levels_from_latest_checkout($user_id, $status, $checkoutid);
		return true;
	}
	
	// This function returns an array of the membership levels for this invoice and adds the levels to the invoice.
	// It works similarly to MemberOrder::getMembershipLevel(), but with an array, not a single level.
// 	function getMembershipLevels($checkout_id = -1) {
// 		global $wpdb;
// 
// 		if($checkout_id<1) { return false; }
// 
// 		$this->membership_levels = $wpdb->get_results("SELECT l.id as level_id, l.id as id, l.name, l.description, l.allow_signups, l.expiration_number, l.expiration_period, mu.*, UNIX_TIMESTAMP(mu.startdate) as startdate, UNIX_TIMESTAMP(mu.enddate) as enddate, l.name, l.description, l.allow_signups FROM $wpdb->pmpro_membership_levels l LEFT JOIN $wpdb->pmpro_memberships_users mu ON l.id = mu.membership_id WHERE mu.status = 'active' AND l.id IN (" . $this->membership_id . ") AND mu.user_id = '" . $this->user_id . "'");
// 
// Leaving this discount code piece commented out because not sure if there's a need to adapt to this circumstance.
// TODO: Confirm with testing.
// 		foreach($this->membership_levels as &$curlevel) {
// 			//okay, do I have a discount code to check? (if there is no membership_level->membership_id value, that means there was no entry in memberships_users)
// 			if(!empty($this->discount_code) && empty($curlevel->membership_id))
// 			{
// 				if(!empty($this->discount_code->code))
// 					$discount_code = $this->discount_code->code;
// 				else
// 					$discount_code = $this->discount_code;
// 
// 				$sqlQuery = "SELECT l.id, cl.*, l.name, l.description, l.allow_signups FROM $wpdb->pmpro_discount_codes_levels cl LEFT JOIN $wpdb->pmpro_membership_levels l ON cl.level_id = l.id LEFT JOIN $wpdb->pmpro_discount_codes dc ON dc.id = cl.code_id WHERE dc.code = '" . $discount_code . "' AND cl.level_id = '" . $this->membership_id . "' LIMIT 1";
// 
// 				$this->membership_level = $wpdb->get_row($sqlQuery);
// 			}
// 		}
// 		return $this->membership_levels;
// 
// 	}
}

?>