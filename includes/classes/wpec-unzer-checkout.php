<?php
/**
 * WPEC_Unzer_Checkout class
 *
 * @class 		WPEC_Unzer_Checkout
 * @since		1.0.0
 * @category	Class
 * @author 		PerfectSolution
 */
class WPEC_Unzer_Checkout
{
    /**
     * Generates and return the callback url
     *
     * @param  int $transaction_id the transaction id
     * @param  int $session_id     the session id
     * @return string   the url
     */
    public static function get_callback_url($transaction_id, $session_id)
    {
        $callback_url = WPEC_Unzer_Settings::get('siteurl');

        $string_end = substr($callback_url, strlen($callback_url) - 1);

        if($string_end != '/')
            $callback_url .= '/';

        $params = array('unzer_callback' => '1', 'transaction_id' => $transaction_id, 'sessionid' => $session_id);
        return add_query_arg($params, $callback_url);
    }


    /**
     * Generates and return the continue url
     *
     * @param  int $transaction_id the transaction id
     * @param  int $session_id     the session id
     * @return string   the url
     */
    public static function get_continue_url($transaction_id, $session_id)
    {
        $continue_url = WPEC_Unzer_Settings::get('transact_url');

        $params = array('unzer_accept' => '1', 'transaction_id' => $transaction_id, 'sessionid' => $session_id);
        return add_query_arg($params, $continue_url);
    }


    /**
     * Generates and return the cancel url
     *
     * @param  int $transaction_id the transaction id
     * @param  int $session_id     the session id
     * @return string   the url
     */
    public static function get_cancel_url($transaction_id, $session_id)
    {
        $cancel_url = WPEC_Unzer_Settings::get('shopping_cart_url');

        $params = array('unzer_cancel' => '1', 'transaction_id' => $transaction_id, 'sessionid' => $session_id);
        return add_query_arg($params, $cancel_url);
    }
}
?>
