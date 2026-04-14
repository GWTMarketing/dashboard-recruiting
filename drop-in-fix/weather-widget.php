<?php
/**
 * GWT Weather Widget – PUBLIC Endpoint Variante
 * ===============================================
 *
 * Ersetzt die restriktive Variante aus dem ZIP.
 *
 * Diese Datei kapselt:
 *   1. Den Shortcode [wetter_widget]
 *   2. Den REST-Endpoint /wp-json/gwt/v1/weather
 *   3. Eine Ausnahme im rest-security.php-Block
 *
 * Installation:
 * 1. Diese Datei nach wp-content/themes/gwt-intranet-child/inc/weather-widget.php
 *    hochladen (die bestehende Datei ueberschreiben).
 * 2. Keine weiteren Aenderungen noetig – Endpoint wird automatisch
 *    von der REST-Security ausgenommen.
 * 3. Seite neu laden (Ctrl+F5 fuer Cache-Refresh).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ---------------------------------------------------------------------- */
/*  1. Standort-Koordinaten-Fallback                                       */
/* ---------------------------------------------------------------------- */
function gwt_weather_get_standort_coords() {
	return [
		'wien'      => [ 'lat' => 48.21, 'lon' => 16.37, 'name' => 'Wien' ],
		'graz'      => [ 'lat' => 47.07, 'lon' => 15.44, 'name' => 'Graz' ],
		'linz'      => [ 'lat' => 48.31, 'lon' => 14.29, 'name' => 'Linz' ],
		'salzburg'  => [ 'lat' => 47.80, 'lon' => 13.04, 'name' => 'Salzburg' ],
		'innsbruck' => [ 'lat' => 47.27, 'lon' => 11.39, 'name' => 'Innsbruck' ],
		'klagenfurt'=> [ 'lat' => 46.62, 'lon' => 14.31, 'name' => 'Klagenfurt' ],
		'bregenz'   => [ 'lat' => 47.50, 'lon' => 9.75,  'name' => 'Bregenz' ],
		'eisenstadt'=> [ 'lat' => 47.85, 'lon' => 16.52, 'name' => 'Eisenstadt' ],
		'st.polten' => [ 'lat' => 48.20, 'lon' => 15.62, 'name' => 'St. Polten' ],
	];
}

/* ---------------------------------------------------------------------- */
/*  2. Shortcode [wetter_widget]                                           */
/* ---------------------------------------------------------------------- */
add_shortcode( 'wetter_widget', function() {
	$user_standort = null;
	if ( is_user_logged_in() ) {
		$user_id = get_current_user_id();
		$employee = get_posts( [
			'post_type'   => 'employee',
			'meta_key'    => '_employee_wp_user_id',
			'meta_value'  => $user_id,
			'numberposts' => 1,
		] );
		if ( ! empty( $employee ) ) {
			$terms = wp_get_post_terms( $employee[0]->ID, 'standort' );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				$user_standort = strtolower( $terms[0]->slug );
			}
		}
	}

	$standorte = gwt_weather_get_standort_coords();
	$fallback  = $standorte[ $user_standort ] ?? $standorte['wien'];

	$nonce = wp_create_nonce( 'wp_rest' );
	$rest_url = esc_url_raw( rest_url( 'gwt/v1/weather' ) );

	ob_start();
	?>
	<div id="gwt-weather" data-fallback-lat="<?php echo esc_attr( $fallback['lat'] ); ?>"
	     data-fallback-lon="<?php echo esc_attr( $fallback['lon'] ); ?>"
	     data-fallback-name="<?php echo esc_attr( $fallback['name'] ); ?>">
		<div class="gwt-weather-loading" style="color:#6b7280;font-size:14px;">Wetter wird geladen…</div>
	</div>
	<style>
		#gwt-weather .gwt-weather-days { display:grid; grid-template-columns: repeat(3, 1fr); gap:12px; }
		#gwt-weather .gwt-weather-day { text-align:center; padding:12px 8px; background:#f5f7fa; border-radius:8px; }
		#gwt-weather .gwt-weather-icon { font-size:32px; line-height:1; margin:4px 0; }
		#gwt-weather .gwt-weather-date { font-size:12px; color:#6b7280; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; }
		#gwt-weather .gwt-weather-temps { font-size:14px; color:#1f2937; margin-top:6px; }
		#gwt-weather .gwt-weather-temps .max { font-weight:700; color:#004071; }
		#gwt-weather .gwt-weather-temps .min { color:#9ca3af; }
		#gwt-weather .gwt-weather-location { font-size:12px; color:#9ca3af; margin-top:12px; text-align:center; }
	</style>
	<script>
	(function(){
		const el = document.getElementById('gwt-weather');
		if (!el) return;
		const fallbackLat  = parseFloat(el.dataset.fallbackLat);
		const fallbackLon  = parseFloat(el.dataset.fallbackLon);
		const fallbackName = el.dataset.fallbackName;
		const restUrl      = <?php echo wp_json_encode( $rest_url ); ?>;

		function render(data, locationName) {
			const icons = {0:'☀️',1:'🌤️',2:'⛅',3:'☁️',45:'🌫️',48:'🌫️',51:'🌦️',53:'🌦️',55:'🌦️',61:'🌧️',63:'🌧️',65:'🌧️',66:'🌨️',67:'🌨️',71:'🌨️',73:'🌨️',75:'❄️',77:'❄️',80:'🌦️',81:'🌧️',82:'⛈️',85:'🌨️',86:'❄️',95:'⛈️',96:'⛈️',99:'⛈️'};
			const days = data.daily.time.slice(0,3);
			const html = days.map((d, i) => {
				const date = new Date(d);
				const dayName = ['So','Mo','Di','Mi','Do','Fr','Sa'][date.getDay()];
				const code = data.daily.weathercode[i];
				const max = Math.round(data.daily.temperature_2m_max[i]);
				const min = Math.round(data.daily.temperature_2m_min[i]);
				return `<div class="gwt-weather-day">
					<div class="gwt-weather-date">${dayName} ${date.getDate()}.${date.getMonth()+1}.</div>
					<div class="gwt-weather-icon">${icons[code] || '🌡️'}</div>
					<div class="gwt-weather-temps"><span class="max">${max}°</span> <span class="min">${min}°</span></div>
				</div>`;
			}).join('');
			el.innerHTML = '<div class="gwt-weather-days">' + html + '</div>' +
				(locationName ? '<div class="gwt-weather-location">' + locationName + '</div>' : '');
		}

		function fetchWeather(lat, lon, locationName) {
			fetch(restUrl + '?lat=' + lat + '&lon=' + lon)
				.then(r => r.ok ? r.json() : Promise.reject(r.status))
				.then(data => {
					if (data && data.daily) render(data, locationName);
					else el.innerHTML = '<div style="color:#9ca3af;font-size:14px;">Wetterdaten nicht verfuegbar</div>';
				})
				.catch(err => {
					console.error('GWT Weather error:', err);
					el.innerHTML = '<div style="color:#9ca3af;font-size:14px;">Wetterdaten nicht verfuegbar</div>';
				});
		}

		if (navigator.geolocation) {
			navigator.geolocation.getCurrentPosition(
				pos => fetchWeather(pos.coords.latitude, pos.coords.longitude, ''),
				err => fetchWeather(fallbackLat, fallbackLon, fallbackName),
				{ timeout: 5000, maximumAge: 600000 }
			);
		} else {
			fetchWeather(fallbackLat, fallbackLon, fallbackName);
		}
	})();
	</script>
	<?php
	return ob_get_clean();
} );

