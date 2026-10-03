<?php
/**
 * Template-Helfer mit KERN-Markup.
 *
 * @package eGovPod
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * KERN-Icon.
 *
 * @param string $name  Icon-Name (z. B. "search", "download").
 * @param string $size  '', 'small', 'large', 'x-large'.
 */
function egovpod_icon( $name, $size = '' ) {
	$class = 'kern-icon kern-icon--' . sanitize_html_class( $name );
	if ( $size ) {
		$class .= ' kern-icon--' . sanitize_html_class( $size );
	}
	return '<span class="' . esc_attr( $class ) . '" aria-hidden="true"></span>';
}

/**
 * Metazeile einer Episode als KERN-Badges/Text.
 *
 * @param int|WP_Post|null $post        Beitrag.
 * @param bool             $show_number Folgennummer als Badge zeigen.
 */
function egovpod_episode_meta( $post = null, $show_number = false ) {
	$post     = get_post( $post );
	$number   = $show_number ? egovpod_episode_number( $post ) : '';
	$duration = egovpod_episode_duration( $post );

	echo '<div class="egp-meta">';
	if ( $number ) {
		printf(
			'<span class="kern-badge kern-badge--info kern-badge--small"><span class="kern-label kern-label--small">%s</span></span>',
			/* translators: %s: Episodennummer */
			esc_html( sprintf( __( 'Folge %s', 'egovpod' ), $number ) )
		);
	}
	printf(
		'<span class="egp-meta__item">%s<time datetime="%s">%s</time></span>',
		egovpod_icon( 'calendar-today', 'small' ), // phpcs:ignore WordPress.Security.EscapeOutput
		esc_attr( get_the_date( 'c', $post ) ),
		esc_html( get_the_date( '', $post ) )
	);
	if ( $duration ) {
		printf(
			'<span class="egp-meta__item"><span class="kern-sr-only">%s </span>%s</span>',
			esc_html__( 'Dauer:', 'egovpod' ),
			esc_html( $duration )
		);
	}
	echo '</div>';
}

/**
 * Metazeile eines Blogbeitrags.
 */
function egovpod_posted_on() {
	printf(
		'<div class="egp-meta"><span class="egp-meta__item">%s<time datetime="%s">%s</time></span><span class="egp-meta__item">%s</span></div>',
		egovpod_icon( 'calendar-today', 'small' ), // phpcs:ignore WordPress.Security.EscapeOutput
		esc_attr( get_the_date( 'c' ) ),
		esc_html( get_the_date() ),
		esc_html( get_the_author() )
	);
}

/**
 * Pagination – nutzt die des Eltern-Themes.
 */
function egovpod_pagination() {
	if ( function_exists( 'kern_ux_pagination' ) ) {
		kern_ux_pagination();
	} else {
		the_posts_pagination();
	}
}

/**
 * Episoden-Suchformular (nur Episoden).
 */
function egovpod_episode_search_form() {
	$id = wp_unique_id( 'egp-episode-search-' );
	?>
	<form role="search" method="get" class="egp-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<div class="kern-form-input">
			<label class="kern-label" for="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Episoden durchsuchen', 'egovpod' ); ?></label>
			<div class="egp-search__row">
				<input class="kern-form-input__input" id="<?php echo esc_attr( $id ); ?>" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'z. B. OZG, Registermodernisierung, EUDI-Wallet', 'egovpod' ); ?>">
				<input type="hidden" name="post_type" value="podcast">
				<button type="submit" class="kern-btn kern-btn--primary">
					<?php echo egovpod_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span class="kern-label"><?php esc_html_e( 'Suchen', 'egovpod' ); ?></span>
				</button>
			</div>
		</div>
	</form>
	<?php
}

/**
 * URL des Episodenarchivs (Podlove-Archiv → Seite mit Template → Fallback).
 */
function egovpod_episode_archive_url() {
	$url = get_post_type_archive_link( 'podcast' );
	if ( $url ) {
		return $url;
	}
	$pages = get_pages(
		array(
			'meta_key'   => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value' => 'page-templates/template-episodes.php', // phpcs:ignore WordPress.DB.SlowDBQuery
			'number'     => 1,
		)
	);
	return $pages ? get_permalink( $pages[0] ) : home_url( '/?post_type=podcast' );
}

/**
 * URL des mitgelieferten Logos (assets/images/logo.png bzw. .svg), falls vorhanden.
 */
function egovpod_bundled_logo_url() {
	foreach ( array( 'logo.svg', 'logo.png' ) as $file ) {
		if ( file_exists( get_stylesheet_directory() . '/assets/images/' . $file ) ) {
			return get_stylesheet_directory_uri() . '/assets/images/' . $file;
		}
	}
	return '';
}

/**
 * Episodenbild für den Kopfbereich der Episodenseite.
 *
 * Gemeint ist das Beitragsbild der Folge – beim eGovernment Podcast ein
 * Banner im Format 3:1 (Dateien nach dem Muster „eGov276_beitrag.png“), das
 * die Folge bebildert. Das ist etwas anderes als die quadratische Cover Art
 * aus Podlove: Die gehört in die Podcast-Apps und bleibt deshalb in den
 * strukturierten Daten, auf den Episodenkarten und im Web Player.
 *
 * Hat eine Folge kein Beitragsbild, fällt die Episodenseite auf das
 * quadratische Cover neben dem Titel zurück.
 *
 * @param int|WP_Post|null $post  Beitrag.
 * @param string           $class CSS-Klasse für das Bild.
 * @return string Bild-Markup oder leer.
 */
function egovpod_episode_banner( $post = null, $class = 'egp-episode__banner' ) {
	$post = get_post( $post );

	if ( ! $post || ! has_post_thumbnail( $post ) ) {
		return '';
	}

	$id  = get_post_thumbnail_id( $post );
	$alt = trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );

	if ( '' === $alt ) {
		/* translators: %s: Titel der Folge */
		$alt = sprintf( __( 'Bild zur Folge „%s“', 'egovpod' ), get_the_title( $post ) );
	}

	return get_the_post_thumbnail(
		$post,
		'large',
		array(
			'class'   => sanitize_html_class( $class, 'egp-episode__banner' ),
			'alt'     => $alt,
			'loading' => 'eager',
		)
	);
}
