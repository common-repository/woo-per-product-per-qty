<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://www.abc.com
 * @since             1.0
 * @package           Woo_Per_Product_Per_Qty
 *
 * @wordpress-plugin
 * Plugin Name:       Woo Per Product Per Qty
 * Description:       Woo Product Per Qty for that shop which that want to sell number of products
 * Version:           1.0
 * Author:            Jitendra Banjara
 * Author URI:        https://profiles.wordpress.org/jitendrabanjara1991
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woo-per-product-per-qty
 */
// If this file is called directly, abort.

if (!defined('ABSPATH')) {
    exit;
}

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) && !is_plugin_active_for_network('woocommerce/woocommerce.php')) {
    wp_die("<strong>Woo Per Product Per Qty</strong> plugin requires <strong>WooCommerce</strong>. Return to <a href='" . get_admin_url(null, 'plugins.php') . "'>Plugins page</a>.");
}

add_filter('woocommerce_settings_tabs_array', 'wppq_add_settings_tab', 50);
add_action('woocommerce_settings_tabs_settings_tab_demo', 'wppq_settings_tab');
add_action('woocommerce_update_options_settings_tab_demo', 'wppq_update_settings');

/**
 * Add a new settings tab to the WooCommerce settings tabs array.
 *
 * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
 * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
 */
function wppq_add_settings_tab($settings_tabs) {
    $settings_tabs['settings_tab_demo'] = __('WooCommerce Per Product Per Qty', 'woocommerce-settings-tab-demo');
    return $settings_tabs;
}

/**
 * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
 *
 * @uses woocommerce_admin_fields()
 * @uses self::get_settings()
 */
function wppq_settings_tab() {
    woocommerce_admin_fields(wppq_get_settings());
}

/**
 * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
 *
 * @uses woocommerce_update_options()
 * @uses self::get_settings()
 */
function wppq_update_settings() {
    woocommerce_update_options(wppq_get_settings());
}

/**
 * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
 *
 * @return array Array of settings for @see woocommerce_admin_fields() function.
 */
function wppq_get_settings() {
    $settings = array(
        'section_title' => array(
            'name' => __('Per Product Per Qty', 'woocommerce-settings-tab-demo'),
            'type' => 'title',
            'desc' => '',
            'id' => 'wppq_section_title'
        ),
        'per_product_per_qty' => array(
            'name' => __('Per Product Per Qty', 'woocommerce-settings-tab-demo'),
            'type' => 'text',
            'desc' => __('', 'woocommerce-settings-tab-demo'),
            'id' => 'wppq_per_product_per_qty'
        ),
        'per_cart_total_qty' => array(
            'name' => __('Total qty in cart', 'woo-per-product-per-qty'),
            'type' => 'text',
            'desc' => __('', 'woo-per-product-per-qty'),
            'id' => 'wppq_per_cart_total_qty'
        ),
        'section_end' => array(
            'type' => 'sectionend',
            'id' => 'wc_settings_tab_demo_section_end'
        )
    );
    return apply_filters('wc_settings_tab_demo_settings', $settings);
}

/**
 * Plugin Path
 *
 * @since    1.0
 * 
 */
function wppq_plugin_path() {

    return untrailingslashit(plugin_dir_path(__FILE__));
}

add_filter('woocommerce_locate_template', 'wppq_woocommerce_locate_template', 10, 3);

/**
 * Override WooCommerce Template File
 *
 * @since    1.0
 * 
 */
function wppq_woocommerce_locate_template($template, $template_name, $template_path) {
    global $woocommerce;

    $_template = $template;

    if (!$template_path)
        $template_path = $woocommerce->template_url;

    $plugin_path = wppq_plugin_path() . '/woocommerce/';

// Look within passed path within the theme - this is priority
    $template = locate_template(
            array(
                $template_path . $template_name,
                $template_name
            )
    );

// Modification: Get the template from this plugin, if it exists
    if (!$template && file_exists($plugin_path . $template_name))
        $template = $plugin_path . $template_name;

// Use default template
    if (!$template)
        $template = $_template;

// Return what we found
    return $template;
}

/**
 * Changing the minimum quantity to 1 for all the WooCommerce products
 *
 * @since    1.0
 * 
 */
function wppq_woocommerce_quantity_input_min_callback($min, $product) {
    global $woocommerce;
    $min = !empty(get_option('wppq_per_product_per_qty')) ? get_option('wppq_per_product_per_qty') : 1;
    return $min;
}

add_filter('woocommerce_quantity_input_min', 'wppq_woocommerce_quantity_input_min_callback', 10, 2);

/**
 * Changing the maximum quantity to 1 for all the WooCommerce products
 *
 * @since    1.0
 * 
 */
