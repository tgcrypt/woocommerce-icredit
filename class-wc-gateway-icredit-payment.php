<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * 30.05.17 - $line_total update by omer avhar 
 * 22.05.17 - currency update for multi currency's - add by omer avhar  
 * 05.04.17  -  get_total_discount() --> update for woocommerce 3.0 - add by omer avhar  
 * 30.3.17  - "PriceIncludeVAT":true - add by omer avhar  
 * iCredit Payment Gateway
 *
 * Provides a iCredit Payment Gateway.
 *
 * @class 		WC_Gateway_ICredit
 * @extends		WC_Payment_Gateway
 */
class WC_Gateway_ICredit extends WC_Payment_Gateway {


    const ICREDIT_VERIFY_GATEWAY_URL_TEST = 'https://testicredit.rivhit.co.il/API/PaymentPageRequest.svc/Verify';
    const ICREDIT_VERIFY_GATEWAY_URL_REAL = 'https://icredit.rivhit.co.il/API/PaymentPageRequest.svc/Verify';

    const ICREDIT_PAYMENT_GATEWAY_URL_TEST = 'https://testicredit.rivhit.co.il/API/PaymentPageRequest.svc/GetUrl';
    const ICREDIT_PAYMENT_GATEWAY_URL_REAL = 'https://icredit.rivhit.co.il/API/PaymentPageRequest.svc/GetUrl';



