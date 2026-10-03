<?php
/**
 * Podlove-Publisher-Integration.
 *
 * Alle Zugriffe auf Podlove sind abgesichert: Ist das Plugin nicht aktiv
 * oder ändert sich die interne API, fällt das Theme auf WordPress-Bordmittel zurück.
 *
 * @package eGovPod
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ist Podlove Publisher aktiv?
 */
function egovpod_podlove_active() {
	return class_exists( '\Podlove\Model\Episode' );
}

/**
 * Handelt es sich um eine Podlove-Episode?
 *
 * @param int|WP_Post|null $post Beitrag.
 */
function egovpod_is_episode( $post = null ) {
	return 'podcast' === get_post_type( $post );
}

/**
 * Podlove-Episodenobjekt zum Beitrag holen.
 *
 * @param int|WP_Post|null $post Beitrag.
 * @return \Podlove\Model\Episode|null
 */
function egovpod_get_episode( $post = null ) {
	static $cache = array();

	$post = get_post( $post );
	if ( ! $post || ! egovpod_podlove_active() || ! egovpod_is_episode( $post ) ) {
		return null;
	}
	if ( array_key_exists( $post->ID, $cache ) ) {
		return $cache[ $post->ID ];
	}

	$episode = null;
	try {
		$episode = \Podlove\Model\Episode::find_one_by_post_id( $post->ID );
	} catch ( \Throwable $e ) {
		$episode = null;
	}

	$cache[ $post->ID ] = $episode ? $episode : null;
	return $cache[ $post->ID ];
}

/**
 * Podlove-Podcastobjekt.
 *
 * @return \Podlove\Model\Podcast|null
 */
function egovpod_get_podcast() {
	if ( ! class_exists( '\Podlove\Model\Podcast' ) ) {
		return null;
	}
	try {
		return \Podlove\Model\Podcast::get();
	} catch ( \Throwable $e ) {
		return null;
	}
}

/**
 * Sicherer Eigenschaftszugriff auf Podlove-Modelle.
 *
 * @param object|null $model Modell.
 * @param string      $prop  Eigenschaft.
 * @return string
 */
function egovpod_prop( $model, $prop ) {
	if ( ! $model ) {
		return '';
	}
	try {
		$value = $model->$prop;
	} catch ( \Throwable $e ) {
		return '';
	}
	return is_scalar( $value ) ? trim( (string) $value ) : '';
}

/**
 * Untertitel einer Episode.
 */
function egovpod_episode_subtitle( $post = null ) {
	return egovpod_prop( egovpod_get_episode( $post ), 'subtitle' );
}

/**
 * Zusammenfassung einer Episode.
 */
function egovpod_episode_summary( $post = null ) {
	return egovpod_prop( egovpod_get_episode( $post ), 'summary' );
}

/**
 * Episodennummer.
 */
function egovpod_episode_number( $post = null ) {
	return egovpod_prop( egovpod_get_episode( $post ), 'number' );
}

/**
 * Dauer in Sekunden.
 */
function egovpod_episode_duration_seconds( $post = null ) {
	$episode = egovpod_get_episode( $post );
	if ( ! $episode ) {
		return 0;
	}
	try {
		$raw = method_exists( $episode, 'get_duration' ) ? $episode->get_duration( 'HH:MM:SS' ) : egovpod_prop( $episode, 'duration' );
	} catch ( \Throwable $e ) {
		$raw = egovpod_prop( $episode, 'duration' );
	}
	if ( ! $raw ) {
		return 0;
	}
	$parts   = array_reverse( array_map( 'floatval', explode( ':', (string) $raw ) ) );
	$seconds = 0;
	foreach ( $parts as $i => $part ) {
		$seconds += $part * pow( 60, $i );
	}
	return (int) round( $seconds );
}

/**
 * Lesbare Dauer, z. B. "1 Std. 12 Min.".
 */
