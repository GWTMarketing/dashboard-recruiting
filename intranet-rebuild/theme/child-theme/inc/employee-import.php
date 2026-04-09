<?php
/**
 * Mitarbeiter-Import: REST-Endpoint + Helfer-Funktionen
 *
 * Stellt einen Custom REST-Endpoint bereit, ueber den n8n neue Mitarbeiter
 * anlegen, bestehende aktualisieren und ausgeschiedene deaktivieren kann.
 *
 * Endpoints:
 *   POST /wp-json/gwt/v1/employee/import      → Anlegen oder aktualisieren
 *   POST /wp-json/gwt/v1/employee/deactivate   → Deaktivieren
 *
 * Jede Aktion erstellt/aktualisiert sowohl den WP-User (fuer Login) als auch
 * den Employee-CPT-Post (fuer die Profilseite). Verknuepfung ueber Personalnummer.
 *
 * @package GWT_Intranet
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ==========================================================================
   REST API Endpoints registrieren
   ========================================================================== */

add_action( 'rest_api_init', 'gwt_register_import_endpoints' );

/**
 * Registriert die Import-Endpoints.
 */
function gwt_register_import_endpoints() {
    // Mitarbeiter anlegen oder aktualisieren
    register_rest_route( 'gwt/v1', '/employee/import', array(
        'methods'             => 'POST',
        'callback'            => 'gwt_employee_import_callback',
        'permission_callback' => 'gwt_import_permission_check',
        'args'                => gwt_get_import_args(),
    ) );

    // Mitarbeiter deaktivieren
    register_rest_route( 'gwt/v1', '/employee/deactivate', array(
        'methods'             => 'POST',
        'callback'            => 'gwt_employee_deactivate_callback',
        'permission_callback' => 'gwt_import_permission_check',
        'args'                => array(
            'personalnummer' => array(
                'required'          => true,
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ) );
}

/**
 * Berechtigungspruefung: Nur Admins und n8n-API-User duerfen importieren.
 *
 * @return bool|WP_Error
 */
function gwt_import_permission_check() {
    if ( current_user_can( 'manage_options' ) ) {
        return true;
    }

    $user = wp_get_current_user();
    if ( in_array( 'n8n_api_user', (array) $user->roles, true ) ) {
        return true;
    }

    return new WP_Error(
        'rest_forbidden',
        'Keine Berechtigung fuer den Mitarbeiter-Import.',
        array( 'status' => 403 )
    );
}

/**
 * Definiert die erwarteten Parameter fuer den Import-Endpoint.
 *
 * @return array
 */
function gwt_get_import_args() {
    return array(
        'personalnummer' => array(
            'required'          => true,
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ),
        'vorname' => array(
            'required'          => true,
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ),
        'nachname' => array(
            'required'          => true,
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ),
        'email' => array(
            'required'          => true,
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_email',
        ),
        'telefon' => array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ),
        'mobil' => array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ),
        'position' => array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ),
        'standort' => array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ),
        'abteilung' => array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ),
        'eintrittsdatum' => array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ),
        'status' => array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'aktiv',
        ),
        'passwort' => array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ),
    );
}

/* ==========================================================================
   Import-Callback: Mitarbeiter anlegen oder aktualisieren
   ========================================================================== */

/**
 * Verarbeitet den Import eines Mitarbeiters.
 * Legt WP-User + Employee-CPT an oder aktualisiert beides.
 *
 * @param WP_REST_Request $request Der Request.
 * @return WP_REST_Response|WP_Error
 */
function gwt_employee_import_callback( $request ) {
    $data = array(
        'personalnummer' => $request->get_param( 'personalnummer' ),
        'vorname'        => $request->get_param( 'vorname' ),
        'nachname'       => $request->get_param( 'nachname' ),
        'email'          => $request->get_param( 'email' ),
        'telefon'        => $request->get_param( 'telefon' ),
        'mobil'          => $request->get_param( 'mobil' ),
        'position'       => $request->get_param( 'position' ),
        'standort'       => $request->get_param( 'standort' ),
        'abteilung'      => $request->get_param( 'abteilung' ),
        'eintrittsdatum' => $request->get_param( 'eintrittsdatum' ),
        'status'         => $request->get_param( 'status' ),
        'passwort'       => $request->get_param( 'passwort' ),
    );

    $result = gwt_create_or_update_employee( $data );

    if ( is_wp_error( $result ) ) {
        gwt_log_import( 'error', $data['personalnummer'], $result->get_error_message(), false );
        return $result;
    }

    return rest_ensure_response( $result );
}

/* ==========================================================================
   Deactivate-Callback: Mitarbeiter deaktivieren
   ========================================================================== */

/**
 * Deaktiviert einen Mitarbeiter (CPT auf Draft, User-Rolle auf Subscriber).
 *
 * @param WP_REST_Request $request Der Request.
 * @return WP_REST_Response|WP_Error
 */
