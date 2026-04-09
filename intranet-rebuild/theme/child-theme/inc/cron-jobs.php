<?php
/**
 * WP-Cron Jobs
 *
 * 1. Schwarzes Brett: Abgelaufene Aushaenge auf "Entwurf" setzen (2x taeglich)
 * 2. Woechentliche Zusammenfassung: Admin bekommt Liste abgelaufener Aushaenge
 * 3. API-Log-Cleanup: Eintraege aelter als 90 Tage loeschen (taeglich)
 *
 * WICHTIG: Auf einem Intranet mit wenig Traffic kann WP-Cron unzuverlaessig sein.
 * Empfehlung: Echten System-Cron einrichten und DISABLE_WP_CRON in wp-config.php setzen.
 *   Beispiel: * * * * * wget -q -O - https://intranet.firma.at/wp-cron.php?doing_wp_cron > /dev/null 2>&1
 *
 * @package GWT_Intranet
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* --------------------------------------------------------------------------
   Cron-Jobs registrieren
   -------------------------------------------------------------------------- */

/**
 * Registriert alle WP-Cron-Jobs. Wird bei Theme-Aktivierung aufgerufen.
 */
function gwt_schedule_cron_jobs() {
    // Schwarzes Brett: 2x taeglich pruefen
    if ( ! wp_next_scheduled( 'gwt_check_expired_bulletins' ) ) {
        wp_schedule_event( time(), 'twicedaily', 'gwt_check_expired_bulletins' );
    }

    // Woechentliche Zusammenfassung
    if ( ! wp_next_scheduled( 'gwt_weekly_bulletin_summary' ) ) {
        // Jeden Montag um 08:00 (naechster Montag berechnen)
        $next_monday = strtotime( 'next monday 08:00:00' );
        wp_schedule_event( $next_monday, 'weekly', 'gwt_weekly_bulletin_summary' );
    }

    // API-Log-Cleanup: Taeglich
    if ( ! wp_next_scheduled( 'gwt_cleanup_api_logs' ) ) {
        wp_schedule_event( time(), 'daily', 'gwt_cleanup_api_logs' );
    }
}

/**
 * Entfernt alle registrierten Cron-Jobs. Wird bei Theme-Deaktivierung aufgerufen.
 */
function gwt_unschedule_cron_jobs() {
    wp_clear_scheduled_hook( 'gwt_check_expired_bulletins' );
    wp_clear_scheduled_hook( 'gwt_weekly_bulletin_summary' );
    wp_clear_scheduled_hook( 'gwt_cleanup_api_logs' );
}

/* --------------------------------------------------------------------------
   Custom Cron-Intervall: woechentlich (existiert nicht standardmaessig)
   -------------------------------------------------------------------------- */

add_filter( 'cron_schedules', 'gwt_add_weekly_schedule' );

/**
 * Fuegt ein woechentliches Cron-Intervall hinzu.
 *
 * @param array $schedules Bestehende Intervalle.
 * @return array Erweiterte Intervalle.
 */
function gwt_add_weekly_schedule( $schedules ) {
    if ( ! isset( $schedules['weekly'] ) ) {
        $schedules['weekly'] = array(
            'interval' => 604800, // 7 Tage in Sekunden
            'display'  => 'Woechentlich',
        );
    }
    return $schedules;
}

/* --------------------------------------------------------------------------
   Job 1: Abgelaufene Aushaenge auf "Entwurf" setzen
   -------------------------------------------------------------------------- */

add_action( 'gwt_check_expired_bulletins', 'gwt_expire_bulletins' );

/**
 * Findet alle veroeffentlichten Aushaenge deren Ablaufdatum ueberschritten ist
 * und setzt deren Status auf "draft" (Entwurf).
 *
 * Die Aushaenge werden NICHT geloescht – sie koennen bei Bedarf reaktiviert werden.
 */
