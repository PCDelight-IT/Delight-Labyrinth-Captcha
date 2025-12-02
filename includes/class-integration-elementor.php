<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
class PCD_Labyrinth_Integration_Elementor {
    public static function init() {
        if ( ! did_action( 'elementor_pro/init' ) ) return;
        $opts = pcd_labyrinth_get_settings();
        if ( empty( $opts['protect_elementor'] ) ) return;
        add_action( 'elementor_pro/forms/validation', array( __CLASS__, 'validate_elementor_form' ), 10, 2 );
    }
    public static function validate_elementor_form( $record, $ajax_handler ) {
        $fields = $record->get( 'fields' );
        $has_captcha = false;
        foreach ( $fields as $field ) {
            if ( isset( $field['field_type'] ) && 'shortcode' === $field['field_type'] ) {
                if ( isset( $field['shortcode'] ) && false !== strpos( $field['shortcode'], 'pcd_labyrinth_captcha' ) ) {
                    $has_captcha = true;
                    break;
                }
            }
        }
        if ( ! $has_captcha ) return;
        $result = PCD_Labyrinth_Validator::check_token();
        if ( is_wp_error( $result ) ) {
            $ajax_handler->add_error(
                'pcd_labyrinth_token',
                $result->get_error_message()
            );
        }
    }
}
