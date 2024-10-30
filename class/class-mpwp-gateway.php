<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * MugglePayForWP Gateway Class.
 */
class MPWP_WC_Gateway extends WC_Payment_Gateway
{
    /** @var Multi Method */
    public $current_method = '';

    /** @var bool Whether or not logging is enabled */
    public static $log_enabled = false;

    /** @var WC_Logger Logger instance */
    public static $log = false;

    /**
     * Constructor for the gateway.
     */
    public function __construct()
    {
        include_once MPWP_PLUGIN_DIR . '/class/class-mugglepay-request.php';
        // Create muggle request
        $this->mpwp_mugglepay_request  = new MPWP_MugglePay_Request($this);

        $this->id           = 'mpwp';
        $this->icon         = '';
        $this->has_fields   = false;
        $this->order_button_text = __('Proceed to MugglePay', 'mugglepay');
        $this->method_title      = __('MugglePay', 'mugglepay');

        $this->gateway_methods = array(
            'muggle_pay_methods' => array(
                'title' => __('MugglePay', 'mugglepay'),
                'currency'   => '',
                'order_button_text' => __('Proceed to MugglePay', 'mugglepay')
            ),
            // 'card_methods'    => array(
            //     'title' => __('Card', 'mugglepay'),
            //     'currency'   => 'CARD',
            //     'order_button_text' => __('Proceed to Card', 'mugglepay')
            // ),
            // 'alipay_methods'    => array(
            //     'title' => __('Alipay', 'mugglepay'),
            //     'currency'   => 'ALIPAY',
            //     'order_button_text' => __('Proceed to Alipay', 'mugglepay')
            // ),
            // 'alipay_global_methods' => array(
            //     'title' => __('Alipay Global', 'mugglepay'),
            //     'currency'   => 'ALIGLOBAL',
            //     'order_button_text' => __('Proceed to Alipay Global', 'mugglepay')
            // ),
            // 'wechat_methods'    => array(
            //     'title' => __('Wechat', 'mugglepay'),
            //     'currency'   => 'WECHAT',
            //     'order_button_text' => __('Proceed to Wechat', 'mugglepay')
            // ),
            // 'btc_methods'       => array(
            //     'title' => __('BTC', 'mugglepay'),
            //     'currency'   => 'BTC',
            //     'order_button_text' => __('Proceed to BTC', 'mugglepay')
            // ),
            // 'ltc_methods'       => array(
            //     'title' => __('LTC', 'mugglepay'),
            //     'currency'   => 'LTC',
            //     'order_button_text' => __('Proceed to LTC', 'mugglepay')
            // ),
            // 'eos_methods'       => array(
            //     'title' => __('EOS', 'mugglepay'),
            //     'currency'   => 'EOS',
            //     'order_button_text' => __('Proceed to EOS', 'mugglepay')
            // ),
            // 'bch_methods'       => array(
            //     'title' => __('BCH', 'mugglepay'),
            //     'currency'   => 'BCH',
            //     'order_button_text' => __('Proceed to BCH', 'mugglepay')
            // ),
            // 'lbtc_methods'      => array(
            //     'title' => __('LBTC (for Lightening BTC)', 'mugglepay'),
            //     'currency'   => 'LBTC',
            //     'order_button_text' => __('Proceed to LBTC', 'mugglepay')
            // ),
            // 'cusd_methods'      => array(
            //     'title' => __('CUSD (for Celo Dollars)', 'mugglepay'),
            //     'currency'   => 'CUSD',
            //     'order_button_text' => __('Proceed to CUSD', 'mugglepay')
            // ),
            'usdt_methods'      => array(
                'title' => __('USDT', 'mugglepay'),
                'currency'   => 'USDT',
                'order_button_text' => __('Proceed to USDT', 'mugglepay')
            ),
            'usdc_methods'      => array(
                'title' => __('USDC', 'mugglepay'),
                'currency'   => 'USDC',
                'order_button_text' => __('Proceed to USDC', 'mugglepay')
            ),
            'eth_methods'       => array(
                'title' => __('ETH', 'mugglepay'),
                'currency'   => 'ETH',
                'order_button_text' => __('Proceed to ETH', 'mugglepay')
            ),
        );

        // supported features.
        $this->supports     = array(
            'products',
            'refunds'
        );

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables.
        $this->title                = $this->get_option('title');
        $this->method_description   = $this->get_option('description');
        $this->debug                = 'yes' === $this->get_option('debug', 'no');

        self::$log_enabled = $this->debug;

        // Setup callback URL
        $this->callback_url = WC()->api_request_url($this->id);

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ));
        // add_action('woocommerce_api_wc_gateway_mpwp', array( $this, 'check_response' ));
        add_filter('woocommerce_order_data_store_cpt_get_orders_query', array( $this, 'custom_query_var' ), 10, 2);
        add_action('woocommerce_api_' . $this->id, array($this, 'check_response'));

	
        // add_action('woocommerce_cancelled_order', array( $this, 'cancel_order' ), 10 ,1);
        // add_action( 'woocommerce_order_status_processing', array( $this, 'capture_payment' ) );
        // add_action( 'woocommerce_order_status_completed', array( $this, 'capture_payment' ) );
    }

    /**
     * Logging method.
     *
     * @param string $message Log message.
     * @param string $level   Optional. Default 'info'.
     *     emergency|alert|critical|error|warning|notice|info|debug
     * @param boolean $is_end insert log end flag
     */
    public static function log($message, $level = 'info', $is_end = true)
    {
        if (self::$log_enabled) {
            if (empty(self::$log)) {
                self::$log = wc_get_logger();
            }
            self::$log->log($level, $message, array( 'source' => 'mugglepay' ));
            if ($is_end) {
                self::$log->log($level, '=========================================== ↑↑↑ END ↑↑↑ ===========================================', array( 'source' => 'mugglepay' ));
            }
        }
    }

	/**
	 * Check if MPWP gateway is enabled.
	 *
	 * @return bool
	 */
	public function is_available() {
		if ( 'yes' == $this->enabled && $this->get_option('api_key') ) {
			return true;
		}
		return false;

	}

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled'       => array(
                'title'         => __('Enable/Disable', 'mugglepay'),
                'type'          => 'checkbox',
                'label'         => __('Enable MugglePay', 'mugglepay'),
                'default'       => 'no'
            ),
            'title'                 => array(
                'title'       => __('Title', 'mugglepay'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'mugglepay'),
                'default'     => __('MugglePay', 'mugglepay'),
                'desc_tip'    => true,
            ),
            'description'           => array(
                'title'       => __('Description', 'mugglepay'),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => __('This controls the description which the user sees during checkout.', 'mugglepay'),
                'default'     => __('MugglePay is a one-stop payment solution for merchants with an online payment need.', 'mugglepay'),
            ),
            'check_orders'      => array(
                'title'       => __('Check Orders', 'mugglepay'),
                'type'        => 'title',
                'description' => __('The plugin automatically checks the order payment status by default and updates the order status every 5 minutes.', 'mugglepay'),
            ),
            // <br>You can click the button to check and update the payment status of all outstanding orders.
            // 'check_orders_btn'      => array(
            //     'title'       => '<div style="margin-top: -20px;"><button class="button change-theme" type="button">更新订单状态</button></div>',
            //     'type'        => 'title'
            // ),
            'setting'              => array(
                'title'       => __('Setting', 'mugglepay'),
                'type'        => 'title',
                'description' => '',
            ),
            'api_key'               => array(
                'title'       => __('API Auth Token (API key) ', 'mugglepay'),
                'type'        => 'text',
                'placeholder' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
                /* translators: %s: URL */
                'description' => sprintf(__('Register your MugglePay merchant accounts with your invitation code and get your API key at <a href="%1$s" target="_blank">Merchants Portal</a>. You will find your API Auth Token (API key) for authentication. <a href="%2$s" target="_blank">MORE</a>', 'mugglepay'), 'https://merchants.mugglepay.com/user/register', 'https://mugglepay.docs.stoplight.io/api-overview/authentication'),
            ),
            'button_styles'               => array(
                'title'       => __('Button Styles', 'mugglepay'),
                'type'        => 'textarea',
                'placeholder' => '.payment_method_muggle_pay_methods { any; }',
                /* translators: %s: URL */
                'description' => __('If the style of your payment page is not displayed properly, you can overwrite your new style here', 'mugglepay'),
            ),
            'debug'          => array(
                'title'       => __('Debug log', 'mugglepay'),
                'type'        => 'checkbox',
                'label'       => __('Enable logging', 'mugglepay'),
                'default'     => 'no',
                // translators: Description for 'Debug log' section of settings page.
                'description' => sprintf(__('Log MPWP API events inside %s', 'mugglepay'), '<code>' . WC_Log_Handler_File::get_log_file_path('mpwp') . '</code>'),
            ),
            'payment_gateway'              => array(
                'title'       => __('Payment Gateway', 'mugglepay'),
                'type'        => 'title',
                'description' => '',
            )
        );

        foreach ($this->gateway_methods as $key => $value) {
            $this->form_fields[$key] = array(
                'title'     => '',
                'type'      => 'checkbox',
                'label'     => $value['title']
            );
        }
    }
    
    /**
     * Process the payment and return the result.
     *
     * @param  int $order_id Order ID.
     * @return array
     */
    public function process_payment($order_id)
    {
        global $woocommerce;

        $order  = wc_get_order($order_id);

        $result = $this->get_payment_url($order, $this->current_method);

        if (is_wp_error($result)) {
            wc_add_notice($result->get_error_message(), 'error');
            return;
        }

        return array(
            'result'   => 'success',
            'redirect' => $result,
        );
    }

    /**
     * Process a refund if supported.
     *
     * @param  int    $order_id Order ID.
     * @param  float  $amount Refund amount.
     * @param  string $reason Refund reason.
     * @return bool|WP_Error
     */
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        $order = wc_get_order($order_id);

        if (! $order || ! $order->get_transaction_id()) {
            return new WP_Error('error', __('Refund failed.', 'mugglepay'));
        }

        $result = $this->refund_transaction($order, $amount, $reason);

        if (is_wp_error($result)) {
            return new WP_Error('error', $result->get_error_message());
        }

        return true;
    }
    
    /**
     * Payment Callback (Webhook)
     * Send Post Request Url Like /?wc-api=MPWP_WC_Gateway
     */
    public function check_response()
    {
        try {
            // Get the raw input data and unslash it
            $input = wp_unslash(file_get_contents('php://input'));
            // Decode the JSON input
            $posted = json_decode($input, true);
    
            // Check if JSON decoding was successful
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON input');
            }
    
            // Extract only the required fields
            $merchant_order_id = isset($posted['merchant_order_id']) ? sanitize_text_field(wp_unslash($posted['merchant_order_id'])) : '';
            $token = isset($posted['token']) ? sanitize_text_field(wp_unslash($posted['token'])) : '';
    
            // Validate the extracted fields
            if (!empty($merchant_order_id) && !empty($token)) {
    
                $order_id = wc_get_order_id_by_order_key($merchant_order_id);
                $order = wc_get_order($order_id);
    
                if (!$order) {
                    self::log('Failed to check IPN response order callback for: ' . esc_html($order_id), 'error');
                    throw new Exception('Invalid IPN response: Order not found');
                }
    
                if (!$this->check_order_token($order, $token)) {
                    self::log('Invalid IPN response token', 'error', false);
                    self::log(print_r($posted, true), 'error', false);
                    self::log(print_r($order, true), 'error');
                    throw new Exception('Invalid IPN response: Token mismatch');
                }
    
                if ($order->has_status(wc_get_is_paid_statuses())) {
                    self::log('Aborting, Order #' . esc_html($order_id) . ' is already complete.', 'error');
                } else {
                    $this->order_complete($order, $posted);
                }
    
                wp_send_json(array(
                    'status' => 200
                ), 200);
                exit;
            }
    
            self::log('Failed to check response order callback: ', 'error', false);
            self::log(print_r($posted, true), 'error', false);
            throw new Exception('MugglePay IPN Request Failure');
        } catch (Exception $e) {
            wp_send_json(array(
                'message' => esc_html($e->getMessage()),
                'status' => 500
            ), 500);
            exit;
        }
    }
    

    /**
     * Complete order payment
     */
    public function order_complete($order, $voucher)
    {

        // Payment is complete
        $order->payment_complete();
        // Set transaction id.
        $order->set_transaction_id($voucher['order_id']);
        // Save payment voucher data
        $order->update_meta_data('_mpwp_payment_voucher', $voucher);
        // Change active status
        $order->update_meta_data('_mpwp_payment_active', false);
        // Save metadata
        $order->save();

        return true;
    }

    /**
     * Check payment statuses on orders and update order statuses.
     */
    public function check_orders()
    {
        // Check the status of non-archived MugglePay orders.
        $orders = wc_get_orders(array( 'mpwp_payment_active' => true, 'status'   => array( 'wc-pending' ) ));
        foreach ($orders as $order) {
            $transaction_id = $order->get_meta('_mpwp_prev_payment_transaction_id');

            usleep(1000000 * 3);  // Ensure we don't hit the rate limit. Delay 5 seconds.

            $mugglepay_order = $this->mpwp_mugglepay_request->get_order($transaction_id);

            self::log('Auto Checking Order #' . $order->get_id(), 'info', false);
            self::log(print_r($mugglepay_order, true), 'info');

            if (is_wp_error($mugglepay_order)) {
                continue;
            }

            if ($mugglepay_order['invoice']['status'] !== 'PAID') {
                continue;
            }

            $this->order_complete($order, $mugglepay_order['invoice']);

            self::log('Auto Complete Order #' . $order->get_id(), 'info');
        }
    }


    /**
     * Get the MugglePay request URL for an order.
     *
     * @param  WC_Order $order Order object.
     * @param string $pay_currency Only use this field if you have the payment gateway enabled, and it will select the payment gateway. e.g. ALIPAY, ALIGLOBAL, WECHAT, BTC, LTC, ETH, EOS, BCH, LBTC (for Lightening BTC), CUSD (for Celo Dollars)
     * @return string
     */
    public function get_payment_url($order, $pay_currency)
    {
        // Create description for charge based on order's products. Ex: 1 x Product1, 2 x Product2
        try {
            $order_items = array_map(function ($item) {
                return $item['name'] . ' x ' . $item['quantity'];
            }, $order->get_items());

            $description = mb_substr(implode(', ', $order_items), 0, 200);
        } catch (Exception $e) {
            $description = null;
        }

        if ($order->get_currency() !== 'USD' && $order->get_currency() !== 'CNY') {
            return new WP_Error('error', "{$order->get_currency()} currency is not supported; only USD and CNY are supported. Please adjust the Currency parameter.", array());
        }

        $mugglepay_args = array(
            'merchant_order_id'	=> $order->get_order_key(),
            'price_amount'		=> $order->get_total(),
            'price_currency'	=> $order->get_currency(),
            'pay_currency'		=> $pay_currency,
            // Translators: %s is the order ID.
            'title'				=> sprintf(__('Payment order #%s', 'mugglepay'), $order->get_id()),
            'description'		=> $description,
            'callback_url'		=> $this->callback_url,
            'cancel_url'		=> esc_url_raw($order->get_cancel_order_url_raw()),
            'success_url'		=> esc_url_raw($this->get_return_url($order)),
            'mobile'			=> wp_is_mobile(),
            // 'fast'				=> '',
            'token'				=> $this->create_order_token($order)
        );
        self::log(print_r($mugglepay_args, true), 'info');

        // Send Request
        $raw_response = $this->mpwp_mugglepay_request->send_request(
            '/orders',
            $mugglepay_args,
            array(
                'token'	=> $this->get_option('api_key')
            )
        );

        self::log('Create Payment Url: ', 'info', false);
        self::log(print_r($raw_response, true), 'info');

        if (
            (($raw_response['status'] === 200 || $raw_response['status'] === 201) && $raw_response['payment_url']) ||
            (($raw_response['status'] === 400 && $raw_response['error_code'] === 'ORDER_MERCHANTID_EXIST') && $raw_response['payment_url'])
        ) {
            // Insert mugglepay order active flag
            $order->update_meta_data('_mpwp_payment_active', true);
            // Save payment order id
            $order->update_meta_data('_mpwp_prev_payment_transaction_id', $raw_response['order']['order_id']);
            // Save metadata
            $order->save();

            return $raw_response['payment_url'];
        } elseif (!empty($raw_response['error_code'])) {
            return new WP_Error('error', $this->get_error_str($raw_response['error_code']), $raw_response);
        }

        return new WP_Error('error', $raw_response['error'], $raw_response);
    }

    /**
     * Refund an order via MugglePay.
     *
     * @param  WC_Order $order Order object.
     * @param  float    $amount Refund amount.
     * @param  string   $reason Refund reason.
     * @return object Either an object of name value pairs for a success, or a WP_ERROR object.
     */
    public function refund_transaction($order, $amount = null, $reason = '')
    {
        // Send Request
        $raw_response = $this->mpwp_mugglepay_request->send_request(
            '/orders/' . $order->get_transaction_id() . '/refund',
            array(),
            array(
                'token'	=> $this->get_option('api_key')
            )
        );
        
        if (is_wp_error($raw_response)) {
            return $raw_response;
        } elseif (empty($raw_response['status'] || $raw_response['status'] !== 200)) {
            return new WP_Error('error', __('Empty Response', 'mugglepay'));
        }

        return (object) $raw_response;
    }

    /**
     * Get Order token to validate Payment
     *
     * @param  WC_Order $order Order object.
     * @return string
     */
    public function create_order_token($order)
    {
        return wp_hash_password($order->get_order_key());
    }

    /**
     * Check Order token to validate Payment
     */
    public function check_order_token($order, $token)
    {
        return wp_check_password($order->get_order_key(), $token);
    }

    /**
     * HTTP Response and Error Codes
     * Most common API errors are as follows, including message, reason and status code.
     */
    public function get_error_str($code)
    {
        switch ($code) {
            case 'AUTHENTICATION_FAILED':
                return __('Authentication Token is not set or expired.', 'mugglepay');
            case 'INVOICE_NOT_EXIST':
                return __('Invoice does not exist.', 'mugglepay');
            case 'INVOICE_VERIFIED_ALREADY':
                return __('It has been verified already.', 'mugglepay');
            case 'INVOICE_CANCELED_FAIILED':
                return __('Invoice does not exist, or it cannot be canceled.', 'mugglepay');
            case 'ORDER_NO_PERMISSION':
                return __('Order does not exist or permission denied.', 'mugglepay');
            case 'ORDER_CANCELED_FAIILED':
                return __('Order does not exist, or it cannot be canceled.', 'mugglepay');
            case 'ORDER_REFUND_FAILED':
                return __('Order does not exist, or it`s status is not refundable.', 'mugglepay');
            case 'ORDER_VERIFIED_ALREADY':
                return __('Payment has been verified with payment already.', 'mugglepay');
            case 'ORDER_VERIFIED_PRICE_NOT_MATCH':
                return __('Payment money does not match the order money, please double check the price.', 'mugglepay');
            case 'ORDER_VERIFIED_MERCHANT_NOT_MATCH':
                return __('Payment money does not the order of current merchant , please double check the order.', 'mugglepay');
            case 'ORDER_NOT_VALID':
                return __('Order id is not valid.', 'mugglepay');
            case 'ORDER_PAID_FAILED':
                return __('Order not exist or is not paid yet.', 'mugglepay');
            case 'ORDER_MERCHANTID_EXIST':
                return __('Order with same merchant_order_id exisits.', 'mugglepay');
            case 'ORDER_NOT_NEW':
                return __('The current order is not new, and payment method cannot be switched.', 'mugglepay');
            case 'PAYMENT_NOT_AVAILABLE':
                return __('The payment method is not working, please retry later.', 'mugglepay');
            case 'MERCHANT_CALLBACK_STATUS_WRONG':
                return __('The current payment status not ready to send callback.', 'mugglepay');
            case 'PARAMETERS_MISSING':
                return __('Missing parameters.', 'mugglepay');
            case 'PAY_PRICE_ERROR':
                switch ($this->current_method) {
                    case 'WECHAT':
                    case 'ALIPAY':
                    case 'ALIGLOBAL':
                        return __('The payment is temporarily unavailable, please use another payment method', 'mugglepay');
                }
                return __('Price amount or currency is not set correctly.', 'mugglepay');
            case 'CREDENTIALS_NOT_MATCH':
                return __('The email or password does not match.', 'mugglepay');
            case 'USER_NOT_EXIST':
                return __('The user does not exist or no permission.', 'mugglepay');
            case 'USER_FAILED':
                return __('The user operatioin failed.', 'mugglepay');
            case 'INVITATION_FAILED':
                return __('The invitation code is not filled correctly.', 'mugglepay');
            case 'ERROR':
                return __('Error.', 'mugglepay');
            case '(Unauthorized)':
                return __('API credentials are not valid', 'mugglepay');
            case '(Not Found)':
                return __('Page, action not found', 'mugglepay');
            case '(Too Many Requests)':
                return __('API request limit is exceeded', 'mugglepay');
            case '(InternalServerError)':
                return __('Server error in MugglePay', 'mugglepay');
        }
        return __('Server error in MugglePay', 'mugglepay');
    }
    
    /**
     * Get gateway icon.
     *
     * @return string
     */
    public function get_icon()
    {
        // We need a base country for the link to work, bail if in the unlikely event no country is set.
        $base_country = WC()->countries->get_base_country();

        $icon_html = '';
        $icon      = (array) $this->get_icon_image($this->current_method, $base_country);

        if (empty($icon[0])) {
            return '';
        }

        foreach ($icon as $i) {
            $icon_html .= '<img src="' . esc_attr($i) . '" alt="' . esc_attr__('MugglePay acceptance mark', 'mugglepay') . '" / width="24px" height="24px">';
        }

        // Insert Styles
        if (!empty($this->get_option('button_styles'))) {
            $buttonStyles = htmlspecialchars($this->get_option('button_styles'), ENT_QUOTES, 'UTF-8');
            $icon_html = '<style id="mugglepay-button-styles">';
            $icon_html .= $buttonStyles;
            $icon_html .= '</style>';
        }

        return apply_filters('mpwp_gateway_icon', $icon_html, $this->id);
    }
    
    /**
     * Get MugglePay images for a country.
     *
     * @param string $method switch mulit language.
     * @param string $country Country code.
     * @return array of image URLs
     */
    protected function get_icon_image($method, $country)
    {

        switch ($method) {
            case '':
                $icon = '/mugglepay-logo-c.png';
            break;
            case 'BTC':
                $icon = '/btc.png';
            break;
            case 'ETH':
                $icon = '/eth.png';
            break;
            case 'USDT':
                $icon = '/usdt.png';
            break;
            case 'USDC':
                $icon = '/usdc.png';
            break;
            default:
                return '';
        }
        return apply_filters('mpwp_gateway_icon_image', MPWP_PLUGIN_URL . '/assets/images/' .$icon);
    }

    /**
     * Handle a custom 'mpwp_prev_payment_transaction_id' query var to get orders
     * payed through MugglePay with the 'mpwp_prev_payment_transaction_id' meta.
     * @param array $query - Args for WP_Query.
     * @param array $query_vars - Query vars from WC_Order_Query.
     * @return array modified $query
     */
    public function custom_query_var($query, $query_vars)
    {
        if (array_key_exists('mpwp_payment_active', $query_vars)) {
            // Only check the order with MugglePay payment voucher
            $query['meta_query'][] = array(
                'key'     => '_mpwp_payment_active',
                'compare' => $query_vars['mpwp_payment_active'] ? 'EXISTS' : 'NOT EXISTS',
            );
        }

        if (array_key_exists('mpwp_prev_payment_transaction_id', $query_vars)) {
            $query['meta_query'][] = array(
                'key'       => '_mpwp_prev_payment_transaction_id',
                'value'     => esc_attr($query_vars['mpwp_prev_payment_transaction_id'])
            );
        }

        return $query;
    }
}