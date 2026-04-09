<?php
/**
 * Custom Post Type: Mitarbeiter (employee)
 *
 * Registriert den Inhaltstyp "Mitarbeiter" mit den Taxonomien "Standort" und "Abteilung".
 * Mitarbeiter-Posts werden automatisch ueber den CSV-Import aus SAGE DPW angelegt.
 * Fotos werden manuell ueber den WordPress-Admin hochgeladen.
 *
 * URL-Struktur:
 *   /mitarbeiter/              → Alle Mitarbeiter (Archiv)
 *   /mitarbeiter/max-muster/   → Einzelner Mitarbeiter
 *   /standort/wien/            → Alle Mitarbeiter am Standort Wien
 *   /abteilung/marketing/      → Alle Mitarbeiter in der Abteilung Marketing
 *
 * @package GWT_Intranet
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* --------------------------------------------------------------------------
   Custom Post Type registrieren
   -------------------------------------------------------------------------- */

add_action( 'init', 'gwt_register_employee_cpt' );

/**
 * Registriert den Custom Post Type "employee" (Mitarbeiter).
 */
function gwt_register_employee_cpt() {

    $labels = array(
        'name'                  => 'Mitarbeiter',
        'singular_name'         => 'Mitarbeiter',
        'menu_name'             => 'Mitarbeiter',
        'add_new'               => 'Neuen Mitarbeiter anlegen',
        'add_new_item'          => 'Neuen Mitarbeiter anlegen',
        'edit_item'             => 'Mitarbeiter bearbeiten',
        'new_item'              => 'Neuer Mitarbeiter',
        'view_item'             => 'Mitarbeiter ansehen',
        'view_items'            => 'Mitarbeiter ansehen',
        'search_items'          => 'Mitarbeiter suchen',
        'not_found'             => 'Keine Mitarbeiter gefunden',
        'not_found_in_trash'    => 'Keine Mitarbeiter im Papierkorb',
        'all_items'             => 'Alle Mitarbeiter',
        'archives'              => 'Mitarbeiter-Archiv',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,   // Wichtig fuer Bricks Builder + REST API
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'mitarbeiter', 'with_front' => false ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 5,
        'menu_icon'          => 'dashicons-groups',
        'supports'           => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
    );

    register_post_type( 'employee', $args );
}

/* --------------------------------------------------------------------------
   Taxonomie: Standort
   -------------------------------------------------------------------------- */

add_action( 'init', 'gwt_register_standort_taxonomy' );

/**
 * Registriert die Taxonomie "standort" (Standort/Standorte).
 * Hierarchisch wie Kategorien – erlaubt Untergruppierung (z.B. Wien > Zentrale).
 */
function gwt_register_standort_taxonomy() {

    $labels = array(
        'name'              => 'Standorte',
        'singular_name'     => 'Standort',
        'search_items'      => 'Standort suchen',
        'all_items'         => 'Alle Standorte',
        'parent_item'       => 'Uebergeordneter Standort',
        'parent_item_colon' => 'Uebergeordneter Standort:',
        'edit_item'         => 'Standort bearbeiten',
        'update_item'       => 'Standort aktualisieren',
        'add_new_item'      => 'Neuen Standort hinzufuegen',
        'new_item_name'     => 'Neuer Standortname',
        'menu_name'         => 'Standorte',
    );

    $args = array(
        'labels'            => $labels,
        'hierarchical'      => true,
        'public'            => true,
        'show_ui'           => true,
        'show_in_rest'      => true,    // Fuer Bricks Builder + REST API
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'standort', 'with_front' => false ),
    );

    register_taxonomy( 'standort', array( 'employee' ), $args );
}

/* --------------------------------------------------------------------------
   Taxonomie: Abteilung
   -------------------------------------------------------------------------- */

add_action( 'init', 'gwt_register_abteilung_taxonomy' );

/**
 * Registriert die Taxonomie "abteilung" (Abteilung/Abteilungen).
 */
function gwt_register_abteilung_taxonomy() {

    $labels = array(
        'name'              => 'Abteilungen',
        'singular_name'     => 'Abteilung',
        'search_items'      => 'Abteilung suchen',
        'all_items'         => 'Alle Abteilungen',
        'parent_item'       => 'Uebergeordnete Abteilung',
        'parent_item_colon' => 'Uebergeordnete Abteilung:',
        'edit_item'         => 'Abteilung bearbeiten',
        'update_item'       => 'Abteilung aktualisieren',
        'add_new_item'      => 'Neue Abteilung hinzufuegen',
        'new_item_name'     => 'Neuer Abteilungsname',
        'menu_name'         => 'Abteilungen',
    );

    $args = array(
        'labels'            => $labels,
        'hierarchical'      => true,
        'public'            => true,
        'show_ui'           => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'abteilung', 'with_front' => false ),
    );

    register_taxonomy( 'abteilung', array( 'employee' ), $args );
}

