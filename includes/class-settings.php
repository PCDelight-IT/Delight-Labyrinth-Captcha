<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PCD_Labyrinth_Settings {

    public static function add_menu() {
        add_options_page(
            __( 'PCD Labyrinth Captcha', 'pcd-labyrinth-captcha' ),
            __( 'Labyrinth Captcha', 'pcd-labyrinth-captcha' ),
            'manage_options',
            'pcd-labyrinth-captcha',
            array( __CLASS__, 'render_page' )
        );
    }

    public static function register_settings() {

        // Hauptsettings speichern
        register_setting(
            'pcd_labyrinth_settings_group',
            'pcd_labyrinth_settings',
            array( __CLASS__, 'sanitize' )
        );

        /* ---------------------------
           GRUND-EINSTELLUNGEN
        ----------------------------*/
        add_settings_section(
            'pcd_labyrinth_main',
            __( 'Grundeinstellungen', 'pcd-labyrinth-captcha' ),
            '__return_false',
            'pcd-labyrinth-captcha'
        );

        add_settings_field(
            'difficulty',
            __( 'Schwierigkeit', 'pcd-labyrinth-captcha' ),
            array( __CLASS__, 'field_difficulty' ),
            'pcd-labyrinth-captcha',
            'pcd_labyrinth_main'
        );

        /* ---------------------------
           AUSRICHTUNG
        ----------------------------*/
        add_settings_section(
            'pcd_labyrinth_alignment',
            __( 'Darstellung', 'pcd-labyrinth-captcha' ),
            '__return_false',
            'pcd-labyrinth-captcha'
        );

        add_settings_field(
            'pcd_laby_alignment',
            __( 'Captcha Ausrichtung', 'pcd-labyrinth-captcha' ),
            array(__CLASS__, 'field_alignment'),
            'pcd-labyrinth-captcha',
            'pcd_labyrinth_alignment'
        );

        register_setting(
            'pcd_labyrinth_settings_group',
            'pcd_laby_alignment',
            array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => 'center'
            )
        );

        /* ---------------------------
           WO SOLL DAS CAPTCHA AKTIV SEIN?
        ----------------------------*/
        add_settings_section(
            'pcd_labyrinth_where',
            __( 'Wo soll das Captcha verwendet werden?', 'pcd-labyrinth-captcha' ),
            '__return_false',
            'pcd-labyrinth-captcha'
        );

        // WordPress Core
        add_settings_field(
            'where_wp_core',
            __( 'WordPress Core', 'pcd-labyrinth-captcha' ),
            array( __CLASS__, 'field_where_wp_core' ),
            'pcd-labyrinth-captcha',
            'pcd_labyrinth_where'
        );

        // Formular-Plugins (nur CF7)
        add_settings_field(
            'where_forms',
            __( 'Formulare', 'pcd-labyrinth-captcha' ),
            array( __CLASS__, 'field_where_forms' ),
            'pcd-labyrinth-captcha',
            'pcd_labyrinth_where'
        );

        // Elementor
        add_settings_field(
            'where_other',
            __( 'Weitere Bereiche', 'pcd-labyrinth-captcha' ),
            array( __CLASS__, 'field_where_other' ),
            'pcd-labyrinth-captcha',
            'pcd_labyrinth_where'
        );
    }

    /* ---------------------------
       SANITIZE
    ----------------------------*/
    public static function sanitize( $input ) {
        $out = array();

        // Nur diese Keys bleiben übrig
        $bool_keys = array(
            'protect_wp_login',
            'protect_wp_register',
            'protect_wp_lostpassword',
            'protect_comments',

            'protect_cf7',
            'protect_elementor',
        );

        foreach ( $bool_keys as $key ) {
            $out[$key] = empty( $input[$key] ) ? 0 : 1;
        }

        // Schwierigkeit validieren
        $allowed = array( 'easy', 'medium', 'hard' );
        $difficulty = isset( $input['difficulty'] ) ? $input['difficulty'] : 'medium';
        $out['difficulty'] = in_array( $difficulty, $allowed, true ) ? $difficulty : 'medium';

        return $out;
    }

    /* ---------------------------
       SETTINGS-FELDER
    ----------------------------*/

    public static function field_difficulty() {
        $opts = pcd_labyrinth_get_settings();
        ?>
        <select name="pcd_labyrinth_settings[difficulty]">
            <option value="easy"   <?php selected( $opts['difficulty'], 'easy' ); ?>>Leicht</option>
            <option value="medium" <?php selected( $opts['difficulty'], 'medium' ); ?>>Mittel</option>
            <option value="hard"   <?php selected( $opts['difficulty'], 'hard' ); ?>>Schwierig</option>
        </select>
        <?php
    }

    public static function field_alignment() {
        $value = get_option('pcd_laby_alignment', 'center');
        ?>
        <select name="pcd_laby_alignment">
            <option value="left"   <?php selected($value, 'left'); ?>>Links</option>
            <option value="center" <?php selected($value, 'center'); ?>>Mittig</option>
            <option value="right"  <?php selected($value, 'right'); ?>>Rechts</option>
        </select>
        <?php
    }

    public static function field_where_wp_core() {
        $opts = pcd_labyrinth_get_settings();
        ?>
        <div class="pcd-settings-group">
            <label><input type="checkbox" name="pcd_labyrinth_settings[protect_wp_login]" value="1" <?php checked( $opts['protect_wp_login'], 1 ); ?> /> Login schützen</label><br />
            <label><input type="checkbox" name="pcd_labyrinth_settings[protect_wp_register]" value="1" <?php checked( $opts['protect_wp_register'], 1 ); ?> /> Registrierung schützen</label><br />
            <label><input type="checkbox" name="pcd_labyrinth_settings[protect_wp_lostpassword]" value="1" <?php checked( $opts['protect_wp_lostpassword'], 1 ); ?> /> Passwort-vergessen schützen</label><br />
            <label><input type="checkbox" name="pcd_labyrinth_settings[protect_comments]" value="1" <?php checked( $opts['protect_comments'], 1 ); ?> /> Kommentare schützen</label>
        </div>
        <?php
    }

    public static function field_where_forms() {
        $opts = pcd_labyrinth_get_settings();
        ?>
        <div class="pcd-settings-group">
            <label><input type="checkbox" name="pcd_labyrinth_settings[protect_cf7]" value="1" <?php checked( $opts['protect_cf7'], 1 ); ?> /> Contact Form 7 schützen</label>
        </div>
        <?php
    }

    public static function field_where_other() {
        $opts = pcd_labyrinth_get_settings();
        ?>
        <div class="pcd-settings-group">
            <label><input type="checkbox" name="pcd_labyrinth_settings[protect_elementor]" value="1" <?php checked( $opts['protect_elementor'], 1 ); ?> /> Elementor Formulare schützen</label>
        </div>
        <?php
    }

    /* ---------------------------
       ADMIN PAGE
    ----------------------------*/
    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap pcd-labyrinth-admin-wrap">
            <h1>PCD Labyrinth Captcha</h1>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'pcd_labyrinth_settings_group' );
                do_settings_sections( 'pcd-labyrinth-captcha' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

}
