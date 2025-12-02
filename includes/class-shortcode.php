<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PCD_Labyrinth_Shortcode {

    public static function register() {

        // Standard-Shortcode (z.B. CF7, Elementor)
        add_shortcode( 'pcd_labyrinth_captcha_ui', array( __CLASS__, 'render' ) );

        // WP-Auth Varianten
        add_shortcode( 'pcd_labyrinth_captcha_wpa', array( __CLASS__, 'render' ) );
    }

    public static function render( $atts = array(), $content = '' ) {

        wp_enqueue_style('pcd-labyrinth-frontend');
        wp_enqueue_script('pcd-maze-display');
        wp_enqueue_script('pcd-path-captcha');

        // ----------------------------------------------------
        // Alignment aus Settings
        // ----------------------------------------------------
        $alignment   = get_option( 'pcd_laby_alignment', 'center' );
        $align_class = 'pcd-align-' . $alignment;

        // ----------------------------------------------------
        // Desktop/Mobile Hinweistext
        // ----------------------------------------------------
        $instruction = wp_is_mobile()
            ? 'Passenden Pfad antippen.'
            : 'Richtigen Pfad ins Labyrinth ziehen.';

        // ----------------------------------------------------
        // Challenge vorbereiten
        // ----------------------------------------------------
        $nonce        = wp_create_nonce('pcd_labyrinth_captcha');
        $challenge_id = wp_generate_uuid4();

        try {
            $solution_token = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            $solution_token = wp_generate_password(64, false, false);
        }

        $solution_hash = wp_hash($solution_token . '|' . $challenge_id);

        set_transient(
            'pcd_labyrinth_' . $challenge_id,
            $solution_hash,
            15 * MINUTE_IN_SECONDS
        );

        ob_start();
        ?>

        <div class="pcd-path-captcha-wrapper <?php echo esc_attr( $align_class ); ?>">

            <!-- KURZER HINWEISTEXT -->
            <p class="pcd-labyrinth-instruction">
               <?php echo esc_html( $instruction ); ?>
            </p>

            <div class="pcd-maze-canvas"></div>
            <div class="pcd-path-options"></div>

            <input type="hidden" name="pcd_labyrinth_nonce" value="<?php echo esc_attr($nonce); ?>">
            <input type="hidden" name="pcd_labyrinth_challenge_id" value="<?php echo esc_attr($challenge_id); ?>">
            <input type="hidden" class="pcd-labyrinth-expected" value="<?php echo esc_attr($solution_token); ?>">

            <input type="hidden" name="pcd_labyrinth_token" class="pcd-labyrinth-token" value="">

            <!-- FEHLERMELDUNG via JS oder Notfall-PHP -->
            <p class="pcd-labyrinth-status">
                <?php 
                    if ( isset($_POST['pcd_laby_show_error']) ) {
                        echo '<span class="pcd-status-error">' . esc_html($_POST['pcd_laby_show_error']) . '</span>';
                    }
                ?>
            </p>

        </div>

        <?php
        return ob_get_clean();
    }
}
