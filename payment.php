<?php
/*
 * Plugin Name: Woocommerce iCredit - admin order page payment 
 * Description: Enable iCredit (Rivhit) admin order page payment
 * Version: 1.0
 * Author: omer avhar
 * 
 */

if(isset($_GET['order'])){
require_once('../../../wp-load.php');
require_once('class-wc-gateway-icredit-payment.php');
$order_id = $_GET['order'];
$admin_payment = new WC_Gateway_ICredit();
$url_redirect = get_site_url().'/wp-admin/edit.php?post_type=shop_order';
$url = $admin_payment->process_payment( $order_id,$url_redirect );
//print_r($url['redirect']);
header('location: '.$url['redirect']);
}




add_filter( 'woocommerce_admin_order_actions', 'add_payment_order_actions_button', PHP_INT_MAX, 2 );
function add_payment_order_actions_button( $actions, $the_order ) {
    
    if ( ! $the_order->has_status( array( 'cancelled' ) ) && ! $the_order->has_status( array( 'completed' ) ) ) { // if order is not cancelled yet...
        
        $actions['cancel'] = array(
            'url'       => wp_nonce_url( content_url('plugins/woocommerce-icredit/payment.php?order='.$the_order->id) , 'woocommerce-mark-order-status' ),
            'name'      => __( 'pay with icredit', 'woocommerce' ),
            'action'    => "pay", // setting "view" for proper button CSS
        );
    }
    
    return $actions;
}





