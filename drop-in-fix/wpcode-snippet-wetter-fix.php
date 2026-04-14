<?php
/**
 * WPCode Snippet: Wetter-Widget REST-Endpoint freischalten
 * =========================================================
 *
 * PROBLEM: rest-security.php sperrt alle nicht-authentifizierten
 * REST-Anfragen. Das Wetter-Widget ruft den Endpoint per JavaScript
 * im Browser auf – dieser Aufruf wird geblockt und das Widget bleibt leer.
 *
 * LÖSUNG: Dieser Snippet läuft mit Priorität 5 – BEVOR rest-security.php
 * (Priorität 10) den Request blockieren kann. Der Wetter-Endpoint wird
 * als "authentifiziert" markiert und durchgelassen.
 *
 * INSTALLATION (ohne FTP – nur WPCode):
 * ──────────────────────────────────────
 * 1. WP Admin → WPCode → Code Snippets → Add New
 * 2. "Add Your Custom Code (New Snippet)" klicken
 * 3. Code Type: "PHP Snippet"
 * 4. Titel: "GWT Wetter-Fix"
 * 5. Den gesamten Code unterhalb dieses Kommentarblocks einfügen
 *    (OHNE die opening <?php Zeile – WPCode fügt die selbst hinzu)
 * 6. Location: "Run Everywhere"
 * 7. Oben rechts auf "Save Snippet" klicken
 * 8. Den Toggle auf "Active" stellen
 * 9. Seite im Browser neu laden (Strg+Shift+R) und Wetter-Widget testen
 *
 * HINWEIS: Dieser Snippet macht NUR /wp-json/gwt/v1/weather öffentlich.
 * Alle anderen REST-Endpoints bleiben weiterhin gesperrt.
 */

// ── Wetter-Endpoint aus der REST-Sicherheitssperre ausnehmen ────────────────
// Priorität 5 → läuft vor rest-security.php (Priorität 10).
// Gibt true zurück → signalisiert WordPress "diese Anfrage ist OK".
// Alle anderen Endpoints werden davon nicht berührt.
add_filter( 'rest_authentication_errors', function( $result ) {
	// Wenn schon ein "OK" (true) signalisiert wurde, nichts tun
	if ( true === $result ) {
		return $result;
	}

	$uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';

	// Sowohl direkte URLs als auch ?rest_route= Variante abdecken
	$is_weather = (
		strpos( $uri, '/wp-json/gwt/v1/weather' ) !== false ||
		strpos( $uri, 'rest_route=/gwt/v1/weather' ) !== false
	);

	if ( $is_weather ) {
		// true = "Request ist authentifiziert, bitte durchlassen"
		// rest-security.php prüft: if ( !empty($result) ) return $result;
		// → empfängt true, gibt true zurück → kein Block mehr
		return true;
	}

	return $result;
}, 5 );
