<?php
/**
 * Episoden-Karte (KERN Card, interaktiv).
 *
 * @package eGovPod
 */

$egp_cover    = egovpod_cover_url( get_the_ID(), 480 );
$egp_subtitle = egovpod_episode_subtitle();
$egp_number   = egovpod_episode_number();
$egp_heading  = isset( $args['heading'] ) ? $args['heading'] : 'h3';
?>
<article <?php post_class( 'kern-card kern-card--interactive egp-card' ); ?>>
	<?php if ( $egp_cover ) : ?>
		<div class="kern-card__media">
			<img src="<?php echo esc_url( $egp_cover ); ?>" alt="" loading="lazy" decoding="async" width="480" height="480">
		</div>
	<?php endif; ?>
	<div class="kern-card__container">
		<header class="kern-card__header">
			<?php if ( $egp_number ) : ?>
				<p class="kern-preline">
					<?php
					/* translators: %s: Episodennummer */
					echo esc_html( sprintf( __( 'Folge %s', 'egovpod' ), $egp_number ) );
					?>
				</p>
			<?php endif; ?>
			<<?php echo tag_escape( $egp_heading ); ?> class="kern-title">
				<a class="kern-link kern-link--stretched" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
			</<?php echo tag_escape( $egp_heading ); ?>>
			<?php if ( $egp_subtitle ) : ?>
				<p class="kern-subline"><?php echo esc_html( $egp_subtitle ); ?></p>
			<?php endif; ?>
		</header>
		<div class="kern-card__body">
			<p class="kern-body kern-body--small"><?php echo esc_html( wp_trim_words( egovpod_episode_summary() ? egovpod_episode_summary() : get_the_excerpt(), 28, ' …' ) ); ?></p>
		</div>
		<footer class="kern-card__footer egp-card__footer">
			<?php egovpod_episode_meta(); ?>
		</footer>
	</div>
</article>
