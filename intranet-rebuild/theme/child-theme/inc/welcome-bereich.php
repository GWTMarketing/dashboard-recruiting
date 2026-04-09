<?php
/**
 * Welcome-Bereich: Neue KollegInnen auf der Startseite
 *
 * Zeigt Mitarbeiter deren Eintrittsdatum innerhalb der letzten 2 Monate liegt
 * in zufaelliger Reihenfolge an. Kompatibel mit WP Rocket Full-Page-Cache
 * durch clientseitige Randomisierung.
 *
 * Verwendung:
 *   - Shortcode: [welcome_bereich] oder [welcome_bereich anzahl="8" monate="3"]
 *   - Bricks Builder: Custom Query "gwt_welcome_employees" verfuegbar
 *
 * @package GWT_Intranet
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* --------------------------------------------------------------------------
   Shortcode: [welcome_bereich]
   -------------------------------------------------------------------------- */

add_shortcode( 'welcome_bereich', 'gwt_welcome_bereich_shortcode' );

/**
 * Rendert den Welcome-Bereich mit neuen Mitarbeitern.
 *
 * Fuer WP Rocket Kompatibilitaet werden ALLE qualifizierten Mitarbeiter als
 * versteckte Cards ausgegeben. Ein kleines Inline-JS waehlt zufaellig N davon
 * aus und zeigt sie an. So bleibt der Full-Page-Cache aktiv.
 *
 * @param array $atts Shortcode-Attribute.
 * @return string HTML-Output.
 */
function gwt_welcome_bereich_shortcode( $atts ) {
    // Nur fuer eingeloggte User
    if ( ! is_user_logged_in() ) {
        return '';
    }

    $atts = shortcode_atts( array(
        'anzahl' => 6,   // Wie viele Mitarbeiter gleichzeitig anzeigen
        'monate' => 2,   // Zeitraum in Monaten
    ), $atts, 'welcome_bereich' );

    $anzahl = absint( $atts['anzahl'] );
    $monate = absint( $atts['monate'] );

    // Alle Mitarbeiter mit Eintrittsdatum innerhalb der letzten X Monate
    $cutoff_date = gmdate( 'Y-m-d', strtotime( sprintf( '-%d months', $monate ) ) );

    $query = new WP_Query( array(
        'post_type'      => 'employee',
        'post_status'    => 'publish',
        'posts_per_page' => 50, // Obergrenze zur Sicherheit
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => '_employee_eintrittsdatum',
                'value'   => $cutoff_date,
                'compare' => '>=',
                'type'    => 'DATE',
            ),
            array(
                'key'     => '_employee_status',
                'value'   => 'aktiv',
                'compare' => '=',
            ),
        ),
        'orderby'        => 'meta_value',
        'meta_key'       => '_employee_eintrittsdatum',
        'order'          => 'DESC',
        'no_found_rows'  => true,
    ) );

    if ( ! $query->have_posts() ) {
        return '';
    }

    // HTML aufbauen
    ob_start();
    ?>
    <div class="gwt-welcome-section">
        <h2 class="gwt-welcome-section__title">Welcome on Board!</h2>
        <div class="gwt-grid gwt-grid--3" id="gwt-welcome-grid">
            <?php
            $index = 0;
            while ( $query->have_posts() ) {
                $query->the_post();
                $post_id   = get_the_ID();
                $vorname   = esc_html( get_post_meta( $post_id, '_employee_vorname', true ) );
                $nachname  = esc_html( get_post_meta( $post_id, '_employee_nachname', true ) );
                $position  = esc_html( get_post_meta( $post_id, '_employee_position', true ) );
                $standort_terms = get_the_terms( $post_id, 'standort' );
                $standort  = ( $standort_terms && ! is_wp_error( $standort_terms ) )
                    ? esc_html( $standort_terms[0]->name )
                    : '';
                $eintritt  = get_post_meta( $post_id, '_employee_eintrittsdatum', true );
                $seit_text = '';
                if ( $eintritt ) {
                    $seit_text = 'Seit ' . esc_html( date_i18n( 'F Y', strtotime( $eintritt ) ) );
                }
                $permalink = esc_url( get_permalink( $post_id ) );
                $foto_url  = get_the_post_thumbnail_url( $post_id, 'employee-thumbnail' );

                // Fallback: Initialen wenn kein Foto vorhanden
                $has_foto  = ! empty( $foto_url );
                $initials  = mb_strtoupper( mb_substr( $vorname, 0, 1 ) . mb_substr( $nachname, 0, 1 ) );

                // Alle Cards werden gerendert, aber nur $anzahl werden per JS sichtbar
                $hidden_attr = ( $index >= $anzahl ) ? ' style="display:none;"' : '';
                ?>
                <div class="gwt-card gwt-welcome-card" data-welcome-card<?php echo $hidden_attr; ?>>
                    <?php if ( $has_foto ) : ?>
                        <img class="gwt-welcome-card__foto"
                             src="<?php echo esc_url( $foto_url ); ?>"
                             alt="<?php echo esc_attr( "$vorname $nachname" ); ?>"
                             loading="lazy">
                    <?php else : ?>
                        <div class="gwt-welcome-card__foto gwt-avatar-fallback"
                             style="width:120px;height:120px;border-radius:9999px;margin:0 auto 1rem;display:flex;align-items:center;justify-content:center;background:#c7eafb;color:#004071;font-size:2.5rem;font-weight:700;">
                            <?php echo esc_html( $initials ); ?>
                        </div>
                    <?php endif; ?>
                    <a href="<?php echo $permalink; ?>" class="gwt-welcome-card__name">
                        <?php echo "$vorname $nachname"; ?>
                    </a>
                    <?php if ( $position ) : ?>
                        <div class="gwt-welcome-card__position"><?php echo $position; ?></div>
                    <?php endif; ?>
                    <?php if ( $standort ) : ?>
                        <div class="gwt-welcome-card__standort"><?php echo $standort; ?></div>
                    <?php endif; ?>
                    <?php if ( $seit_text ) : ?>
                        <span class="gwt-welcome-card__seit"><?php echo $seit_text; ?></span>
                    <?php endif; ?>
                </div>
                <?php
                $index++;
            }
            wp_reset_postdata();
            ?>
        </div>
    </div>

    <?php // Inline-JS: Zufaellige Auswahl fuer Cache-Kompatibilitaet ?>
    <script>
    (function() {
        var container = document.getElementById('gwt-welcome-grid');
        if (!container) return;

        var cards = Array.from(container.querySelectorAll('[data-welcome-card]'));
        var maxShow = <?php echo (int) $anzahl; ?>;

        if (cards.length <= maxShow) {
            // Weniger Cards als Limit: alle anzeigen, nur Reihenfolge mischen
            cards.forEach(function(c) { c.style.display = ''; });
        } else {
            // Zufaellig mischen (Fisher-Yates)
            for (var i = cards.length - 1; i > 0; i--) {
                var j = Math.floor(Math.random() * (i + 1));
                var temp = cards[i];
                cards[i] = cards[j];
                cards[j] = temp;
            }

            // Nur die ersten N anzeigen
            cards.forEach(function(card, idx) {
                card.style.display = idx < maxShow ? '' : 'none';
            });
        }

        // DOM-Reihenfolge auch mischen (fuer natuerlichen Flow)
        cards.forEach(function(card) {
            container.appendChild(card);
        });
    })();
    </script>
    <?php

    return ob_get_clean();
}

