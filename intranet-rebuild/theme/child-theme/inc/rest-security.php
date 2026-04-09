<?php
/**
 * REST API Absicherung
 *
 * 1. Sperrt die REST API fuer nicht-authentifizierte Benutzer
 * 2. Legt die Custom-Rolle "n8n_api_user" an (minimale Berechtigungen)
 * 3. Rate Limiting: 60 Requests/Minute pro IP (300 fuer n8n-Rolle)
 * 4. Logging aller API-Zugriffe in eine Custom-Datenbanktabelle
 * 5. Alerting bei wiederholten Fehlversuchen
 *
 * @package GWT_Intranet
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ==========================================================================
   1. REST API fuer nicht-authentifizierte User sperren
   ========================================================================== */

add_filter( 'rest_authentication_errors', 'gwt_restrict_rest_api' );

/**
 * Blockiert REST-API-Zugriffe fuer nicht eingeloggte Benutzer.
 * Ausnahmen: Endpoints die Bricks Builder und oEmbed benoetigen.
 *
 * @param WP_Error|null|true $result Bisheriges Auth-Ergebnis.
 * @return WP_Error|null|true
 */
function gwt_restrict_rest_api( $result ) {
    // Wenn bereits authentifiziert oder ein Fehler vorliegt, nicht eingreifen
    if ( true === $result || is_wp_error( $result ) ) {
        return $result;
    }

    // Eingeloggte User durchlassen
    if ( is_user_logged_in() ) {
        return $result;
    }

    // Ausnahmen: Endpoints die ohne Login funktionieren muessen
    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

    $allowed_patterns = array(
        '/wp-json/wp/v2/types',       // Bricks Builder benoetigt dies
        '/wp-json/oembed/',           // oEmbed fuer eingebettete Inhalte
        '/wp-json/wp/v2/oembed',     // oEmbed Alternative
    );

    foreach ( $allowed_patterns as $pattern ) {
        if ( false !== strpos( $request_uri, $pattern ) ) {
            return $result;
        }
    }

    // WP Rocket Preload-Bot durchlassen (interne Requests)
    $remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
    if ( in_array( $remote_addr, array( '127.0.0.1', '::1' ), true ) ) {
        return $result;
    }

    // Alle anderen blockieren
    return new WP_Error(
        'rest_not_logged_in',
        'Zugriff verweigert. Authentifizierung erforderlich.',
        array( 'status' => 401 )
    );
}

/* ==========================================================================
   2. Custom Rolle: n8n_api_user
   ========================================================================== */

/**
 * Erstellt die Rolle "n8n_api_user" mit minimalen Berechtigungen.
 * Wird bei Theme-Aktivierung aufgerufen (aus functions.php).
 */
function gwt_create_n8n_api_role() {
    // Bestehende Rolle entfernen falls vorhanden (fuer Updates)
    remove_role( 'n8n_api_user' );

    add_role( 'n8n_api_user', 'n8n API Benutzer', array(
        // Basis
        'read'                   => true,

        // Posts (fuer News-Erstellung)
        'edit_posts'             => true,
        'publish_posts'          => true,
        'delete_posts'           => true,
        'edit_others_posts'      => true,

        // User-Verwaltung (fuer Mitarbeiter-Import)
        'create_users'           => true,
        'edit_users'             => true,
        'list_users'             => true,

        // Medien (fuer Bild-Uploads)
        'upload_files'           => true,

        // Taxonomien (fuer Standort/Abteilung-Erstellung)
        'manage_categories'      => true,

        // KEIN Admin-Zugriff
        'manage_options'         => false,
        'install_plugins'        => false,
        'switch_themes'          => false,
        'edit_theme_options'     => false,
    ) );
}

/* ==========================================================================
   3. Rate Limiting (60 Requests/Minute pro IP)
   ========================================================================== */

add_filter( 'rest_pre_dispatch', 'gwt_rate_limit_rest_api', 10, 3 );

/**
 * Begrenzt REST-API-Anfragen auf 60 pro Minute pro IP.
 * n8n-API-User erhalten ein hoeheres Limit (300/Minute).
 *
 * @param mixed           $result  Bisheriges Ergebnis.
 * @param WP_REST_Server  $server  REST-Server-Instanz.
 * @param WP_REST_Request $request Der aktuelle Request.
 * @return mixed|WP_Error
 */
function gwt_rate_limit_rest_api( $result, $server, $request ) {
    // Wenn bereits ein Ergebnis vorliegt, nicht eingreifen
    if ( null !== $result ) {
        return $result;
    }

    $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';

    // Limit bestimmen: 300 fuer n8n-User, 60 fuer alle anderen
    $limit = 60;
    $current_user = wp_get_current_user();
    if ( $current_user->ID && in_array( 'n8n_api_user', (array) $current_user->roles, true ) ) {
        $limit = 300;
    }

    // Transient-Key: IP-basiert, 1 Minute Fenster
    $transient_key = 'gwt_rate_' . md5( $ip );
    $count = (int) get_transient( $transient_key );

    if ( $count >= $limit ) {
        // Alerting bei massiver Ueberschreitung (>2x Limit)
        if ( $count === $limit ) {
            gwt_log_rate_limit_breach( $ip, $current_user->ID );
        }

        return new WP_Error(
            'rate_limit_exceeded',
            sprintf( 'Zu viele Anfragen. Maximal %d Anfragen pro Minute erlaubt.', $limit ),
            array(
                'status'      => 429,
                'retry_after' => 60,
            )
        );
    }

    // Zaehler erhoehen
    if ( false === get_transient( $transient_key ) ) {
        set_transient( $transient_key, 1, 60 );
    } else {
        set_transient( $transient_key, $count + 1, 60 );
    }

    return $result;
}

