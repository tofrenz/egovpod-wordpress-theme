<?php
/**
 * Template Name: Episodenarchiv
 * Template Post Type: page
 *
 * Alternative zum Podlove-Episodenarchiv: eine normale Seite, die alle Episoden listet.
 *
 * @package eGovPod
 */

get_header();

$egp_paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
$egp_query = new WP_Query(
	array(
		'post_type'      => 'podcast',
		'posts_per_page' => (int) get_theme_mod( 'egovpod_archive_per_page', 12 ),
		'paged'          => $egp_paged,
	)
);
?>

<?php if ( function_exists( 'kern_ux_breadcrumb' ) ) { kern_ux_breadcrumb(); } ?>

<header class="kux-page-header egp-archive-head">
	<p class="kern-preline"><?php echo esc_html( egovpod_podcast_title() ); ?></p>
	<?php the_title( '<h1 class="kern-heading-x-large">', '</h1>' ); ?>
	<?php
	while ( have_posts() ) :
		the_post();
		if ( get_the_content() ) :
			?>
			<div class="kux-prose"><?php the_content(); ?></div>
			<?php
		endif;
	endwhile;
	?>
	<?php egovpod_episode_search_form(); ?>
</header>

<?php if ( $egp_query->have_posts() ) : ?>
	<div class="egp-grid">
		<?php
		while ( $egp_query->have_posts() ) :
			$egp_query->the_post();
			get_template_part( 'template-parts/episode-card', null, array( 'heading' => 'h2' ) );
		endwhile;
		?>
	</div>
	<?php
	$GLOBALS['wp_query']->max_num_pages = $egp_query->max_num_pages; // Für paginate_links().
	egovpod_pagination();
	wp_reset_postdata();
	?>
<?php else : ?>
	<?php get_template_part( 'template-parts/content', 'none' ); ?>
<?php endif; ?>

<?php
get_footer();
