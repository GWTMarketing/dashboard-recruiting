<?php
/**
 * Custom Post Type: Schwarzes Brett (bulletin_board)
 *
 * Interne Aushaenge mit automatischem Ablaufdatum.
 * Abgelaufene Eintraege werden per WP-Cron auf "Entwurf" gesetzt (nicht geloescht).
 * Sichtbar nur fuer eingeloggte Benutzer.
 *
 * @package GWT_Intranet
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* --------------------------------------------------------------------------
   Custom Post Type registrieren
   -------------------------------------------------------------------------- */

add_action( 'init', 'gwt_register_bulletin_cpt' );

/**
 * Registriert den Custom Post Type "bulletin_board" (Schwarzes Brett).
 */
function gwt_register_bulletin_cpt() {

    $labels = array(
        'name'                  => 'Schwarzes Brett',
        'singular_name'         => 'Aushang',
        'menu_name'             => 'Schwarzes Brett',
        'add_new'               => 'Neuen Aushang erstellen',
        'add_new_item'          => 'Neuen Aushang erstellen',
        'edit_item'             => 'Aushang bearbeiten',
        'new_item'              => 'Neuer Aushang',
        'view_item'             => 'Aushang ansehen',
        'search_items'          => 'Aushang suchen',
        'not_found'             => 'Keine Aushaenge gefunden',
        'not_found_in_trash'    => 'Keine Aushaenge im Papierkorb',
        'all_items'             => 'Alle Aushaenge',
        'archives'              => 'Aushang-Archiv',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'aushang', 'with_front' => false ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 6,
        'menu_icon'          => 'dashicons-megaphone',
        'supports'           => array( 'title', 'editor', 'thumbnail', 'custom-fields', 'author' ),
    );

    register_post_type( 'bulletin_board', $args );
}

/* --------------------------------------------------------------------------
   Meta-Felder registrieren
   -------------------------------------------------------------------------- */

add_action( 'init', 'gwt_register_bulletin_meta' );

/**
 * Registriert Meta-Felder fuer das Schwarze Brett.
 */
function gwt_register_bulletin_meta() {
    register_post_meta( 'bulletin_board', '_bulletin_expiry_date', array(
        'type'              => 'string',
        'description'       => 'Ablaufdatum im Format YYYY-MM-DD',
        'single'            => true,
        'show_in_rest'      => true,
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback'     => function () {
            return current_user_can( 'edit_posts' );
        },
    ) );

    register_post_meta( 'bulletin_board', '_bulletin_kategorie', array(
        'type'              => 'string',
        'description'       => 'Kategorie: allgemein, hr, events, sonstiges',
        'single'            => true,
        'show_in_rest'      => true,
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback'     => function () {
            return current_user_can( 'edit_posts' );
        },
    ) );

    register_post_meta( 'bulletin_board', '_bulletin_prioritaet', array(
        'type'              => 'string',
        'description'       => 'Prioritaet: normal oder wichtig',
        'single'            => true,
        'show_in_rest'      => true,
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback'     => function () {
            return current_user_can( 'edit_posts' );
        },
    ) );
}

/* --------------------------------------------------------------------------
   Meta-Box: Ablaufdatum im Editor
   -------------------------------------------------------------------------- */

add_action( 'add_meta_boxes', 'gwt_bulletin_add_meta_boxes' );

/**
 * Fuegt die Meta-Box "Aushang-Einstellungen" zum Editor hinzu.
 */
function gwt_bulletin_add_meta_boxes() {
    add_meta_box(
        'gwt_bulletin_settings',
        'Aushang-Einstellungen',
        'gwt_bulletin_meta_box_callback',
        'bulletin_board',
        'side',
        'high'
    );
}

/**
 * Rendert den Inhalt der Meta-Box.
 *
 * @param WP_Post $post Der aktuelle Post.
 */