/**
 * Setzt den Retry-After Header bei Rate-Limit-Ueberschreitung.
 */
add_filter( 'rest_post_dispatch', 'gwt_add_rate_limit_headers', 10, 3 );

/**
 * Fuegt Rate-Limit-Header zur REST-Antwort hinzu.
 *
 * @param WP_REST_Response $response Die Antwort.
 * @param WP_REST_Server   $server   REST-Server.
 * @param WP_REST_Request  $request  Der Request.
 * @return WP_REST_Response
 */
function gwt_add_rate_limit_headers( $response, $server, $request ) {
    if ( 429 === $response->get_status() ) {
        $response->header( 'Retry-After', '60' );
    }

    return $response;
}

/* ==========================================================================
   4. API Access Logging
   ========================================================================== */

/**
 * Erstellt die Datenbanktabelle fuer API-Zugriffslogs.
 * Wird bei Theme-Aktivierung aufgerufen.
 */
function gwt_create_api_log_table() {
    global $wpdb;

    $table_name      = $wpdb->prefix . 'api_access_log';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        log_timestamp datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        user_id bigint(20) unsigned DEFAULT 0,
        ip_address varchar(45) NOT NULL DEFAULT '',
        endpoint varchar(255) NOT NULL DEFAULT '',
        http_method varchar(10) NOT NULL DEFAULT '',
        status_code smallint(5) unsigned DEFAULT 0,
        PRIMARY KEY (id),
        KEY idx_timestamp (log_timestamp),
        KEY idx_ip (ip_address),
        KEY idx_user (user_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}

add_action( 'rest_post_dispatch', 'gwt_log_api_access', 20, 3 );

/**
 * Loggt jeden REST-API-Zugriff in die Datenbank.
 *
 * @param WP_REST_Response $response Die Antwort.
 * @param WP_REST_Server   $server   REST-Server.
 * @param WP_REST_Request  $request  Der Request.
 * @return WP_REST_Response
 */
function gwt_log_api_access( $response, $server, $request ) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'api_access_log';

    // Pruefen ob die Tabelle existiert (Fehler vermeiden beim ersten Aufruf)
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $table_exists = $wpdb->get_var(
        $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name )
    );

    if ( ! $table_exists ) {
        return $response;
    }

    $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    $wpdb->insert(
        $table_name,
        array(
            'log_timestamp' => current_time( 'mysql' ),
            'user_id'       => get_current_user_id(),
            'ip_address'    => $ip,
            'endpoint'      => sanitize_text_field( $request->get_route() ),
            'http_method'   => sanitize_text_field( $request->get_method() ),
            'status_code'   => $response->get_status(),
        ),
        array( '%s', '%d', '%s', '%s', '%s', '%d' )
    );

    return $response;
}

/* ==========================================================================
   5. Alerting bei Sicherheitsvorfaellen
   ========================================================================== */

/**
 * Loggt und alarmiert bei Rate-Limit-Ueberschreitungen.
 *
 * @param string $ip      IP-Adresse.
 * @param int    $user_id WordPress User-ID (0 wenn nicht eingeloggt).
 */
function gwt_log_rate_limit_breach( $ip, $user_id ) {
    // Pruefen ob wir fuer diese IP bereits innerhalb der letzten Stunde alarmiert haben
    $alert_transient = 'gwt_alert_' . md5( $ip );
    if ( get_transient( $alert_transient ) ) {
        return;
    }

    // Alert-Mail senden
    $admin_email = get_option( 'admin_email' );
    $subject     = '[GWT Intranet] Rate-Limit-Ueberschreitung';
    $message     = sprintf(
        "Rate-Limit ueberschritten:\n\nIP: %s\nUser-ID: %d\nZeitpunkt: %s\n\nBitte pruefen Sie, ob es sich um einen Angriff handelt.",
        $ip,
        $user_id,
        current_time( 'd.m.Y H:i:s' )
    );

    wp_mail( $admin_email, $subject, $message );

    // Eine Stunde lang nicht erneut alarmieren
    set_transient( $alert_transient, 1, HOUR_IN_SECONDS );
}

/**
 * Prueft auf wiederholte Auth-Fehler und sendet Alerts.
 * Wird ueber den rest_post_dispatch Hook ausgeloest.
 */
add_action( 'rest_post_dispatch', 'gwt_check_auth_failures', 30, 3 );

/**
 * Zaehlt 401-Fehler pro IP und alarmiert bei mehr als 5 pro Stunde.
 *
 * @param WP_REST_Response $response Die Antwort.
 * @param WP_REST_Server   $server   REST-Server.
 * @param WP_REST_Request  $request  Der Request.
 * @return WP_REST_Response
 */
function gwt_check_auth_failures( $response, $server, $request ) {
    if ( 401 !== $response->get_status() ) {
        return $response;
    }

    $ip            = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
    $transient_key = 'gwt_auth_fail_' . md5( $ip );
    $fail_count    = (int) get_transient( $transient_key );

    $fail_count++;
    set_transient( $transient_key, $fail_count, HOUR_IN_SECONDS );

    // Bei mehr als 5 Fehlversuchen pro Stunde alarmieren
    if ( 5 === $fail_count ) {
        $admin_email = get_option( 'admin_email' );
        $subject     = '[GWT Intranet] Mehrfache Auth-Fehler erkannt';
        $message     = sprintf(
            "Wiederholte Authentifizierungsfehler erkannt:\n\nIP: %s\nAnzahl: %d in der letzten Stunde\nZeitpunkt: %s\n\nMoeglicher Brute-Force-Versuch.",
            $ip,
            $fail_count,
            current_time( 'd.m.Y H:i:s' )
        );

        wp_mail( $admin_email, $subject, $message );
    }

    return $response;
}