function egovpod_episode_duration( $post = null ) {
	$seconds = egovpod_episode_duration_seconds( $post );
	if ( $seconds <= 0 ) {
		return '';
	}
	$h = (int) floor( $seconds / 3600 );
	$m = (int) round( ( $seconds % 3600 ) / 60 );
	if ( 60 === $m ) {
		++$h;
		$m = 0;
	}
	if ( $h > 0 ) {
		/* translators: 1: Stunden, 2: Minuten */
		return sprintf( __( '%1$d Std. %2$d Min.', 'egovpod' ), $h, $m );
	}
	/* translators: %d: Minuten */
	return sprintf( __( '%d Min.', 'egovpod' ), max( 1, $m ) );
}

/**
 * ISO-8601-Dauer für strukturierte Daten.
 */
function egovpod_episode_duration_iso( $post = null ) {
	$s = egovpod_episode_duration_seconds( $post );
	if ( $s <= 0 ) {
		return '';
	}
	return sprintf( 'PT%dH%dM%dS', floor( $s / 3600 ), floor( ( $s % 3600 ) / 60 ), $s % 60 );
}

/**
 * Cover-URL: Episodencover (Podlove) → Beitragsbild → Podcastcover → leer.
 *
 * @param int|WP_Post|null $post Beitrag.
 * @param int              $size Kantenlänge in Pixeln.
 */
function egovpod_cover_url( $post = null, $size = 600 ) {
	$post    = get_post( $post );
	$episode = egovpod_get_episode( $post );

	if ( $episode && method_exists( $episode, 'cover_art_with_fallback' ) ) {
		try {
			$image = $episode->cover_art_with_fallback();
			if ( $image && is_object( $image ) ) {
				if ( method_exists( $image, 'setWidth' ) ) {
					$image->setWidth( $size );
				}
				if ( method_exists( $image, 'setHeight' ) ) {
					$image->setHeight( $size );
				}
				$url = $image->url();
				if ( $url ) {
					return $url;
				}
			}
		} catch ( \Throwable $e ) {
			// Weiter mit Fallback.
		}
	}

	if ( $post && has_post_thumbnail( $post ) ) {
		return get_the_post_thumbnail_url( $post, 'egovpod-cover' );
	}

	return egovpod_podcast_cover_url( $size );
}

/**
 * Cover des Podcasts.
 */
function egovpod_podcast_cover_url( $size = 600 ) {
	$podcast = egovpod_get_podcast();
	if ( $podcast && method_exists( $podcast, 'cover_art' ) ) {
		try {
			$image = $podcast->cover_art();
			if ( $image && is_object( $image ) ) {
				if ( method_exists( $image, 'setWidth' ) ) {
					$image->setWidth( $size );
				}
				if ( method_exists( $image, 'setHeight' ) ) {
					$image->setHeight( $size );
				}
				$url = $image->url();
				if ( $url ) {
					return $url;
				}
			}
		} catch ( \Throwable $e ) {
			// Fallback unten.
		}
	}
	$logo = get_theme_mod( 'custom_logo' );
	if ( $logo ) {
		return wp_get_attachment_image_url( $logo, 'egovpod-cover' );
	}
	return function_exists( 'egovpod_bundled_logo_url' ) ? egovpod_bundled_logo_url() : '';
}

/**
 * Titel und Untertitel des Podcasts (Podlove → WordPress).
 */
function egovpod_podcast_title() {
	$title = egovpod_prop( egovpod_get_podcast(), 'title' );
	return $title ? $title : get_bloginfo( 'name' );
}

function egovpod_podcast_subtitle() {
	$sub = egovpod_prop( egovpod_get_podcast(), 'subtitle' );
	return $sub ? $sub : get_bloginfo( 'description' );
}

function egovpod_podcast_summary() {
	return egovpod_prop( egovpod_get_podcast(), 'summary' );
}

/**
 * Fügt Podlove über seine Template-Zuweisung (Podlove → Templates →
 * "Template automatisch einfügen") bereits etwas in the_content ein?
 */