/* --------------------------------------------------------------------------
   Meta-Felder registrieren (fuer REST API + Bricks Builder)
   -------------------------------------------------------------------------- */

add_action( 'init', 'gwt_register_employee_meta' );

/**
 * Registriert alle Meta-Felder des Mitarbeiter-CPT.
 * Durch show_in_rest = true sind die Felder ueber die REST API
 * und in Bricks Builder als Dynamic Data verfuegbar.
 */
function gwt_register_employee_meta() {

    $meta_fields = array(
        '_employee_personalnummer' => array(
            'type'        => 'string',
            'description' => 'Eindeutige Personalnummer aus SAGE DPW',
        ),
        '_employee_vorname' => array(
            'type'        => 'string',
            'description' => 'Vorname des Mitarbeiters',
        ),
        '_employee_nachname' => array(
            'type'        => 'string',
            'description' => 'Nachname des Mitarbeiters',
        ),
        '_employee_email' => array(
            'type'        => 'string',
            'description' => 'E-Mail-Adresse des Mitarbeiters',
        ),
        '_employee_telefon' => array(
            'type'        => 'string',
            'description' => 'Telefonnummer (Festnetz)',
        ),
        '_employee_mobil' => array(
            'type'        => 'string',
            'description' => 'Mobilnummer',
        ),
        '_employee_position' => array(
            'type'        => 'string',
            'description' => 'Berufsbezeichnung / Position',
        ),
        '_employee_eintrittsdatum' => array(
            'type'        => 'string',
            'description' => 'Eintrittsdatum im Format YYYY-MM-DD',
        ),
        '_employee_status' => array(
            'type'        => 'string',
            'description' => 'Status: aktiv oder inaktiv',
        ),
        '_employee_wp_user_id' => array(
            'type'        => 'integer',
            'description' => 'Verknuepfter WordPress User-ID',
        ),
    );

    foreach ( $meta_fields as $key => $config ) {
        register_post_meta( 'employee', $key, array(
            'type'              => $config['type'],
            'description'       => $config['description'],
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => ( 'integer' === $config['type'] ) ? 'absint' : 'sanitize_text_field',
            'auth_callback'     => function () {
                return current_user_can( 'edit_posts' );
            },
        ) );
    }
}

/* --------------------------------------------------------------------------
   Sichtbarkeit: Nur fuer eingeloggte User
   -------------------------------------------------------------------------- */

add_action( 'pre_get_posts', 'gwt_restrict_employee_visibility' );

/**
 * Verhindert den Zugriff auf Mitarbeiter-Seiten fuer nicht eingeloggte User.
 * Leitet auf die Login-Seite weiter.
 *
 * @param WP_Query $query Die aktuelle WordPress-Abfrage.
 */
function gwt_restrict_employee_visibility( $query ) {
    // Nur im Frontend, nicht im Admin
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }

    // Nur fuer Employee-CPT und dessen Taxonomien
    $is_employee_query = is_post_type_archive( 'employee' )
        || is_singular( 'employee' )
        || is_tax( 'standort' )
        || is_tax( 'abteilung' );

    if ( $is_employee_query && ! is_user_logged_in() ) {
        wp_safe_redirect( wp_login_url( get_permalink() ) );
        exit;
    }
}

/* --------------------------------------------------------------------------
   Admin-Spalten: Personalnummer und Status in der Uebersicht
   -------------------------------------------------------------------------- */

add_filter( 'manage_employee_posts_columns', 'gwt_employee_admin_columns' );

/**
 * Fuegt Custom-Spalten zur Mitarbeiter-Uebersicht im Admin hinzu.
 *
 * @param array $columns Bestehende Spalten.
 * @return array Erweiterte Spalten.
 */
function gwt_employee_admin_columns( $columns ) {
    $new_columns = array();

    foreach ( $columns as $key => $value ) {
        $new_columns[ $key ] = $value;

        // Nach dem Titel die eigenen Spalten einfuegen
        if ( 'title' === $key ) {
            $new_columns['personalnummer'] = 'Personalnr.';
            $new_columns['position']       = 'Position';
            $new_columns['employee_email'] = 'E-Mail';
            $new_columns['employee_status'] = 'Status';
        }
    }

    return $new_columns;
}

