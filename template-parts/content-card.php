<?php
/**
 * Karte für Blogbeiträge und Seiten in Listen.
 *
 * @package eGovPod
 */

if ( egovpod_is_episode() ) {
	get_template_part( 'template-parts/episode-card', null, isset( $args ) ? $args : array() );
	return;
}
$egp_heading = isset( $args['heading'] ) ? $args['heading'] : 'h3';
?>
<article <?php post_class( 'kern-card kern-card--interactive egp-card' ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<div class="kern-card__media"><?php the_post_thumbnail( 'medium_large', array( 'alt' => '', 'loading' => 'lazy' ) ); ?></div>
	<?php endif; ?>
	<div class="kern-card__container">
		<header class="kern-card__header">
			<?php
			$egp_cats = get_the_category();
			if ( $egp_cats ) :
				?>
				<p class="kern-preline"><?php echo esc_html( $egp_cats[0]->name ); ?></p>
			<?php endif; ?>
			<<?php echo tag_escape( $egp_heading ); ?> class="kern-title">
				<a class="kern-link kern-link--stretched" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
			</<?php echo tag_escape( $egp_heading ); ?>>
		</header>
		<div class="kern-card__body">
			<p class="kern-body kern-body--small"><?php echo esc_html( wp_strip_all_tags( get_the_excerpt() ) ); ?></p>
		</div>
		<footer class="kern-card__footer egp-card__footer">
			<?php egovpod_posted_on(); ?>
		</footer>
	</div>
</article>