function egovpod_podlove_template_assigned() {
	if ( ! class_exists( '\Podlove\Model\TemplateAssignment' ) ) {
		return false;
	}
	try {
		$assignment = \Podlove\Model\TemplateAssignment::get_instance();
		return ! empty( $assignment->top ) || ! empty( $assignment->bottom );
	} catch ( \Throwable $e ) {
		return false;
	}
}

/**
 * Soll das Theme die Podlove-Bausteine selbst ausgeben?
 *
 * Modus "auto": nur, wenn Podlove keine Template-Zuweisung hat (verhindert doppelte Player).
 */
function egovpod_theme_renders_episode_parts() {
	if ( ! egovpod_podlove_active() ) {
		return false;
	}
	$mode = get_theme_mod( 'egovpod_player_mode', 'auto' );
	if ( 'theme' === $mode ) {
		return true;
	}
	if ( 'podlove' === $mode ) {
		return false;
	}
	return ! egovpod_podlove_template_assigned();
}

/**
 * Shortcode nur ausführen, wenn er registriert ist.
 *
 * @param string $tag  Shortcode-Name.
 * @param array  $atts Attribute.
 */
function egovpod_shortcode( $tag, $atts = array() ) {
	if ( ! shortcode_exists( $tag ) ) {
		return '';
	}
	$attr = '';
	foreach ( $atts as $key => $value ) {
		if ( '' === $value || null === $value ) {
			continue;
		}
		$attr .= sprintf( ' %s="%s"', sanitize_key( $key ), esc_attr( $value ) );
	}
	$out = do_shortcode( '[' . $tag . $attr . ']' );
	return trim( (string) $out );
}

/**
 * Podlove Web Player.
 */
function egovpod_web_player( $post_id = null ) {
	$atts = $post_id ? array( 'post_id' => (int) $post_id ) : array();
	return egovpod_shortcode( 'podlove-episode-web-player', $atts );
}

/**
 * Podlove Subscribe Button als KERN-Button.
 *
 * Podlove erlaubt, den eigenen Button auszublenden (hide) und das Abo-Popup
 * über ein eigenes Element auszulösen (buttonid → Klasse
 * "podlove-subscribe-button-{buttonid}"). So sieht der Abo-Button genauso aus
 * wie alle anderen KERN-Buttons daneben – gleiche Höhe, gleiche Schrift,
 * gleiche Fokusdarstellung.
 *
 * @param string $id      Eindeutige Kennung auf der Seite (a–z, 0–9, -).
 * @param string $classes Zusätzliche KERN-Klassen, z. B. "kern-btn--large".
 * @return string HTML oder leer, wenn das Podlove-Modul fehlt.
 */
function egovpod_subscribe_button( $id = 'egp-subscribe', $classes = '' ) {
	$id     = sanitize_html_class( $id );
	$loader = egovpod_shortcode(
		'podlove-podcast-subscribe-button',
		array(
			'buttonid' => $id,
			'hide'     => 'true',
			'color'    => apply_filters( 'egovpod_subscribe_color', '#A64F00' ), // Farbe im Popup: dunkles Markenorange.
			'language' => 'de',
		)
	);

	if ( '' === $loader ) {
		return '';
	}

	return sprintf(
		'<button type="button" class="kern-btn kern-btn--primary %1$s podlove-subscribe-button-%2$s">%3$s<span class="kern-label">%4$s</span></button>%5$s',
		esc_attr( $classes ),
		esc_attr( $id ),
		egovpod_icon( 'add' ),
		esc_html__( 'Abonnieren', 'egovpod' ),
		$loader
	);
}

/**
 * Mitwirkende der Episode.
 */
function egovpod_contributors() {
	return egovpod_shortcode(
		'podlove-episode-contributor-list',
		array(
			'preset'    => get_theme_mod( 'egovpod_contributor_preset', 'list' ),
			'avatars'   => 'yes',
			'roles'     => 'yes',
			'groups'    => 'yes',
			'donations' => 'yes',
			'title'     => '',
		)
	);
}

/**
 * Shownotes (Podlove-Modul "Shownotes").
 */
function egovpod_shownotes() {
	return egovpod_shortcode( 'podlove-episode-shownotes' );
}