	/**
	 * Constructor for the gateway.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {


        $this->id				= 'icredit_payment';
        $this->icon 			= '';
        $this->has_fields 		= false;
        $this->method_title     = __( 'iCredit Payment', 'woocommerce_icredit' );

        $base_url = home_url('/?') . http_build_query(array('wc-api' => 'WC_Gateway_ICredit'), '', '%26amp;' );

        $this->success_url		= $base_url . "%26amp;target=success" . "%26amp;";
        $this->error_url		= $base_url . "%26amp;target=error" . "%26amp;";
        $this->cancel_url		= $base_url . "%26amp;target=cancel" . "%26amp;";


        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        $this->title      	= $this->get_option( 'title' );
        $this->description 	= $this->get_option( 'description' );
        $this->test_mode	= $this->get_option( 'test_mode' ) == 'yes';
        $this->redirect_url = $this->get_option('redirect_url')?$this->get_option('redirect_url'):false;



        $this->hide_items   = $this->get_option( 'hide_items' ) == 'yes';
        $this->create_token = $this->get_option( 'create_token' ) == 'yes';
        $this->max_payments = $this->get_option( 'max_payments' )? $this->get_option( 'max_payments' ):0;
        $this->credit_from_payment = ($this->get_option( 'credit_from_payment') != '')?$this->get_option( 'credit_from_payment' ):0;

        $this->document_language = $this->get_option( 'document_language');
        $this->exempt_vat = $this->get_option ('exempt_vat');


        $this->handle_paypal_ipn = $this->get_option( 'handle_paypal_ipn' ) == 'yes';
        $this->paypal_api_token = $this->get_option( 'paypal_api_token' );
        $this->paypal_document_type = $this->get_option( 'paypal_document_type' );
        $this->paypal_customer_id = $this->get_option( 'paypal_customer_id' );
        $this->paypal_sort_code = $this->get_option( 'paypal_sort_code' );
        $this->paypal_exampt_sort_code = $this->get_option( 'paypal_exampt_sort_code' );
        $this->paypal_rivpayment_type = $this->get_option( 'paypal_rivpayment_type' );
        $this->paypal_sign_pin = $this->get_option( 'paypal_sign_pin' );
        $this->paypal_send_mail = $this->get_option( 'paypal_send_mail' );
        $this->paypal_create_customer = $this->get_option( 'paypal_create_customer' );
        $this->popup_mode = $this->get_option('popup_mode');
        $this->iframe_height = $this->get_option('iframe_height');
        $this->http_https = $this->get_option('http_https');    
        # ipn_integration add by omeravhar 30.05.17
        $this->ipn_integration = $this->get_option('ipn_integration'); 



        

        $this->field_firstname   = $this->get_option( 'field_firstname' ) == 'yes';
        $this->field_lastname   = $this->get_option( 'field_lastname' ) == 'yes';
        $this->field_email   = $this->get_option( 'field_email' ) == 'yes';
        $this->field_address   = $this->get_option( 'field_address' ) == 'yes';
        $this->field_city   = $this->get_option( 'field_city' ) == 'yes';
        $this->field_phonenumber   = $this->get_option( 'field_phonenumber' ) == 'yes';
        $this->field_company   = $this->get_option( 'field_company' ) == 'yes';
        $this->field_zipcode   = $this->get_option( 'field_zipcode' ) == 'yes';
        $this->user_comments   = $this->get_option( 'user_comments' ) == 'yes';



		$this->icredit_verify_gateway_url = $this->test_mode ? self::ICREDIT_VERIFY_GATEWAY_URL_TEST : self::ICREDIT_VERIFY_GATEWAY_URL_REAL ;
        $this->icredit_payment_gateway_url = $this->test_mode ? self::ICREDIT_PAYMENT_GATEWAY_URL_TEST : self::ICREDIT_PAYMENT_GATEWAY_URL_REAL ;

        $this->payment_token = $this->test_mode ? $this->get_option ( 'test_token') : $this->get_option ( 'real_token');
#wpml token add by omeravhar 23.11.16
        $this->payment_token = $this->test_mode ? $this->get_option ( 'test_token') : $this->get_option ( 'real_token');

        $this->real_token_lang_symbol_1 = $this->get_option ( 'real_token_lang_symbol_1' );
        $this->real_token_lang_1 = $this->get_option ( 'real_token_lang_1' );


		// Actions

	add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_receipt_icredit_payment', array( $this, 'receipt_page' ), 10 );
    }

    function reset_payment_session(){
        WC()->session->set('icredit_iframe_displayed', false);
    }

    function receipt_page( $order_id ){


        if (WC()->session->get('icredit_iframe_displayed') == true){
            return;
        }
        WC()->session->set('icredit_iframe_displayed', true);




        $order = new WC_Order( $order_id );


        if ( $icredit_payment_url = WC()->session->get('icredit_payment_url') ):
?>

            <div id="step-payment" class="checkout-step">

                <div class="checkout-step-heading clearfix">
                    <div class="sprite-stepsIndicator indicatorStep3 step-indicator pull-left"></div>
                    <h3 class="step-title"><?php _e('Payment', 'woocommerce_icredit'); ?></h3>
                </div>
                <div class="checkout-frame">
                    <iframe id="icredit-iframe" width="100%" height="<?php echo $this->iframe_height; ?>" src="<?php echo $icredit_payment_url; ?>" scrolling="yes"></iframe>

                </div><!-- .checkout-frame -->

            </div>
            <?php
        endif;
    }

    
    /**
     * Initialize Gateway Settings Form Fields
     *
     * @access public
     * @return void
     */
    function init_form_fields() {

        $this->form_fields = array(
            'enabled' => array(
                'title' => __( 'Enable/Disable', 'woocommerce_icredit' ),
                'type' => 'checkbox',
                'label' => __( 'Enable iCredit Payment', 'woocommerce_icredit' ),
                'default' => 'no'
            ),
            'title' => array(
                'title' => __( 'Payment Method Title', 'woocommerce_icredit' ),
                'type' => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce_icredit' ),
                'default' => __( 'iCredit Payment', 'woocommerce_icredit' ),
                'desc_tip'      => true,
            ),
            'description' => array(
                'title' => __( 'Payment Method Description', 'woocommerce_icredit' ),
                'type' => 'textarea',
                'default' => __( 'Pay via iCredit - secure payment page.', 'woocommerce_icredit' )
            ),

            'real_token' => array(
                'title' => __('Group Private Token', 'woocommerce_icredit'),
                'type' => 'text',
            ),

            'test_token' => array(
                'title' => __('Test Group Private Token (optional)', 'woocommerce_icredit'),
                'type' => 'text',
                'default' => 'bb8a47ab-42e0-4b7f-ba08-72d55f2d9e41'
            ),
            
            'test_mode'	=> array(
                'title'	=> __('Test Mode', 'woocommerce_icredit'),
                'type'	=> 'checkbox',
                'label'	=> __('Use test mode', 'woocommerce_icredit'),
                'default' => 'yes'
            ),

            'max_payments' => array(
                'title' => __('Max Payments', 'woocommerce_icredit'),
                'type' => 'number',
                'default' => 0
            ),

            'credit_from_payment' => array(
                'title' => __('Credit From Payment', 'woocommerce_icredit'),
                'type' => 'number',
                'default' => 0
            ),

            'create_token'	=> array(
                'title'	=> __('Create Token', 'woocommerce_icredit'),
                'type'	=> 'checkbox',
                'default' => 'no'
            ),

            
            'http_https'	=> array(
                'title'	=> __('IPN Protocol', 'woocommerce_icredit'),
                'type'	=> 'select',
                'label'	=> __('', 'woocommerce_icredit'),

                'options' => array(
                    'http'	=> __('http', 'woocommerce_icredit'),

                    'https'		=> __('https', 'woocommerce_icredit'),
                   
                ),
                'default' => 'http',
            ),
            
            
            'ipn_integration' => array(
                'title' => __('IPN Integration', 'woocommerce_icredit'),
                'type' => 'text',
                'default' => ''
            ),

            'document_language'	=> array(
                'title'	=> __('Document Language', 'woocommerce_icredit'),
                'description'	=> __( 'Select the language the invoice should be created. Select "By Country Code" to create Hebrew documents for Israel and English for any other country.', 'woocommerce' ),
                'type'	=> 'select',
                'label'	=> '',

                'options' => array(
                    'always_hebrew'	=> __('Always Hebrew', 'woocommerce_icredit'),
                    'always_english'		=> __('Always English', 'woocommerce_icredit'),
                    'by_country_code' 		=> __('By Country Code', 'woocommerce_icredit'),
                ),
                'default' => 'by_country_code',
                'desc_tip'		=> true,
            ),


            'exempt_vat'	=> array(
                'title'	=> __('Exampt VAT', 'woocommerce_icredit'),
                'description'	=> __( 'Select if to exempt VAT.', 'woocommerce' ),
                'type'	=> 'select',
                'label'	=> '',
                'options' => array(
                    'always_not_exempt'	=> __('Always Not Exempt', 'woocommerce_icredit'),
                    'always_exempt'		=> __('Always Exempt', 'woocommerce_icredit'),
                    'by_shipping_address' 		=> __('By Shipping Country', 'woocommerce_icredit'),
                    'by_billing_address' 		=> __('By Billing Country', 'woocommerce_icredit'),
                    'by_shipping_or_billing_address' 		=> __('By Shipping or Billing Country', 'woocommerce_icredit'),
                ),
                'default' => 'by_shipping_or_billing_address',
                'desc_tip'		=> true,
            ),

            'subheading1'	=> array(
                'title'	=> __('<h2>iCredit Payment View</h2>', 'woocommerce_icredit'),
                'type'	=> 'title'
            ),

            'popup_mode'	=> array(
                'title'	=> __('Payment Window Mode', 'woocommerce_icredit'),
                'type'	=> 'select',
                'label'	=> __('', 'woocommerce_icredit'),

                'options' => array(
                    'redirect'	=> __('Redirect', 'woocommerce_icredit'),

                    'iframe'		=> __('iFrame', 'woocommerce_icredit'),
                    /*
                'popup' 		=> __('Lightbox PopUp', 'woocommerce_icredit'),
                */
                ),
                'default' => 'redirect',
            ),


            'iframe_height' => array(
            'title' => __('iFrame Height', 'woocommerce_icredit'),
            'type' => 'number',
            'default' => 700
                ),


            'hide_items'	=> array(
                'title'	=> __('Hide Items on Payment Page', 'woocommerce_icredit'),
                'type'	=> 'checkbox',
                'default' => 'no'
            ),

            'redirect_url' => array(
                'title' => __('Thank You page URL', 'woocommerce_icredit'),
                'type' => 'text',
                'description' => __('Leave empty to use iCredit Group Settings. <br />Leave iCredit Group Redirect URL Setting empty in order to use WooCommerce default thank you page (<strong>recommended</strong>).'),
                'desc_tip' => false
            ),


            'subheading2'	=> array(
                'title'	=> __('<h2>Transmitted Information</h2>', 'woocommerce_icredit'),
                'type'	=> 'title'
            ),

            'field_firstname'	=> array(
                'title'	=> __('First Name', 'woocommerce_icredit'),
                'type'	=> 'checkbox',
                'default' => 'yes'
            ),

            'field_lastname'	=> array(
                'title'	=> __('Last Name', 'woocommerce_icredit'),
                'type'	=> 'checkbox',
                'default' => 'yes'
            ),

            'field_email'	=> array(
                'title'	=> __('e-mail Address', 'woocommerce_icredit'),
                'type'	=> 'checkbox',
                'default' => 'yes'
            ),

            'field_address'	=> array(
                'title'	=> __('Address', 'woocommerce_icredit'),
                'type'	=> 'checkbox',
                'default' => 'yes'
            ),


            'field_city'	=> array(
                'title'	=> __('City', 'woocommerce_icredit'),
                'type'	=> 'checkbox',
                'default' => 'yes'
            ),

            'field_zipcode'	=> array(
                'title'	=> __('Zip Code', 'woocommerce_icredit'),
                'type'	=> 'checkbox',
                'default' => 'yes'
            ),

            'field_phonenumber'	=> array(
                'title'	=> __('Phone Number', 'woocommerce_icredit'),
                'type'	=> 'checkbox',
                'default' => 'yes'
            ),

            'field_companyname'	=> array(
                'title'	=> __('Company Name', 'woocommerce_icredit'),
                'type'	=> 'checkbox',
                'default' => 'yes'
            ),

            'user_comments'	=> array(
                'title'	=> __('User Comments', 'woocommerce_icredit'),
                'type'	=> 'checkbox',
                'default' => 'yes'
            ),

            'subheading3'	=> array(
                'title'	=> __('<h2>PayPal Integration</h2>', 'woocommerce_icredit'),
                'type'	=> 'title'
            ),

            'handle_paypal_ipn'	=> array(
                'title'	=> __('Handle PayPal IPN', 'woocommerce_icredit'),
                'type'	=> 'checkbox',
                'label'	=> __('Check this if you wish to create iCredit invoices for orders that completed successfuly with PayPal payment', 'woocommerce_icredit'),
                'default' => 'no'
            ),

            'paypal_api_token' => array(
                'title' => __('PayPal API Token', 'woocommerce_icredit'),
                'type' => 'text'
            ),

            'paypal_document_type' => array(
                'title' => __('Document Type', 'woocommerce_icredit'),
                'type' => 'text',
                'default' => '2'
            ),

            'paypal_customer_id' => array(
                'title' => __('Customer ID', 'woocommerce_icredit'),
                'type' => 'text',
                'default' => '0'
            ),

            'paypal_sort_code' => array(
                'title' => __('Sort Code', 'woocommerce_icredit'),
                'type' => 'text',
                'default' => '100'
            ),

            'paypal_exampt_sort_code' => array(
                'title' => __('Exampt Sort Code', 'woocommerce_icredit'),
                'type' => 'text',
                'default' => '150'
            ),


            'paypal_rivpayment_type' => array(
                'title' => __('Riv Payment Type', 'woocommerce_icredit'),
                'type' => 'text',
                'default' => '10'
            ),

            'paypal_sign_pin' => array(
                'title' => __('Sign PIN', 'woocommerce_icredit'),
                'type' => 'text',
                'default' => ''
            ),

            'paypal_send_mail' => array(
                'title' => __('Send Mail', 'woocommerce_icredit'),
                'type' => 'select',
                'options' => array(
                    'true'	=> __('True', 'woocommerce_icredit'),
                    'false'		=> __('False', 'woocommerce_icredit')
                ),
                'default' => 'true',
            ),


            'paypal_create_customer' => array(
                'title' => __('Create Customer', 'woocommerce_icredit'),
                'type' => 'select',
                'options' => array(
                    'true'	=> __('True', 'woocommerce_icredit'),
                    'false'		=> __('False', 'woocommerce_icredit')
                ),
                'default' => 'true',
            ),

            'paypal_free_query' => array(
                'title' => __('Custom Query', 'woocommerce_icredit'),
                'label'	=> __('Add your own variables. ex.: key1=data1&key2=data2&key3...', 'woocommerce_icredit'),
                'type' => 'text',
                'default' => ''
            ),

             'subheading5'	=> array(
                'title'	=> __('<h2>WPML Integration</h2>', 'woocommerce_icredit'),
                'type'	=> 'title'
            ),


            'real_token_lang_symbol_1' => array(
                'title' => __('Second Language', 'woocommerce_icredit'),
                'type' => 'text',
                'description' => 'i.e. EN, HE, IT, FR'
            ),

            'real_token_lang_1' => array(
                'title' => __('Second Language Token', 'woocommerce_icredit'),
                'type' => 'text',

            ),        





        );

    }



    