function gwt_employee_deactivate_callback( $request ) {
    $personalnummer = $request->get_param( 'personalnummer' );

    // Employee-Post finden
    $post_id = gwt_find_employee_by_personalnummer( $personalnummer );

    if ( ! $post_id ) {
        $error = new WP_Error(
            'employee_not_found',
            sprintf( 'Mitarbeiter mit Personalnummer %s nicht gefunden.', $personalnummer ),
            array( 'status' => 404 )
        );
        gwt_log_import( 'deactivate', $personalnummer, 'Nicht gefunden', false );
        return $error;
    }

    // CPT auf Draft setzen
    wp_update_post( array(
        'ID'          => $post_id,
        'post_status' => 'draft',
    ) );

    // Meta-Felder aktualisieren
    update_post_meta( $post_id, '_employee_status', 'inaktiv' );

    // WP-User deaktivieren (Rolle auf Subscriber)
    $wp_user_id = (int) get_post_meta( $post_id, '_employee_wp_user_id', true );
    if ( $wp_user_id ) {
        $user = get_userdata( $wp_user_id );
        if ( $user ) {
            $user->set_role( 'subscriber' );
        }
    }

    // Cache leeren
    if ( function_exists( 'gwt_clear_relevant_cache' ) ) {
        gwt_clear_relevant_cache( $post_id );
    }

    gwt_log_import( 'deactivate', $personalnummer, 'Erfolgreich deaktiviert', true );

    return rest_ensure_response( array(
        'success'        => true,
        'action'         => 'deactivated',
        'personalnummer' => $personalnummer,
        'post_id'        => $post_id,
    ) );
}

/* ==========================================================================
   Kernfunktion: Mitarbeiter anlegen oder aktualisieren
   ========================================================================== */

/**
 * Legt einen neuen Mitarbeiter an oder aktualisiert einen bestehenden.
 * Erstellt/aktualisiert sowohl den WP-User als auch den Employee-CPT-Post.
 *
 * @param array $data Mitarbeiter-Daten.
 * @return array|WP_Error Ergebnis mit post_id, user_id, action.
 */
function gwt_create_or_update_employee( $data ) {
    $personalnummer = $data['personalnummer'];

    // Pruefen ob Mitarbeiter bereits existiert
    $existing_post_id = gwt_find_employee_by_personalnummer( $personalnummer );

    if ( $existing_post_id ) {
        return gwt_update_employee( $existing_post_id, $data );
    }

    return gwt_create_employee( $data );
}

/**
 * Legt einen neuen Mitarbeiter an (WP-User + CPT-Post).
 *
 * @param array $data Mitarbeiter-Daten.
 * @return array|WP_Error
 */
function gwt_create_employee( $data ) {
    // 1. WordPress-User anlegen
    $username = gwt_generate_username( $data['vorname'], $data['nachname'] );
    $password = ! empty( $data['passwort'] ) ? $data['passwort'] : wp_generate_password( 24, true, false );

    $user_data = array(
        'user_login'   => $username,
        'user_email'   => $data['email'],
        'user_pass'    => $password,
        'first_name'   => $data['vorname'],
        'last_name'    => $data['nachname'],
        'display_name' => $data['vorname'] . ' ' . $data['nachname'],
        'role'         => 'subscriber',
    );

    // Pruefen ob E-Mail bereits existiert
    $existing_user = get_user_by( 'email', $data['email'] );
    if ( $existing_user ) {
        $user_id = $existing_user->ID;
        // Bestehenden User aktualisieren (ohne Passwort)
        wp_update_user( array(
            'ID'           => $user_id,
            'first_name'   => $data['vorname'],
            'last_name'    => $data['nachname'],
            'display_name' => $data['vorname'] . ' ' . $data['nachname'],
        ) );
    } else {
        $user_id = wp_insert_user( $user_data );
        if ( is_wp_error( $user_id ) ) {
            return $user_id;
        }
    }

    // Personalnummer als User-Meta speichern (fuer AD-SSO Mapping)
    update_user_meta( $user_id, 'personalnummer', $data['personalnummer'] );

    // 2. Employee-CPT-Post anlegen
    $post_data = array(
        'post_type'   => 'employee',
        'post_title'  => $data['vorname'] . ' ' . $data['nachname'],
        'post_status' => 'publish',
        'post_name'   => sanitize_title( $data['vorname'] . '-' . $data['nachname'] ),
    );

    $post_id = wp_insert_post( $post_data, true );

    if ( is_wp_error( $post_id ) ) {
        return $post_id;
    }

    // 3. Meta-Felder setzen
    gwt_update_employee_meta( $post_id, $data, $user_id );

    // 4. Taxonomien zuweisen
    gwt_assign_employee_taxonomies( $post_id, $data );

    // 5. Cache leeren
    if ( function_exists( 'gwt_clear_relevant_cache' ) ) {
        gwt_clear_relevant_cache( $post_id );
    }

    gwt_log_import( 'create', $data['personalnummer'], 'Erfolgreich angelegt', true );

    return array(
        'success'        => true,
        'action'         => 'created',
        'personalnummer' => $data['personalnummer'],
        'post_id'        => $post_id,
        'user_id'        => $user_id,
        'username'       => $username,
        'password'       => $password, // Wird fuer die Welcome-Mail benoetigt
    );
}

