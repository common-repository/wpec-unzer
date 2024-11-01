<?php

use QuickPay\QuickPay;

/**
 * WPEC_Unzer_API class
 *
 * @class        WPEC_Unzer_API
 * @since        1.0.0
 * @category    Class
 * @author        PerfectSolution
 * @docs        http://tech.Unzer.net/api/services/?scope=merchant
 */
class WPEC_Unzer_API {

	/**
	 * @var QuickPay
	 * @access protected
	 */
	protected $ch;


	/**
	 * Contains a resource data object
	 * @access private
	 */
	protected $resource_data;


	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		// Instantiate an empty object ready for population
		$this->resource_data = new stdClass();
	}


	/**
	 * is_authorized_callback function.
	 *
	 * Performs a check on payment callbacks to see if it is legal or spoofed
	 *
	 * @access public
	 *
	 * @param $response_body
	 *
	 * @return boolean
	 */
	public function is_authorized_callback( $response_body ) {
		if ( ! isset( $_SERVER["HTTP_QUICKPAY_CHECKSUM_SHA256"] ) ) {
			return false;
		}

		return hash_hmac( 'sha256', $response_body, WPEC_Unzer_Settings::get( 'unzer_privatekey' ) ) === $_SERVER["HTTP_QUICKPAY_CHECKSUM_SHA256"];
	}


	/**
	 * get function.
	 *
	 * Performs an API GET request
	 *
	 * @access public
	 *
	 * @param $path
	 *
	 * @return object
	 */
	public function get( $path ) {
		return $this->client()->request->get( $this->prepare_url( $path ) );
	}


	/**
	 * post function.
	 *
	 * Performs an API POST request
	 *
	 * @access public
	 *
	 * @param $path
	 * @param array $form
	 *
	 * @return object
	 */
	public function post( $path, $form = array() ) {
		// Start the request and return the response
		return $this->client()->request->post( $this->prepare_url( $path ), $form );
	}


	/**
	 * @access public
	 * @return string
	 */
	public function prepare_url( $params ) {
		return trim( $params, '/' );
	}


	/**
	 * remote_instance function.
	 *
	 * Create a cURL instance if none exists already
	 *
	 * @access public
	 * @return QuickPay object
	 */
	protected function client() {
		if ( $this->ch === null ) {
			$api_key  = WPEC_Unzer_Settings::get( 'unzer_apikey' );
			$this->ch = new QuickPay( ":{$api_key}" );
		}

		return $this->ch;
	}
}
