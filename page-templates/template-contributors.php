<?php
/**
 * Template Name: Teilnehmer:innen (Karten)
 * Template Post Type: page
 *
 * Nimmt die Tabelle, die das Podlove-Template auf dieser Seite ausgibt, und
 * macht daraus Karten mit alphabetischem Register. Findet sich keine Tabelle,
 * wird der Inhalt unverändert ausgegeben – die Seite funktioniert also auch,
 * wenn sich an der Quelle etwas ändert.
 *
 * @package eGovPod
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>

	<?php if ( function_exists( 'kern_ux_breadcrumb' ) ) { kern_ux_breadcrumb(); } ?>

	<article id="post-<?php the_ID(); ?>" <?php post_class( 'egp-people-page' ); ?>>

		<header class="kux-page-header">
			<?php the_title( '<h1 class="kern-heading-large">', '</h1>' ); ?>
		</header>

		<?php
		ob_start();
		the_content();
		$egp_inhalt = ob_get_clean();

		$egp_leute = egovpod_parse_contributor_table( $egp_inhalt );

		if ( $egp_leute ) {
			printf(
				'<p class="kern-body kern-body--large egp-people__intro">%s</p>',
				esc_html(
					sprintf(
						/* translators: %s: Anzahl der Teilnehmer:innen */
						_n(
							'%s Person war bisher im eGovernment Podcast zu Gast oder hat mitgewirkt.',
							'%s Menschen waren bisher im eGovernment Podcast zu Gast oder haben mitgewirkt.',
							count( $egp_leute ),
							'egovpod'
						),
						number_format_i18n( count( $egp_leute ) )
					)
				)
			);

			echo egovpod_render_contributor_cards( $egp_leute ); // phpcs:ignore WordPress.Security.EscapeOutput -- im Modul aufgebaut und escaped.
		} else {
			echo '<div class="kux-prose entry-content">' . $egp_inhalt . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- unveränderter Beitragsinhalt.
		}
		?>

	</article>

	<?php
endwhile;

get_footer();
