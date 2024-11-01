<?php

/**
 * WPEC_Unzer_Gateway class
 *
 * @class        WPEC_Unzer_Gateway
 * @since        1.0.0
 * @category    Class
 * @author        PerfectSolution
 */
class WPEC_Unzer_Gateway {
	/**
	 * Process the callbacks returned from Unzer
	 *
	 * @static
	 * @return void
	 */
	public static function callback() {
		global $wpdb;

		// Fetch the callback response body
		$request_body = file_get_contents( "php://input" );

		// Decode the request body
		$json = json_decode( $request_body );

		if ( empty( $json ) ) {
			return;
		}

		// Instantiate payment object
		$payment = new WPEC_Unzer_API_Payment( $request_body );

		// Stop if either transaction id or session id is missing
		if ( ! isset( $_GET['transaction_id'] ) or ! isset( $_GET['sessionid'] ) ) {
			return false;
		}

		// Fetch the session ID
		$sessionid = trim( stripslashes( sanitize_text_field( $_GET['sessionid'] ) ) );

		// Check if this is a callback from a fulfilled transaction request
		$is_callback = ( isset( $_GET['unzer_callback'] ) && $_GET['unzer_callback'] == '1' ) ? true : false;

		// Instantiate logger
		$log = new WPEC_Unzer_Logs();

		if ( $is_callback ) {
			// Check if the callback is valid
			if ( $payment->is_authorized_callback( $request_body ) ) {

				// Get last transaction in operation history
				$transaction = end( $json->operations );

				// Is the transaction accepted?
				if ( $json->accepted ) {
					// Perform action depending on the operation status type
					try {
						switch ( $transaction->type ) {
							case 'authorize' :
								$new_transaction = $json->id;
								// Order is accepted.
								$notes = "Payment approved at Unzer:\ntransaction id: " . $new_transaction;

								$purchase_log = new WPSC_Purchase_Log( $sessionid, 'sessionid' );

								if ( ! $purchase_log->exists() || $purchase_log->is_transaction_completed() ) {
									return;
								}

								$purchase_log->set( 'processed', WPSC_Purchase_Log::ACCEPTED_PAYMENT );
								$purchase_log->set( 'transactid', $new_transaction );
								$purchase_log->set( 'notes', $notes );
								$purchase_log->save();
								break;
						}
					} catch ( Unzer_API_Exception $e ) {
						$e->write_to_logs();
					}
				}

				// The transaction was not accepted.
				// Print debug information to logs
				else {
					// Write debug information
					$log->separator();
					$log->add( sprintf( __( 'Transaction failed for #%s.', 'woo-Unzer' ), $json->order_id ) );
					$log->add( sprintf( __( 'Unzer status code: %s.', 'woo-Unzer' ), $transaction->qp_status_code ) );
					$log->add( sprintf( __( 'Unzer status message: %s.', 'woo-Unzer' ), $transaction->qp_status_msg ) );
					$log->add( sprintf( __( 'Acquirer status code: %s', 'woo-Unzer' ), $transaction->aq_status_code ) );
					$log->add( sprintf( __( 'Acquirer status message: %s', 'woo-Unzer' ), $transaction->aq_status_msg ) );
					$log->separator();
				}
			} else {
				$log->add( sprintf( __( 'Invalid callback body for order #%s.', 'woo-Unzer' ), $json->order_id ) );
			}
		}

		// Check if this is a cancellation callback
		$is_cancellation = ( isset( $_GET['unzer_cancel'] ) && $_GET['unzer_cancel'] == '1' ) ? true : false;

		if ( $is_cancellation ) {
			// Check and process "Keep contents of basket on failure?".
			if ( WPEC_Unzer_Settings::get( 'unzer_keepbasket' ) != '1' ) {
				$log_id              = $wpdb->get_var( "SELECT id FROM " . WPSC_TABLE_PURCHASE_LOGS . " WHERE sessionid = '$sessionid' LIMIT 1" );
				$delete_log_form_sql = "SELECT * FROM " . WPSC_TABLE_CART_CONTENTS . " WHERE purchaseid = '$log_id'";

				$cart_content = $wpdb->get_results( $delete_log_form_sql, ARRAY_A );

				foreach ( (array) $cart_content as $cart_item ) {
					$wpdb->query( "DELETE FROM " . WPSC_TABLE_CART_ITEM_VARIATIONS . " WHERE cart_id = '" . $cart_item['id'] . "'" );
				}

				$wpdb->query( "DELETE FROM " . WPSC_TABLE_CART_CONTENTS . " WHERE purchaseid = '$log_id'" );
				$wpdb->query( "DELETE FROM " . WPSC_TABLE_SUBMITED_FORM_DATA . " WHERE log_id IN ('$log_id')" );
				$wpdb->query( "DELETE FROM " . WPSC_TABLE_PURCHASE_LOGS . " WHERE id = '$log_id' LIMIT 1" );
			}

		}

	}


	/**
	 * Used to perform manual API calls from the single order view in wp-admin
	 *
	 * @public
	 * @return void
	 */
	public static function ajax_manual_transaction_actions() {
		if ( isset( $_REQUEST['unzer_action'], $_REQUEST['unzer_transaction_id'], $_REQUEST['unzer_log_id'] ) ) {
			$param_action         = sanitize_text_field( $_REQUEST['unzer_action'] );
			$param_transaction_id = sanitize_text_field( $_REQUEST['unzer_transaction_id'] );
			$param_order_id       = sanitize_text_field( $_REQUEST['unzer_log_id'] );

			try {
				$payment = new WPEC_Unzer_API_Payment();
				$payment->get( $param_transaction_id );

				// Based on the current transaction state, we check if
				// the requested action is allowed
				if ( $payment->is_action_allowed( $param_action ) ) {
					// Check if the action method is available in the payment class
					if ( method_exists( $payment, $param_action ) ) {
						// Call the action method and parse the transaction id and order object
						call_user_func_array( array( $payment, $param_action ), array( $param_transaction_id, $param_order_id ) );
					} else {
						throw new Unzer_API_Exception( sprintf( "Unsupported action: %s.", $param_action ) );
					}
				} // The action was not allowed. Throw an exception
				else {
					throw new Unzer_API_Exception( sprintf(
						"Action: \"%s\", is not allowed for order #%d, with type state \"%s\"",
						$param_action,
						$param_order_id,
						$payment->get_current_type()
					) );
				}
			} catch ( Unzer_Exception $e ) {
				$e->write_to_logs();
			} catch ( Unzer_API_Exception $e ) {
				$e->write_to_logs();
			}

		}
	}


	/**
	 * Returns the price with no decimals. 10.10 returns as 1010.
	 *
	 * @access public static
	 *
	 * @param float $amount
	 *
	 * @return integer
	 */
	public static function price_multiply( $price ) {
		return number_format( $price * 100, 0, '', '' );
	}
}