/* --------------------------------------------------------------------------
   Bricks Builder Integration: Custom Query Type
   -------------------------------------------------------------------------- */

add_filter( 'bricks/setup/control_options', 'gwt_register_welcome_query_type' );

/**
 * Registriert den Custom Query Type "gwt_welcome_employees" in Bricks Builder.
 * Damit koennen Redakteure im Bricks Template-Editor eine Query Loop mit
 * neuen Mitarbeitern erstellen, ohne den Shortcode zu verwenden.
 *
 * @param array $control_options Bricks Control-Optionen.
 * @return array Erweiterte Optionen.
 */
function gwt_register_welcome_query_type( $control_options ) {
    if ( isset( $control_options['queryTypes'] ) ) {
        $control_options['queryTypes']['gwt_welcome_employees'] = 'Neue Mitarbeiter (Welcome)';
    }
    return $control_options;
}

add_filter( 'bricks/query/run', 'gwt_run_welcome_query', 10, 2 );

/**
 * Fuehrt die Custom Query fuer den Welcome-Bereich aus.
 *
 * @param array  $results   Bisherige Ergebnisse.
 * @param object $query_obj Bricks Query-Objekt.
 * @return array WP_Post-Array.
 */
function gwt_run_welcome_query( $results, $query_obj ) {
    if ( 'gwt_welcome_employees' !== $query_obj->object_type ) {
        return $results;
    }

    $cutoff_date = gmdate( 'Y-m-d', strtotime( '-2 months' ) );

    $query = new WP_Query( array(
        'post_type'      => 'employee',
        'post_status'    => 'publish',
        'posts_per_page' => 12,
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => '_employee_eintrittsdatum',
                'value'   => $cutoff_date,
                'compare' => '>=',
                'type'    => 'DATE',
            ),
            array(
                'key'     => '_employee_status',
                'value'   => 'aktiv',
                'compare' => '=',
            ),
        ),
        'orderby'        => 'rand',
        'no_found_rows'  => true,
    ) );

    return $query->posts;
}

add_filter( 'bricks/query/loop_object', 'gwt_welcome_loop_object', 10, 3 );

/**
 * Setzt das Loop-Objekt fuer jeden Durchlauf der Welcome-Query.
 *
 * @param mixed  $loop_object Aktuelles Objekt.
 * @param int    $loop_key    Index.
 * @param object $query_obj   Bricks Query-Objekt.
 * @return WP_Post
 */
function gwt_welcome_loop_object( $loop_object, $loop_key, $query_obj ) {
    if ( 'gwt_welcome_employees' !== $query_obj->object_type ) {
        return $loop_object;
    }

    // Global Post setzen damit Bricks Dynamic Data funktioniert
    global $post;
    $post = $query_obj->results[ $loop_key ];
    setup_postdata( $post );

    return $post;
}
