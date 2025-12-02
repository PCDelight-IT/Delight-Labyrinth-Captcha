<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PCD_Labyrinth_Integration_WP_Auth {

    public static function init() {

        // Debug: Klasse & Init laufen
        error_log("### WP_AUTH INIT RUNNING (PCD_Labyrinth_Integration_WP_Auth::init) ###");

        // Doppelte Initialisierung vermeiden
        static $done = false;
        if ( $done ) {
            return;
        }
        $done = true;

        // Haken an die Core-Login-Formulare
        add_action( 'login_form',        array( __CLASS__, 'render_on_login' ) );
        add_action( 'register_form',     array( __CLASS__, 'render_on_register' ) );
        add_action( 'lostpassword_form', array( __CLASS__, 'render_on_lostpassword' ) );

        // Validierungen
        add_filter( 'authenticate',          array( __CLASS__, 'validate_login' ),      30, 3 );
        add_filter( 'registration_errors',   array( __CLASS__, 'validate_register' ),   10, 3 );
        add_action( 'lostpassword_post',     array( __CLASS__, 'validate_lostpassword'), 10, 1 );
    }

    /**
     * Captcha im normalen WP-Login-Formular ausgeben
     */
    public static function render_on_login() {

        error_log("### WP_AUTH render_on_login HOOK REACHED ###");

        $opts = pcd_labyrinth_get_settings();
        if ( empty( $opts['protect_wp_login'] ) ) {
            error_log("### WP_AUTH: protect_wp_login = 0 → nichts rendern ###");
            return;
        }

        // Captcha direkt rendern (NICHT als Shortcode!)
        echo '<div class="pcd-labyrinth-login">';
        echo PCD_Labyrinth_Shortcode::render(); // <-- direkte Ausgabe
        echo '</div>';
    }

    /**
     * Captcha im WP-Registrierungsformular ausgeben
     */
    public static function render_on_register() {

        error_log("### WP_AUTH render_on_register HOOK REACHED ###");

        $opts = pcd_labyrinth_get_settings();
        if ( empty( $opts['protect_wp_register'] ) ) {
            error_log("### WP_AUTH: protect_wp_register = 0 → nichts rendern ###");
            return;
        }

        echo '<div class="pcd-labyrinth-register">';
        echo PCD_Labyrinth_Shortcode::render();
        echo '</div>';
    }

    /**
     * Captcha im Passwort-vergessen-Formular ausgeben
     */
    public static function render_on_lostpassword() {

        error_log("### WP_AUTH render_on_lostpassword HOOK REACHED ###");

        $opts = pcd_labyrinth_get_settings();
        if ( empty( $opts['protect_wp_lostpassword'] ) ) {
            error_log("### WP_AUTH: protect_wp_lostpassword = 0 → nichts rendern ###");
            return;
        }

        echo '<div class="pcd-labyrinth-lostpassword">';
        echo PCD_Labyrinth_Shortcode::render();
        echo '</div>';
    }

    // ============================================================
    // VALIDIERUNGEN
    // ============================================================

    public static function validate_login( $user, $username, $password ) {

        $opts = pcd_labyrinth_get_settings();
        if ( empty( $opts['protect_wp_login'] ) ) {
            return $user;
        }

        $result = PCD_Labyrinth_Validator::check_token();

        if ( is_wp_error( $result ) ) {
            // WP zeigt die Fehlermeldung automatisch im Login an
            return $result;
        }

        return $user;
    }

    public static function validate_register( $errors, $sanitized_user_login, $user_email ) {

        $opts = pcd_labyrinth_get_settings();
        if ( empty( $opts['protect_wp_register'] ) ) {
            return $errors;
        }

        $result = PCD_Labyrinth_Validator::check_token();

        if ( is_wp_error( $result ) ) {
            $errors->add(
                $result->get_error_code(),
                $result->get_error_message()
            );
        }

        return $errors;
    }

    public static function validate_lostpassword( $errors ) {

        $opts = pcd_labyrinth_get_settings();
        if ( empty( $opts['protect_wp_lostpassword'] ) ) {
            return;
        }

        $result = PCD_Labyrinth_Validator::check_token();

        if ( is_wp_error( $result ) ) {
            $errors->add(
                $result->get_error_code(),
                $result->get_error_message()
            );
        }
    }
}