/**
 * Aktualisiert einen bestehenden Mitarbeiter.
 *
 * @param int   $post_id Post-ID des Employee-CPT.
 * @param array $data    Neue Daten.
 * @return array|WP_Error
 */
function gwt_update_employee( $post_id, $data ) {
    // Post-Titel aktualisieren
    wp_update_post( array(
        'ID'         => $post_id,
        'post_title' => $data['vorname'] . ' ' . $data['nachname'],
    ) );

    // Meta-Felder aktualisieren
    $wp_user_id = (int) get_post_meta( $post_id, '_employee_wp_user_id', true );
    gwt_update_employee_meta( $post_id, $data, $wp_user_id );

    // WP-User aktualisieren (falls vorhanden)
    if ( $wp_user_id ) {
        $update_data = array(
            'ID'           => $wp_user_id,
            'first_name'   => $data['vorname'],
            'last_name'    => $data['nachname'],
            'display_name' => $data['vorname'] . ' ' . $data['nachname'],
        );

        // E-Mail nur aktualisieren wenn sie sich geaendert hat
        if ( ! empty( $data['email'] ) ) {
            $current_email = get_userdata( $wp_user_id )->user_email ?? '';
            if ( strtolower( $data['email'] ) !== strtolower( $current_email ) ) {
                $update_data['user_email'] = $data['email'];
            }
        }

        wp_update_user( $update_data );
    }

    // Taxonomien aktualisieren
    gwt_assign_employee_taxonomies( $post_id, $data );

    // Cache leeren
    if ( function_exists( 'gwt_clear_relevant_cache' ) ) {
        gwt_clear_relevant_cache( $post_id );
    }

    gwt_log_import( 'update', $data['personalnummer'], 'Erfolgreich aktualisiert', true );

    return array(
        'success'        => true,
        'action'         => 'updated',
        'personalnummer' => $data['personalnummer'],
        'post_id'        => $post_id,
        'user_id'        => $wp_user_id,
    );
}

/* ==========================================================================
   Helfer-Funktionen
   ========================================================================== */

/**
 * Setzt alle Meta-Felder eines Employee-Posts.
 *
 * @param int   $post_id Post-ID.
 * @param array $data    Mitarbeiter-Daten.
 * @param int   $user_id WordPress User-ID.
 */
function gwt_update_employee_meta( $post_id, $data, $user_id ) {
    $meta_mapping = array(
        '_employee_personalnummer'  => $data['personalnummer'],
        '_employee_vorname'         => $data['vorname'],
        '_employee_nachname'        => $data['nachname'],
        '_employee_email'           => $data['email'],
        '_employee_telefon'         => $data['telefon'],
        '_employee_mobil'           => $data['mobil'],
        '_employee_position'        => $data['position'],
        '_employee_eintrittsdatum'  => $data['eintrittsdatum'],
        '_employee_status'          => $data['status'],
        '_employee_wp_user_id'      => $user_id,
    );

    foreach ( $meta_mapping as $key => $value ) {
        update_post_meta( $post_id, $key, $value );
    }
}

/**
 * Weist einem Employee-Post die Standort- und Abteilungs-Taxonomien zu.
 * Erstellt neue Terms automatisch wenn sie noch nicht existieren.
 *
 * @param int   $post_id Post-ID.
 * @param array $data    Mitarbeiter-Daten mit 'standort' und 'abteilung'.
 */
function gwt_assign_employee_taxonomies( $post_id, $data ) {
    // Standort zuweisen (Term automatisch anlegen wenn noetig)
    if ( ! empty( $data['standort'] ) ) {
        $standort_term = term_exists( $data['standort'], 'standort' );
        if ( ! $standort_term ) {
            $standort_term = wp_insert_term( $data['standort'], 'standort' );
        }
        if ( ! is_wp_error( $standort_term ) ) {
            $term_id = is_array( $standort_term ) ? (int) $standort_term['term_id'] : (int) $standort_term;
            wp_set_object_terms( $post_id, $term_id, 'standort' );
        }
    }

    // Abteilung zuweisen (Term automatisch anlegen wenn noetig)
    if ( ! empty( $data['abteilung'] ) ) {
        $abteilung_term = term_exists( $data['abteilung'], 'abteilung' );
        if ( ! $abteilung_term ) {
            $abteilung_term = wp_insert_term( $data['abteilung'], 'abteilung' );
        }
        if ( ! is_wp_error( $abteilung_term ) ) {
            $term_id = is_array( $abteilung_term ) ? (int) $abteilung_term['term_id'] : (int) $abteilung_term;
            wp_set_object_terms( $post_id, $term_id, 'abteilung' );
        }
    }
}

