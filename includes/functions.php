<?php

/**
 * Generates the payment form on checkout.
 * The form is automatically submitted when the form is done being generated.
 *
 * @param $seperator
 * @param $sessionid
 */
function wpec_unzer_gateway( $seperator, $sessionid ) {
	global $wpdb, $wpsc_cart;

	// Get ordernumber
	$ordernumber = 'WPEC' . $wpdb->get_var( "SELECT id FROM " . WPSC_TABLE_PURCHASE_LOGS . " WHERE sessionid = '$sessionid' LIMIT 1;" );
	if ( strlen( $ordernumber ) > 20 ) {
		$ordernumber = time();
	}

	// Prepare amount
	$amount = WPEC_Unzer_Gateway::price_multiply( $wpsc_cart->total_price );

	// Prepare transaction ID for internal use
	$transaction_id = uniqid( md5( rand( 1, 666 ) ), true );
	$wpdb->query( "UPDATE " . WPSC_TABLE_PURCHASE_LOGS . " SET processed = '1', transactid = '" . $transaction_id . "', date = '" . time() . "' WHERE sessionid = " . $sessionid . " LIMIT 1" );

	// Prepare params
	$params = array(
		'agreement_id'    => WPEC_Unzer_Settings::get( 'unzer_agreement_id' ),
		'merchant_id'     => WPEC_Unzer_Settings::get( 'unzer_merchant_id' ),
		'subscription'    => 0,
		'description'     => '',
		'language'        => WPEC_Unzer_Settings::get( 'unzer_language' ),
		'order_id'        => $ordernumber,
		'amount'          => $amount,
		'currency'        => WPEC_Unzer_Settings::get( 'unzer_currency' ),
		'continueurl'     => WPEC_Unzer_Checkout::get_continue_url( $transaction_id, $sessionid ),
		'cancelurl'       => WPEC_Unzer_Checkout::get_cancel_url( $transaction_id, $sessionid ),
		'callbackurl'     => WPEC_Unzer_Checkout::get_callback_url( $transaction_id, $sessionid ),
		'autocapture'     => WPEC_Unzer_Settings::get( 'unzer_autocapture', '0' ),
		'autofee'         => WPEC_Unzer_Settings::get( 'unzer_autofee', '0' ),
		'payment_methods' => WPEC_Unzer_Settings::get( 'unzer_cardtypelock', 'creditcard' ),
		'branding_id'     => WPEC_Unzer_Settings::get( 'unzer_branding_id' ),
		'version'         => 'v10'
	);

	ksort( $params );

	$checksum = hash_hmac( "sha256", implode( " ", $params ), WPEC_Unzer_Settings::get( 'unzer_agreement_apikey' ) );

	// Generate the form output.
	$output = "<form id=\"unzer_form\" name=\"unzer_form\" action=\"" . WPEC_Unzer_Settings::$_gateway_form_url . "\" method=\"post\">\n";
	foreach ( $params as $name => $value ) {
		$output .= WPEC_Unzer_Settings::field( $name, $value );
	}
	$output .= WPEC_Unzer_Settings::field( "checksum", $checksum, "hidden" );
	$output .= WPEC_Unzer_Settings::field( "", "Pay", "submit" );
	$output .= "</form>";


	echo $output;
	echo "<script language=\"javascript\" type=\"text/javascript\">document.getElementById('unzer_form').submit();</script>";
	echo "Please wait..";
	exit();
}

/**
 * Prints the gateway settings field in wp-admin
 *
 * @return string > the form fields
 */
