<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Zentrale Validierungslogik für das Labyrinth-Captcha.
 *
 * - check_token(): wird von allen Integrations-Klassen aufgerufen.
 * - init(): optionaler Schutz für generische POST-Formulare.
 */
class PCD_Labyrinth_Validator {

    /**
     * Initialisiert generische Hooks.
     *
     * Alle spezifischen Integrationen (WP-Login, WooCommerce, CF7, etc.)
     * hängen bereits über ihre eigenen Klassen an WordPress und rufen
     * hier nur check_token() auf.
     */
    public static function init() {

        // Optional: "Generische POST-Formulare" schützen,
        // wenn diese Option in den Einstellungen aktiv ist.
        add_action( 'init', array( __CLASS__, 'maybe_protect_generic_post' ) );
    }

    /**
     * Generische POST-Validierung:
     * - Nur aktiv, wenn "protect_generic_post" eingeschaltet ist.
     * - Nur, wenn ein pcd_labyrinth_token im POST vorhanden ist.
     * - Verwendet check_token() für die eigentliche Prüfung.
     */
    public static function maybe_protect_generic_post() {

        if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
            return;
        }

        if ( empty( $_POST['pcd_labyrinth_token'] ) ) {
            // Formular nutzt unser Captcha gar nicht → nichts tun
            return;
        }

        if ( ! function_exists( 'pcd_labyrinth_get_settings' ) ) {
            return;
        }

        $opts = pcd_labyrinth_get_settings();
        if ( empty( $opts['protect_generic_post'] ) ) {
            return;
        }

        $check = self::check_token();

        if ( is_wp_error( $check ) ) {
            // Generischer Abbruch, wenn kein spezieller Integrations-Handler existiert
            wp_die(
                esc_html( $check->get_error_message() ),
                esc_html__( 'Sicherheitsüberprüfung fehlgeschlagen', 'pcd-labyrinth-captcha' ),
                array( 'response' => 403 )
            );
        }
    }

    /**
     * Zentrale Prüf-Funktion.
     *
     * Wird von allen Integrationen aufgerufen:
     * - WordPress Auth / Register / Lost Password
     * - WooCommerce Checkout / Login / Register / Reviews
     * - Comments
     * - Contact Form 7
     * - WPForms
     * - Fluent Forms
     * - Gravity Forms
     * - Elementor Forms
     *
     * Rückgabe:
     *   - true        → Captcha gültig
     *   - WP_Error    → Captcha ungültig / abgelaufen / manipuliert
     *
     * @return true|\WP_Error
     */
    public static function check_token() {

        // Nur POST-Anfragen sind sinnvoll
        if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
            return new WP_Error(
                'pcd_labyrinth_invalid_method',
                __( 'Ungültige Anfrage.', 'pcd-labyrinth-captcha' )
            );
        }

        // ------------------------------
        // 1) Nonce prüfen
        // ------------------------------
        if ( empty( $_POST['pcd_labyrinth_nonce'] ) ) {
            return new WP_Error(
                'pcd_labyrinth_missing_nonce',
                __( 'Sicherheitsprüfung fehlgeschlagen. Bitte versuche es erneut.', 'pcd-labyrinth-captcha' )
            );
        }

        $raw_nonce = wp_unslash( $_POST['pcd_labyrinth_nonce'] );
        $nonce     = sanitize_text_field( $raw_nonce );

        if ( ! wp_verify_nonce( $nonce, 'pcd_labyrinth_captcha' ) ) {
            return new WP_Error(
                'pcd_labyrinth_nonce_failed',
                __( 'Sicherheitsprüfung fehlgeschlagen. Bitte Formular neu laden.', 'pcd-labyrinth-captcha' )
            );
        }

        // ------------------------------
        // 2) Challenge-ID & Token einsammeln
        // ------------------------------
        $challenge_id = isset( $_POST['pcd_labyrinth_challenge_id'] )
            ? sanitize_text_field( wp_unslash( $_POST['pcd_labyrinth_challenge_id'] ) )
            : '';

        $token = isset( $_POST['pcd_labyrinth_token'] )
            ? sanitize_text_field( wp_unslash( $_POST['pcd_labyrinth_token'] ) )
            : '';

        if ( '' === $challenge_id || '' === $token ) {
            return new WP_Error(
                'pcd_labyrinth_missing',
                __( 'Bitte löse das Labyrinth-Captcha.', 'pcd-labyrinth-captcha' )
            );
        }

        // ------------------------------
        // 3) Gespeicherten Hash aus Transient lesen
        // ------------------------------
        $transient_key = 'pcd_labyrinth_' . $challenge_id;

        $stored_hash = get_transient( $transient_key );

        // Egal ob Erfolgs- oder Fehlerfall → Einmal-Challenge
        delete_transient( $transient_key );

        if ( false === $stored_hash || '' === $stored_hash ) {
            return new WP_Error(
                'pcd_labyrinth_expired',
                __( 'Die Labyrinth-Aufgabe ist abgelaufen. Bitte Formular neu laden.', 'pcd-labyrinth-captcha' )
            );
        }

        // ------------------------------
        // 4) Hash neu berechnen und vergleichen
        // ------------------------------
        $calculated_hash = wp_hash( $token . '|' . $challenge_id );

        // String-Cast für maximale Typ-Sicherheit
        $stored_hash     = (string) $stored_hash;
        $calculated_hash = (string) $calculated_hash;

        if ( ! hash_equals( $stored_hash, $calculated_hash ) ) {
            return new WP_Error(
                'pcd_labyrinth_wrong',
                __( 'Die Labyrinth-Lösung ist leider falsch.', 'pcd-labyrinth-captcha' )
            );
        }

        // Alles sauber
        return true;
    }
}
