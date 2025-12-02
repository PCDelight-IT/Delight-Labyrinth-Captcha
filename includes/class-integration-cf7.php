<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PCD_Labyrinth_Integration_CF7 {

    public static function init() {

        error_log('LABY_CF7: init() aufgerufen');

        static $done = false;
        if ( $done ) {
            return;
        }
        $done = true;

        if ( ! defined( 'WPCF7_VERSION' ) ) {
            error_log('LABY_CF7: WPCF7_VERSION NICHT definiert → CF7 nicht geladen');
            return;
        }

        error_log('LABY_CF7: WPCF7_VERSION gefunden → CF7 aktiv');

        // CF7-Formtag registrieren: [pcd_labyrinth_captcha] / [pcd_labyrinth_captcha*]
        wpcf7_add_form_tag(
            array( 'pcd_labyrinth_captcha', 'pcd_labyrinth_captcha*' ),
            array( __CLASS__, 'render_captcha' ),
            array(
                'name-attr'      => true,
                'form-control'   => true,
                'display-block'  => true,
                'not-for-mail'   => true,
            )
        );
        error_log('LABY_CF7: Formtag pcd_labyrinth_captcha registriert');

        // Validator registrieren
        add_filter(
            'wpcf7_validate_pcd_labyrinth_captcha',
            array( __CLASS__, 'validate_captcha' ),
            10,
            2
        );
    }

    /**
     * Rendert das Captcha-Feld für CF7
     */
    public static function render_captcha( $tag ) {

        $name = $tag->name;

        // Alignment aus Einstellungen holen
        $alignment   = get_option( 'pcd_laby_alignment', 'center' );
        $align_class = 'pcd-align-' . $alignment;

        wp_enqueue_style('pcd-labyrinth-frontend');
        wp_enqueue_script('pcd-maze-display');
        wp_enqueue_script('pcd-path-captcha');

        $nonce        = wp_create_nonce('pcd_labyrinth_captcha');
        $challenge_id = wp_generate_uuid4();

        try {
            $solution     = bin2hex(random_bytes(32));
        } catch ( Exception $e ) {
            $solution = wp_generate_password(64, false, false);
        }

        $hash = wp_hash($solution . '|' . $challenge_id);

        set_transient(
            'pcd_labyrinth_' . $challenge_id,
            $hash,
            15 * MINUTE_IN_SECONDS
        );

        // Hinweistext – IMMER funktionieren in CF7
        $instruction = wp_is_mobile()
            ? 'Passenden Pfad antippen.'
            : 'Richtigen Pfad ins Labyrinth ziehen.';

        ob_start();
        ?>

<span class="wpcf7-form-control-wrap <?php echo esc_attr( $name ); ?>">

    <div class="pcd-path-captcha-wrapper <?php echo esc_attr( $align_class ); ?>">

        <div class="pcd-maze-canvas"></div>

        <!-- HINWEISTEXT (muss ein DIV sein, nicht P → CF7 löscht sonst) -->
        <div class="pcd-labyrinth-instruction">
            <?php echo esc_html( $instruction ); ?>
        </div>

        <div class="pcd-path-options"></div>

        <!-- SERVER INPUTS -->
        <input type="hidden" name="pcd_labyrinth_nonce" value="<?php echo esc_attr( $nonce ); ?>">
        <input type="hidden" name="pcd_labyrinth_challenge_id" value="<?php echo esc_attr( $challenge_id ); ?>">
        <input type="hidden" class="pcd-labyrinth-expected" value="<?php echo esc_attr( $solution ); ?>">
        <input type="hidden" name="pcd_labyrinth_token" class="pcd-labyrinth-token" value="">

        <!-- Entscheidend für CF7: Feldname für die Validierung -->
        <input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="sent">

        <p class="pcd-labyrinth-status"></p>

    </div>

</span>

        <?php
        return ob_get_clean();
    }

    /**
     * Validiert das Captcha
     */
    public static function validate_captcha( $result, $tag ) {

        $name = $tag->name;
        error_log('LABY_CF7: validate() name=' . $name);

        // Token aus POST holen
        $token = isset($_POST['pcd_labyrinth_token'])
            ? sanitize_text_field( wp_unslash( $_POST['pcd_labyrinth_token'] ) )
            : '';

        // FALL 1: Token fehlt → Captcha nicht gelöst
        if ( empty( $token ) ) {

            error_log('LABY_CF7: Token leer → invalid');

            $result->invalidate(
                $name,   // "captcha"
                __( 'bitte captcha lösen', 'pcd-labyrinth-captcha' )
            );

            return $result;
        }

        // FALL 2: Serverseitige Prüfung
        $check = PCD_Labyrinth_Validator::check_token();

        // FALL 3: Validator liefert Fehler
        if ( is_wp_error( $check ) ) {

            error_log('LABY_CF7: Validator-Fehler: ' . $check->get_error_message());

            $result->invalidate(
                $name,
                __( 'bitte captcha lösen', 'pcd-labyrinth-captcha' )
            );

            return $result;
        }

        // FALL 4: Alles OK
        error_log('LABY_CF7: Captcha OK');
        return $result;
    }
}