function wpec_unzer_gateway_form() {
	// Generate output.
	$output = '<tr><td colspan="2"><strong>Payment Window - Core Configuration</strong></td></tr>';

	// Merchant ID.
	$output .= '<tr><td><label for="unzer_merchant_id">Merchant ID</label></td>';
	$output .= '<td><input name="unzer_merchant_id" id="unzer_merchant_id" type="text" value="' . WPEC_Unzer_Settings::get( 'unzer_merchant_id' ) . '"/><br/>';
	$output .= WPEC_Unzer_Settings::field_hint( 'Your Payment Window agreement merchant id. Found in the "Integration" tab inside the Unzer manager.' );
	$output .= '</td></tr>';

	// Agreement ID.
	$output .= '<tr><td><label for="unzer_agreement_id">Agreement ID</label></td>';
	$output .= '<td><input name="unzer_agreement_id" id="unzer_agreement_id" type="text" value="' . WPEC_Unzer_Settings::get( 'unzer_agreement_id' ) . '"/><br/>';
	$output .= WPEC_Unzer_Settings::field_hint( 'Your Payment Window agreement id. Found in the "Integration" tab inside the Unzer manager.' );
	$output .= '</td></tr>';

	// Agreement API key.
	$output .= '<tr><td><label for="unzer_agreement_apikey">API Key</label></td>';
	$output .= '<td><input name="unzer_agreement_apikey" id="unzer_agreement_apikey" type="text" value="' . WPEC_Unzer_Settings::get( 'unzer_agreement_apikey' ) . '"/><br/>';
	$output .= WPEC_Unzer_Settings::field_hint( 'Your Payment Window agreement API key. Found in the "Integration" tab inside the Unzer manager.' );
	$output .= '</td></tr>';

	// Agreement private key.
	$output .= '<tr><td><label for="unzer_privatekey">Private Key</label></td>';
	$output .= '<td><input name="unzer_privatekey" id="unzer_privatekey" type="text" value="' . WPEC_Unzer_Settings::get( 'unzer_privatekey' ) . '"/><br/>';
	$output .= WPEC_Unzer_Settings::field_hint( 'Your Payment Window agreement private key. Found in the "Integration" tab inside the Unzer manager.' );
	$output .= '</td></tr>';

	// API user key
	$output .= '<tr><td><label for="unzer_apikey">API User key</label></td>';
	$output .= '<td><input name="unzer_apikey" id="unzer_apikey" type="text" value="' . WPEC_Unzer_Settings::get( 'unzer_apikey' ) . '"/><br/>';
	$output .= WPEC_Unzer_Settings::field_hint( 'Your API User\'s key. Create a separate API user in the "Users" tab inside the Unzer manager.' );
	$output .= '</td></tr>';

	// Automatic capture on/off.
	$output .= '<tr><td><label for="unzer_autocapture">Automatic capture</label></td><td>';
	$output .= '<input name="unzer_autocapture" id="unzer_autocapture" value="0"' . ( WPEC_Unzer_Settings::get( 'unzer_autocapture' ) == '0' ? ' checked="checked"' : '' ) . ' type="radio"/> Off<br/>';
	$output .= '<input name="unzer_autocapture" id="unzer_autocapture_1" value="1"' . ( WPEC_Unzer_Settings::get( 'unzer_autocapture' ) == '1' ? ' checked="checked"' : '' ) . ' type="radio"/> On<br/>';
	$output .= WPEC_Unzer_Settings::field_hint( 'Automatic capture means you will automatically deduct the amount from the customer.' );
	$output .= '</td></tr>';

	// Automatic fee on/off.
	$output .= '<tr><td><label for="unzer_autofee">Automatic fees</label></td><td>';
	$output .= '<input name="unzer_autofee" id="unzer_autofee" value="0"' . ( WPEC_Unzer_Settings::get( 'unzer_autofee', '0' ) == '0' ? ' checked="checked"' : '' ) . ' type="radio"/> Off<br/>';
	$output .= '<input name="unzer_autofee" id="unzer_autofee_1" value="1"' . ( WPEC_Unzer_Settings::get( 'unzer_autofee' ) == '1' ? ' checked="checked"' : '' ) . ' type="radio"/> On<br/>';
	$output .= WPEC_Unzer_Settings::field_hint( 'When enabled, the payment window will automatically add transaction fees to the transaction.' );
	$output .= '</td></tr>';


	$output .= '<tr><td colspan="2"><strong>Payment Window - Visual Configuration</strong></td></tr>';

	// Branding ID.
	$output .= '<tr><td><label for="unzer_branding_id">Branding ID</label></td>';
	$output .= '<td><input name="unzer_branding_id" id="unzer_branding_id" type="text" value="' . WPEC_Unzer_Settings::get( 'unzer_branding_id' ) . '"/><br/>';
	$output .= WPEC_Unzer_Settings::field_hint( 'Leave empty if you have no custom branding options' );
	$output .= '</td></tr>';

	// Language.
	$languages       = array();
	$languages['da'] = 'Danish';
	$languages['de'] = 'German';
	$languages['en'] = 'English';
	$languages['fr'] = 'French';
	$languages['it'] = 'Italian';
	$languages['no'] = 'Norwegian';
	$languages['nl'] = 'Dutch';
	$languages['pl'] = 'Polish';
	$languages['se'] = 'Swedish';

	$output .= '<tr><td><label for="unzer_language">Language</label></td><td>';
	$output .= "<select name='unzer_language'>";

	$language = $currency = WPEC_Unzer_Settings::get( 'unzer_language' );
	foreach ( $languages as $key => $value ) {
		$output .= '<option value="' . $key . '"';

		if ( $language == $key ) {
			$output .= ' selected="selected"';
		}

		$output .= '>' . $value . '</option>';
	}

	$output .= '</select><br/>';
	$output .= WPEC_Unzer_Settings::field_hint( 'Choose which language the payment window will use.' );
	$output .= '</td></tr>';

	// Currency.
	$currency   = $currency = WPEC_Unzer_Settings::get( 'unzer_currency' );
	$currencies = array( 'DKK', 'EUR', 'GBP', 'NOK', 'SEK', 'USD' );
	$output     .= '<tr><td><label for="unzer_currency">Currency</label></td><td>';
	$output     .= '<select name="unzer_currency" id="unzer_currency">';

	foreach ( $currencies as $curr ) {
		$output .= '<option value="' . $curr . '"';

		if ( $currency === $curr ) {
			$output .= ' selected="selected"';
		}

		$output .= '>' . $curr . '</option>';
	}

	$output .= '</select><br/>';
	$output .= WPEC_Unzer_Settings::field_hint( 'Choose your currency. Please make sure to use the same currency as in your WP E-Commerce currency settings.' );
	$output .= '</td></tr>';

	$output .= '<tr><td><label for="unzer_cardtypelock">Lock payment options</label></td><td>';
	$output .= '<input name="unzer_cardtypelock" id="unzer_cardtypelock" type="text" value="' . WPEC_Unzer_Settings::get( 'unzer_cardtypelock' ) . '"/><br/>';
	$output .= WPEC_Unzer_Settings::field_hint( 'Read more here: <a href="https://www.unzerdirect.com/documentation//appendixes/payment-methods/" target="_new">Lock payments to given card types</a>.' );
	$output .= '</td></tr>';

	// Other settings.
	$output .= '<tr><td colspan="2" style="padding-top:6px;"><strong>Other settings</strong></td></tr>';

	$output .= '<tr><td><label for="unzer_keepbasket">Keep contents of basket on failure?</label></td><td>';
	$output .= '<input name="unzer_keepbasket" id="unzer_keepbasket" value="1"' . ( WPEC_Unzer_Settings::get( 'unzer_keepbasket' ) == '1' ? ' checked="checked"' : '' ) . ' type="checkbox"/> Yes<br/>';
	$output .= WPEC_Unzer_Settings::field_hint( 'If a transaction fails or is cancelled and the user returns to your webshop, do you wish the contents of the users shopping basket to be kept? Otherwise it will be emptied.' );
	$output .= '</td></tr>';

	return $output;
}


