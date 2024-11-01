<?php
/**
 * Plugin Name: Integrate Unzer Direct payment gateway with eCommerce
 * Plugin URI: http://wordpress.org/plugins/wpec-unzer/
 * Description: Integrates your Unzer payment gateway into your WP e-Commerce webshop.
 * Version: 1.0.0
 * Author: Unzer
 * Text Domain: wpec-unzer
 * Author URI: https://www.unzer.com
*/

require_once __DIR__ . '/vendor/autoload.php';

class WPEC_Unzer {

	/**
	 * $_instance
	 *
	 * @var mixed
	 * @public
	 * @static
	 */
	public static $_instance = null;


	/**
	 * get_instance
	 *
	 * Returns a new instance of self, if it does not already exist.
	 *
	 * @static
	 * @return object WC_Unzer
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}


	/**
	 * The class construct
	 *
	 * @public
	 * @return void
	 */
	public function __construct() {
		// Load the plugin files
		$this->prepare_files();

		// Prepare hooks and filters
		$this->hooks_and_filters();

		// Setup the gateway in WP E-Commerce
		$this->setup_gateway();
	}

	/**
	 * Includes all the vital plugin files containing functions and classes
	 *
	 * @public
	 * @return  void
	 * @since   1.0.0
	 */
	public function prepare_files() {
		$this->require_file( 'includes/classes/wpec-unzer-settings.php' );
		$this->require_file( 'includes/classes/wpec-unzer-checkout.php' );
		$this->require_file( 'includes/classes/wpec-unzer-logs.php' );
		$this->require_file( 'includes/classes/wpec-unzer-exceptions.php' );
		$this->require_file( 'includes/classes/api/wpec-unzer-api.php' );
		$this->require_file( 'includes/classes/api/wpec-unzer-api-transaction.php' );
		$this->require_file( 'includes/classes/api/wpec-unzer-api-payment.php' );
		$this->require_file( 'includes/classes/wpec-unzer-gateway.php' );
		$this->require_file( 'includes/functions.php' );
	}


	/**
	 * Prepares all the hooks and filters
	 *
	 * @public
	 * @return  void
	 * @since   1.0.0
	 */
	public function hooks_and_filters() {
		add_action( 'init', array( 'WPEC_Unzer_Gateway', 'callback' ) );
		add_action( 'wp_ajax_Unzer_manual_transaction_actions', array( 'WPEC_Unzer_Gateway', 'ajax_manual_transaction_actions' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'static_files_admin' ) );
	}


	/**
	 * Checks for file availability and requires the file if it exists.
	 *
	 * @private
	 *
	 * @param string $file_path > the file to require.
	 *
	 * @return boolean/void > returns FALSE if the requested file doesn't exist. Return void otherwise.
	 */
	private function require_file( $file_path ) {
		$dir_path = plugin_dir_path( __FILE__ );

		if ( ! file_exists( $dir_path . $file_path ) ) {
			return false;
		}

		require_once( $dir_path . $file_path );
	}


	/**
	 * Enqueue static css/js in the admin area
	 *
	 * @public
	 * @return void
	 */
	public function static_files_admin() {
		wp_register_style( 'wpec-unzer-admin', plugins_url( '/assets/css/admin.css', __FILE__ ), false, '1.0.0' );
		wp_enqueue_style( 'wpec-unzer-admin' );

		wp_enqueue_script( 'wpec-unzer-admin', plugins_url( '/assets/js/admin.js', __FILE__ ), array( 'jquery' ) );
		wp_localize_script( 'wpec-unzer-admin', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
	}


	/**
	 * Prepares the gateway
	 *
	 * @public
	 * @return void
	 */
	public function setup_gateway() {
		global $nzshpcrt_gateways;
		$num                                       = time();
		$nzshpcrt_gateways[ $num ]['name']         = 'Unzer';
		$nzshpcrt_gateways[ $num ]['internalname'] = 'Unzer';

		$nzshpcrt_gateways[ $num ]['function']        = 'wpec_unzer_gateway';
		$nzshpcrt_gateways[ $num ]['form']            = 'wpec_unzer_gateway_form';
		$nzshpcrt_gateways[ $num ]['submit_function'] = 'wpec_unzer_gateway_submit';
	}
}

if ( ! function_exists( 'WPEC_Unzer' ) ) {
	function WPEC_Unzer() {
		return WPEC_Unzer::get_instance();
	}

	WPEC_Unzer();
}
?>
