<?php
/**
 * Wetter-Widget: 3-Tages-Vorschau mit Geotargeting
 *
 * Zeigt eine 3-Tages-Wettervorschau auf der Startseite.
 * Nutzt die kostenlose Open-Meteo API (kein API-Key noetig).
 *
 * Ablauf:
 *   1. Browser fragt Geolocation ab
 *   2. Falls erlaubt: Koordinaten an unseren REST-Endpoint senden
 *   3. Falls verweigert: Fallback auf Standort des Mitarbeiters
 *   4. Backend holt Wetter von Open-Meteo, cached fuer 1 Stunde
 *
 * Verwendung: [wetter_widget]
 *
 * @package GWT_Intranet
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* --------------------------------------------------------------------------
   Standort-Koordinaten (Fallback wenn Geolocation verweigert)
   -------------------------------------------------------------------------- */

/**
 * Gibt die vordefinierten Koordinaten fuer bekannte Standorte zurueck.
 * Neue Standorte koennen hier einfach ergaenzt werden.
 *
 * @return array Assoziatives Array: Standortname => [lat, lon].
 */
function gwt_get_standort_coordinates() {
    return array(
        'Wien'       => array( 'lat' => 48.21, 'lon' => 16.37 ),
        'Graz'       => array( 'lat' => 47.07, 'lon' => 15.44 ),
        'Linz'       => array( 'lat' => 48.31, 'lon' => 14.29 ),
        'Salzburg'   => array( 'lat' => 47.80, 'lon' => 13.04 ),
        'Innsbruck'  => array( 'lat' => 47.26, 'lon' => 11.39 ),
        'Klagenfurt' => array( 'lat' => 46.62, 'lon' => 14.31 ),
        'Villach'    => array( 'lat' => 46.61, 'lon' => 13.85 ),
        'Wels'       => array( 'lat' => 48.16, 'lon' => 14.03 ),
        'St. Poelten' => array( 'lat' => 48.20, 'lon' => 15.63 ),
        'Dornbirn'   => array( 'lat' => 47.41, 'lon' => 9.74 ),
    );
}

/* --------------------------------------------------------------------------
   REST API Endpoint: /wp-json/gwt/v1/weather
   -------------------------------------------------------------------------- */

add_action( 'rest_api_init', 'gwt_register_weather_endpoint' );

/**
 * Registriert den Wetter-REST-Endpoint.
 */
function gwt_register_weather_endpoint() {
    register_rest_route( 'gwt/v1', '/weather', array(
        'methods'             => 'GET',
        'callback'            => 'gwt_weather_endpoint_callback',
        'permission_callback' => function () {
            return is_user_logged_in(); // Nur fuer eingeloggte User
        },
        'args'                => array(
            'lat' => array(
                'required'          => true,
                'validate_callback' => function ( $param ) {
                    return is_numeric( $param ) && $param >= -90 && $param <= 90;
                },
                'sanitize_callback' => 'floatval',
            ),
            'lon' => array(
                'required'          => true,
                'validate_callback' => function ( $param ) {
                    return is_numeric( $param ) && $param >= -180 && $param <= 180;
                },
                'sanitize_callback' => 'floatval',
            ),
        ),
    ) );
}

/**
 * Callback fuer den Wetter-Endpoint.
 * Holt Wetterdaten von Open-Meteo und cached sie als Transient.
 *
 * @param WP_REST_Request $request Der Request.
 * @return WP_REST_Response|WP_Error
 */