function wppq_woocommerce_quantity_input_max_callback($max, $product) {
    $max = !empty(get_option('wppq_per_product_per_qty')) ? get_option('wppq_per_product_per_qty') : 1;
    return $max;
}

add_filter('woocommerce_quantity_input_max', 'wppq_woocommerce_quantity_input_max_callback', 10, 2);

/**
 * Validating the quantity on add to cart action with the quantity of the same product available in the cart. 
 *
 * @since    1.0
 * 
 */
function wppq_qty_add_to_cart_validation($passed, $product_id, $quantity, $variation_id = '', $variations = '') {
    $wppq_per_product_per_qty = !empty(get_option('wppq_per_product_per_qty')) ? get_option('wppq_per_product_per_qty') : 1;
    $wppq_per_cart_total_qtyy = !empty(get_option('wppq_per_cart_total_qty')) ? get_option('wppq_per_cart_total_qty') : 5;
    $product_min = $wppq_per_product_per_qty;
    $product_max = $wppq_per_product_per_qty;
    if (!empty($product_min)) {
// min is empty
        if (false !== $product_min) {
            $new_min = $product_min;
        } else {
// neither max is set, so get out
            return $passed;
        }
    }
    if (!empty($product_max)) {
// min is empty
        if (false !== $product_max) {
            $new_max = $product_max;
        } else {
// neither max is set, so get out
            return $passed;
        }
    }
    $already_in_cart = wppq_qty_get_cart_qty($product_id);
    $product = wc_get_product($product_id);
    $product_title = $product->get_title();

    if (!empty($already_in_cart)) {

        if (( $already_in_cart + $quantity ) > $new_max) {
// oops. too much.
            $passed = false;
            wc_add_notice(apply_filters('isa_wc_max_qty_error_message_already_had', sprintf(__('You can add a maximum of %1$s %2$s\'s to %3$s. You already have %4$s.', 'woo-per-product-per-qty'), $new_max, $product_title, '<a href="' . esc_url(wc_get_cart_url()) . '">' . __('your cart', 'woo-per-product-per-qty') . '</a>', $already_in_cart), $new_max, $already_in_cart), 'error');
        }
        if ($already_in_cart == $wppq_per_cart_total_qtyy) {
            wc_add_notice(apply_filters('isa_wc_max_qty_error_message_already_had', sprintf(__('You can add a maximum of %1$s %2$s\'s to %3$s. You already have %4$s.', 'woo-per-product-per-qty'), '5', 'product', '<a href="' . esc_url(wc_get_cart_url()) . '">' . __('your cart', 'woo-per-product-per-qty') . '</a>', $already_in_cart), $new_max, $already_in_cart), 'error');
        }
    }
    return $passed;
}

add_filter('woocommerce_add_to_cart_validation', 'wppq_qty_add_to_cart_validation', 1, 5);

/**
 * Get the total quantity of the product available in the cart.
 *
 * @since    1.0
 * 
 */
function wppq_qty_get_cart_qty($product_id) {
    global $woocommerce;
    $running_qty = 0;
    foreach ($woocommerce->cart->get_cart() as $other_cart_item_keys => $values) {
        if ($product_id == $values['product_id']) {
            $running_qty += (int) $values['quantity'];
        }
    }
    return $running_qty;
}

add_action('woocommerce_check_cart_items', 'wppq_validate_cart_max_quantity', 10, 0);

/**
 * Validate Cart Max Qty
 *
 * @since    1.0
 * 
 */
function wppq_validate_cart_max_quantity() {

    global $woocommerce;

    $cart_max_quanity = !empty(get_option('wppq_per_cart_total_qty')) ? get_option('wppq_per_cart_total_qty') : 5;

    $cartQty = $woocommerce->cart->get_cart_item_quantities();

    $cart_quantity = 0;

    foreach ($cartQty as $key => $value) {
        $cart_quantity += $value;
    }

    if ($cart_max_quanity < $cart_quantity) {

        wc_add_notice(sprintf(__('You can’t have more than %s items in cart', 'woo-per-product-per-qty'), $cart_max_quanity), 'error');
    }
}

add_action('woocommerce_check_cart_items', 'wppq_validate_cart_min_items', 10, 0);

/**
 * Validate Cart Min Qty
 *
 * @since    1.0
 * 
 */
function wppq_validate_cart_min_items() {

    global $woocommerce;

    $cart_min_quanity = !empty(get_option('wppq_per_cart_total_qty')) ? get_option('wppq_per_cart_total_qty') : 5;

    $cartQty = $woocommerce->cart->get_cart_item_quantities();

    $cart_quantity = 0;

    foreach ($cartQty as $key => $value) {

        $cart_quantity += $value;
    }

    if ($cart_min_quanity > $cart_quantity) {

        wc_add_notice(sprintf(__('You need to buy minimum %s products', 'woo-per-product-per-qty'), $cart_min_quanity), 'error');
    }
}
