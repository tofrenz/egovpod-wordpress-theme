<?php
/**
 * Transkripte: Downloads je Folge und die Übersichtsseite.
 *
 * Die Daten kommen aus dem Podlove-Modul „Transcripts" (Tabelle
 * podlove_modules_transcripts_transcript: start, end, voice, content, dazu
 * die Sprecherzuordnung aus VoiceAssignment). Das Theme liest sie über
 * Podloves eigene Methoden, nicht aus dem gerenderten Akkordeon-HTML — das
 * ist bei einer Folge rund 144 KB groß und wäre für eine Liste unbrauchbar.
 *
 * Alles ist abgesichert: Fehlt Podlove oder das Transkript-Modul, gibt es
 * keine Download-Knöpfe und die Übersichtsseite bleibt leer, statt zu brechen.
 *
 * @package eGovPod
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Query-Variable für die Downloads anmelden.
 *
 * @param array<int, string> $vars Bekannte Variablen.
 * @return array<int, string>
 */
function egovpod_transcript_query_vars( $vars ) {
	$vars[] = 'egp_transkript';
	return $vars;
}
add_filter( 'query_vars', 'egovpod_transcript_query_vars' );

/**
 * Tabellenname des Transkript-Moduls, oder '' wenn es das Modul nicht gibt.
 *
 * @return string
 */
function egovpod_transcript_table() {
	return egovpod_podlove_table( '\Podlove\Modules\Transcripts\Model\Transcript' );
}

/**
 * Gibt es für diese Folgen ein Transkript? Eine Abfrage für die ganze Seite.
 *
 * @param array<int, int> $post_ids Beitrags-IDs.
 * @return array<int, int> Beitrags-ID => Anzahl der Abschnitte.
 */
function egovpod_prime_transcripts( $post_ids ) {
	static $gespeichert = array();

	$post_ids = array_values( array_unique( array_map( 'intval', (array) $post_ids ) ) );
	$offen    = array_diff( $post_ids, array_keys( $gespeichert ) );

	if ( ! $offen ) {
		return $gespeichert;
	}

	foreach ( $offen as $id ) {
		$gespeichert[ $id ] = 0;
	}

	$t_transkript = egovpod_transcript_table();
	$t_folge      = egovpod_podlove_table( '\Podlove\Model\Episode' );

	if ( ! $t_transkript || ! $t_folge ) {
		return $gespeichert;
	}

	global $wpdb;

	$platzhalter = implode( ',', array_fill( 0, count( $offen ), '%d' ) );

	$zeilen = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
		$wpdb->prepare(
			"SELECT f.post_id AS post_id, COUNT(t.id) AS anzahl
			 FROM `{$t_folge}` AS f
			 INNER JOIN `{$t_transkript}` AS t ON t.episode_id = f.id
			 WHERE f.post_id IN ({$platzhalter})
			 GROUP BY f.post_id", // phpcs:ignore WordPress.DB.PreparedSQL
			$offen
		)
	);

	foreach ( (array) $zeilen as $zeile ) {
		$gespeichert[ (int) $zeile->post_id ] = (int) $zeile->anzahl;
	}

	return $gespeichert;
}

/**
 * Hat diese Folge ein Transkript?
 *
 * @param int|WP_Post|null $post Beitrag.
 * @return bool
 */
function egovpod_has_transcript( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return false;
	}
	$alle = egovpod_prime_transcripts( array( $post->ID ) );
	return ! empty( $alle[ $post->ID ] );
}

/**
 * Transkript einer Folge als Blöcke: je Sprecherwechsel ein Absatz.
 *
 * @param int|WP_Post|null $post Beitrag.
 * @return array<int, array<string, mixed>> Liste aus sprecher, text, start.
 */
