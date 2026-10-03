<?php
/**
 * Einzelne Podlove-Episode.
 *
 * @package eGovPod
 */

get_header();

while ( have_posts() ) :
	the_post();

	$egp_cover    = egovpod_cover_url( get_the_ID(), 600 );
	$egp_banner   = egovpod_episode_banner();
	$egp_number   = egovpod_episode_number();
	$egp_subtitle = egovpod_episode_subtitle();
	$egp_summary  = egovpod_episode_summary();
	$egp_parts    = egovpod_theme_renders_episode_parts();
	?>

	<?php if ( function_exists( 'kern_ux_breadcrumb' ) ) { kern_ux_breadcrumb(); } ?>

	<article id="post-<?php the_ID(); ?>" <?php post_class( 'egp-episode' ); ?>>

		<header class="egp-panel egp-episode__hero kern-layer kern-level-1">
			<div class="egp-panel__inner">
				<?php if ( $egp_banner ) : ?>
					<?php echo $egp_banner; // phpcs:ignore WordPress.Security.EscapeOutput -- in egovpod_episode_banner() aufgebaut und escaped. ?>
				<?php endif; ?>
				<div class="egp-episode__intro<?php echo $egp_banner ? ' egp-episode__intro--breit' : ''; ?>">
					<?php if ( ! $egp_banner && $egp_cover ) : ?>
						<img class="egp-episode__cover" src="<?php echo esc_url( $egp_cover ); ?>" alt="<?php echo esc_attr( sprintf( /* translators: %s: Titel */ __( 'Cover der Episode „%s“', 'egovpod' ), get_the_title() ) ); ?>" width="300" height="300">
					<?php endif; ?>
					<hgroup class="kern-hgroup egp-episode__titles">
						<?php if ( $egp_number ) : ?>
							<p class="kern-preline kern-preline--large">
								<?php
								/* translators: %s: Episodennummer */
								echo esc_html( sprintf( __( 'Folge %s', 'egovpod' ), $egp_number ) );
								?>
							</p>
						<?php endif; ?>
						<?php the_title( '<h1 class="kern-heading-large">', '</h1>' ); ?>
						<?php if ( $egp_subtitle ) : ?>
							<p class="kern-subline kern-subline--large"><?php echo esc_html( $egp_subtitle ); ?></p>
						<?php endif; ?>
					</hgroup>
					<?php egovpod_episode_meta(); ?>
				</div>

				<?php if ( $egp_parts ) : ?>
					<?php $egp_player = egovpod_web_player(); ?>
					<?php if ( $egp_player ) : ?>
						<section class="egp-player" aria-label="<?php esc_attr_e( 'Audio-Player', 'egovpod' ); ?>">
							<?php echo $egp_player; // phpcs:ignore WordPress.Security.EscapeOutput -- Podlove Web Player ?>
						</section>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</header>

		<div class="egp-episode__body">
			<div class="kux-layout">
				<div class="kux-layout__content">

					<?php
					/*
					 * Zusammenfassung: standardmäßig aus. Beim eGovernment Podcast
					 * steht sie bereits als Zitat im Episodentext; das Theme würde
					 * sie sonst ein zweites Mal ausgeben. Customizer → eGovPod →
					 * Doppelte Bausteine.
					 */
					?>
					<?php if ( $egp_summary && get_theme_mod( 'egovpod_show_summary', false ) ) : ?>
						<p class="kern-body kern-body--large egp-lead"><?php echo nl2br( esc_html( $egp_summary ) ); ?></p>
					<?php endif; ?>

					<div class="kux-prose entry-content">
						<?php
						the_content();
						wp_link_pages(
							array(
								'before' => '<nav class="egp-page-links" aria-label="' . esc_attr__( 'Seiten', 'egovpod' ) . '">',
								'after'  => '</nav>',
							)
						);
						?>
					</div>

					<?php if ( $egp_parts ) : ?>

						<?php $egp_shownotes = egovpod_shownotes(); ?>
						<?php if ( $egp_shownotes ) : ?>
							<section class="egp-section" aria-labelledby="egp-shownotes-title">
								<h2 id="egp-shownotes-title" class="kern-heading-medium"><?php esc_html_e( 'Shownotes', 'egovpod' ); ?></h2>
								<div class="egp-shownotes kux-prose"><?php echo $egp_shownotes; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
							</section>
						<?php endif; ?>

						<?php
						/*
						 * Mitwirkende: standardmäßig aus, weil der Episodentext
						 * bereits einen eigenen Block mit den Beteiligten enthält.
						 */
						?>
						<?php $egp_contributors = get_theme_mod( 'egovpod_show_contributors', false ) ? egovpod_contributors() : ''; ?>
						<?php if ( $egp_contributors ) : ?>
							<section class="egp-section" aria-labelledby="egp-contributors-title">
								<h2 id="egp-contributors-title" class="kern-heading-medium"><?php esc_html_e( 'Mitwirkende', 'egovpod' ); ?></h2>
								<div class="egp-contributors"><?php echo $egp_contributors; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
							</section>
						<?php endif; ?>

						<?php if ( get_theme_mod( 'egovpod_show_transcript', true ) ) : ?>
							<?php $egp_transcript = egovpod_transcript(); ?>
							<?php if ( $egp_transcript && wp_strip_all_tags( $egp_transcript ) ) : ?>
								<section class="egp-section" aria-label="<?php esc_attr_e( 'Transkript', 'egovpod' ); ?>">
									<details class="kern-accordion">
										<summary class="kern-accordion__header">
											<span class="kern-title"><?php esc_html_e( 'Transkript anzeigen', 'egovpod' ); ?></span>
										</summary>
										<section class="kern-accordion__body egp-transcript">
											<?php echo $egp_transcript; // phpcs:ignore WordPress.Security.EscapeOutput ?>
										</section>
									</details>
								</section>
							<?php endif; ?>
						<?php endif; ?>

					<?php endif; ?>

					<?php
					$egp_tags = get_the_tag_list( '<ul class="kern-list kern-list--horizontal egp-tags"><li>', '</li><li>', '</li></ul>' );
					if ( $egp_tags && ! is_wp_error( $egp_tags ) ) :
						?>
						<section class="egp-section" aria-label="<?php esc_attr_e( 'Schlagwörter', 'egovpod' ); ?>">
							<?php echo wp_kses_post( $egp_tags ); ?>
						</section>
					<?php endif; ?>
				</div>

				<aside class="kux-layout__sidebar egp-aside" aria-label="<?php esc_attr_e( 'Zur Episode', 'egovpod' ); ?>">

					<?php $egp_subscribe = egovpod_subscribe_button( 'egp-subscribe-episode' ); ?>
					<?php if ( $egp_subscribe ) : ?>
						<div class="kern-card kern-card--hug kern-card--surface egp-aside__card">
							<div class="kern-card__container">
								<div class="kern-card__header">
									<h2 class="kern-title"><?php esc_html_e( 'Podcast abonnieren', 'egovpod' ); ?></h2>
								</div>
								<div class="kern-card__body">
									<p class="kern-body kern-body--small"><?php esc_html_e( 'Keine Folge mehr verpassen – in deiner Podcast-App oder per RSS.', 'egovpod' ); ?></p>
									<div class="egp-subscribe"><?php echo $egp_subscribe; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
								</div>
							</div>
						</div>
					<?php endif; ?>

					<?php if ( $egp_parts && get_theme_mod( 'egovpod_show_downloads', true ) ) : ?>
						<?php $egp_downloads = egovpod_downloads(); ?>
						<?php if ( $egp_downloads ) : ?>
							<div class="kern-card kern-card--hug egp-aside__card">
								<div class="kern-card__container">
									<div class="kern-card__header">
										<h2 class="kern-title"><?php echo egovpod_icon( 'download' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php esc_html_e( 'Herunterladen', 'egovpod' ); ?></h2>
									</div>
									<div class="kern-card__body egp-downloads"><?php echo $egp_downloads; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
								</div>
							</div>
						<?php endif; ?>
					<?php endif; ?>

					<?php if ( is_active_sidebar( 'sidebar-1' ) ) : ?>
						<div class="egp-widgets kux-sidebar"><?php dynamic_sidebar( 'sidebar-1' ); ?></div>
					<?php endif; ?>
				</aside>
			</div>

			<?php
			$egp_prev = get_previous_post();
			$egp_next = get_next_post();
			if ( $egp_prev || $egp_next ) :
				?>
				<nav class="egp-adjacent" aria-label="<?php esc_attr_e( 'Weitere Episoden', 'egovpod' ); ?>">
					<?php if ( $egp_prev ) : ?>
						<a class="kern-card kern-card--hug egp-adjacent__link egp-adjacent__link--prev" href="<?php echo esc_url( get_permalink( $egp_prev ) ); ?>">
							<span class="kern-card__container">
								<span class="kern-preline"><?php echo egovpod_icon( 'arrow-back', 'small' ); // phpcs:ignore ?> <?php esc_html_e( 'Vorherige Folge', 'egovpod' ); ?></span>
								<span class="kern-title kern-title--small"><?php echo esc_html( get_the_title( $egp_prev ) ); ?></span>
							</span>
						</a>
					<?php endif; ?>
					<?php if ( $egp_next ) : ?>
						<a class="kern-card kern-card--hug egp-adjacent__link egp-adjacent__link--next" href="<?php echo esc_url( get_permalink( $egp_next ) ); ?>">
							<span class="kern-card__container">
								<span class="kern-preline"><?php esc_html_e( 'Nächste Folge', 'egovpod' ); ?> <?php echo egovpod_icon( 'arrow-forward', 'small' ); // phpcs:ignore ?></span>
								<span class="kern-title kern-title--small"><?php echo esc_html( get_the_title( $egp_next ) ); ?></span>
							</span>
						</a>
					<?php endif; ?>
				</nav>
			<?php endif; ?>

			<?php if ( get_theme_mod( 'egovpod_show_related', true ) ) : ?>
				<?php $egp_related = egovpod_related_episodes(); ?>
				<?php if ( $egp_related && wp_strip_all_tags( $egp_related ) ) : ?>
					<section class="egp-section egp-related" aria-labelledby="egp-related-title">
						<h2 id="egp-related-title" class="kern-heading-medium"><?php esc_html_e( 'Das könnte dich auch interessieren', 'egovpod' ); ?></h2>
						<?php echo $egp_related; // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</section>
				<?php endif; ?>
			<?php endif; ?>

			<?php
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
			?>
		</div>
	</article>

	<?php
endwhile;

get_footer();