    /**
     * @param $post
     * Recieve and transmit PayPal IPN to iCredit
     */

    public function icredit_valid_paypal_standard_ipn_request($post){

        $logger = new WC_Logger();
        $logger->add('rivhit-paypal', 'Valid PayPal IPN received');
        $logger->add('rivhit-paypal', '$_POST: ' . print_r($post, true));


        /* Filter out other PayPal Payments */
        $json_custom_fields = json_decode($post['custom']);
        /*
        if ( !isset($post['transaction_subject']) || (strpos($post['transaction_subject'], 'wc_order_') === false)){
            $logger->add('rivhit-paypal', 'EXIT: NO transaction_subject OR NO wc_order_');
            return false; # This order is not from WooCommerce
        }
        */
        $logger->add('rivhit-paypal', 'Custom Fields: '.print_r($json_custom_fields, true) );

        if (!isset($json_custom_fields->order_key) || (strpos($json_custom_fields->order_key,'wc_order') === false)){
            $logger->add('rivhit-paypal', 'EXIT: NO order_key');
            return false; # This order is not from WooCommerce
        }


        $logger->add('rivhit-paypal', 'Phase 2' );
       
        

        


        /* Exit if PayPal payments should not be integrated */
        if (!$this->handle_paypal_ipn || !$this->paypal_api_token){

            $logger->add('rivhit-paypal', 'Requested not to handle IPN --OR-- NO paypal_api_token');
            return false;
        }

        $logger->add('rivhit-paypal', 'Phase 3' );

        $logger->add('rivhit-paypal', 'START PayPal IPN Process');
        $order_id = $json_custom_fields->order_id;

        $logger->add('rivhit-paypal', 'ORDER ID: '.$order_id);




        $received_values = stripslashes_deep( $post );
        $params = array(
            'body'                          => $received_values,
            'sslverify'         => false,
            'timeout'                      => 60
        );

        $document_params = array(
           'api_token' =>   trim($this->paypal_api_token),
            'document_type' => trim($this->paypal_document_type),
            'customer_id' => trim($this->paypal_customer_id),
            'sort_code' => trim($this->paypal_sort_code),
            'exampt_sort_code' => trim($this->paypal_exampt_sort_code),
            'sign_pin'=> trim($this->paypal_sign_pin),
            'send_mail' => trim($this->paypal_send_mail),
            'rivpayment_type' => trim($this->paypal_rivpayment_type),
            'create_customer' => trim($this->paypal_create_customer),
            'paypal_free_query' => trim($this->paypal_free_query)
        );

        $revhit_target = 'https://api.rivhit.co.il/paypal/PaypalIpnListener.aspx?'.http_build_query($document_params);

        $logger->add('rivhit-paypal', "Sending to rivhit:\nURL: " . $revhit_target);
        $response = wp_remote_post( $revhit_target , $params );
        $logger->add('rivhit-paypal', "Rivhit Response: " . print_r($response, true) );

        include('simpledom/simple_html_dom.php');

        $html = str_get_html($response['body']);
        //$response_document_url = $html->find('span[id=ResultJSON]', 0)->innertext;
        $response_document_url = $html->find('document_link', 0)->innertext;
        add_post_meta($order_id, 'DocumentURL', $response_document_url);

    }