function egovpod_transcript_blocks( $post = null ) {
	$episode = egovpod_get_episode( $post );
	if ( ! $episode || ! class_exists( '\Podlove\Modules\Transcripts\Model\Transcript' ) ) {
		return array();
	}

	try {
		$zeilen = \Podlove\Modules\Transcripts\Model\Transcript::get_transcript( (int) $episode->id );
	} catch ( \Throwable $e ) {
		return array();
	}

	$bloecke  = array();
	$aktuell  = null;
	$namen    = array();

	foreach ( (array) $zeilen as $zeile ) {
		$text = trim( (string) $zeile->content );
		if ( '' === $text ) {
			continue;
		}

		$kennung = isset( $zeile->contributor_id ) && $zeile->contributor_id
			? 'c' . (int) $zeile->contributor_id
			: 'v' . (string) $zeile->voice;

		if ( ! isset( $namen[ $kennung ] ) ) {
			$namen[ $kennung ] = egovpod_transcript_speaker_name( $zeile );
		}

		if ( $aktuell && $aktuell['kennung'] === $kennung ) {
			$aktuell['text'] .= ' ' . $text;
			continue;
		}

		if ( $aktuell ) {
			$bloecke[] = $aktuell;
		}

		$aktuell = array(
			'kennung'  => $kennung,
			'sprecher' => $namen[ $kennung ],
			'text'     => $text,
			'start'    => isset( $zeile->start ) ? (int) $zeile->start : 0,
		);
	}

	if ( $aktuell ) {
		$bloecke[] = $aktuell;
	}

	return $bloecke;
}

/**
 * Name zu einer Transkriptzeile.
 *
 * @param object $zeile Zeile aus Podlove.
 * @return string
 */
function egovpod_transcript_speaker_name( $zeile ) {
	if ( ! empty( $zeile->contributor_id ) && class_exists( '\Podlove\Modules\Contributors\Model\Contributor' ) ) {
		try {
			$person = \Podlove\Modules\Contributors\Model\Contributor::find_by_id( (int) $zeile->contributor_id );
			if ( $person && method_exists( $person, 'getName' ) ) {
				$name = trim( (string) $person->getName() );
				if ( '' !== $name ) {
					return $name;
				}
			}
		} catch ( \Throwable $e ) {
			$name = '';
		}
	}

	$voice = isset( $zeile->voice ) ? trim( (string) $zeile->voice ) : '';
	return '' !== $voice ? $voice : '';
}

/**
 * Die alte Linkliste aus dem Seiteninhalt entfernen.
 *
 * Die Seite „Transkripte" trägt im Inhalt eine von Hand gepflegte Liste mit
 * Links auf /transkript-html/. Das Seiten-Template ersetzt sie; als Vorspann
 * wäre sie doppelt. Ein Einleitungstext daneben bleibt stehen, und der
 * gespeicherte Inhalt bleibt unverändert — ohne dieses Template steht die
 * Liste wieder da.
 *
 * @param string $html Inhalt.
 * @return string
 */
function egovpod_strip_transcript_list( $html ) {
	if ( false === stripos( $html, 'transkript-html' ) ) {
		return $html;
	}

	$ohne = preg_replace_callback(
		'#<ul\b[^>]*>.*?</ul>#is',
		static function ( $treffer ) {
			return ( false !== stripos( $treffer[0], 'transkript-html' ) ) ? '' : $treffer[0];
		},
		$html
	);

	return null === $ohne ? $html : $ohne;
}

/**
 * Welche Formate werden angeboten?
 *
 * Über den Filter `egovpod_transcript_formats` anpassbar.
 *
 * @return array<string, string> Kürzel => Beschriftung.
 */
function egovpod_transcript_formats() {
	$formate = array(
		'html'  => __( 'HTML', 'egovpod' ),
		'txt'   => __( 'Text', 'egovpod' ),
		'druck' => __( 'PDF', 'egovpod' ),
	);

	return (array) apply_filters( 'egovpod_transcript_formats', $formate );
}

/**
 * Adresse eines Transkript-Downloads.
 *
 * @param int|WP_Post|null $post   Beitrag.
 * @param string           $format html, txt oder druck.
 * @return string
 */
function egovpod_transcript_url( $post, $format ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	return add_query_arg( 'egp_transkript', rawurlencode( $format ), get_permalink( $post ) );
}

/**
 * Dateiname ohne Endung, aus dem Beitragsnamen.
 *
 * @param WP_Post $post Beitrag.
 * @return string
 */