function gwt_bulletin_meta_box_callback( $post ) {
    // Nonce-Feld fuer Sicherheit
    wp_nonce_field( 'gwt_bulletin_save_meta', 'gwt_bulletin_nonce' );

    $expiry_date = get_post_meta( $post->ID, '_bulletin_expiry_date', true );
    $kategorie   = get_post_meta( $post->ID, '_bulletin_kategorie', true );
    $prioritaet  = get_post_meta( $post->ID, '_bulletin_prioritaet', true );

    // Standard-Kategorie
    if ( empty( $kategorie ) ) {
        $kategorie = 'allgemein';
    }

    // Standard-Prioritaet
    if ( empty( $prioritaet ) ) {
        $prioritaet = 'normal';
    }
    ?>
    <p>
        <label for="gwt_bulletin_expiry_date"><strong>Ablaufdatum:</strong></label><br>
        <input type="date"
               id="gwt_bulletin_expiry_date"
               name="gwt_bulletin_expiry_date"
               value="<?php echo esc_attr( $expiry_date ); ?>"
               style="width: 100%;"
               min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>">
        <span class="description" style="font-size: 12px; color: #666;">
            Leer lassen = kein automatisches Ablaufdatum.
        </span>
    </p>

    <p>
        <label for="gwt_bulletin_kategorie"><strong>Kategorie:</strong></label><br>
        <select id="gwt_bulletin_kategorie" name="gwt_bulletin_kategorie" style="width: 100%;">
            <option value="allgemein" <?php selected( $kategorie, 'allgemein' ); ?>>Allgemein</option>
            <option value="hr" <?php selected( $kategorie, 'hr' ); ?>>HR / Personal</option>
            <option value="events" <?php selected( $kategorie, 'events' ); ?>>Events</option>
            <option value="sonstiges" <?php selected( $kategorie, 'sonstiges' ); ?>>Sonstiges</option>
        </select>
    </p>

    <p>
        <label for="gwt_bulletin_prioritaet"><strong>Prioritaet:</strong></label><br>
        <select id="gwt_bulletin_prioritaet" name="gwt_bulletin_prioritaet" style="width: 100%;">
            <option value="normal" <?php selected( $prioritaet, 'normal' ); ?>>Normal</option>
            <option value="wichtig" <?php selected( $prioritaet, 'wichtig' ); ?>>Wichtig</option>
        </select>
    </p>
    <?php
}

/* --------------------------------------------------------------------------
   Meta-Box speichern
   -------------------------------------------------------------------------- */

add_action( 'save_post_bulletin_board', 'gwt_bulletin_save_meta', 10, 2 );

/**
 * Speichert die Meta-Box-Daten beim Speichern eines Aushangs.
 *
 * @param int     $post_id Post-ID.
 * @param WP_Post $post    Der Post.
 */
function gwt_bulletin_save_meta( $post_id, $post ) {
    // Nonce pruefen
    if ( ! isset( $_POST['gwt_bulletin_nonce'] )
         || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gwt_bulletin_nonce'] ) ), 'gwt_bulletin_save_meta' ) ) {
        return;
    }

    // Auto-Save ignorieren
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Berechtigungen pruefen
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Ablaufdatum speichern
    if ( isset( $_POST['gwt_bulletin_expiry_date'] ) ) {
        $expiry_date = sanitize_text_field( wp_unslash( $_POST['gwt_bulletin_expiry_date'] ) );

        // Datumsformat validieren (YYYY-MM-DD)
        if ( empty( $expiry_date ) || preg_match( '/^\d{4}-\d{2}-\d{2}$/', $expiry_date ) ) {
            update_post_meta( $post_id, '_bulletin_expiry_date', $expiry_date );
        }
    }

    // Kategorie speichern
    if ( isset( $_POST['gwt_bulletin_kategorie'] ) ) {
        $kategorie = sanitize_text_field( wp_unslash( $_POST['gwt_bulletin_kategorie'] ) );
        $erlaubte  = array( 'allgemein', 'hr', 'events', 'sonstiges' );

        if ( in_array( $kategorie, $erlaubte, true ) ) {
            update_post_meta( $post_id, '_bulletin_kategorie', $kategorie );
        }
    }

    // Prioritaet speichern
    if ( isset( $_POST['gwt_bulletin_prioritaet'] ) ) {
        $prioritaet = sanitize_text_field( wp_unslash( $_POST['gwt_bulletin_prioritaet'] ) );
        $erlaubte   = array( 'normal', 'wichtig' );

        if ( in_array( $prioritaet, $erlaubte, true ) ) {
            update_post_meta( $post_id, '_bulletin_prioritaet', $prioritaet );
        }
    }
}