/* ---------------------------------------------------------------------- */
/*  3. REST-Endpoint – PUBLIC (Wetterdaten sind nicht sensibel)            */
/* ---------------------------------------------------------------------- */
add_action( 'rest_api_init', function() {
	register_rest_route( 'gwt/v1', '/weather', [
		'methods'             => 'GET',
		'callback'            => 'gwt_weather_endpoint',
		'permission_callback' => '__return_true', // PUBLIC: kein Login noetig
		'args' => [
			'lat' => [
				'required' => true,
				'validate_callback' => function( $param ) { return is_numeric( $param ) && $param >= -90 && $param <= 90; },
			],
			'lon' => [
				'required' => true,
				'validate_callback' => function( $param ) { return is_numeric( $param ) && $param >= -180 && $param <= 180; },
			],
		],
	] );
} );

function gwt_weather_endpoint( WP_REST_Request $request ) {
	$lat = round( floatval( $request->get_param( 'lat' ) ), 2 );
	$lon = round( floatval( $request->get_param( 'lon' ) ), 2 );

	$cache_key = 'gwt_weather_' . md5( $lat . '_' . $lon );
	$cached    = get_transient( $cache_key );
	if ( $cached !== false ) {
		return new WP_REST_Response( $cached, 200 );
	}

	$url = sprintf(
		'https://api.open-meteo.com/v1/forecast?latitude=%s&longitude=%s&daily=temperature_2m_max,temperature_2m_min,weathercode&timezone=Europe/Vienna&forecast_days=3',
		$lat,
		$lon
	);

	$response = wp_remote_get( $url, [ 'timeout' => 10 ] );
	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'weather_api_error', 'Wetter-API nicht erreichbar', [ 'status' => 502 ] );
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );
	if ( empty( $data ) || empty( $data['daily'] ) ) {
		return new WP_Error( 'weather_api_invalid', 'Ungueltige Wetter-Antwort', [ 'status' => 502 ] );
	}

	// Cache 1 Stunde
	set_transient( $cache_key, $data, HOUR_IN_SECONDS );
	return new WP_REST_Response( $data, 200 );
}

/* ---------------------------------------------------------------------- */
/*  4. REST-Security-Ausnahme                                              */
/*  Prio 5 laeuft VOR der Standard-Restriction (Prio 10)                   */
/* ---------------------------------------------------------------------- */
add_filter( 'rest_authentication_errors', function( $result ) {
	if ( ! empty( $result ) ) return $result; // nichts weiter tun wenn schon Ergebnis

	$uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
	if ( strpos( $uri, '/wp-json/gwt/v1/weather' ) !== false
	  || strpos( $uri, '/wp-json/gwt/v1/weather/' ) !== false
	  || strpos( $uri, 'rest_route=/gwt/v1/weather' ) !== false ) {
		// Signalisiere: "Alles ok, nicht blocken" indem wir true zurueckgeben.
		// rest_authentication_errors erwartet null fuer "no opinion" oder true
		// Wir geben explizit true zurueck, damit nachfolgende Filter nicht
		// mehr blocken koennen.
		return true;
	}
	return $result;
}, 5 );