add_action( 'manage_employee_posts_custom_column', 'gwt_employee_admin_column_content', 10, 2 );

/**
 * Befuellt die Custom-Spalten mit Inhalt.
 *
 * @param string $column  Spaltenname.
 * @param int    $post_id Post-ID.
 */
function gwt_employee_admin_column_content( $column, $post_id ) {
    switch ( $column ) {
        case 'personalnummer':
            echo esc_html( get_post_meta( $post_id, '_employee_personalnummer', true ) );
            break;

        case 'position':
            echo esc_html( get_post_meta( $post_id, '_employee_position', true ) );
            break;

        case 'employee_email':
            $email = get_post_meta( $post_id, '_employee_email', true );
            if ( $email ) {
                echo '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
            }
            break;

        case 'employee_status':
            $status = get_post_meta( $post_id, '_employee_status', true );
            if ( 'aktiv' === $status ) {
                echo '<span style="color: #48bb78; font-weight: 600;">&#9679; Aktiv</span>';
            } else {
                echo '<span style="color: #f56565; font-weight: 600;">&#9679; Inaktiv</span>';
            }
            break;
    }
}

/* --------------------------------------------------------------------------
   Admin-Filter: Nach Standort und Abteilung filtern
   -------------------------------------------------------------------------- */

add_action( 'restrict_manage_posts', 'gwt_employee_admin_filters' );

/**
 * Fuegt Dropdown-Filter fuer Standort und Abteilung in der Admin-Uebersicht hinzu.
 *
 * @param string $post_type Der aktuelle Post-Typ.
 */
function gwt_employee_admin_filters( $post_type ) {
    if ( 'employee' !== $post_type ) {
        return;
    }

    // Standort-Filter
    $standort_terms = get_terms( array(
        'taxonomy'   => 'standort',
        'hide_empty' => false,
    ) );

    if ( ! empty( $standort_terms ) && ! is_wp_error( $standort_terms ) ) {
        $selected = isset( $_GET['standort'] ) ? sanitize_text_field( wp_unslash( $_GET['standort'] ) ) : '';
        echo '<select name="standort">';
        echo '<option value="">Alle Standorte</option>';
        foreach ( $standort_terms as $term ) {
            printf(
                '<option value="%s"%s>%s (%d)</option>',
                esc_attr( $term->slug ),
                selected( $selected, $term->slug, false ),
                esc_html( $term->name ),
                (int) $term->count
            );
        }
        echo '</select>';
    }

    // Abteilungs-Filter
    $abteilung_terms = get_terms( array(
        'taxonomy'   => 'abteilung',
        'hide_empty' => false,
    ) );

    if ( ! empty( $abteilung_terms ) && ! is_wp_error( $abteilung_terms ) ) {
        $selected = isset( $_GET['abteilung'] ) ? sanitize_text_field( wp_unslash( $_GET['abteilung'] ) ) : '';
        echo '<select name="abteilung">';
        echo '<option value="">Alle Abteilungen</option>';
        foreach ( $abteilung_terms as $term ) {
            printf(
                '<option value="%s"%s>%s (%d)</option>',
                esc_attr( $term->slug ),
                selected( $selected, $term->slug, false ),
                esc_html( $term->name ),
                (int) $term->count
            );
        }
        echo '</select>';
    }
}

/* --------------------------------------------------------------------------
   Hilfsfunktion: Mitarbeiter anhand der Personalnummer finden
   -------------------------------------------------------------------------- */

/**
 * Findet einen Mitarbeiter-Post anhand der SAGE Personalnummer.
 *
 * @param string $personalnummer Die Personalnummer aus SAGE DPW.
 * @return int|false Post-ID oder false wenn nicht gefunden.
 */
function gwt_find_employee_by_personalnummer( $personalnummer ) {
    $query = new WP_Query( array(
        'post_type'      => 'employee',
        'post_status'    => array( 'publish', 'draft', 'private' ),
        'posts_per_page' => 1,
        'meta_query'     => array(
            array(
                'key'   => '_employee_personalnummer',
                'value' => sanitize_text_field( $personalnummer ),
            ),
        ),
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ) );

    if ( $query->have_posts() ) {
        return $query->posts[0];
    }

    return false;
}