/* --------------------------------------------------------------------------
   Sichtbarkeit: Nur fuer eingeloggte User
   -------------------------------------------------------------------------- */

add_action( 'pre_get_posts', 'gwt_restrict_bulletin_visibility' );

/**
 * Verhindert Zugriff auf das Schwarze Brett fuer nicht eingeloggte User.
 *
 * @param WP_Query $query Die aktuelle WordPress-Abfrage.
 */
function gwt_restrict_bulletin_visibility( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }

    $is_bulletin_query = is_post_type_archive( 'bulletin_board' )
        || is_singular( 'bulletin_board' );

    if ( $is_bulletin_query && ! is_user_logged_in() ) {
        wp_safe_redirect( wp_login_url( get_permalink() ) );
        exit;
    }
}

/* --------------------------------------------------------------------------
   Admin-Spalten: Ablaufdatum und Kategorie in der Uebersicht
   -------------------------------------------------------------------------- */

add_filter( 'manage_bulletin_board_posts_columns', 'gwt_bulletin_admin_columns' );

/**
 * Fuegt Custom-Spalten zur Schwarzes-Brett-Uebersicht hinzu.
 *
 * @param array $columns Bestehende Spalten.
 * @return array Erweiterte Spalten.
 */
function gwt_bulletin_admin_columns( $columns ) {
    $new_columns = array();

    foreach ( $columns as $key => $value ) {
        $new_columns[ $key ] = $value;

        if ( 'title' === $key ) {
            $new_columns['bulletin_kategorie'] = 'Kategorie';
            $new_columns['bulletin_expiry']    = 'Ablaufdatum';
            $new_columns['bulletin_priority']  = 'Prioritaet';
        }
    }

    return $new_columns;
}

add_action( 'manage_bulletin_board_posts_custom_column', 'gwt_bulletin_admin_column_content', 10, 2 );

/**
 * Befuellt die Custom-Spalten mit Inhalt.
 *
 * @param string $column  Spaltenname.
 * @param int    $post_id Post-ID.
 */
function gwt_bulletin_admin_column_content( $column, $post_id ) {
    switch ( $column ) {
        case 'bulletin_kategorie':
            $kategorie = get_post_meta( $post_id, '_bulletin_kategorie', true );
            $labels    = array(
                'allgemein'  => 'Allgemein',
                'hr'         => 'HR / Personal',
                'events'     => 'Events',
                'sonstiges'  => 'Sonstiges',
            );
            echo esc_html( isset( $labels[ $kategorie ] ) ? $labels[ $kategorie ] : $kategorie );
            break;

        case 'bulletin_expiry':
            $expiry = get_post_meta( $post_id, '_bulletin_expiry_date', true );
            if ( $expiry ) {
                $is_expired = strtotime( $expiry ) < time();
                $style      = $is_expired ? 'color: #f56565; font-weight: 600;' : '';
                echo '<span style="' . esc_attr( $style ) . '">';
                echo esc_html( gmdate( 'd.m.Y', strtotime( $expiry ) ) );
                if ( $is_expired ) {
                    echo ' (abgelaufen)';
                }
                echo '</span>';
            } else {
                echo '<span style="color: #a0aec0;">Kein Ablaufdatum</span>';
            }
            break;

        case 'bulletin_priority':
            $priority = get_post_meta( $post_id, '_bulletin_prioritaet', true );
            if ( 'wichtig' === $priority ) {
                echo '<span style="color: #ed8936; font-weight: 600;">&#9733; Wichtig</span>';
            } else {
                echo 'Normal';
            }
            break;
    }
}