/**
 * Downloads.
 */
function egovpod_downloads() {
	return egovpod_shortcode( 'podlove-episode-downloads', array( 'style' => 'buttons' ) );
}

/**
 * Transkript (Podlove-Modul "Transcripts").
 */
function egovpod_transcript() {
	return egovpod_shortcode( 'podlove-transcript' );
}

/**
 * Feed-Liste.
 */
function egovpod_feed_list() {
	return egovpod_shortcode( 'podlove-feed-list' );
}

/**
 * Verwandte Episoden (Podlove-Modul "Related Episodes").
 */
function egovpod_related_episodes() {
	return egovpod_shortcode( 'podlove-related-episodes' );
}

/**
 * Strukturierte Daten (schema.org PodcastEpisode) für Episodenseiten.
 */
function egovpod_episode_jsonld() {
	if ( ! is_singular( 'podcast' ) || ! get_theme_mod( 'egovpod_jsonld', true ) ) {
		return;
	}
	$post = get_queried_object();
	$data = array(
		'@context'      => 'https://schema.org',
		'@type'         => 'PodcastEpisode',
		'url'           => get_permalink( $post ),
		'name'          => wp_strip_all_tags( get_the_title( $post ) ),
		'datePublished' => get_the_date( 'c', $post ),
		'description'   => wp_strip_all_tags( egovpod_episode_summary( $post ) ? egovpod_episode_summary( $post ) : get_the_excerpt( $post ) ),
		'partOfSeries'  => array(
			'@type' => 'PodcastSeries',
			'name'  => egovpod_podcast_title(),
			'url'   => home_url( '/' ),
		),
	);
	$number = egovpod_episode_number( $post );
	if ( $number ) {
		$data['episodeNumber'] = $number;
	}
	$duration = egovpod_episode_duration_iso( $post );
	if ( $duration ) {
		$data['timeRequired'] = $duration;
	}
	$cover = egovpod_cover_url( $post, 1400 );
	if ( $cover ) {
		$data['image'] = $cover;
	}
	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
}
add_action( 'wp_head', 'egovpod_episode_jsonld' );

/**
 * Hinweis im Backend, falls Podlove fehlt.
 */
function egovpod_admin_notice() {
	if ( egovpod_podlove_active() || ! current_user_can( 'install_plugins' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->id, array( 'themes', 'dashboard' ), true ) ) {
		return;
	}
	printf(
		'<div class="notice notice-info is-dismissible"><p>%s</p></div>',
		wp_kses_post( __( 'Das Theme <strong>eGovPod</strong> ist für das Plugin <strong>Podlove Podcast Publisher</strong> optimiert. Ohne Podlove funktioniert es als normales Blog-Theme.', 'egovpod' ) )
	);
}
add_action( 'admin_notices', 'egovpod_admin_notice' );

/**
 * Abo-Fenster des Subscribe-Button-Widgets einfärben.
 *
 * Das Widget des Plugins „Podlove Subscribe Button“ speichert keine Farbe in
 * seiner Instanz. Es schickt deshalb ein leeres `data-color` mit, und das
 * Skript von cdn.podlove.org nimmt dann sein eigenes Grün (#75ad91) – im
 * eGovPod-Umfeld ein Fremdkörper. Die Ausgabe des Widgets wird hier
 * abgefangen und auf die Markenfarbe sowie auf Deutsch umgestellt.
 *
 * Nur die Ausgabe wird angefasst, an den Einstellungen des Plugins ändert
 * sich nichts. Die Farbe lässt sich über den Filter `egovpod_subscribe_color`
 * überschreiben, die Sprache über `egovpod_subscribe_language`.
 *
 * @param string $html Markup des Widgets.
 * @return string
 */
