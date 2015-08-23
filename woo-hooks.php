<?php

add_action( 'woocommerce_add_to_cart', 'membership_discount_add_cart_item', 20, 2 );
function membership_discount_add_cart_item( $key,$pid ) {
    global $woocommerce;
    $membership_discount_options = get_option( 'membership_discount_option_name' );
    $discount_product = $membership_discount_options['discount_product_0'];
    $product_cats = wp_get_post_terms( $pid, 'product_cat' );
    $product_cat_terms = array();
    foreach($product_cats as $product_cat) {
        $product_cat_terms[] = $product_cat->term_id;
    }

    foreach ( $discount_product as $ddd ){
        if ( in_array($ddd, $product_cat_terms) ) {
            $current_month_discount = find_current_month_discount($membership_discount_options);
            if ( $current_month_discount ) {
                $woocommerce->cart->add_discount($current_month_discount);
                if ( $membership_discount_options['custom_notice_3'] !== '' ) {
                    wc_clear_notices();
                    wc_add_notice( $membership_discount_options['custom_notice_3']);
                }
                break;
            }
        }
    }
}

function find_current_month_discount($membership_discount_options) {
    $start_date = $membership_discount_options['start_date_1'];
    $start_date = explode('/',$start_date);
//    $end_date = $membership_discount_options['end_date_2'];
    $today = time();
    $current_month = date('n', $today);
    $current_day = date('j');
    $start_month = $start_date[1];
    if ( $current_month < $start_month ) {
        $month_diff = 12 - $start_month + $current_month;
    } else {
        $month_diff = $current_month - $start_month;
    }
    if ( $current_day > $membership_discount_options['mid_month_2'] ) {
        $month_diff++;
    }
    if ( $month_diff == 0 ) {
        return false;
    }
    $discount_num = $month_diff;
    $discount_code = 'discount_month_'.$discount_num;
    return $discount_code;
}