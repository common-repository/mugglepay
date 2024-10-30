<?php

if (! defined('ABSPATH')) {
    exit;
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Automattic\WooCommerce\StoreApi\Payments\PaymentContext;
use Automattic\WooCommerce\StoreApi\Payments\PaymentResult;

final class MPWP_WC_Gateway_Blocks_Support extends AbstractPaymentMethodType {
	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'mpwp';


	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_mpwp_settings', array() );

		add_action( 'woocommerce_rest_checkout_process_payment_with_context', array( $this, 'mpwp_failed_payment_notice' ), 8, 2 );
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		// $payment_gateways_class = WC()->payment_gateways();
		// $payment_gateways       = $payment_gateways_class->payment_gateways();
		// if ( ! isset( $payment_gateways[ $this->name ] ) ) {
		// 	return false;
		// }

		// return $payment_gateways[ $this->name ]->is_available();

        return true;
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$script_url = plugins_url( "/assets/js/blocks/mpwp.js", MPWP_MAIN_FILE );

		wp_register_script(
			"wc-mpwp-blocks",
			$script_url,
			array('wc-blocks-checkout', 'react', 'wc-blocks-registry', 'wc-settings', 'wp-html-entities', 'wp-i18n'),
			'1.2',
			true
		);
		wp_set_script_translations( 'wc-mpwp-blocks', 'mugglepay' );
		return array( "wc-mpwp-blocks" );
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		$payment_gateways_class = WC()->payment_gateways();
		$payment_gateways       = $payment_gateways_class->payment_gateways();
		$gateway                = $payment_gateways[ $this->name ];
		return array(
			'title'             => $this->get_setting( 'title' ),
			'description'       => $this->get_setting( 'description' ),
			'supports'          => array_filter( $gateway->supports, array( $gateway, 'supports' ) ),
			'allow_saved_cards' => is_user_logged_in(),
		);
	}

	/**
	 * Add failed payment notice to the payment details.
	 *
	 * @param PaymentContext $context Holds context for the payment.
	 * @param PaymentResult  $result  Result object for the payment.
	 */
	public function mpwp_failed_payment_notice( PaymentContext $context, PaymentResult &$result ) {
		if ( 'mpwp' === $context->payment_method ) {
			// add_action(
			// 	'mpwp_wc_gateway_process_payment_error',
			// 	function( $failed_notice ) use ( &$result ) {
			// 		$payment_details                 = $result->payment_details;
			// 		$payment_details['errorMessage'] = wp_strip_all_tags( $failed_notice );
			// 		$result->set_payment_details( $payment_details );
			// 	}
			// );
		}
	}
}