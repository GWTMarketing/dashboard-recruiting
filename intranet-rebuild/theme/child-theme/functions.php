<?php
/**
 * GWT Intranet Child-Theme – Hauptdatei
 *
 * Laedt alle Module aus dem /inc/-Verzeichnis.
 * Die eigentliche Gestaltung erfolgt ueber Bricks Builder –
 * diese Datei stellt nur die Infrastruktur bereit (CPTs, Taxonomien,
 * API-Sicherheit, Import-Logik, Shortcodes, Cron-Jobs).
 *
 * @package GWT_Intranet
 * @version 1.0.0
 */

// Sicherheitscheck: Direktzugriff verhindern
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Theme-Konstanten
define( 'GWT_THEME_VERSION', '1.0.0' );
define( 'GWT_THEME_DIR', get_stylesheet_directory() );
define( 'GWT_THEME_URI', get_stylesheet_directory_uri() );

/* --------------------------------------------------------------------------
   Module laden
   -------------------------------------------------------------------------- */

// Custom Post Types
require_once GWT_THEME_DIR . '/inc/cpt-employee.php';
require_once GWT_THEME_DIR . '/inc/cpt-bulletin.php';

// REST API Absicherung (Sperre, Rolle, Rate Limiting, Logging)
require_once GWT_THEME_DIR . '/inc/rest-security.php';

// Mitarbeiter-Import (REST-Endpoint + Helfer-Funktionen)
require_once GWT_THEME_DIR . '/inc/employee-import.php';

// Startseiten-Widgets
require_once GWT_THEME_DIR . '/inc/welcome-bereich.php';
require_once GWT_THEME_DIR . '/inc/weather-widget.php';

// Mitarbeiter-Navigation (Vor/Zurueck innerhalb Abteilung)
require_once GWT_THEME_DIR . '/inc/employee-navigation.php';

// WP-Cron Jobs (Bulletin-Ablauf, Log-Cleanup)
require_once GWT_THEME_DIR . '/inc/cron-jobs.php';

/* --------------------------------------------------------------------------
   Theme-Setup
   -------------------------------------------------------------------------- */

add_action( 'after_setup_theme', 'gwt_theme_setup' );

/**
 * Grundlegende Theme-Unterstuetzung aktivieren.
 */
function gwt_theme_setup() {
    // Beitragsbilder (fuer Mitarbeiter-Fotos und Aushang-Bilder)
    add_theme_support( 'post-thumbnails' );

    // Seitentitel ueber WordPress verwalten lassen
    add_theme_support( 'title-tag' );

    // Bild-Groessen fuer Mitarbeiter-Fotos
    add_image_size( 'employee-thumbnail', 300, 300, true );  // Quadratisch, zugeschnitten
    add_image_size( 'employee-full', 600, 800, false );       // Hochformat, proportional
}

/* --------------------------------------------------------------------------
   Google Fonts (Mulish) einbinden
   -------------------------------------------------------------------------- */

add_action( 'wp_enqueue_scripts', 'gwt_enqueue_fonts' );

/**
 * Mulish-Schriftart von Google Fonts laden.
 */
function gwt_enqueue_fonts() {
    wp_enqueue_style(
        'gwt-mulish-font',
        'https://fonts.googleapis.com/css2?family=Mulish:wght@300;400;500;600;700;800&display=swap',
        array(),
        null // Kein Versions-Parameter bei externen Fonts
    );
}

/* --------------------------------------------------------------------------
   Theme-Aktivierung: Rollen anlegen, DB-Tabellen erstellen, Cron registrieren
   -------------------------------------------------------------------------- */

add_action( 'after_switch_theme', 'gwt_theme_activate' );

/**
 * Wird beim Aktivieren des Child-Themes ausgefuehrt.
 * Legt die n8n-API-Rolle an, erstellt DB-Tabellen und registriert Cron-Jobs.
 */
function gwt_theme_activate() {
    // n8n API Rolle anlegen (aus rest-security.php)
    if ( function_exists( 'gwt_create_n8n_api_role' ) ) {
        gwt_create_n8n_api_role();
    }

    // API-Log-Tabelle anlegen (aus rest-security.php)
    if ( function_exists( 'gwt_create_api_log_table' ) ) {
        gwt_create_api_log_table();
    }

    // Import-Log-Tabelle anlegen (aus employee-import.php)
    if ( function_exists( 'gwt_create_import_log_table' ) ) {
        gwt_create_import_log_table();
    }

    // Cron-Jobs registrieren (aus cron-jobs.php)
    if ( function_exists( 'gwt_schedule_cron_jobs' ) ) {
        gwt_schedule_cron_jobs();
    }

    // Rewrite-Rules flushen (wichtig fuer neue CPT-Slugs)
    flush_rewrite_rules();
}

/* --------------------------------------------------------------------------
   Theme-Deaktivierung: Cron-Jobs entfernen
   -------------------------------------------------------------------------- */

add_action( 'switch_theme', 'gwt_theme_deactivate' );

/**
 * Wird beim Deaktivieren des Child-Themes ausgefuehrt.
 * Entfernt registrierte Cron-Jobs.
 */
function gwt_theme_deactivate() {
    // Cron-Jobs entfernen (aus cron-jobs.php)
    if ( function_exists( 'gwt_unschedule_cron_jobs' ) ) {
        gwt_unschedule_cron_jobs();
    }
}

/* --------------------------------------------------------------------------
   WP Rocket Kompatibilitaet
   -------------------------------------------------------------------------- */

/**
 * Leert den WP Rocket Cache fuer relevante Seiten nach einem Import.
 * Wird von employee-import.php aufgerufen.
 *
 * @param int $post_id Optional: ID eines bestimmten Posts zum Cache-Leeren.
 */
function gwt_clear_relevant_cache( $post_id = 0 ) {
    // Einzelnen Post-Cache leeren
    if ( $post_id && function_exists( 'rocket_clean_post' ) ) {
        rocket_clean_post( $post_id );
    }

    // Startseiten-Cache leeren (fuer Welcome-Bereich)
    if ( function_exists( 'rocket_clean_home' ) ) {
        rocket_clean_home();
    }
}

// TODO: AD SSO Integration (Go-Live September 2026)
// Hier wird spaeter der authenticate-Filter fuer Active Directory eingehaengt.
// Die user_login-Felder sind bereits auf das Format vorname.nachname vorbereitet.
// Die Personalnummer ist als usermeta gespeichert und dient als Mapping-Key.
