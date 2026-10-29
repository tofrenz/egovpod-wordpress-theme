<?php
/**
 * 404 – Seite nicht gefunden.
 *
 * Statt einer Sackgasse: Suche über die Episoden, die neuesten Folgen und der
 * Abo-Knopf. Die Suche geht an die Seite mit dem Template „Episodenarchiv",
 * weil nur dort `egp_q` ausgewertet wird; gibt es sie nicht, fällt das
 * Formular auf die allgemeine Suche nach Episoden zurück.
 *
 * @package eGovPod
 */

get_header();

$egp_has_eps = post_type_exists( 'podcast' );

// Seite mit dem Episodenarchiv-Template suchen – sie versteht `egp_q`.
$egp_archiv_seiten = get_pages(
	array(
		'meta_key'   => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'meta_value' => 'page-templates/template-episodes.php', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		'number'     => 1,
	)
);
$egp_such_basis = $egp_archiv_seiten ? get_permalink( $egp_archiv_seiten[0] ) : '';
?>

<header class="kux-page-header egp-404__head">
	<p class="kern-preline"><?php esc_html_e( 'Fehler 404', 'egovpod' ); ?></p>
	<h1 class="kern-heading-x-large"><?php esc_html_e( 'Für diese Seite ist leider niemand zuständig.', 'egovpod' ); ?></h1>
	<div class="kux-prose egp-404__text">
		<p class="kern-body kern-body--large">
			<?php esc_html_e( 'Wir haben alle Ablagen durchsucht, auch den Keller: Die Seite, die du suchst, gibt es hier nicht. Vielleicht wurde sie umbenannt, verschoben oder sie war nie da.', 'egovpod' ); ?>
		</p>
		<p class="kern-body">
			<?php esc_html_e( 'Ein Vorschlag zur Güte: Such doch einfach direkt in den Folgen.', 'egovpod' ); ?>
		</p>
	</div>
	<?php
	if ( $egp_has_eps && function_exists( 'egovpod_episode_search_form' ) ) {
		egovpod_episode_search_form( $egp_such_basis );
	}
	?>
	<div class="egp-404__links">
		<a class="kern-btn kern-btn--secondary" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<span class="kern-label"><?php esc_html_e( 'Zur Startseite', 'egovpod' ); ?></span>
		</a>
		<?php if ( $egp_has_eps ) : ?>
			<a class="kern-btn kern-btn--secondary" href="<?php echo esc_url( egovpod_episode_archive_url() ); ?>">
				<span class="kern-label"><?php esc_html_e( 'Alle Episoden', 'egovpod' ); ?></span>
				<?php echo egovpod_icon( 'arrow-forward' ); // phpcs:ignore WordPress.Security.EscapeOutput -- feste SVG aus dem Theme. ?>
			</a>
		<?php endif; ?>
		<?php
		/*
		 * Seite mit dem Teilnehmer-Template suchen. Über WP_Query statt
		 * get_pages(): Letzteres findet die Seite auf dieser Installation
		 * nicht, vermutlich weil sie eine Unterseite ist.
		 */
		$egp_leute_q = new WP_Query(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => 'page-templates/template-contributors.php', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		$egp_leute = $egp_leute_q->posts ? get_permalink( $egp_leute_q->posts[0] ) : '';
		?>
		<?php if ( $egp_leute ) : ?>
			<a class="kern-btn kern-btn--secondary" href="<?php echo esc_url( $egp_leute ); ?>">
				<span class="kern-label"><?php esc_html_e( 'Teilnehmer:innen', 'egovpod' ); ?></span>
			</a>
		<?php endif; ?>
	</div>
</header>

<?php if ( $egp_has_eps ) : ?>
	<?php
	$egp_neueste = new WP_Query(
		array(
			'post_type'           => 'podcast',
			'posts_per_page'      => 3,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);
	?>
	<?php if ( $egp_neueste->have_posts() ) : ?>
		<section class="egp-section" aria-labelledby="egp-404-neueste">
			<div class="egp-section__head">
				<h2 id="egp-404-neueste" class="kern-heading-medium"><?php esc_html_e( 'Die neuesten Folgen', 'egovpod' ); ?></h2>
				<a class="kern-link" href="<?php echo esc_url( egovpod_episode_archive_url() ); ?>"><?php esc_html_e( 'Zum Episodenarchiv', 'egovpod' ); ?></a>
			</div>
			<?php
			if ( function_exists( 'egovpod_prime_episode_contributors' ) ) {
				egovpod_prime_episode_contributors( wp_list_pluck( $egp_neueste->posts, 'ID' ) );
			}
			?>
			<div class="egp-grid">
				<?php
				while ( $egp_neueste->have_posts() ) :
					$egp_neueste->the_post();
					get_template_part( 'template-parts/episode-card' );
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		</section>
	<?php endif; ?>

	<?php $egp_abo = function_exists( 'egovpod_subscribe_button' ) ? egovpod_subscribe_button( 'egp-subscribe-404', 'kern-btn--large' ) : ''; ?>
	<?php if ( $egp_abo ) : ?>
		<section class="egp-section egp-404__abo kern-layer kern-level-1" aria-labelledby="egp-404-abo">
			<div class="egp-404__abo-inner">
				<div>
					<h2 id="egp-404-abo" class="kern-heading-medium"><?php esc_html_e( 'Gleich ganz abonnieren?', 'egovpod' ); ?></h2>
					<p class="kern-body">
						<?php esc_html_e( 'Dann landet jede neue Folge von selbst in deiner Podcast-App, und du musst keine Adresse mehr von Hand eintippen.', 'egovpod' ); ?>
					</p>
				</div>
				<div class="egp-404__abo-btn">
					<?php echo $egp_abo; // phpcs:ignore WordPress.Security.EscapeOutput -- in egovpod_subscribe_button() aufgebaut und escaped. ?>
				</div>
			</div>
		</section>
	<?php endif; ?>
<?php endif; ?>

<?php
get_footer();
