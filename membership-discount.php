<?php

/*
Plugin Name: Membership Discount
Description: a pro rata membership discount plugin.
Version: 1.0
Author: Martin Greenwood
Author URI: http://www.pixelpudu.com/plugins/membership-discounts
*/

include_once('woo-hooks.php');

class MembershipDiscount {
    private $membership_discount_options;

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'membership_discount_add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'membership_discount_page_init' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_date_picker') );
    }

    public function membership_discount_add_plugin_page() {
        add_options_page(
            'Membership Discount', // page_title
            'Membership Discount', // menu_title
            'manage_options', // capability
            'membership-discount', // menu_slug
            array( $this, 'membership_discount_create_admin_page' ) // function
        );
    }

    public function membership_discount_create_admin_page() {
        $this->membership_discount_options = get_option( 'membership_discount_option_name' ); ?>

        <div class="wrap membership-discount">
            <h2>Membership Discount</h2>
            <p></p>
            <?php settings_errors(); ?>
            <style type="text/css" scoped="scoped">
                .membership-discount .ui-datepicker-year
                {
                    display:none;
                }
            </style>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'membership_discount_option_group' );
                do_settings_sections( 'membership-discount-admin' );
                submit_button();
                ?>
            </form>
            <script>
                jQuery(document).ready(function(){
                    jQuery('.datepicker').datepicker({ changeYear: false, dateFormat: 'dd/mm'});
                });
            </script>
        </div>
    <?php }

    public function membership_discount_page_init() {
        register_setting(
            'membership_discount_option_group', // option_group
            'membership_discount_option_name', // option_name
            array( $this, 'membership_discount_sanitize' ) // sanitize_callback
        );

        add_settings_section(
            'membership_discount_setting_section', // id
            'Settings', // title
            array( $this, 'membership_discount_section_info' ), // callback
            'membership-discount-admin' // page
        );

        add_settings_field(
            'discount_product_0', // id
            'Discount Category', // title
            array( $this, 'discount_product_0_callback' ), // callback
            'membership-discount-admin', // page
            'membership_discount_setting_section' // section
        );

        add_settings_field(
            'start_date_1', // id
            'Start date', // title
            array( $this, 'start_date_1_callback' ), // callback
            'membership-discount-admin', // page
            'membership_discount_setting_section' // section
        );

        add_settings_field(
            'mid_month_2', // id
            'Date of cutoff', // title
            array( $this, 'mid_month_2_callback' ), // callback
            'membership-discount-admin', // page
            'membership_discount_setting_section' // section
        );

        add_settings_field(
            'custom_notice_3', // id
            'Custom cart notice', // title
            array( $this, 'custom_notice_3_callback' ), // callback
            'membership-discount-admin', // page
            'membership_discount_setting_section' // section
        );
    }

    public function membership_discount_sanitize($input) {
        $sanitary_values = array();
        if ( isset( $input['discount_product_0'] ) ) {
            $sanitary_values['discount_product_0'] = $input['discount_product_0'];
        }

        if ( isset( $input['start_date_1'] ) ) {
            $sanitary_values['start_date_1'] = sanitize_text_field( $input['start_date_1'] );
        }

        if ( isset( $input['mid_month_2'] ) ) {
            $sanitary_values['mid_month_2'] = sanitize_text_field( $input['mid_month_2'] );
        }
        if ( isset( $input['custom_notice_3'] ) ) {
            $sanitary_values['custom_notice_3'] = sanitize_text_field( $input['custom_notice_3'] );
        }
        $membership_discount_options = get_option( 'membership_discount_option_name' );
        if ( $sanitary_values['discount_product_0'] !==  $membership_discount_options['discount_product_0'] ) {
//            $this->create_coupons($sanitary_values['discount_product_0']);
        }
        return $sanitary_values;
    }

    public function membership_discount_section_info() {

    }

    public function discount_product_0_callback() {
        $current = (isset( $this->membership_discount_options['discount_product_0'] )) ? $this->membership_discount_options['discount_product_0'] : '' ;
//        print_r($current);
        ?> <select name="membership_discount_option_name[discount_product_0][]" id="discount_product_0" multiple="multiple">
            <?php
            $args = array(
                'taxonomy' => 'product_cat',
                'hide_empty' => false
            );
            $product_cats = get_categories( $args );
            foreach($product_cats as $product_cat) {
                $selected = '';
                if ( in_array($product_cat->term_id, $current) ) {
                    $selected = 'selected="selected"';
                }
                echo '<option value="'.$product_cat->term_id.'" '.$selected.'>'.$product_cat->name.'</option>';
            }
            ?>
        </select> <?php
    }

    public function start_date_1_callback() {
        printf(
            '<input class="regular-text datepicker" type="text" name="membership_discount_option_name[start_date_1]" id="start_date_1" value="%s">',
            isset( $this->membership_discount_options['start_date_1'] ) ? esc_attr( $this->membership_discount_options['start_date_1']) : ''
        );
    }

    public function mid_month_2_callback() {
        printf(
            '<input class="regular-text" type="text" name="membership_discount_option_name[mid_month_2]" id="mid_month_2" value="%s">',
            isset( $this->membership_discount_options['mid_month_2'] ) ? esc_attr( $this->membership_discount_options['mid_month_2']) : ''
        );
    }
    public function custom_notice_3_callback() {
        printf(
            '<input class="regular-text" type="text" name="membership_discount_option_name[custom_notice_3]" id="custom_notice_3" value="%s">',
            isset( $this->membership_discount_options['custom_notice_3'] ) ? esc_attr( $this->membership_discount_options['custom_notice_3']) : ''
        );
    }

    public function enqueue_date_picker(){
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
    }

    private function create_coupons($pid) {
        $pf = new WC_Product_Factory();
        $product = $pf->get_product($pid);
        $months = 12;
        $i = 1;
        $monthly_rate = $product->price / $months;
        while ( $i < $months) {
            $coupon_code = 'discount_month_'.$i; // Code
            $amount = $monthly_rate*$i; // Amount
            $discount_type = 'fixed_product'; // Type: fixed_cart, percent, fixed_product, percent_product
            $coupon = array(
                'post_title' => $coupon_code,
                'post_content' => '',
                'post_status' => 'publish',
                'post_author' => 1,
                'post_type'		=> 'shop_coupon'
            );
            $new_coupon_id = wp_insert_post( $coupon );
            update_post_meta( $new_coupon_id, 'discount_type', $discount_type );
            update_post_meta( $new_coupon_id, 'coupon_amount', $amount );
            update_post_meta( $new_coupon_id, 'individual_use', 'no' );
            update_post_meta( $new_coupon_id, 'product_ids', $pid );
            update_post_meta( $new_coupon_id, 'exclude_product_ids', '' );
            update_post_meta( $new_coupon_id, 'usage_limit', '' );
            update_post_meta( $new_coupon_id, 'expiry_date', '' );
            update_post_meta( $new_coupon_id, 'apply_before_tax', 'yes' );
            update_post_meta( $new_coupon_id, 'free_shipping', 'no' );
            $i++;
        }
    }

}
if ( is_admin() )
    $membership_discount = new MembershipDiscount();

/*
 * Retrieve this value with:
 * $membership_discount_options = get_option( 'membership_discount_option_name' ); // Array of All Options
 * $discount_product_0 = $membership_discount_options['discount_product_0']; // Discount Product
 * $start_date_1 = $membership_discount_options['start_date_1']; // Start date
 * $end_date_2 = $membership_discount_options['end_date_2']; // End date
 */
