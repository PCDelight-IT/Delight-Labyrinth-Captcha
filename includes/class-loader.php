<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PCD_Labyrinth_Loader {

    public static function init() {
        self::includes();
        self::hooks();
    }

    /* ===========================================
       BENÖTIGTE PHP-KLASSEN LADEN
    ============================================ */

    protected static function includes() {

        // Core
        require_once PCD_LAB_PLUGIN_PATH . 'includes/functions-helpers.php';
        require_once PCD_LAB_PLUGIN_PATH . 'includes/class-settings.php';
        require_once PCD_LAB_PLUGIN_PATH . 'includes/class-shortcode.php';
        require_once PCD_LAB_PLUGIN_PATH . 'includes/class-validator.php';

        // Integrationen, die BEHALTEN bleiben:
        require_once PCD_LAB_PLUGIN_PATH . 'includes/class-integration-comment.php';     // optional
        require_once PCD_LAB_PLUGIN_PATH . 'includes/class-integration-elementor.php';
        require_once PCD_LAB_PLUGIN_PATH . 'includes/class-integration-wp-auth.php';
        require_once PCD_LAB_PLUGIN_PATH . 'includes/class-integration-cf7.php';

        // ❌ ENTFERNT (nicht mehr geladen):
        // require_once 'class-integration-wc.php';
        // require_once 'class-integration-fluentforms.php';
        // require_once 'class-integration-wpforms.php';
        // require_once 'class-integration-gforms.php';
    }

    /* ===========================================
       HOOKS REGISTRIEREN
    ============================================ */

    protected static function hooks() {

        // Admin Einstellungen
        add_action( 'admin_menu', array( 'PCD_Labyrinth_Settings', 'add_menu' ) );
        add_action( 'admin_init', array( 'PCD_Labyrinth_Settings', 'register_settings' ) );

        // Shortcode
        add_action( 'init', array( 'PCD_Labyrinth_Shortcode', 'register' ) );

        // Frontend Scripts & Styles
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend' ) );

        // Login Page Scripts
        add_action( 'login_enqueue_scripts', array( __CLASS__, 'enqueue_login' ) );

        // Admin Styles
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin' ) );

        // Validator
        PCD_Labyrinth_Validator::init();

        // WordPress Core Integrationen
        PCD_Labyrinth_Integration_Comment::init();      // optional
        PCD_Labyrinth_Integration_Elementor::init();
        PCD_Labyrinth_Integration_WP_Auth::init();

        // Contact Form 7 Integration (korrekt über wpcf7_init)
        add_action(
            'wpcf7_init',
            array( 'PCD_Labyrinth_Integration_CF7', 'init' ),
            20
        );

        // ❌ ENTFERNT:
        // PCD_Labyrinth_Integration_WC::init();
        // PCD_Labyrinth_Integration_FluentForms::init();
        // PCD_Labyrinth_Integration_WPForms::init();
        // PCD_Labyrinth_Integration_GForms::init();
    }


    /* ===========================================
       FRONTEND: CSS & JAVASCRIPT
    ============================================ */

    public static function enqueue_frontend() {

        wp_register_style(
            'pcd-labyrinth-frontend',
            PCD_LAB_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            PCD_LAB_PLUGIN_VERSION
        );

        wp_register_script(
            'pcd-maze-display',
            PCD_LAB_PLUGIN_URL . 'assets/js/maze-display.js',
            array(),
            PCD_LAB_PLUGIN_VERSION,
            true
        );

        wp_register_script(
            'pcd-path-captcha',
            PCD_LAB_PLUGIN_URL . 'assets/js/path-captcha.js',
            array('pcd-maze-display'),
            PCD_LAB_PLUGIN_VERSION,
            true
        );
    }


    /* ===========================================
       LOGIN: CSS & JS für /wp-login.php
    ============================================ */

    public static function enqueue_login() {

        wp_enqueue_style(
            'pcd-labyrinth-frontend',
            PCD_LAB_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            PCD_LAB_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'pcd-maze-display',
            PCD_LAB_PLUGIN_URL . 'assets/js/maze-display.js',
            array(),
            PCD_LAB_PLUGIN_VERSION,
            true
        );

        wp_enqueue_script(
            'pcd-path-captcha',
            PCD_LAB_PLUGIN_URL . 'assets/js/path-captcha.js',
            array('pcd-maze-display'),
            PCD_LAB_PLUGIN_VERSION,
            true
        );

        error_log("### LOGIN ASSETS LOADED (PCD_Labyrinth_Loader::enqueue_login) ###");
    }


    /* ===========================================
       ADMIN CSS
    ============================================ */

    public static function enqueue_admin( $hook ) {

        if ( $hook !== 'settings_page_pcd-labyrinth-captcha' ) {
            return;
        }

        wp_enqueue_style(
            'pcd-labyrinth-admin',
            PCD_LAB_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            PCD_LAB_PLUGIN_VERSION
        );
    }
}
