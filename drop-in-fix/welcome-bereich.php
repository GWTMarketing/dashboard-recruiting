<?php
/**
 * GWT Welcome-Bereich (NEU mit Layout-Support)
 * =============================================
 *
 * Ersetzt die alte welcome-bereich.php. Unterstuetzt jetzt:
 *   - layout="grid" (default, fuer Hauptbereich)
 *   - layout="list" (kompakte Seitenspaltenliste)
 *
 * Installation:
 *   wp-content/themes/gwt-intranet-child/inc/welcome-bereich.php
 *   bestehende Datei ueberschreiben
 *
 * Shortcode:
 *   [welcome_bereich anzahl="6" monate="2" layout="grid"]
 *   [welcome_bereich anzahl="8" monate="2" layout="list"]
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'welcome_bereich', function( $atts ) {
	$atts = shortcode_atts( [
		'anzahl' => '6',
		'monate' => '2',
		'layout' => 'grid', // 'grid' | 'list'
	], $atts, 'welcome_bereich' );

	$anzahl = max( 1, absint( $atts['anzahl'] ) );
	$monate = max( 1, absint( $atts['monate'] ) );
	$layout = in_array( $atts['layout'], [ 'grid', 'list' ], true ) ? $atts['layout'] : 'grid';

	$cutoff = date( 'Y-m-d', strtotime( '-' . $monate . ' months' ) );

	$query = new WP_Query( [
		'post_type'      => 'employee',
		'post_status'    => 'publish',
		'posts_per_page' => 50,
		'meta_query'     => [
			'relation' => 'AND',
			[
				'key'     => '_employee_eintrittsdatum',
				'value'   => $cutoff,
				'compare' => '>=',
				'type'    => 'DATE',
			],
			[
				'key'     => '_employee_status',
				'value'   => 'aktiv',
				'compare' => '=',
			],
		],
		'orderby' => 'meta_value',
		'meta_key' => '_employee_eintrittsdatum',
		'order'   => 'DESC',
	] );

	if ( ! $query->have_posts() ) {
		return '<div class="gwt-welcome-empty" style="color:#9ca3af;font-size:14px;">Aktuell keine neuen Mitarbeiter.</div>';
	}

	$items = [];
	while ( $query->have_posts() ) {
		$query->the_post();
		$pid        = get_the_ID();
		$photo_url  = get_the_post_thumbnail_url( $pid, 'thumbnail' ) ?: '';
		$name       = trim( get_post_meta( $pid, '_employee_vorname', true ) . ' ' . get_post_meta( $pid, '_employee_nachname', true ) );
		if ( empty( $name ) ) $name = get_the_title();
		$position   = get_post_meta( $pid, '_employee_position', true );
		$eintritt   = get_post_meta( $pid, '_employee_eintrittsdatum', true );
		$standort_terms = wp_get_post_terms( $pid, 'standort' );
		$standort   = ( ! empty( $standort_terms ) && ! is_wp_error( $standort_terms ) ) ? $standort_terms[0]->name : '';

		$items[] = [
			'id'       => $pid,
			'url'      => get_permalink( $pid ),
			'photo'    => $photo_url,
			'name'     => $name,
			'position' => $position,
			'eintritt' => $eintritt,
			'standort' => $standort,
			'initials' => strtoupper( mb_substr( $name, 0, 1 ) . mb_substr( explode( ' ', $name )[1] ?? '', 0, 1 ) ),
		];
	}
	wp_reset_postdata();

	// Zufaellig sortieren (cache-freundlich via JS) – wir rendern ALLE, JS
	// waehlt zufaellig $anzahl aus und blendet den Rest aus. Somit ist der
	// Full-Page-Cache von WP Rocket nicht hinfaellig.
	ob_start();

	if ( $layout === 'list' ) {
		?>
		<ul class="gwt-welcome-list" data-show="<?php echo esc_attr( $anzahl ); ?>">
			<?php foreach ( $items as $it ) : ?>
				<li class="gwt-welcome-list-item">
					<a href="<?php echo esc_url( $it['url'] ); ?>" class="gwt-welcome-list-link">
						<?php if ( $it['photo'] ) : ?>
							<img src="<?php echo esc_url( $it['photo'] ); ?>" alt="<?php echo esc_attr( $it['name'] ); ?>" class="gwt-welcome-list-photo">
						<?php else : ?>
							<span class="gwt-welcome-list-photo gwt-welcome-initials"><?php echo esc_html( $it['initials'] ); ?></span>
						<?php endif; ?>
						<span class="gwt-welcome-list-text">
							<span class="gwt-welcome-list-name"><?php echo esc_html( $it['name'] ); ?></span>
							<?php if ( $it['position'] ) : ?>
								<span class="gwt-welcome-list-position"><?php echo esc_html( $it['position'] ); ?></span>
							<?php endif; ?>
						</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
	} else {
		// Grid-Layout (Default)
		?>
		<div class="gwt-welcome-grid" data-show="<?php echo esc_attr( $anzahl ); ?>">
			<?php foreach ( $items as $it ) : ?>
				<a href="<?php echo esc_url( $it['url'] ); ?>" class="gwt-welcome-card">
					<?php if ( $it['photo'] ) : ?>
						<img src="<?php echo esc_url( $it['photo'] ); ?>" alt="<?php echo esc_attr( $it['name'] ); ?>" class="gwt-welcome-photo">
					<?php else : ?>
						<span class="gwt-welcome-photo gwt-welcome-initials"><?php echo esc_html( $it['initials'] ); ?></span>
					<?php endif; ?>
					<span class="gwt-welcome-name"><?php echo esc_html( $it['name'] ); ?></span>
					<?php if ( $it['position'] ) : ?>
						<span class="gwt-welcome-position"><?php echo esc_html( $it['position'] ); ?></span>
					<?php endif; ?>
					<?php if ( $it['standort'] ) : ?>
						<span class="gwt-welcome-standort"><?php echo esc_html( $it['standort'] ); ?></span>
					<?php endif; ?>
				</a>
			<?php endforeach; ?>
		</div>
		<?php
	}

	// Inline-CSS + JS (nur einmal pro Seite)
	static $printed_assets = false;
	if ( ! $printed_assets ) {
		$printed_assets = true;
		?>
		<style>
		/* ===== GRID-Layout ===== */
		.gwt-welcome-grid {
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
			gap: 20px;
		}
		.gwt-welcome-card {
			display: flex;
			flex-direction: column;
			align-items: center;
			text-align: center;
			padding: 16px 12px;
			background: #f9fafb;
			border-radius: 12px;
			text-decoration: none;
			color: inherit;
			transition: transform .15s ease, box-shadow .15s ease;
		}
		.gwt-welcome-card:hover {
			transform: translateY(-2px);
			box-shadow: 0 8px 20px rgba(0,64,113,0.08);
		}
		.gwt-welcome-photo {
			width: 80px; height: 80px;
			border-radius: 50%;
			object-fit: cover;
			margin-bottom: 12px;
			background: #e5e7eb;
		}
		.gwt-welcome-initials {
			display: flex; align-items: center; justify-content: center;
			background: #c7eafb; color: #004071;
			font-weight: 700; font-size: 24px;
			font-family: 'Mulish', sans-serif;
		}
		.gwt-welcome-name {
			font-family: 'Mulish', sans-serif;
			font-weight: 700; font-size: 14px;
			color: #0a2540; letter-spacing: -0.01em;
			margin-bottom: 2px;
		}
		.gwt-welcome-position {
			font-family: 'Mulish', sans-serif;
			font-size: 12px; color: #6b7280;
		}
		.gwt-welcome-standort {
			font-size: 11px; color: #9ca3af;
			margin-top: 4px;
			text-transform: uppercase; letter-spacing: 0.06em;
		}

		/* ===== LIST-Layout ===== */
		.gwt-welcome-list {
			list-style: none; padding: 0; margin: 0;
			display: flex; flex-direction: column; gap: 4px;
		}
		.gwt-welcome-list-item { margin: 0; }
		.gwt-welcome-list-link {
			display: flex; align-items: center; gap: 12px;
			padding: 10px 8px; border-radius: 8px;
			text-decoration: none; color: inherit;
			transition: background .15s ease;
		}
		.gwt-welcome-list-link:hover { background: #f5f7fa; }
		.gwt-welcome-list-photo {
			width: 40px; height: 40px;
			border-radius: 50%;
			object-fit: cover;
			background: #e5e7eb;
			flex-shrink: 0;
		}
		.gwt-welcome-list-photo.gwt-welcome-initials {
			display: flex; align-items: center; justify-content: center;
			background: #c7eafb; color: #004071;
			font-weight: 700; font-size: 14px;
			font-family: 'Mulish', sans-serif;
		}
		.gwt-welcome-list-text {
			display: flex; flex-direction: column;
			min-width: 0; flex: 1;
		}
		.gwt-welcome-list-name {
			font-family: 'Mulish', sans-serif;
			font-weight: 700; font-size: 14px;
			color: #0a2540; letter-spacing: -0.01em;
			white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
		}
		.gwt-welcome-list-position {
			font-family: 'Mulish', sans-serif;
			font-size: 12px; color: #6b7280;
			white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
		}
		</style>
		<script>
		(function(){
			function shuffle(arr){
				for (let i=arr.length-1;i>0;i--){
					const j=Math.floor(Math.random()*(i+1));
					[arr[i],arr[j]]=[arr[j],arr[i]];
				}
				return arr;
			}
			document.querySelectorAll('.gwt-welcome-grid, .gwt-welcome-list').forEach(function(container){
				const show = parseInt(container.dataset.show || '6', 10);
				const items = Array.from(container.children);
				if (items.length <= show) return;
				const keep = new Set(shuffle(items.slice()).slice(0, show));
				items.forEach(function(it){ if (!keep.has(it)) it.style.display='none'; });
			});
		})();
		</script>
		<?php
	}

	return ob_get_clean();
} );