function egovpod_subscribe_widget_markup( $html ) {
	if ( false === strpos( $html, 'podlove-subscribe-button' ) ) {
		return $html;
	}

	$color    = apply_filters( 'egovpod_subscribe_color', '#A64F00' );
	$language = apply_filters( 'egovpod_subscribe_language', 'de' );

	if ( preg_match( '/\sdata-color=("|\')[^"\']*\1/i', $html ) ) {
		$html = preg_replace( '/\sdata-color=("|\')[^"\']*\1/i', ' data-color="' . esc_attr( $color ) . '"', $html );
	} else {
		$html = preg_replace(
			'/(<script\b[^>]*\bclass=("|\')[^"\']*podlove-subscribe-button[^"\']*\2)/i',
			'$1 data-color="' . esc_attr( $color ) . '"',
			$html
		);
	}

	if ( preg_match( '/\sdata-language=("|\')[^"\']*\1/i', $html ) ) {
		$html = preg_replace( '/\sdata-language=("|\')[^"\']*\1/i', ' data-language="' . esc_attr( $language ) . '"', $html );
	} else {
		$html = preg_replace(
			'/(<script\b[^>]*\bclass=("|\')[^"\']*podlove-subscribe-button[^"\']*\2)/i',
			'$1 data-language="' . esc_attr( $language ) . '"',
			$html
		);
	}

	return $html;
}

/**
 * Ausgabe des Subscribe-Button-Widgets durch einen Puffer schicken.
 *
 * WordPress kennt keinen Filter für das fertige Markup eines Widgets.
 * Deshalb wird der Callback des betroffenen Widgets einmal pro Anfrage
 * umgehängt; alle anderen Widgets bleiben unberührt.
 *
 * @param array $params Parameter der Seitenleiste.
 * @return array
 */
function egovpod_subscribe_widget_filter( $params ) {
	global $wp_registered_widgets;

	if ( empty( $params[0]['widget_id'] ) ) {
		return $params;
	}

	$id = $params[0]['widget_id'];

	if ( 0 !== strpos( $id, 'podlove_subscribe_button' ) ) {
		return $params;
	}
	if ( empty( $wp_registered_widgets[ $id ]['callback'] ) || ! empty( $wp_registered_widgets[ $id ]['egovpod_wrapped'] ) ) {
		return $params;
	}

	$original = $wp_registered_widgets[ $id ]['callback'];

	$wp_registered_widgets[ $id ]['egovpod_wrapped'] = true;
	$wp_registered_widgets[ $id ]['callback']        = static function () use ( $original ) {
		ob_start();
		call_user_func_array( $original, func_get_args() );
		echo egovpod_subscribe_widget_markup( ob_get_clean() ); // phpcs:ignore WordPress.Security.EscapeOutput -- Widget-Markup, nur Attribute ersetzt.
	};

	return $params;
}
add_filter( 'dynamic_sidebar_params', 'egovpod_subscribe_widget_filter' );

/**
 * Gleiches für den Shortcode [podlove-subscribe-button] in Inhalten,
 * solange dort keine eigene Farbe gesetzt ist.
 *
 * @param string $output Shortcode-Ausgabe.
 * @param string $tag    Shortcode-Name.
 * @return string
 */
function egovpod_subscribe_shortcode_markup( $output, $tag ) {
	if ( 'podlove-subscribe-button' !== $tag ) {
		return $output;
	}
	if ( preg_match( '/\sdata-color=("|\')\s*\1/i', $output ) || false === strpos( $output, 'data-color' ) ) {
		return egovpod_subscribe_widget_markup( $output );
	}
	return $output;
}
add_filter( 'do_shortcode_tag', 'egovpod_subscribe_shortcode_markup', 10, 2 );

/**
 * Doppelten Web Player aus dem Episodentext entfernen.
 *
 * Das Theme zeigt den Player oben neben Cover und Titel. Viele Folgen des
 * eGovernment Podcast haben ihn zusätzlich im Fließtext stehen – ein
 * Überbleibsel aus der Zeit vor diesem Theme. Dann steht der Player zweimal
 * auf der Seite.
 *
 * Entfernt wird nur die Ausgabe, der gespeicherte Episodentext bleibt
 * unverändert: Schaltet man die Einstellung wieder aus, ist der Player im
 * Text sofort wieder da.
 *
 * Mehrere Durchgänge, weil der Player je nach Alter der Folge über ein
 * Podlove-Template, als Shortcode, als Block oder bereits als fertiges
 * Markup im Text liegen kann.
 */
