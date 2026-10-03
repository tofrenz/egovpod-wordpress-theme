<?php
/**
 * Episodenarchiv (Podlove: Einstellungen → Website → Episodenarchiv aktivieren).
 *
 * Läuft im Seitengerüst des Eltern-Themes KERN-UX (main > .kern-container).
 * Sortierung, Jahresfilter und Mitwirkende kommen aus inc/archive.php.
 *
 * @package eGovPod
 */

get_header();
?>

<?php if ( function_exists( 'kern_ux_breadcrumb' ) ) { kern_ux_breadcrumb(); } ?>

<header class="kux-page-header egp-archive-head">
	<p class="kern-preline"><?php echo esc_html( egovpod_podcast_title() ); ?></p>
	<h1 class="kern-heading-x-large"><?php esc_html_e( 'Alle Episoden', 'egovpod' ); ?></h1>
	<?php egovpod_episode_search_form(); ?>
</header>

<?php egovpod_year_nav(); ?>

<?php if ( have_posts() ) : ?>

	<?php
	egovpod_archive_toolbar( $GLOBALS['wp_query'] );
	egovpod_prime_episode_contributors( wp_list_pluck( $GLOBALS['wp_query']->posts, 'ID' ) );
	?>

	<div class="egp-grid">
		<?php
		while ( have_posts() ) :
			the_post();
			get_template_part( 'template-parts/episode-card', null, array( 'heading' => 'h2' ) );
		endwhile;
		?>
	</div>

	<?php
	add_filter( 'paginate_links', 'egovpod_year_in_pagination' );
	egovpod_pagination();
	remove_filter( 'paginate_links', 'egovpod_year_in_pagination' );
	?>

<?php else : ?>
	<?php get_template_part( 'template-parts/content', 'none' ); ?>
<?php endif; ?>

<?php
get_footer();
