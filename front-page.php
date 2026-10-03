<?php
/**
 * Startseite: Podcast-Vorstellung, neueste Episode mit Player, weitere Episoden, Blog.
 *
 * @package eGovPod
 */

get_header();

$egp_hero_title = get_theme_mod( 'egovpod_hero_title', '' );
$egp_hero_title = $egp_hero_title ? $egp_hero_title : egovpod_podcast_title();
$egp_hero_text  = get_theme_mod( 'egovpod_hero_text', '' );
$egp_hero_text  = $egp_hero_text ? $egp_hero_text : egovpod_podcast_summary();
$egp_cover      = egovpod_podcast_cover_url( 600 );
$egp_has_eps    = post_type_exists( 'podcast' );
?>

<section class="egp-panel egp-hero kern-layer kern-level-1" aria-labelledby="egp-hero-title">
	<div class="egp-panel__inner egp-hero__inner">
		<div class="egp-hero__text">
			<hgroup class="kern-hgroup">
				<h1 id="egp-hero-title" class="kern-heading-x-large"><?php echo esc_html( $egp_hero_title ); ?></h1>
				<?php if ( egovpod_podcast_subtitle() ) : ?>
					<p class="kern-subline kern-subline--large"><?php echo esc_html( egovpod_podcast_subtitle() ); ?></p>
				<?php endif; ?>
			</hgroup>
			<?php if ( $egp_hero_text ) : ?>
				<div class="kern-body kern-body--large egp-hero__summary"><?php echo wp_kses_post( wpautop( $egp_hero_text ) ); ?></div>
			<?php endif; ?>
			<div class="egp-hero__actions">
				<?php $egp_subscribe = egovpod_subscribe_button( 'egp-subscribe-hero', 'kern-btn--large' ); ?>
				<?php if ( $egp_subscribe ) : ?>
					<?php echo $egp_subscribe; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php endif; ?>
				<?php if ( $egp_has_eps ) : ?>
					<a class="kern-btn kern-btn--secondary kern-btn--large" href="<?php echo esc_url( egovpod_episode_archive_url() ); ?>">
						<span class="kern-label"><?php esc_html_e( 'Alle Episoden', 'egovpod' ); ?></span>
						<?php echo egovpod_icon( 'arrow-forward' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
		<?php if ( $egp_cover ) : ?>
			<img class="egp-hero__cover" src="<?php echo esc_url( $egp_cover ); ?>" alt="<?php echo esc_attr( sprintf( /* translators: %s: Podcast */ __( 'Cover von %s', 'egovpod' ), egovpod_podcast_title() ) ); ?>" width="360" height="360">
		<?php endif; ?>
	</div>
</section>

<?php
// Inhalte einer statischen Startseite (Einstellungen → Lesen).
if ( is_page() ) :
	while ( have_posts() ) :
		the_post();
		if ( get_the_content() ) :
			?>
			<section class="egp-section">
				<div class="kux-prose entry-content"><?php the_content(); ?></div>
			</section>
			<?php
		endif;
	endwhile;
endif;
?>

<?php if ( $egp_has_eps ) : ?>
	<?php
	$egp_latest = new WP_Query(
		array(
			'post_type'           => 'podcast',
			'posts_per_page'      => 1 + absint( get_theme_mod( 'egovpod_front_count', 6 ) ),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);
	?>
	<?php if ( $egp_latest->have_posts() ) : ?>
		<?php
		$egp_latest->the_post();
		$egp_player = egovpod_podlove_active() ? egovpod_web_player( get_the_ID() ) : '';
		$egp_ep_cov = egovpod_cover_url( get_the_ID(), 400 );
		// Beitragsbild der Folge (Banner 3:1); nur wenn keins da ist, das Cover.
		$egp_ep_ban = egovpod_episode_banner( null, 'egp-latest__banner' );
		?>
		<section class="egp-section egp-latest" aria-labelledby="egp-latest-title">
			<h2 id="egp-latest-title" class="kern-heading-medium"><?php esc_html_e( 'Neueste Folge', 'egovpod' ); ?></h2>
			<div class="kern-card kern-card--hug kern-card--large egp-latest__card">
				<div class="kern-card__container">
					<?php if ( $egp_ep_ban ) : ?>
						<?php echo $egp_ep_ban; // phpcs:ignore WordPress.Security.EscapeOutput -- in egovpod_episode_banner() aufgebaut und escaped. ?>
					<?php endif; ?>
					<div class="egp-latest__head<?php echo $egp_ep_ban ? ' egp-latest__head--breit' : ''; ?>">
						<?php if ( ! $egp_ep_ban && $egp_ep_cov ) : ?>
							<img class="egp-latest__cover" src="<?php echo esc_url( $egp_ep_cov ); ?>" alt="" width="200" height="200">
						<?php endif; ?>
						<div class="kern-card__header">
							<?php if ( egovpod_episode_number() ) : ?>
								<p class="kern-preline"><?php echo esc_html( sprintf( /* translators: %s: Nummer */ __( 'Folge %s', 'egovpod' ), egovpod_episode_number() ) ); ?></p>
							<?php endif; ?>
							<h3 class="kern-title kern-title--large"><a class="kern-link" href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
							<?php if ( egovpod_episode_subtitle() ) : ?>
								<p class="kern-subline"><?php echo esc_html( egovpod_episode_subtitle() ); ?></p>
							<?php endif; ?>
							<?php egovpod_episode_meta(); ?>
						</div>
					</div>
					<?php if ( $egp_player ) : ?>
						<div class="egp-player"><?php echo $egp_player; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
					<?php endif; ?>
					<div class="kern-card__body">
						<p class="kern-body"><?php echo esc_html( wp_trim_words( egovpod_episode_summary() ? egovpod_episode_summary() : get_the_excerpt(), 55, ' …' ) ); ?></p>
					</div>
					<div class="kern-card__footer">
						<a class="kern-btn kern-btn--primary" href="<?php the_permalink(); ?>">
							<span class="kern-label"><?php esc_html_e( 'Zur Folge mit Shownotes', 'egovpod' ); ?></span>
						</a>
					</div>
				</div>
			</div>
		</section>

		<?php if ( $egp_latest->have_posts() ) : ?>
			<section class="egp-section" aria-labelledby="egp-more-title">
				<div class="egp-section__head">
					<h2 id="egp-more-title" class="kern-heading-medium"><?php esc_html_e( 'Weitere Episoden', 'egovpod' ); ?></h2>
					<a class="kern-link" href="<?php echo esc_url( egovpod_episode_archive_url() ); ?>"><?php esc_html_e( 'Zum Episodenarchiv', 'egovpod' ); ?></a>
				</div>
				<div class="egp-grid">
					<?php
					while ( $egp_latest->have_posts() ) :
						$egp_latest->the_post();
						get_template_part( 'template-parts/episode-card' );
					endwhile;
					?>
				</div>
			</section>
		<?php endif; ?>
		<?php wp_reset_postdata(); ?>
	<?php endif; ?>
<?php endif; ?>

<?php if ( get_theme_mod( 'egovpod_front_posts', true ) ) : ?>
	<?php
	$egp_posts = new WP_Query(
		array(
			'post_type'           => 'post',
			'posts_per_page'      => 3,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);
	?>
	<?php if ( $egp_posts->have_posts() ) : ?>
		<section class="egp-section" aria-labelledby="egp-blog-title">
			<div class="egp-section__head">
				<h2 id="egp-blog-title" class="kern-heading-medium"><?php esc_html_e( 'Aus dem Blog', 'egovpod' ); ?></h2>
				<?php $egp_blog = get_option( 'page_for_posts' ); ?>
				<?php if ( $egp_blog ) : ?>
					<a class="kern-link" href="<?php echo esc_url( get_permalink( $egp_blog ) ); ?>"><?php esc_html_e( 'Alle Beiträge', 'egovpod' ); ?></a>
				<?php endif; ?>
			</div>
			<div class="egp-grid">
				<?php
				while ( $egp_posts->have_posts() ) :
					$egp_posts->the_post();
					get_template_part( 'template-parts/content-card' );
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		</section>
	<?php endif; ?>
<?php endif; ?>

<?php
get_footer();