	/**
	 * Process the payment and return the result
	 *
	 * @access public
	 * @param int $order_id
	 * @return array
	 */
    function process_payment( $order_id ){
        global $woocommerce;

        $logger = new WC_Logger();
        $logger->add('rivhit_process_payment', 'New Payment Process Fired');


        $order = new WC_Order( $order_id );

        $billing_country =  ($order->billing_country == 'IL' || $order->billing_country == '')?'IL':$order->billing_country;
        $shipping_country =  ($order->shipping_country == 'IL' || $order->shipping_country == '')?'IL':$order->shipping_country;

        //$total = $order->get_order_total();

        #ADD ITEMS TO ORDER
        $items = array();
        $order_items = $order->get_items();
        foreach ($order_items as $order_item){
            $product = new WC_Product($order_item['product_id']);

            $line_total = ($order_item['line_tax'] > 0)?number_format( ($order_item['subtotal'] + $order_item['line_tax'])/$order_item['qty'], 2): number_format($order_item['subtotal']/$order_item['qty'], 2);


            $attributes = array();

            /* Fix for variation SKU */
            if ($order_item['variation_id']){


                $temp_product = new WC_Product_Variation($order_item['variation_id']);
                $attributes = $temp_product->get_variation_attributes();
                //print_r($attributes);

            }
            else $temp_product = new WC_Product($order_item['product_id']);



            array_push($items, array('Id'=>'0',
                'CatalogNumber'=>$temp_product->get_sku(),
                'UnitPrice'=>str_replace(',','',$line_total),
                'Quantity'=> $order_item['qty'] ,
                'Description'=>$order_item['name']
            ));

            if (count($attributes)){
                foreach ($attributes as $key=>$value){
                    $key = str_replace('attribute_','',$key);
                    $key = str_replace('pa_','',$key);
                    array_push($items, array('Id'=>'0',
                        'CatalogNumber'=>'0',
                        'UnitPrice'=>'0',
                        'Quantity'=> '1' ,
                        'Description'=> urldecode($key).': '.urldecode($value)
                    ));
                }


            }
        }


        #ADD SHIPPING COSTS
        foreach ($order->get_shipping_methods() as $shipping_method){

            $shipping_method_name = $shipping_method['name'];
            $shipping_method_cost = $shipping_method['cost'];

            array_push($items, array('Id'=>'0',
                'UnitPrice'=>str_replace(',','',number_format($shipping_method_cost, 2)),
                'Quantity'=> 1 ,
                'Description'=> $shipping_method_name
            ));
        }


        $logger->add('rivhit_process_payment', 'Shipping and Items added');

        $ipn_url = plugins_url( '/', __FILE__ ).'icredit-ipn.php' ;

        if ( $this->http_https == 'http' ){
            $ipn_url = str_replace('https:','http:',$ipn_url);
        }
        else 
        {
            $ipn_url = str_replace('http:','https:',$ipn_url);
        }
        
        #IPN_Integration
        $ipn_integration = '';
        if ( $this->ipn_integration ){
            $ipn_integration = $this->ipn_integration;
        }
        
 
        #WPML Integration add by omeravhar 23.11.16
        $wpml_token == false;

        if ( function_exists('icl_object_id') ) {
            if ((ICL_LANGUAGE_CODE) &&
                ( strtolower(ICL_LANGUAGE_CODE) == strtolower($this->real_token_lang_symbol_1)) &&
                ( $this->real_token_lang_1)){

                $this->payment_token = $this->real_token_lang_1;
                # set parameter to check token in icredit-ipn.php //add by omeravhar 24.11.16
                $wpml_token = true;
            }
        }
        
        
        $currency = get_woocommerce_currency();
            switch ($currency){
                    case "ILS":
                      $currency="1";
                      break;
                    case "USD":
                      $currency="2";
                      break;
                    case "EUR":
                      $currency="3";
                      break;
                    case "GBP":
                      $currency="4";
                      break;
                    case "AUD":
                      $currency="5";
                      break;
                    case "CAD":
                      $currency="6";
                      break;
		    }

        $postData = array('IPNURL'=> $ipn_url,
            'Order'=>$order->id,
            'Custom1'=>$order->id,
            'Custom2'=>$wpml_token,
            'Custom3'=>$ipn_integration,
            'HideItemList'=>$this->hide_items,
            'GroupPrivateToken' => $this->payment_token,
            'Items' => $items,
            'MaxPayments' => $this->max_payments,
            'CreditFromPayment' => $this->credit_from_payment,
            'CreateToken' => $this->create_token,
            'Discount' => $order->get_total_discount(),
            'PriceIncludeVAT' => true,
            'Currency' => $currency,
        );
        switch ($this->document_language){
            case 'always_hebrew':
                $postData['DocumentLanguage'] = 'he';
                break;
            case 'always_english':
                $postData['DocumentLanguage'] = 'en';
                break;
            case 'by_country_code':
                $postData['DocumentLanguage'] = ($billing_country == 'IL')?'he':'en';
                break;
        }

        switch ($this->exempt_vat){
            case 'always_not_exempt':
                $postData['ExemptVAT'] = false;
                break;
            case 'always_exempt':
                $postData['ExemptVAT'] = true;
                break;
            case 'by_shipping_address':
                $postData['ExemptVAT'] = ($billing_country == 'IL')?false:true;
                break;

            case 'by_billing_address':
                $postData['ExemptVAT'] = ($shipping_country == 'IL')?false:true;
                break;

            case 'by_shipping_or_billing_address':
                $postData['ExemptVAT'] = (($billing_country == 'IL')||($shipping_country == 'IL'))?false:true;
                break;
        }

        if ($this->redirect_url){
            $redirect_url = $this->redirect_url;
        }
        else {
            $redirect_url = $order->get_checkout_order_received_url();
        }
        
        
        // if iframe, save the final redirect url and use redirect.php.
        if ( $this->popup_mode == 'iframe' ){
            WC()->session->set('icredit_iframe_redirect_url', $redirect_url);
            $redirect_url = plugins_url( '/', __FILE__ ).'redirect.php' ;
        }

        $postData['RedirectURL'] = $redirect_url;


        if ($this->field_firstname){ $postData['CustomerFirstName']= $order->billing_first_name; }
        if ($this->field_lastname) { $postData['CustomerLastName']= $order->billing_last_name; }
        if ($this->field_email) {$postData['EmailAddress']= $order->billing_email; }
        if ($this->field_address) {$postData['Address']= $order->billing_address_1.' '.$order->billing_address_2; }
        if ($this->field_city) {$postData['City']= $order->billing_city;}
        if ($this->field_phonenumber) {$postData['PhoneNumber']= $order->billing_phone;}
        if (($order->billing_company) && ($this->field_company)) { $postData['CustomerLastName'].= ' - '.$order->billing_company; }
        if (($order->billing_postcode) && ($this->field_zipcode)) {
            if (!is_numeric($order->billing_postcode)) $postData['Zipcode'] = '00000';
            else if (strlen($order->billing_postcode) > 7){
                $postData['Zipcode']= substr($order->billing_postcode,0,7);
            } else {
                $postData['Zipcode']= $order->billing_postcode;
            }
        }
        if (($this->user_comments) && ($order->get_customer_order_notes())) {$postData['Comments']= $order->get_customer_order_notes(); }


        $_SESSION['current_customer_order_id'] = $order->id;

        $logger->add('rivhit_process_payment', 'All data collected before Sending to Rivhit');

        $jsonData = json_encode($postData);



        $response = wp_remote_post( $this->icredit_payment_gateway_url, array(
                'method' => 'POST',
                'timeout' => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => array('Content-Type' => 'application/json'),
                'body' => $jsonData,
                'cookies' => array()
            )
        );


        $logger->add('rivhit_process_payment', 'WP Remote Post Completed');
        $logger->add('rivhit_process_payment', print_r($response, true));

        $json_response = json_decode($response['body']);
        WC()->session->set('icredit_payment_url', $json_response->URL);

        do_action('rivhit_process_payment_completed');

        /* If iFrame mode */

        $this->reset_payment_session();
        if ($this->popup_mode == 'iframe') {
            return array(
                'result' => 'success',
                'redirect' => $order->get_checkout_payment_url(true)
            );

        }


        return array(
            'result' => 'success',
            'redirect' => $json_response->URL
        );

        wp_die();



    }

}