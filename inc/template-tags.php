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
 *
 * Mit $basis bleibt die Suche auf der aufrufenden Seite: das Formular schickt
 * `egp_q` an dieselbe Adresse zurück, und das Seiten-Template filtert damit
 * seine eigene Abfrage. Ohne $basis geht es wie bisher in die allgemeine
 * Trefferliste.
 *
 * @param string $basis Adresse, an die gesucht wird; leer = Startseite.
 */
function egovpod_episode_search_form( $basis = '' ) {
	$id     = wp_unique_id( 'egp-episode-search-' );
	$eigen  = '' !== $basis;
	$ziel   = $eigen ? $basis : home_url( '/' );
	$feld   = $eigen ? 'egp_q' : 's';
	$wert   = $eigen && function_exists( 'egovpod_search_term' ) ? egovpod_search_term() : get_search_query();
	?>
	<form role="search" method="get" class="egp-search" action="<?php echo esc_url( $ziel ); ?>">
		<div class="kern-form-input">
			<label class="kern-label" for="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Episoden durchsuchen', 'egovpod' ); ?></label>
			<div class="egp-search__row">
				<input class="kern-form-input__input" id="<?php echo esc_attr( $id ); ?>" type="search" name="<?php echo esc_attr( $feld ); ?>" value="<?php echo esc_attr( $wert ); ?>" placeholder="<?php esc_attr_e( 'z. B. OZG, Registermodernisierung, EUDI-Wallet', 'egovpod' ); ?>">
				<?php if ( ! $eigen ) : ?>
					<input type="hidden" name="post_type" value="podcast">
				<?php endif; ?>
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

/**
 * Kommentare als Akkordeon.
 *
 * Steht auf der Episodenseite über dem Transkript statt ganz unten. Zugeklappt,
 * mit der Anzahl in der Beschriftung — dadurch ist sichtbar, dass es den
 * Bereich gibt, ohne dass die Liste die Seite verlängert.
 *
 * Die Ausgabe selbst kommt unverändert aus comments_template() des
 * Eltern-Themes; hier kommt nur die Hülle dazu.
 */
function egovpod_comments_accordion() {
	if ( ! comments_open() && ! get_comments_number() ) {
		return;
	}

	$egp_anzahl = (int) get_comments_number();

	if ( $egp_anzahl ) {
		$egp_titel = sprintf(
			/* translators: %s: Anzahl der Kommentare */
			_n( '%s Kommentar', '%s Kommentare', $egp_anzahl, 'egovpod' ),
			number_format_i18n( $egp_anzahl )
		);
	} else {
		$egp_titel = __( 'Kommentar schreiben', 'egovpod' );
	}
	?>
	<section class="egp-section egp-comments">
		<details class="kern-accordion egp-comments__accordion" id="egp-kommentare">
			<summary class="kern-accordion__header">
				<span class="kern-title"><?php echo esc_html( $egp_titel ); ?></span>
			</summary>
			<div class="kern-accordion__body egp-comments__body">
				<?php comments_template(); ?>
			</div>
		</details>
	</section>
	<?php
}

/**
 * Akkordeon aufklappen, wenn die Adresse hineinzeigt.
 *
 * Nötig nach dem Absenden eines Kommentars (WordPress springt auf
 * #comment-123) und für Links aus Benachrichtigungen. Ohne das Skript landet
 * man auf der Seite, und der Kommentar steckt im zugeklappten Bereich.
 */
function egovpod_comments_accordion_script() {
	if ( ! is_singular( 'podcast' ) ) {
		return;
	}
	?>
	<script>
	(function () {
		var akkordeon = document.getElementById('egp-kommentare');
		if (!akkordeon) { return; }

		function aufklappen() {
			var marke = window.location.hash.slice(1);
			if (!marke) { return; }
			var ziel = document.getElementById(marke);
			if (!ziel || !akkordeon.contains(ziel)) { return; }
			akkordeon.open = true;
			ziel.scrollIntoView();
		}

		aufklappen();
		window.addEventListener('hashchange', aufklappen);
	}());
	</script>
	<?php
}
add_action( 'wp_footer', 'egovpod_comments_accordion_script' );
