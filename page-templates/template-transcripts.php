<?php
/**
 * Template Name: Transkripte
 * Template Post Type: page
 *
 * Dieselbe Darstellung wie das Episodenarchiv – Suche, Jahresleiste, Umschalter
 * Karten/Liste, Blätterung –, nur auf Folgen mit Transkript eingeschränkt und
 * mit einer Knopfreihe je Folge zum Herunterladen.
 *
 * Die Liste entsteht bei jedem Aufruf neu aus den Episoden; eine neue Folge
 * steht sofort drin, sobald ihr Transkript in Podlove liegt. Der gespeicherte
 * Seiteninhalt (die alte Linkliste) bleibt unangetastet und kommt zurück,
 * sobald man auf „Standard-Template" zurückstellt.
 *
 * @package eGovPod
 */

get_header();

$egp_paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
$egp_query = new WP_Query( egovpod_archive_query_args( $egp_paged, true ) );
$egp_basis = get_permalink();
?>

<?php if ( function_exists( 'kern_ux_breadcrumb' ) ) { kern_ux_breadcrumb(); } ?>

<header class="kux-page-header egp-archive-head">
	<p class="kern-preline"><?php echo esc_html( egovpod_podcast_title() ); ?></p>
	<?php the_title( '<h1 class="kern-heading-x-large">', '</h1>' ); ?>
	<?php
	while ( have_posts() ) :
		the_post();

		ob_start();
		the_content();
		$egp_intro = egovpod_strip_transcript_list( ob_get_clean() );

		if ( '' !== trim( wp_strip_all_tags( $egp_intro ) ) ) {
			echo '<div class="kux-prose">' . $egp_intro . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- Beitragsinhalt.
		}
	endwhile;
	?>
	<?php egovpod_episode_search_form( $egp_basis ); ?>
</header>

<?php egovpod_year_nav( true, $egp_basis ); ?>

<?php if ( $egp_query->have_posts() ) : ?>

	<?php
	egovpod_archive_toolbar( $egp_query );
	egovpod_prime_episode_contributors( wp_list_pluck( $egp_query->posts, 'ID' ) );
	egovpod_prime_transcripts( wp_list_pluck( $egp_query->posts, 'ID' ) );
	?>

	<div class="egp-grid egp-grid--transkripte">
		<?php
		while ( $egp_query->have_posts() ) :
			$egp_query->the_post();
			get_template_part(
				'template-parts/episode-card',
				null,
				array(
					'heading'    => 'h2',
					'transkript' => true,
				)
			);
		endwhile;
		?>
	</div>

	<?php
	$GLOBALS['wp_query']->max_num_pages = $egp_query->max_num_pages; // Für paginate_links().
	add_filter( 'paginate_links', 'egovpod_year_in_pagination' );
	egovpod_pagination();
	remove_filter( 'paginate_links', 'egovpod_year_in_pagination' );
	wp_reset_postdata();
	?>

<?php else : ?>

	<p class="kern-body egp-archive-bar__zahl">
		<?php esc_html_e( 'Zu den gewählten Folgen liegt kein Transkript vor.', 'egovpod' ); ?>
	</p>

<?php endif; ?>

<?php
get_footer();