function egovpod_strip_player_shortcode( $content ) {
	if ( ! egovpod_strip_player_active() ) {
		return $content;
	}

	/*
	 * Podlove-Templates, die nichts als den Player enthalten.
	 * Beim eGovernment Podcast steht in jeder Folge – von eGov002 bis heute –
	 * [podlove-template id="default"] im Text, und dieses Template besteht aus
	 * der einen Zeile {{ episode.player }}. Wer das Template später mit echtem
	 * Inhalt füllt, nimmt es hier über den Filter wieder heraus oder schaltet
	 * die Einstellung im Customizer ab.
	 */
	foreach ( egovpod_player_only_templates() as $template ) {
		$content = preg_replace(
			'/\[podlove-template\b[^\]]*\bid=("|\')' . preg_quote( $template, '/' ) . '\1[^\]]*\]/i',
			'',
			$content
		);
	}

	// Shortcode, in beiden Schreibweisen.
	$content = preg_replace( '/\[podlove-(?:episode-)?web-player\b[^\]]*\]/i', '', $content );

	// Block des Podlove-Plugins.
	$content = preg_replace( '/<!--\s*wp:podlove\/(?:web-)?player\b.*?\/-->/is', '', $content );
	$content = preg_replace( '/<!--\s*wp:podlove\/(?:web-)?player\b.*?-->.*?<!--\s*\/wp:podlove\/(?:web-)?player\s*-->/is', '', $content );

	return $content;
}
add_filter( 'the_content', 'egovpod_strip_player_shortcode', 9 );

/**
 * Namen der Podlove-Templates, die ausschließlich den Player ausgeben.
 *
 * @return string[]
 */
function egovpod_player_only_templates() {
	return (array) apply_filters( 'egovpod_player_only_templates', array( 'default' ) );
}

/**
 * Sicherheitsnetz: fertiges Player-Markup im Text.
 *
 * Läuft nach den Shortcodes, falls eine Folge den Player als fertiges HTML
 * im Text stehen hat. Podlove gibt einen Container mit Ladegerüst aus,
 * gefolgt von einem Script und einem Style – die werden mitgenommen, sonst
 * bleibt toter Ballast stehen. Das Muster zählt verschachtelte <div> mit,
 * damit es am richtigen </div> endet.
 */
function egovpod_strip_player_markup( $content ) {
	if ( ! egovpod_strip_player_active() ) {
		return $content;
	}
	if ( false === stripos( $content, 'podlove-web-player' ) ) {
		return $content;
	}

	$stripped = preg_replace(
		'#<div[^>]*class="[^"]*podlove-web-player[^"]*"[^>]*>(?P<inhalt>(?:[^<]++|<(?!/?div\b)|<div\b[^>]*+>(?&inhalt)</div>)*+)</div>\s*(?:<script\b[^>]*>.*?</script>\s*)?(?:<style\b[^>]*>.*?</style>\s*)?#is',
		'',
		$content
	);

	// Bei zu komplexem Markup gibt preg_replace null zurück – dann lieber
	// den Player doppelt als die Episode gar nicht.
	return null === $stripped ? $content : $stripped;
}
add_filter( 'the_content', 'egovpod_strip_player_markup', 20 );

/**
 * Greift das Entfernen hier überhaupt?
 *
 * Nur auf der Einzelansicht einer Episode, nur im Hauptinhalt und nur, wenn
 * das Theme den Player oben tatsächlich selbst ausgibt – sonst stünde am Ende
 * gar keiner mehr auf der Seite.
 */
function egovpod_strip_player_active() {
	if ( is_admin() || ! is_singular( 'podcast' ) || ! in_the_loop() || ! is_main_query() ) {
		return false;
	}
	if ( ! get_theme_mod( 'egovpod_strip_content_player', true ) ) {
		return false;
	}
	return egovpod_theme_renders_episode_parts();
}
