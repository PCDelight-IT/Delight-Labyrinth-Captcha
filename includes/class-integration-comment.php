<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
class PCD_Labyrinth_Integration_Comment {
    public static function init() {
        add_filter( 'comment_form_defaults', array( __CLASS__, 'inject_shortcode_into_comment_form' ) );
        add_filter( 'preprocess_comment', array( __CLASS__, 'validate_comment' ) );
    }
    public static function inject_shortcode_into_comment_form( $defaults ) {
        $opts = pcd_labyrinth_get_settings();
        if ( empty( $opts['protect_comments'] ) ) return $defaults;
        $captcha = do_shortcode( '[pcd_labyrinth_captcha]' );
        $defaults['comment_field'] .= $captcha;
        return $defaults;
    }
    public static function validate_comment( $commentdata ) {
        $opts = pcd_labyrinth_get_settings();
        if ( empty( $opts['protect_comments'] ) ) return $commentdata;
        $result = PCD_Labyrinth_Validator::check_token();
        if ( is_wp_error( $result ) ) {
            wp_die(
                esc_html( $result->get_error_message() ),
                esc_html__( 'Sicherheitsüberprüfung fehlgeschlagen', 'pcd-labyrinth-captcha' ),
                array( 'response' => 403 )
            );
        }
        return $commentdata;
    }
}
