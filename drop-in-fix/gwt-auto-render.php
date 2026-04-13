<?php
/**
 * GWT Auto-Render – rendert Mitarbeiter-Einzelseiten und Startseiten-Block
 * komplett automatisch, ohne dass ein Bricks-Template angelegt werden muss.
 *
 * Installation:
 * 1. Diese Datei nach wp-content/themes/gwt-intranet-child/inc/ kopieren
 * 2. In functions.php ergänzen:
 *      require_once get_stylesheet_directory() . '/inc/gwt-auto-render.php';
 * 3. Fertig – keine Bricks-Templates, keine Conditions nötig.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ---------------------------------------------------------------------- */
/*  Inline-Styles laden (für alle Seiten, die Mitarbeiter oder Startseite
/*  rendern – klein, daher vertretbar inline)                              */
/* ---------------------------------------------------------------------- */
add_action( 'wp_head', function() {
	if ( ! is_singular( 'employee' ) && ! has_shortcode( get_post() ? get_post()->post_content : '', 'gwt_startseite' ) ) {
		return;
	}
	?>
	<style>
		.gwt-wrap { max-width: 1200px; margin: 0 auto; padding: 48px 24px; font-family: 'Mulish', system-ui, sans-serif; color: #1a1a1a; }
		.gwt-card { background: #fff; border-radius: 12px; padding: 32px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
		.gwt-card + .gwt-card { margin-top: 32px; }
		.gwt-grid-2 { display: grid; grid-template-columns: 1fr 2fr; gap: 24px; }
		@media (max-width: 768px) { .gwt-grid-2 { grid-template-columns: 1fr; } }
		.gwt-h1 { color: #004071; font-size: 36px; font-weight: 700; margin: 0 0 24px; line-height: 1.2; }
		.gwt-h2 { color: #004071; font-size: 22px; font-weight: 700; margin: 0 0 16px; }
		.gwt-h3 { color: #004071; font-size: 18px; font-weight: 700; margin: 0 0 12px; }
		.gwt-emp-row { display: flex; gap: 32px; align-items: center; flex-wrap: wrap; }
		.gwt-emp-photo { width: 200px; height: 200px; border-radius: 50%; object-fit: cover; background: #c7eafb; flex-shrink: 0; }
		.gwt-emp-info { flex: 1; min-width: 240px; }
		.gwt-emp-name { color: #004071; font-size: 32px; font-weight: 700; margin: 0 0 4px; }
		.gwt-emp-pos { color: #005e9e; font-size: 18px; font-weight: 600; margin: 0 0 20px; }
		.gwt-emp-line { color: #333; font-size: 15px; margin: 6px 0; }
		.gwt-emp-line a { color: #005e9e; text-decoration: none; }
		.gwt-emp-line a:hover { text-decoration: underline; }
		.gwt-news-item { padding: 14px 0; border-bottom: 1px solid #e5e7eb; }
		.gwt-news-item:last-child { border-bottom: none; }
		.gwt-news-item a { color: #004071; font-weight: 600; text-decoration: none; font-size: 16px; }
		.gwt-news-item a:hover { color: #005e9e; }
		.gwt-news-excerpt { color: #555; font-size: 13px; margin-top: 4px; }
		.gwt-bulletin-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; }
		.gwt-bulletin-item { background: #f5f7fa; padding: 16px; border-radius: 8px; }
		.gwt-bulletin-item a { color: #004071; font-weight: 600; text-decoration: none; display: block; margin-bottom: 6px; font-size: 15px; }
		.gwt-bulletin-item a:hover { color: #005e9e; }
		.gwt-bulletin-expiry { color: #888; font-size: 12px; font-style: italic; margin-top: 8px; }
		.gwt-welcome-title::before { content: "🎉 "; }
	</style>
	<?php
} );

/* ---------------------------------------------------------------------- */
/*  Mitarbeiter-Einzelseite: automatisches Rendering                       */
/*  Hook in the_content – wenn User nichts im Editor eingegeben hat,       */
/*  zeigen wir die vollständige Profil-Card.                               */
/* ---------------------------------------------------------------------- */
add_filter( 'the_content', function( $content ) {
	if ( ! is_singular( 'employee' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$post_id    = get_the_ID();
	$vorname    = get_post_meta( $post_id, '_employee_vorname', true );
	$nachname   = get_post_meta( $post_id, '_employee_nachname', true );
	$position   = get_post_meta( $post_id, '_employee_position', true );
	$email      = get_post_meta( $post_id, '_employee_email', true );
	$telefon    = get_post_meta( $post_id, '_employee_telefon', true );
	$mobil      = get_post_meta( $post_id, '_employee_mobil', true );
	$foto       = get_the_post_thumbnail_url( $post_id, 'employee-thumbnail' );
	$name       = trim( $vorname . ' ' . $nachname );
	if ( empty( $name ) ) $name = get_the_title();

	ob_start();
	?>
	<div class="gwt-wrap">
		<div class="gwt-card">
			<div class="gwt-emp-row">
				<?php if ( $foto ) : ?>
					<img src="<?php echo esc_url( $foto ); ?>" alt="<?php echo esc_attr( $name ); ?>" class="gwt-emp-photo">
				<?php else : ?>
					<div class="gwt-emp-photo" style="display:flex;align-items:center;justify-content:center;color:#004071;font-size:48px;font-weight:700;">
						<?php echo esc_html( strtoupper( substr( $vorname, 0, 1 ) . substr( $nachname, 0, 1 ) ) ); ?>
					</div>
				<?php endif; ?>

				<div class="gwt-emp-info">
					<div class="gwt-emp-name"><?php echo esc_html( $name ); ?></div>
					<?php if ( $position ) : ?>
						<div class="gwt-emp-pos"><?php echo esc_html( $position ); ?></div>
					<?php endif; ?>
					<?php if ( $telefon ) : ?>
						<div class="gwt-emp-line">📞 <a href="tel:<?php echo esc_attr( $telefon ); ?>"><?php echo esc_html( $telefon ); ?></a></div>
					<?php endif; ?>
					<?php if ( $mobil ) : ?>
						<div class="gwt-emp-line">📱 <a href="tel:<?php echo esc_attr( $mobil ); ?>"><?php echo esc_html( $mobil ); ?></a></div>
					<?php endif; ?>
					<?php if ( $email ) : ?>
						<div class="gwt-emp-line">✉️ <a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></div>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( trim( strip_tags( $content ) ) ) : ?>
				<div style="margin-top:32px;padding-top:24px;border-top:1px solid #e5e7eb;">
					<?php echo $content; ?>
				</div>
			<?php endif; ?>
		</div>

		<?php echo do_shortcode( '[mitarbeiter_navigation]' ); ?>
	</div>
	<?php
	return ob_get_clean();
}, 20 );


/* ---------------------------------------------------------------------- */
/*  Shortcode [gwt_startseite]                                             */
/*  Komplette Intranet-Startseite in eine normale Seite einbetten.         */
/* ---------------------------------------------------------------------- */
add_shortcode( 'gwt_startseite', function() {
	$user      = wp_get_current_user();
	$vorname   = '';
	if ( $user && $user->ID ) {
		$vorname = $user->user_firstname ?: $user->display_name;
	}

	ob_start();
	?>
	<div class="gwt-wrap">

		<h1 class="gwt-h1">
			<?php echo $vorname ? esc_html( sprintf( 'Willkommen, %s!', $vorname ) ) : 'Willkommen!'; ?>
		</h1>

		<div class="gwt-grid-2">
			<div class="gwt-card">
				<h2 class="gwt-h3">Wetter</h2>
				<?php echo do_shortcode( '[wetter_widget]' ); ?>
			</div>

			<div class="gwt-card">
				<h2 class="gwt-h3">Aktuelle News</h2>
				<?php
				$news = new WP_Query( [
					'post_type'      => 'post',
					'posts_per_page' => 3,
					'post_status'    => 'publish',
				] );
				if ( $news->have_posts() ) {
					while ( $news->have_posts() ) {
						$news->the_post();
						echo '<div class="gwt-news-item">';
						echo '<a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a>';
						echo '<div class="gwt-news-excerpt">' . esc_html( wp_trim_words( get_the_excerpt(), 18 ) ) . '</div>';
						echo '</div>';
					}
					wp_reset_postdata();
				} else {
					echo '<p style="color:#888;">Noch keine News vorhanden.</p>';
				}
				?>
			</div>
		</div>

		<div class="gwt-card" style="margin-top:24px;">
			<h2 class="gwt-h2 gwt-welcome-title">Welcome on Board!</h2>
			<?php echo do_shortcode( '[welcome_bereich anzahl="6" monate="2"]' ); ?>
		</div>

		<div class="gwt-card" style="margin-top:24px;">
			<h2 class="gwt-h2">Schwarzes Brett</h2>
			<?php
			$bulletins = new WP_Query( [
				'post_type'      => 'bulletin_board',
				'posts_per_page' => 6,
				'post_status'    => 'publish',
			] );
			if ( $bulletins->have_posts() ) {
				echo '<div class="gwt-bulletin-grid">';
				while ( $bulletins->have_posts() ) {
					$bulletins->the_post();
					$expiry = get_post_meta( get_the_ID(), '_bulletin_expiry_date', true );
					echo '<div class="gwt-bulletin-item">';
					echo '<a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a>';
					echo '<div style="color:#555;font-size:13px;">' . esc_html( wp_trim_words( get_the_excerpt(), 14 ) ) . '</div>';
					if ( $expiry ) {
						echo '<div class="gwt-bulletin-expiry">Gültig bis: ' . esc_html( date_i18n( 'd.m.Y', strtotime( $expiry ) ) ) . '</div>';
					}
					echo '</div>';
				}
				echo '</div>';
				wp_reset_postdata();
			} else {
				echo '<p style="color:#888;">Noch keine Aushänge vorhanden.</p>';
			}
			?>
		</div>

	</div>
	<?php
	return ob_get_clean();
} );