function gwt_expire_bulletins() {
    $today = gmdate( 'Y-m-d' );

    $expired_posts = new WP_Query( array(
        'post_type'      => 'bulletin_board',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => '_bulletin_expiry_date',
                'value'   => $today,
                'compare' => '<',
                'type'    => 'DATE',
            ),
        ),
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ) );

    if ( ! $expired_posts->have_posts() ) {
        return;
    }

    $expired_count = 0;
    $expired_titles = array();

    foreach ( $expired_posts->posts as $post_id ) {
        $title = get_the_title( $post_id );

        wp_update_post( array(
            'ID'          => $post_id,
            'post_status' => 'draft',
        ) );

        $expired_titles[] = $title;
        $expired_count++;

        // Autor benachrichtigen
        $author_id = get_post_field( 'post_author', $post_id );
        $author    = get_userdata( $author_id );

        if ( $author && $author->user_email ) {
            $message = sprintf(
                "Hallo %s,\n\nDein Aushang \"%s\" am Schwarzen Brett ist abgelaufen und wurde automatisch deaktiviert.\n\nWenn der Aushang weiterhin gelten soll, kannst du ihn im WordPress-Admin reaktivieren und ein neues Ablaufdatum setzen.\n\nViele Gruesse\nDein Intranet-System",
                $author->display_name,
                $title
            );

            wp_mail(
                $author->user_email,
                sprintf( '[Intranet] Aushang abgelaufen: %s', $title ),
                $message
            );
        }
    }

    // Im Error-Log protokollieren (fuer Debugging)
    if ( $expired_count > 0 && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        error_log( sprintf(
            '[GWT Intranet] %d Aushaenge abgelaufen und deaktiviert: %s',
            $expired_count,
            implode( ', ', $expired_titles )
        ) );
    }
}

/* --------------------------------------------------------------------------
   Job 2: Woechentliche Zusammenfassung fuer Admin
   -------------------------------------------------------------------------- */

add_action( 'gwt_weekly_bulletin_summary', 'gwt_send_bulletin_summary' );

/**
 * Sendet eine woechentliche Zusammenfassung aller in der letzten Woche
 * abgelaufenen Aushaenge an den Admin.
 */
function gwt_send_bulletin_summary() {
    $one_week_ago = gmdate( 'Y-m-d', strtotime( '-7 days' ) );
    $today        = gmdate( 'Y-m-d' );

    // Aushaenge die in der letzten Woche abgelaufen sind (jetzt im Entwurf-Status)
    $expired_posts = new WP_Query( array(
        'post_type'      => 'bulletin_board',
        'post_status'    => 'draft',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => '_bulletin_expiry_date',
                'value'   => array( $one_week_ago, $today ),
                'compare' => 'BETWEEN',
                'type'    => 'DATE',
            ),
        ),
        'no_found_rows'  => true,
    ) );

    if ( ! $expired_posts->have_posts() ) {
        return; // Keine abgelaufenen Aushaenge = keine Mail
    }

    $admin_email = get_option( 'admin_email' );
    $subject     = '[GWT Intranet] Woechentliche Zusammenfassung: Schwarzes Brett';

    $message = "Hallo,\n\nfolgende Aushaenge sind in der letzten Woche abgelaufen und wurden automatisch deaktiviert:\n\n";

    foreach ( $expired_posts->posts as $post ) {
        $expiry_date = get_post_meta( $post->ID, '_bulletin_expiry_date', true );
        $author      = get_userdata( $post->post_author );
        $author_name = $author ? $author->display_name : 'Unbekannt';

        $message .= sprintf(
            "- %s (abgelaufen am %s, Autor: %s)\n",
            $post->post_title,
            gmdate( 'd.m.Y', strtotime( $expiry_date ) ),
            $author_name
        );
    }

    $message .= sprintf(
        "\nInsgesamt: %d Aushaenge\n\nWenn Aushaenge verlaengert werden sollen, koennen sie im WordPress-Admin reaktiviert werden.\n\nDein Intranet-System",
        $expired_posts->found_posts
    );

    wp_mail( $admin_email, $subject, $message );
}

/* --------------------------------------------------------------------------
   Job 3: API-Log-Cleanup (Eintraege aelter als 90 Tage)
   -------------------------------------------------------------------------- */

add_action( 'gwt_cleanup_api_logs', 'gwt_cleanup_old_api_logs' );

/**
 * Loescht API-Zugriffslogs die aelter als 90 Tage sind.
 */
function gwt_cleanup_old_api_logs() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'api_access_log';

    // Pruefen ob Tabelle existiert
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $table_exists = $wpdb->get_var(
        $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name )
    );

    if ( ! $table_exists ) {
        return;
    }

    $cutoff_date = gmdate( 'Y-m-d H:i:s', strtotime( '-90 days' ) );

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $deleted = $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM $table_name WHERE log_timestamp < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $cutoff_date
        )
    );

    if ( $deleted > 0 && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        error_log( sprintf( '[GWT Intranet] API-Log-Cleanup: %d Eintraege geloescht (aelter als 90 Tage).', $deleted ) );
    }
}
