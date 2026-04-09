<?php
/**
 * Mitarbeiter-Navigation: Vor/Zurueck innerhalb der Abteilung
 *
 * Zeigt auf der Mitarbeiter-Detailseite Links zum vorherigen und naechsten
 * Mitarbeiter innerhalb derselben Abteilung an. Die Reihenfolge basiert
 * auf dem Nachnamen (alphabetisch).
 *
 * Verwendung:
 *   - Automatisch: Haengt sich an the_content fuer employee-Posts
 *   - Shortcode: [mitarbeiter_navigation] (fuer Bricks Builder Templates)
 *
 * @package GWT_Intranet
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* --------------------------------------------------------------------------
   Automatische Navigation via the_content Filter
   -------------------------------------------------------------------------- */

add_filter( 'the_content', 'gwt_append_employee_navigation' );

/**
 * Fuegt die Vor/Zurueck-Navigation am Ende des Mitarbeiter-Contents ein.
 * Nur auf Einzelseiten des Employee-CPT aktiv.
 *
 * @param string $content Der bisherige Inhalt.
 * @return string Inhalt mit Navigation.
 */
function gwt_append_employee_navigation( $content ) {
    // Nur auf einzelnen Mitarbeiter-Seiten im Frontend
    if ( ! is_singular( 'employee' ) || is_admin() ) {
        return $content;
    }

    // Nur im Main-Loop (nicht in Widgets/Sidebars)
    if ( ! in_the_loop() || ! is_main_query() ) {
        return $content;
    }

    $nav_html = gwt_render_employee_navigation( get_the_ID() );

    return $content . $nav_html;
}

/* --------------------------------------------------------------------------
   Shortcode: [mitarbeiter_navigation]
   -------------------------------------------------------------------------- */

add_shortcode( 'mitarbeiter_navigation', 'gwt_employee_navigation_shortcode' );

/**
 * Shortcode-Wrapper fuer die Mitarbeiter-Navigation.
 * Nützlich in Bricks Builder Templates wo the_content nicht verwendet wird.
 *
 * @return string HTML-Output.
 */
function gwt_employee_navigation_shortcode() {
    if ( ! is_singular( 'employee' ) ) {
        return '';
    }

    return gwt_render_employee_navigation( get_the_ID() );
}

/* --------------------------------------------------------------------------
   Navigation rendern
   -------------------------------------------------------------------------- */

/**
 * Erzeugt die HTML-Navigation fuer Vor/Zurueck-Links.
 *
 * Logik:
 *   1. Abteilung des aktuellen Mitarbeiters ermitteln
 *   2. Alle Mitarbeiter in dieser Abteilung nach Nachname sortiert laden
 *   3. Vorherigen und naechsten in der Liste finden
 *   4. HTML rendern
 *
 * @param int $post_id ID des aktuellen Mitarbeiter-Posts.
 * @return string HTML der Navigation.
 */
function gwt_render_employee_navigation( $post_id ) {
    // Abteilung des aktuellen Mitarbeiters
    $abteilung_terms = get_the_terms( $post_id, 'abteilung' );

    if ( ! $abteilung_terms || is_wp_error( $abteilung_terms ) ) {
        return ''; // Keine Abteilung zugewiesen → keine Navigation
    }

    $abteilung = $abteilung_terms[0];

    // Alle aktiven Mitarbeiter in dieser Abteilung, sortiert nach Nachname
    $colleagues = new WP_Query( array(
        'post_type'      => 'employee',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'tax_query'      => array(
            array(
                'taxonomy' => 'abteilung',
                'field'    => 'term_id',
                'terms'    => $abteilung->term_id,
            ),
        ),
        'meta_query'     => array(
            array(
                'key'     => '_employee_status',
                'value'   => 'aktiv',
                'compare' => '=',
            ),
        ),
        'meta_key'       => '_employee_nachname',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ) );

    if ( ! $colleagues->have_posts() || count( $colleagues->posts ) < 2 ) {
        return ''; // Nur ein Mitarbeiter in der Abteilung → keine Navigation
    }

    // Position des aktuellen Mitarbeiters finden
    $ids          = $colleagues->posts;
    $current_index = array_search( $post_id, $ids, true );

    if ( false === $current_index ) {
        return '';
    }

    $prev_id = ( $current_index > 0 ) ? $ids[ $current_index - 1 ] : false;
    $next_id = ( $current_index < count( $ids ) - 1 ) ? $ids[ $current_index + 1 ] : false;

    // Wenn weder Vor noch Zurueck → nichts anzeigen
    if ( ! $prev_id && ! $next_id ) {
        return '';
    }

    // Zurueck-Link zur Abteilungsseite
    $abteilung_url  = esc_url( get_term_link( $abteilung ) );
    $abteilung_name = esc_html( $abteilung->name );

    ob_start();
    ?>
    <div class="gwt-employee-nav">
        <?php if ( $prev_id ) : ?>
            <a href="<?php echo esc_url( get_permalink( $prev_id ) ); ?>" class="gwt-employee-nav__link">
                <?php
                $prev_foto = get_the_post_thumbnail_url( $prev_id, 'employee-thumbnail' );
                $prev_name = esc_html( get_post_meta( $prev_id, '_employee_vorname', true ) . ' ' . get_post_meta( $prev_id, '_employee_nachname', true ) );

                if ( $prev_foto ) :
                ?>
                    <img class="gwt-employee-nav__foto"
                         src="<?php echo esc_url( $prev_foto ); ?>"
                         alt="<?php echo esc_attr( $prev_name ); ?>"
                         loading="lazy">
                <?php endif; ?>
                <div>
                    <div class="gwt-employee-nav__label">&larr; Vorherige/r</div>
                    <div class="gwt-employee-nav__name"><?php echo $prev_name; ?></div>
                </div>
            </a>
        <?php else : ?>
            <div></div><?php // Platzhalter fuer Flexbox-Ausrichtung ?>
        <?php endif; ?>

        <?php if ( $next_id ) : ?>
            <a href="<?php echo esc_url( get_permalink( $next_id ) ); ?>" class="gwt-employee-nav__link" style="text-align: right;">
                <div>
                    <div class="gwt-employee-nav__label">Naechste/r &rarr;</div>
                    <div class="gwt-employee-nav__name">
                        <?php echo esc_html( get_post_meta( $next_id, '_employee_vorname', true ) . ' ' . get_post_meta( $next_id, '_employee_nachname', true ) ); ?>
                    </div>
                </div>
                <?php
                $next_foto = get_the_post_thumbnail_url( $next_id, 'employee-thumbnail' );
                if ( $next_foto ) :
                ?>
                    <img class="gwt-employee-nav__foto"
                         src="<?php echo esc_url( $next_foto ); ?>"
                         alt=""
                         loading="lazy">
                <?php endif; ?>
            </a>
        <?php else : ?>
            <div></div>
        <?php endif; ?>
    </div>

    <div style="text-align: center; margin-top: 0.5rem;">
        <a href="<?php echo $abteilung_url; ?>"
           style="color: var(--gwt-secondary, #005e9e); text-decoration: none; font-size: 0.875rem;">
            &larr; Alle Mitarbeiter in <?php echo $abteilung_name; ?>
        </a>
    </div>
    <?php

    return ob_get_clean();
}
