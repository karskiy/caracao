<?php
// @see https://rudrastyh.com/woocommerce/checkout-fields.html
// @see https://woocommerce.com/document/tutorial-customising-checkout-fields-using-actions-and-filters/

add_action('wp_enqueue_scripts', 'my_theme_enqueue_styles');
function my_theme_enqueue_styles()
{
    $parenthandle = 'parent-style'; // This is 'twentyfifteen-style' for the Twenty Fifteen theme.
    $theme = wp_get_theme();
    wp_enqueue_style($parenthandle,
        get_template_directory_uri() . '/style.css',
        array(),  // If the parent theme code has a dependency, copy it to here.
        $theme->parent()->get('Version')
    );
    wp_enqueue_style('child-style',
        get_stylesheet_uri(),
        array($parenthandle),
        $theme->get('Version') // This only works if you have Version defined in the style header.
    );
}

/**
 * @snippet       Rename State Field Label @ WooCommerce Checkout
 * @author        Code = Poetry
 */

add_filter('woocommerce_default_address_fields', 'bbloomer_rename_state_province', 9999);

function bbloomer_rename_state_province($fields)
{
    $fields['city']['label'] = 'Город';
    return $fields;
}


/**
 * @snippet       Move Order Notes @ WooCommerce Checkout
 * @author        Code = Poetry
 */

// 1. Hide default notes

add_filter('woocommerce_enable_order_notes_field', '__return_false');

// 2. Create new billing field

add_filter('woocommerce_checkout_fields', 'bbloomer_custom_order_notes');

function bbloomer_custom_order_notes($fields)
{
    $fields['billing']['new_order_notes'] = array(
        'type' => 'textarea',
        'label' => 'Примечание к заказу',
        'class' => array('form-row-wide'),
        'clear' => true,
        'priority' => 999,
    );
    return $fields;
}

// 3. Save to existing order notes

add_action('woocommerce_checkout_update_order_meta', 'bbloomer_custom_field_value_to_order_notes', 10, 2);

function bbloomer_custom_field_value_to_order_notes($order_id, $data)
{
    if (!is_object($order_id)) {
        $order = wc_get_order($order_id);
    }
    $order->set_customer_note(isset($data['new_order_notes']) ? $data['new_order_notes'] : '');
    wc_create_order_note($order_id, $data['new_order_notes'], true, true);
    $order->save();
}


add_filter('woocommerce_form_field', 'woo_remove_checkout_optional_text', 10, 4);
function woo_remove_checkout_optional_text($field, $key, $args, $value)
{
    if (is_checkout() && !is_wc_endpoint_url()) {
        $optional = '&nbsp;<span class="optional">(' . esc_html__('optional', 'woocommerce') . ')</span>';
        $field = str_replace($optional, '', $field);
    }
    return $field;
}


// Conditional Show hide checkout fields based on chosen payment methods
add_action('wp_footer', 'conditionally_show_hide_billing_custom_field');
function conditionally_show_hide_billing_custom_field()
{
    // Только на checkout page
    if (is_checkout() && !is_wc_endpoint_url()) :
        ?>
        <script>
            jQuery(function ($) {

                const poleHide1 = "#inn_field";
                const poleHide2 = "#billing_company_field";
                const fizik = "Физическое лицо";

                // скрываем по умолчанию
                $(poleHide1).hide();
                $(poleHide2).hide();

                $("#tip-pokupatelya").on('change', function () {
                    let value = $(this).val();
                    if (value && value != fizik) {
                        $(poleHide1).show('slow');
                        $(poleHide2).show('slow');
                    } else {
                        $(poleHide1).hide('slow');
                        $(poleHide2).hide('slow');
                    }
                });
            });
        </script>
    <?php

    endif;
}

add_action( 'woocommerce_checkout_process', 'bbloomer_matching_email_addresses' );

function bbloomer_matching_email_addresses() {
    $poleInn = $_POST['inn'];
    $poleTip = $_POST['tip-pokupatelya'];

    if($poleTip=='') return;

    if ( $poleTip != 'Физическое лицо' && $poleInn == '' ) {

        wc_add_notice( '<strong>Заполните пожалуйста поля:</strong> ИНН и Название компании', 'error' );
    }
}

add_action('woocommerce_share', function (){
    echo 1;
});