/* ---------------------------------------------------------------------- */
/*  Optionale Bricks-Query-Integration                                     */
/*  ermoeglicht Query-Loop in Bricks mit queryType = "gwt_welcome_employees" */
/* ---------------------------------------------------------------------- */
add_filter( 'bricks/posts/query_vars', function( $query_vars, $settings, $element_id ) {
	if ( ! empty( $settings['query']['objectType'] ) && $settings['query']['objectType'] === 'gwt_welcome_employees' ) {
		$monate = ! empty( $settings['query']['gwt_monate'] ) ? absint( $settings['query']['gwt_monate'] ) : 2;
		$cutoff = date( 'Y-m-d', strtotime( '-' . $monate . ' months' ) );
		$query_vars['post_type']   = 'employee';
		$query_vars['post_status'] = 'publish';
		$query_vars['meta_query']  = [
			[ 'key' => '_employee_eintrittsdatum', 'value' => $cutoff, 'compare' => '>=', 'type' => 'DATE' ],
			[ 'key' => '_employee_status', 'value' => 'aktiv' ],
		];
		$query_vars['orderby']  = 'meta_value';
		$query_vars['meta_key'] = '_employee_eintrittsdatum';
		$query_vars['order']    = 'DESC';
	}
	return $query_vars;
}, 10, 3 );