/**
 * Generiert einen eindeutigen Benutzernamen im Format vorname.nachname.
 * Umlaute werden ersetzt, Sonderzeichen entfernt.
 * Bei Konflikten wird eine Zahl angehaengt (vorname.nachname2, vorname.nachname3).
 *
 * @param string $vorname  Vorname.
 * @param string $nachname Nachname.
 * @return string Eindeutiger Benutzername.
 */
function gwt_generate_username( $vorname, $nachname ) {
    // Umlaute ersetzen
    $replacements = array(
        'ae' => 'ae', 'oe' => 'oe', 'ue' => 'ue', 'ss' => 'ss',
        'Ae' => 'ae', 'Oe' => 'oe', 'Ue' => 'ue',
        "\xC3\xA4" => 'ae', "\xC3\xB6" => 'oe', "\xC3\xBC" => 'ue', "\xC3\x9F" => 'ss',
        "\xC3\x84" => 'ae', "\xC3\x96" => 'oe', "\xC3\x9C" => 'ue',
    );

    $vorname_clean  = mb_strtolower( $vorname );
    $nachname_clean = mb_strtolower( $nachname );

    // UTF-8 Umlaute ersetzen
    $vorname_clean  = str_replace(
        array( 'ä', 'ö', 'ü', 'ß', 'Ä', 'Ö', 'Ü' ),
        array( 'ae', 'oe', 'ue', 'ss', 'ae', 'oe', 'ue' ),
        $vorname_clean
    );
    $nachname_clean = str_replace(
        array( 'ä', 'ö', 'ü', 'ß', 'Ä', 'Ö', 'Ü' ),
        array( 'ae', 'oe', 'ue', 'ss', 'ae', 'oe', 'ue' ),
        $nachname_clean
    );

    // Nur Buchstaben und Punkte behalten
    $vorname_clean  = preg_replace( '/[^a-z]/', '', $vorname_clean );
    $nachname_clean = preg_replace( '/[^a-z]/', '', $nachname_clean );

    $base_username = $vorname_clean . '.' . $nachname_clean;
    $username      = $base_username;
    $counter       = 2;

    // Eindeutigkeit sicherstellen
    while ( username_exists( $username ) ) {
        $username = $base_username . $counter;
        $counter++;
    }

    return $username;
}

/* ==========================================================================
   Import-Logging
   ========================================================================== */

/**
 * Erstellt die Datenbanktabelle fuer Import-Logs.
 * Wird bei Theme-Aktivierung aufgerufen.
 */
function gwt_create_import_log_table() {
    global $wpdb;

    $table_name      = $wpdb->prefix . 'employee_import_log';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        log_timestamp datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        action varchar(20) NOT NULL DEFAULT '',
        personalnummer varchar(50) NOT NULL DEFAULT '',
        details text NOT NULL,
        success tinyint(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        KEY idx_timestamp (log_timestamp),
        KEY idx_personalnummer (personalnummer)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}

/**
 * Schreibt einen Eintrag in das Import-Log.
 *
 * @param string $action         Aktion: create, update, deactivate, error.
 * @param string $personalnummer Personalnummer.
 * @param string $details        Beschreibung.
 * @param bool   $success        Erfolgreich oder nicht.
 */
function gwt_log_import( $action, $personalnummer, $details, $success ) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'employee_import_log';

    // Pruefen ob Tabelle existiert
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $table_exists = $wpdb->get_var(
        $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name )
    );

    if ( ! $table_exists ) {
        return;
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    $wpdb->insert(
        $table_name,
        array(
            'log_timestamp'  => current_time( 'mysql' ),
            'action'         => sanitize_text_field( $action ),
            'personalnummer' => sanitize_text_field( $personalnummer ),
            'details'        => sanitize_text_field( $details ),
            'success'        => $success ? 1 : 0,
        ),
        array( '%s', '%s', '%s', '%s', '%d' )
    );

    // Bei Fehlern eine Alert-Mail senden
    if ( ! $success ) {
        $admin_email = get_option( 'admin_email' );
        wp_mail(
            $admin_email,
            '[GWT Intranet] Import-Fehler',
            sprintf(
                "Fehler beim Mitarbeiter-Import:\n\nAktion: %s\nPersonalnummer: %s\nDetails: %s\nZeitpunkt: %s",
                $action,
                $personalnummer,
                $details,
                current_time( 'd.m.Y H:i:s' )
            )
        );
    }
}