function gwt_weather_endpoint_callback( $request ) {
    $lat = round( $request->get_param( 'lat' ), 2 ); // Auf 2 Dezimalstellen runden (Privacy + Cache)
    $lon = round( $request->get_param( 'lon' ), 2 );

    // Cache-Key basierend auf gerundeten Koordinaten (gleicher Ort = gleicher Cache)
    $cache_key = sprintf( 'gwt_weather_%s_%s', str_replace( '.', '_', (string) $lat ), str_replace( '.', '_', (string) $lon ) );
    $cached    = get_transient( $cache_key );

    if ( false !== $cached ) {
        return rest_ensure_response( $cached );
    }

    // Open-Meteo API aufrufen (kostenlos, kein API-Key noetig)
    $api_url = sprintf(
        'https://api.open-meteo.com/v1/forecast?latitude=%s&longitude=%s&daily=temperature_2m_max,temperature_2m_min,weathercode&timezone=Europe/Vienna&forecast_days=3',
        $lat,
        $lon
    );

    $response = wp_remote_get( $api_url, array(
        'timeout' => 10,
    ) );

    if ( is_wp_error( $response ) ) {
        return new WP_Error(
            'weather_api_error',
            'Wetterdaten konnten nicht geladen werden.',
            array( 'status' => 502 )
        );
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( empty( $body['daily'] ) ) {
        return new WP_Error(
            'weather_invalid_response',
            'Ungueltige Antwort von der Wetter-API.',
            array( 'status' => 502 )
        );
    }

    // Daten aufbereiten
    $weather_data = array(
        'tage' => array(),
    );

    $wochentage = array( 'So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa' );

    for ( $i = 0; $i < 3; $i++ ) {
        $date       = $body['daily']['time'][ $i ];
        $day_of_week = (int) gmdate( 'w', strtotime( $date ) );
        $weathercode = (int) $body['daily']['weathercode'][ $i ];

        $weather_data['tage'][] = array(
            'datum'     => $date,
            'wochentag' => $i === 0 ? 'Heute' : $wochentage[ $day_of_week ],
            'temp_max'  => round( $body['daily']['temperature_2m_max'][ $i ] ),
            'temp_min'  => round( $body['daily']['temperature_2m_min'][ $i ] ),
            'icon'      => gwt_weathercode_to_emoji( $weathercode ),
            'text'      => gwt_weathercode_to_text( $weathercode ),
        );
    }

    // 1 Stunde cachen
    set_transient( $cache_key, $weather_data, HOUR_IN_SECONDS );

    return rest_ensure_response( $weather_data );
}

/* --------------------------------------------------------------------------
   Wetter-Code zu Emoji/Text Mapping (WMO Standard)
   -------------------------------------------------------------------------- */

/**
 * Wandelt einen WMO-Wettercode in ein Emoji um.
 *
 * @param int $code WMO-Wettercode.
 * @return string Emoji.
 */
function gwt_weathercode_to_emoji( $code ) {
    $map = array(
        0  => "\u{2600}\u{FE0F}",    // Sonnig
        1  => "\u{1F324}\u{FE0F}",   // Ueberwiegend sonnig
        2  => "\u{26C5}",             // Teilweise bewoelkt
        3  => "\u{2601}\u{FE0F}",    // Bewoelkt
        45 => "\u{1F32B}\u{FE0F}",   // Nebel
        48 => "\u{1F32B}\u{FE0F}",   // Reif-Nebel
        51 => "\u{1F326}\u{FE0F}",   // Leichter Nieselregen
        53 => "\u{1F326}\u{FE0F}",   // Nieselregen
        55 => "\u{1F327}\u{FE0F}",   // Starker Nieselregen
        61 => "\u{1F326}\u{FE0F}",   // Leichter Regen
        63 => "\u{1F327}\u{FE0F}",   // Regen
        65 => "\u{1F327}\u{FE0F}",   // Starker Regen
        71 => "\u{1F328}\u{FE0F}",   // Leichter Schneefall
        73 => "\u{1F328}\u{FE0F}",   // Schneefall
        75 => "\u{1F328}\u{FE0F}",   // Starker Schneefall
        80 => "\u{1F326}\u{FE0F}",   // Leichte Regenschauer
        81 => "\u{1F327}\u{FE0F}",   // Regenschauer
        82 => "\u{26C8}\u{FE0F}",    // Starke Regenschauer
        85 => "\u{1F328}\u{FE0F}",   // Leichte Schneeschauer
        86 => "\u{1F328}\u{FE0F}",   // Starke Schneeschauer
        95 => "\u{26C8}\u{FE0F}",    // Gewitter
        96 => "\u{26C8}\u{FE0F}",    // Gewitter mit leichtem Hagel
        99 => "\u{26C8}\u{FE0F}",    // Gewitter mit starkem Hagel
    );

    return isset( $map[ $code ] ) ? $map[ $code ] : "\u{2601}\u{FE0F}";
}

/**
 * Wandelt einen WMO-Wettercode in deutschen Text um.
 *
 * @param int $code WMO-Wettercode.
 * @return string Beschreibung.
 */
function gwt_weathercode_to_text( $code ) {
    $map = array(
        0  => 'Sonnig',
        1  => 'Ueberwiegend sonnig',
        2  => 'Teilweise bewoelkt',
        3  => 'Bewoelkt',
        45 => 'Nebel',
        48 => 'Reif-Nebel',
        51 => 'Leichter Nieselregen',
        53 => 'Nieselregen',
        55 => 'Starker Nieselregen',
        61 => 'Leichter Regen',
        63 => 'Regen',
        65 => 'Starker Regen',
        71 => 'Leichter Schneefall',
        73 => 'Schneefall',
        75 => 'Starker Schneefall',
        80 => 'Leichte Schauer',
        81 => 'Regenschauer',
        82 => 'Starke Schauer',
        85 => 'Schneeschauer',
        86 => 'Starke Schneeschauer',
        95 => 'Gewitter',
        96 => 'Gewitter mit Hagel',
        99 => 'Schweres Gewitter',
    );

    return isset( $map[ $code ] ) ? $map[ $code ] : 'Bewoelkt';
}

/* --------------------------------------------------------------------------
   Shortcode: [wetter_widget]
   -------------------------------------------------------------------------- */

add_shortcode( 'wetter_widget', 'gwt_weather_widget_shortcode' );

/**
 * Rendert das Wetter-Widget.
 * Das Widget laedt die Daten per JavaScript nach (fuer Cache-Kompatibilitaet).
 *
 * @return string HTML-Output.
 */
function gwt_weather_widget_shortcode() {
    if ( ! is_user_logged_in() ) {
        return '';
    }

    // Fallback-Koordinaten fuer den aktuellen User ermitteln
    $fallback_lat = 48.21; // Wien als Default
    $fallback_lon = 16.37;

    $current_user_id = get_current_user_id();
    $user_standort   = gwt_get_user_standort( $current_user_id );

    if ( $user_standort ) {
        $coords = gwt_get_standort_coordinates();
        if ( isset( $coords[ $user_standort ] ) ) {
            $fallback_lat = $coords[ $user_standort ]['lat'];
            $fallback_lon = $coords[ $user_standort ]['lon'];
        }
    }

    $rest_url = esc_url( rest_url( 'gwt/v1/weather' ) );
    $nonce    = wp_create_nonce( 'wp_rest' );

    ob_start();
    ?>
    <div class="gwt-card" id="gwt-weather-widget">
        <div class="gwt-weather__loading">Wetter wird geladen...</div>
    </div>

    <script>
    (function() {
        var widget = document.getElementById('gwt-weather-widget');
        if (!widget) return;

        var restUrl = <?php echo wp_json_encode( $rest_url ); ?>;
        var nonce = <?php echo wp_json_encode( $nonce ); ?>;
        var fallbackLat = <?php echo (float) $fallback_lat; ?>;
        var fallbackLon = <?php echo (float) $fallback_lon; ?>;

        function loadWeather(lat, lon) {
            fetch(restUrl + '?lat=' + lat + '&lon=' + lon, {
                headers: { 'X-WP-Nonce': nonce }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.tage) {
                    widget.innerHTML = '<div class="gwt-weather__loading">Wetterdaten nicht verfuegbar</div>';
                    return;
                }
                var html = '<div class="gwt-weather">';
                data.tage.forEach(function(tag) {
                    html += '<div class="gwt-weather__day">';
                    html += '<div class="gwt-weather__day-name">' + tag.wochentag + '</div>';
                    html += '<div class="gwt-weather__icon">' + tag.icon + '</div>';
                    html += '<div class="gwt-weather__temp">';
                    html += '<span class="gwt-weather__temp-max">' + tag.temp_max + '°</span> ';
                    html += '<span class="gwt-weather__temp-min">' + tag.temp_min + '°</span>';
                    html += '</div>';
                    html += '</div>';
                });
                html += '</div>';
                widget.innerHTML = html;
            })
            .catch(function() {
                widget.innerHTML = '<div class="gwt-weather__loading">Wetterdaten nicht verfuegbar</div>';
            });
        }

        // Geolocation versuchen, bei Fehler Fallback nutzen
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(pos) { loadWeather(pos.coords.latitude, pos.coords.longitude); },
                function() { loadWeather(fallbackLat, fallbackLon); },
                { timeout: 5000 }
            );
        } else {
            loadWeather(fallbackLat, fallbackLon);
        }
    })();
    </script>
    <?php

    return ob_get_clean();
}

/* --------------------------------------------------------------------------
   Hilfsfunktion: Standort des Users ermitteln
   -------------------------------------------------------------------------- */

/**
 * Ermittelt den Standort eines Users ueber dessen verknuepften Employee-CPT.
 *
 * @param int $user_id WordPress User-ID.
 * @return string|false Standortname oder false.
 */
function gwt_get_user_standort( $user_id ) {
    // Personalnummer des Users holen
    $personalnummer = get_user_meta( $user_id, 'personalnummer', true );

    if ( empty( $personalnummer ) ) {
        return false;
    }

    // Employee-Post finden
    $post_id = gwt_find_employee_by_personalnummer( $personalnummer );

    if ( ! $post_id ) {
        return false;
    }

    // Standort-Taxonomy holen
    $terms = get_the_terms( $post_id, 'standort' );

    if ( $terms && ! is_wp_error( $terms ) ) {
        return $terms[0]->name;
    }

    return false;
}