function egovpod_transcript_filename( $post ) {
	$name = $post->post_name ? $post->post_name : ( 'folge-' . $post->ID );
	return sanitize_file_name( $name . '-transkript' );
}

/**
 * Knopfreihe mit den Downloads.
 *
 * @param int|WP_Post|null $post  Beitrag.
 * @param string           $klasse Zusätzliche Klasse.
 */
function egovpod_transcript_downloads( $post = null, $klasse = '' ) {
	$post = get_post( $post );
	if ( ! $post || ! egovpod_has_transcript( $post ) ) {
		return;
	}

	$formate = egovpod_transcript_formats();
	if ( ! $formate ) {
		return;
	}
	?>
	<div class="egp-ts-downloads <?php echo esc_attr( $klasse ); ?>">
		<span class="egp-ts-downloads__titel"><?php esc_html_e( 'Transkript herunterladen:', 'egovpod' ); ?></span>
		<ul class="egp-ts-downloads__liste">
			<?php foreach ( $formate as $kuerzel => $beschriftung ) : ?>
				<li>
					<a class="kern-btn kern-btn--secondary kern-btn--small egp-ts-downloads__btn"
						href="<?php echo esc_url( egovpod_transcript_url( $post, $kuerzel ) ); ?>"
						<?php echo ( 'druck' === $kuerzel ) ? 'target="_blank" rel="noopener"' : 'download'; ?>>
						<span class="kern-label"><?php echo esc_html( $beschriftung ); ?></span>
						<span class="egp-sr-only">
							<?php
							/* translators: %s: Titel der Folge */
							echo esc_html( sprintf( __( '– Transkript der Folge „%s“', 'egovpod' ), get_the_title( $post ) ) );
							?>
						</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
}

/**
 * Download ausliefern, bevor das Template greift.
 */
function egovpod_transcript_deliver() {
	$format = get_query_var( 'egp_transkript' );
	if ( ! $format || ! is_singular( 'podcast' ) ) {
		return;
	}

	$format = strtolower( (string) $format );
	if ( ! isset( egovpod_transcript_formats()[ $format ] ) ) {
		return;
	}

	$post    = get_queried_object();
	$bloecke = egovpod_transcript_blocks( $post );

	if ( ! $bloecke ) {
		return; // Ohne Transkript bleibt es bei der normalen Episodenseite.
	}

	nocache_headers();
	$dateiname = egovpod_transcript_filename( $post );

	if ( 'txt' === $format ) {
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $dateiname . '.txt"' );
		echo egovpod_transcript_as_text( $post, $bloecke ); // phpcs:ignore WordPress.Security.EscapeOutput
		exit;
	}

	header( 'Content-Type: text/html; charset=utf-8' );
	if ( 'html' === $format ) {
		header( 'Content-Disposition: attachment; filename="' . $dateiname . '.html"' );
	}
	echo egovpod_transcript_as_html( $post, $bloecke, 'druck' === $format ); // phpcs:ignore WordPress.Security.EscapeOutput
	exit;
}
add_action( 'template_redirect', 'egovpod_transcript_deliver' );

/**
 * Kopfzeilen für beide Ausgaben.
 *
 * @param WP_Post $post Beitrag.
 * @return array<int, string>
 */
function egovpod_transcript_header_lines( $post ) {
	$zeilen = array();

	$nummer = egovpod_episode_number( $post );
	if ( $nummer ) {
		/* translators: %s: Episodennummer */
		$zeilen[] = sprintf( __( 'Folge %s', 'egovpod' ), $nummer );
	}

	$zeilen[] = get_the_title( $post );
	$zeilen[] = egovpod_podcast_title();
	$zeilen[] = get_the_date( '', $post );
	$zeilen[] = get_permalink( $post );

	return array_values( array_filter( $zeilen ) );
}

/**
 * Transkript als reiner Text.
 *
 * @param WP_Post                          $post    Beitrag.
 * @param array<int, array<string, mixed>> $bloecke Blöcke.
 * @return string
 */
