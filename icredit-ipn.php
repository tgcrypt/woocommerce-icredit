<?php


define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

$ipn_logger = new WC_Logger();
$ipn_logger->add('rivhit_ipn', 'NEW IPN FIRED');

global $wp, $wp_query, $wp_the_query, $wp_rewrite, $wp_did_header, $woocommerce;

    $icredit = new WC_Gateway_ICredit;
    $raw_post_data = file_get_contents('php://input');
    $raw_post_array = explode('&', $raw_post_data);
    $IPNPost = array();
    foreach ($raw_post_array as $keyval) {
        $keyval = explode ('=', $keyval);
        if (count($keyval) == 2)
            $IPNPost[$keyval[0]] = urldecode($keyval[1]);
    }


$ipn_logger->add('rivhit_ipn', 'IPN POST DUMP: '.print_r($IPNPost, true));
$ipn_logger->add('rivhit_ipn', 'Payment Token: '. $icredit->payment_token);
$ipn_logger->add('rivhit_ipn', 'Order: '. $IPNPost['Order']);
$ipn_logger->add('rivhit_ipn', 'Sale Id: '. $IPNPost['SaleId']);
$ipn_logger->add('rivhit_ipn', 'Transaction Amount: '. $IPNPost['TransactionAmount']);


    $postData = array('GroupPrivateToken' => $icredit->payment_token,
                        'SaleId'=>$IPNPost['SaleId'],
                        'TotalAmount'=>$IPNPost['TransactionAmount']
                        );


$ipn_logger->add('rivhit_ipn', 'Step1');

$jsonData = json_encode($postData);


$response = wp_remote_post( $icredit->icredit_verify_gateway_url, array(
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

$ipn_logger->add('rivhit_ipn', 'WP IPN Verify Completed');
$ipn_logger->add('rivhit_ipn', print_r($response, true));

$json_response = json_decode($response['body']);


$ipn_logger->add('rivhit_ipn', 'json Status: '. $json_response->Status);
$ipn_logger->add('rivhit_ipn', 'TransactionCardNum: '. $IPNPost['TransactionCardNum']);
$ipn_logger->add('rivhit_ipn', 'Reference: '. $IPNPost['Reference']);
$ipn_logger->add('rivhit_ipn', 'DocumentURL: '. $IPNPost['DocumentURL']);

    // inspect IPN validation result and act accordingly
    add_post_meta($IPNPost['Order'], 'icredit_status', $json_response->Status);
    if ($json_response->Status == 'VERIFIED'){



       $order = new WC_Order($IPNPost['Order']);

        $order_id = $order->id;

        $ipn_logger->add('rivhit_ipn', 'Order ID: '. $order_id);


        add_post_meta($order_id, 'icredit_ccnum', $IPNPost['TransactionCardNum']);
        add_post_meta($order_id, 'icredit_cardname', $IPNPost['TransactionCardName']);
        add_post_meta($order_id, 'SaleId', $IPNPost['SaleId']);
        add_post_meta($order_id, 'Reference', $IPNPost['Reference']);
        add_post_meta($order_id, 'TransactionAmount', $IPNPost['TransactionAmount']);
        add_post_meta($order_id, 'DocumentURL', $IPNPost['DocumentURL']);

        $order->add_order_note( __( 'iCredit payment complete.', 'woocommerce_icredit' ) );
        $order->payment_complete();
        do_action('icredit_payment_complete');
    }

?>