/**
 * Saves the gateway settings
 *
 * @return boolean
 */
function wpec_unzer_gateway_submit() {
	WPEC_Unzer_Settings::update_on_post( 'unzer_merchant_id' );
	WPEC_Unzer_Settings::update_on_post( 'unzer_agreement_id' );
	WPEC_Unzer_Settings::update_on_post( 'unzer_agreement_apikey' );
	WPEC_Unzer_Settings::update_on_post( 'unzer_apikey' );
	WPEC_Unzer_Settings::update_on_post( 'unzer_privatekey' );
	WPEC_Unzer_Settings::update_on_post( 'unzer_autocapture' );
	WPEC_Unzer_Settings::update_on_post( 'unzer_autofee' );
	WPEC_Unzer_Settings::update_on_post( 'unzer_branding_id' );
	WPEC_Unzer_Settings::update_on_post( 'unzer_language' );
	WPEC_Unzer_Settings::update_on_post( 'unzer_currency' );
	WPEC_Unzer_Settings::update_on_post( 'unzer_cardtypelock' );
	WPEC_Unzer_Settings::update_on_post( 'unzer_keepbasket' );

	return true;
}

/**
 * Adds admin pages
 */
function wpec_unzer_pages() {
	add_management_page( __( 'Unzer logs', 'wpec-unzer' ), __( 'Unzer logs', 'wpec-unzer' ), 'manage_options', 'wpec-unzer-logs', 'wpec_unzer_page_logs' );
}

add_action( 'admin_menu', 'wpec_unzer_pages' );