function egovpod_transcript_as_text( $post, $bloecke ) {
	$kopf = egovpod_transcript_header_lines( $post );
	$aus  = implode( "\n", $kopf ) . "\n";
	$aus .= str_repeat( '=', 60 ) . "\n\n";

	foreach ( $bloecke as $block ) {
		if ( '' !== $block['sprecher'] ) {
			$aus .= $block['sprecher'] . ":\n";
		}
		$aus .= wordwrap( $block['text'], 80, "\n", false ) . "\n\n";
	}

	return $aus;
}

/**
 * Transkript als eigenständiges HTML-Dokument.
 *
 * @param WP_Post                          $post    Beitrag.
 * @param array<int, array<string, mixed>> $bloecke Blöcke.
 * @param bool                             $drucken Druckdialog beim Öffnen anstoßen.
 * @return string
 */
function egovpod_transcript_as_html( $post, $bloecke, $drucken = false ) {
	$titel  = get_the_title( $post );
	$nummer = egovpod_episode_number( $post );
	$kopf   = $nummer
		/* translators: 1: Episodennummer, 2: Titel */
		? sprintf( __( 'Folge %1$s – %2$s', 'egovpod' ), $nummer, $titel )
		: $titel;

	ob_start();
	?>
<!doctype html>
<html lang="<?php echo esc_attr( str_replace( '_', '-', get_locale() ) ); ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html( $kopf ); ?> – <?php esc_html_e( 'Transkript', 'egovpod' ); ?></title>
<style>
	:root { color-scheme: light; }
	body { margin: 0 auto; padding: 2rem 1.25rem 4rem; max-width: 46rem;
		font-family: "Fira Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
		font-size: 1rem; line-height: 1.65; color: #1a1a1a; background: #fff; }
	header { border-bottom: 3px solid #a64f00; padding-bottom: 1rem; margin-bottom: 2rem; }
	h1 { font-size: 1.5rem; line-height: 1.3; margin: 0 0 .25rem; }
	.meta { margin: 0; color: #595959; font-size: .9375rem; }
	.meta a { color: #a64f00; }
	.block { margin: 0 0 1.5rem; }
	.sprecher { margin: 0 0 .15rem; font-weight: 600; color: #a64f00; }
	.text { margin: 0; }
	.hinweis { margin: 0 0 2rem; padding: .75rem 1rem; background: #f4f4f4;
		border-left: 4px solid #a64f00; font-size: .9375rem; }
	.hinweis button { font: inherit; cursor: pointer; }
	@media print {
		body { max-width: none; padding: 0; font-size: 11pt; }
		.hinweis { display: none; }
		.block { break-inside: avoid; }
		header { border-bottom: 2pt solid #000; }
		.sprecher { color: #000; }
		.meta a { color: #000; text-decoration: none; }
	}
</style>
</head>
<body>
<?php if ( $drucken ) : ?>
	<div class="hinweis">
		<?php esc_html_e( 'Zum Speichern als PDF im Druckfenster „Als PDF sichern“ wählen.', 'egovpod' ); ?>
		<button type="button" onclick="window.print()"><?php esc_html_e( 'Drucken', 'egovpod' ); ?></button>
	</div>
<?php endif; ?>
<header>
	<h1><?php echo esc_html( $kopf ); ?></h1>
	<p class="meta">
		<?php echo esc_html( egovpod_podcast_title() ); ?> ·
		<?php echo esc_html( get_the_date( '', $post ) ); ?> ·
		<a href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo esc_html( get_permalink( $post ) ); ?></a>
	</p>
</header>
<?php foreach ( $bloecke as $block ) : ?>
	<div class="block">
		<?php if ( '' !== $block['sprecher'] ) : ?>
			<p class="sprecher"><?php echo esc_html( $block['sprecher'] ); ?></p>
		<?php endif; ?>
		<p class="text"><?php echo esc_html( $block['text'] ); ?></p>
	</div>
<?php endforeach; ?>
<?php if ( $drucken ) : ?>
	<script>window.addEventListener('load', function () { window.print(); });</script>
<?php endif; ?>
</body>
</html>
	<?php
	return ob_get_clean();
}
