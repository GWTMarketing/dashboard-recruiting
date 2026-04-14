<?php
/**
 * WPCode Snippet: [welcome_liste] Shortcode
 * ==========================================
 *
 * INSTALLATION (ohne FTP/Dateimanager):
 * 1. WP Admin → WPCode → Code Snippets → Add New
 * 2. "Add Your Custom Code (New Snippet)" waehlen
 * 3. Code Type: "PHP Snippet"
 * 4. Title: "GWT Welcome Liste"
 * 5. Den gesamten Code UNTEN (ab "add_shortcode") einfuegen –
 *    OHNE die "<?php" Zeile am Anfang
 * 6. Insertion: "Auto Insert" → Location: "Run Everywhere"
 * 7. Activate (Toggle oben rechts auf AN)
 * 8. Save Snippet
 *
 * Nach dem Aktivieren funktioniert [welcome_liste] auf jeder Seite.
 * Die bestehende Datei welcome-bereich.php im Theme bleibt unveraendert.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // diese Zeile NICHT mit in WPCode kopieren

// ===== AB HIER IN WPCODE EINFUEGEN =====

add_shortcode( 'welcome_liste', function( $atts ) {
	$atts = shortcode_atts( [
		'anzahl' => '8',
		'monate' => '2',
	], $atts, 'welcome_liste' );

	$anzahl = max( 1, absint( $atts['anzahl'] ) );
	$monate = max( 1, absint( $atts['monate'] ) );
	$cutoff = date( 'Y-m-d', strtotime( '-' . $monate . ' months' ) );

	$query = new WP_Query( [
		'post_type'      => 'employee',
		'post_status'    => 'publish',
		'posts_per_page' => 50,
		'meta_query'     => [
			[ 'key' => '_employee_eintrittsdatum', 'value' => $cutoff, 'compare' => '>=', 'type' => 'DATE' ],
			[ 'key' => '_employee_status', 'value' => 'aktiv' ],
		],
		'orderby'  => 'meta_value',
		'meta_key' => '_employee_eintrittsdatum',
		'order'    => 'DESC',
	] );

	if ( ! $query->have_posts() ) {
		return '<div style="color:#9ca3af;font-size:14px;font-family:Mulish,sans-serif;">Aktuell keine neuen Mitarbeiter.</div>';
	}

	$items = [];
	while ( $query->have_posts() ) {
		$query->the_post();
		$pid       = get_the_ID();
		$photo_url = get_the_post_thumbnail_url( $pid, 'thumbnail' ) ?: '';
		$vorname   = get_post_meta( $pid, '_employee_vorname', true );
		$nachname  = get_post_meta( $pid, '_employee_nachname', true );
		$name      = trim( $vorname . ' ' . $nachname );
		if ( empty( $name ) ) $name = get_the_title();
		$position  = get_post_meta( $pid, '_employee_position', true );
		$initials  = strtoupper(
			( $vorname  ? mb_substr( $vorname, 0, 1 )  : mb_substr( $name, 0, 1 ) ) .
			( $nachname ? mb_substr( $nachname, 0, 1 ) : '' )
		);
		$items[] = compact( 'pid', 'photo_url', 'name', 'position', 'initials' ) + [ 'url' => get_permalink( $pid ) ];
	}
	wp_reset_postdata();

	ob_start();
	?>
	<ul class="gwt-welcome-liste" data-show="<?php echo esc_attr( $anzahl ); ?>">
		<?php foreach ( $items as $it ) : ?>
			<li>
				<a href="<?php echo esc_url( $it['url'] ); ?>">
					<?php if ( $it['photo_url'] ) : ?>
						<img src="<?php echo esc_url( $it['photo_url'] ); ?>" alt="<?php echo esc_attr( $it['name'] ); ?>">
					<?php else : ?>
						<span class="gwt-welcome-liste-initials"><?php echo esc_html( $it['initials'] ); ?></span>
					<?php endif; ?>
					<span class="gwt-welcome-liste-text">
						<span class="gwt-welcome-liste-name"><?php echo esc_html( $it['name'] ); ?></span>
						<?php if ( $it['position'] ) : ?>
							<span class="gwt-welcome-liste-position"><?php echo esc_html( $it['position'] ); ?></span>
						<?php endif; ?>
					</span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
	<style>
	.gwt-welcome-liste { list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:2px; }
	.gwt-welcome-liste li { margin:0; }
	.gwt-welcome-liste a {
		display:flex; align-items:center; gap:12px;
		padding:10px 8px; border-radius:10px;
		text-decoration:none; color:inherit;
		transition: background .15s ease;
	}
	.gwt-welcome-liste a:hover { background:#f5f7fa; }
	.gwt-welcome-liste img,
	.gwt-welcome-liste-initials {
		width:40px; height:40px; border-radius:50%;
		object-fit:cover; flex-shrink:0;
		background:#e5e7eb;
	}
	.gwt-welcome-liste-initials {
		display:flex; align-items:center; justify-content:center;
		background:#c7eafb; color:#004071;
		font-family:'Mulish',sans-serif;
		font-weight:700; font-size:13px;
	}
	.gwt-welcome-liste-text { display:flex; flex-direction:column; min-width:0; flex:1; }
	.gwt-welcome-liste-name {
		font-family:'Mulish',sans-serif;
		font-weight:700; font-size:14px;
		color:#0a2540; letter-spacing:-0.01em;
		white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
	}
	.gwt-welcome-liste-position {
		font-family:'Mulish',sans-serif;
		font-size:12px; color:#6b7280;
		white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
	}
	</style>
	<script>
	(function(){
		document.querySelectorAll('.gwt-welcome-liste').forEach(function(ul){
			var show = parseInt(ul.dataset.show || '8', 10);
			var items = Array.prototype.slice.call(ul.children);
			if (items.length <= show) return;
			// Fisher-Yates shuffle
			for (var i = items.length - 1; i > 0; i--) {
				var j = Math.floor(Math.random() * (i + 1));
				var t = items[i]; items[i] = items[j]; items[j] = t;
			}
			var keep = items.slice(0, show);
			Array.prototype.slice.call(ul.children).forEach(function(it){
				if (keep.indexOf(it) === -1) it.style.display = 'none';
			});
		});
	})();
	</script>
	<?php
	return ob_get_clean();
} );