function wpec_unzer_page_logs() {
	$logs = new WPEC_Unzer_Logs();
	?>
    <div class="wrap">
        <h2>Unzer logs</h2>
        <p><?php _e( 'This section contains information about any problems that might have occured on some transactions - it can become very useful for debug purposes.</p>', 'wpec-unzer' ); ?></p>
        <textarea style="width:100%;min-height:400px;"><?php echo $logs->output(); ?></textarea>

        <form action="<?php echo add_query_arg( null, null ); ?>" method="post">
            <input type="hidden" name="wpec-unzer-action" value="logs--clear"/>
            <input class="button action" type="submit" name="wpec-unzer-logs--clear" value="<?php _e( 'Clear the log', 'wpec-unzer' ); ?>"/>
        </form>
    </div>
	<?php
}

/**
 * Handles requests for wpec-unzer-action post requests
 */
function wpec_unzer_actions() {
	if ( isset( $_POST['wpec-unzer-action'] ) ) {
		switch ( $_POST['wpec-unzer-action'] ) {
			case 'logs--clear':
				$logs = new WPEC_Unzer_Logs();
				$logs->clear();
				break;
		}

		wp_safe_redirect( wp_get_referer() );
	}
}

add_action( 'init', 'wpec_unzer_actions' );

/**
 * Prints the transaction metabox inside the order view.
 *
 * @param int $log_id > The log id used to retrieve transaction ID and gateway type
 */
function wpec_unzer_page_items__metabox( $log_id ) {
	global $wpdb;

	$transaction_id = $wpdb->get_var( "SELECT transactid FROM " . WPSC_TABLE_PURCHASE_LOGS . " WHERE id = '$log_id' LIMIT 1" );
	$gateway        = $wpdb->get_var( "SELECT gateway FROM " . WPSC_TABLE_PURCHASE_LOGS . " WHERE id = '$log_id' LIMIT 1" );

	if ( $gateway == 'Unzer' && isset( $transaction_id ) && is_numeric( $transaction_id ) ) :

		?>
        <div class="metabox-holder">
            <div id="Unzer-payment-actions" class="postbox" data-transaction-id="<?php echo $transaction_id; ?>" data-log-id="<?php echo $log_id; ?>">
                <h3 class='hndle'><?php _e( 'Unzer - Transaction Actions', 'wpec-unzer' ); ?></h3>
                <div class='inside'>
					<?php
					try {
						// Get the payment information via the Unzer API
						$payment = new WPEC_Unzer_API_Payment();
						$payment->get( $transaction_id );

						var_dump($payment); exit;

						// Get the current transaction status
						$status = $payment->get_current_type();
						?>

						<?php if ( $payment->is_test() ) : ?>
                            <p style="color:red;font-weight:bold;">This is a test transaction</p>
						<?php endif; ?>

                        <p class="wpec-unzer-<?php echo $status; ?>"><strong> <?php echo __( 'Current payment state', 'wpec-unzer' ) . ": " . $status; ?></strong></p>

						<?php if ( $payment->is_action_allowed( 'standard_actions' ) ) : ?>

                            <h4><strong><?php _e( 'Standard actions', 'woo-Unzer' ); ?></strong></h4>
                            <ul class="order_action">

								<?php if ( $payment->is_action_allowed( 'capture' ) ) : ?>
                                    <li class="left">
                                        <a class="button" data-action="capture"
                                           data-confirm="<?php _e( 'You are about to CAPTURE this payment', 'wpec-unzer' ); ?>"><?php _e( 'Capture', 'wpec-unzer' ); ?></a>
                                    </li>
								<?php endif; ?>

								<?php if ( $payment->is_action_allowed( 'refund' ) ) : ?>
                                    <li class="left">
                                        <a class="button" data-action="refund"
                                           data-confirm="<?php _e( 'You are about to REFUND this payment', 'wpec-unzer' ); ?>"><?php _e( 'Refund', 'wpec-unzer' ); ?></a>
                                    </li>
								<?php endif; ?>

								<?php if ( $payment->is_action_allowed( 'cancel' ) ) : ?>
                                    <li class="right"><a class="button" data-action="cancel"
                                                         data-confirm="<?php _e( 'You are about to CANCEL this payment', 'wpec-unzer' ); ?>"><?php _e( 'Cancel', 'wpec-unzer' ); ?></a>
                                    </li>
								<?php endif; ?>

                            </ul>

                            <br/>
						<?php endif; ?>
						<?php
					} catch ( Unzer_API_Exception $e ) {
						$e->write_to_logs();
						$e->write_standard_warning();
					}
					?>
                </div>
            </div>
        </div>

	<?php endif; ?>
	<?php
}

add_action( 'wpsc_purchlogitem_metabox_end', 'wpec_unzer_page_items__metabox' );
?>
