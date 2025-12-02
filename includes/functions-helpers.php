<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function pcd_labyrinth_get_settings() {
    $defaults = array(
        // WordPress Core
        'protect_wp_login'        => 0,
        'protect_wp_register'     => 0,
        'protect_wp_lostpassword' => 0,
        'protect_comments'        => 1,

        // WooCommerce
        'protect_wc_checkout' => 0,
        'protect_wc_login'    => 0,
        'protect_wc_register' => 0,
        'protect_wc_reviews'  => 0,

        // Form-Plugins
        'protect_cf7'          => 0,
        'protect_fluentforms'  => 0,
        'protect_wpforms'      => 0,
        'protect_gforms'       => 0,

        // Generic / Other
        'protect_generic_post' => 0,
        'protect_elementor'    => 0,

        // Labyrinth
        'difficulty'           => 'medium',
    );

    $opts = get_option( 'pcd_labyrinth_settings', array() );
    return wp_parse_args( $opts, $defaults );
}