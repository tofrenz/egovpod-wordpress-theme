<?php
/**
 * Episodenarchiv: Sortierung, Jahresfilter, Mitwirkende, Darstellung.
 *
 * Betrifft beide Archive – das Podlove-Archiv (archive-podcast.php) und die
 * Seite mit dem Template „Episodenarchiv". Beide bekommen dieselben
 * Abfrageeinstellungen über egovpod_archive_query_args().
 *
 * Alle Zugriffe auf Podlove-Tabellen laufen über deren eigene table_name()-
 * Methoden und sind abgesichert: Fehlt das Plugin oder ändert sich etwas,
 * fällt das Archiv auf Sortierung nach Datum und Karten ohne Mitwirkende
 * zurück.
 *
 * @package eGovPod
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Query-Variable für den Jahresfilter anmelden.
 *
 * @param array<int, string> $vars Bekannte Variablen.
 * @return array<int, string>
 */
function egovpod_query_vars( $vars ) {
	$vars[] = 'egp_jahr';
	return $vars;
}
add_filter( 'query_vars', 'egovpod_query_vars' );

/**
 * Gewähltes Jahr, oder 0.
 *
 * @return int
 */
function egovpod_current_year() {
	$jahr = (int) get_query_var( 'egp_jahr' );
	if ( ! $jahr && isset( $_GET['egp_jahr'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nur ein Filter, keine Aktion.
		$jahr = (int) $_GET['egp_jahr']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	return ( $jahr >= 2000 && $jahr <= (int) gmdate( 'Y' ) + 1 ) ? $jahr : 0;
}

/**
 * Episoden pro Seite.
 *
 * @return int
 */
function egovpod_archive_per_page() {
	return max( 1, (int) get_theme_mod( 'egovpod_archive_per_page', 24 ) );
}

/**
 * Abfrageeinstellungen für beide Archive.
 *
 * @param int $paged Seitennummer.
 * @return array<string, mixed>
 */
function egovpod_archive_query_args( $paged = 1 ) {
	$args = array(
		'post_type'         => 'podcast',
		'posts_per_page'    => egovpod_archive_per_page(),
		'paged'             => max( 1, (int) $paged ),
		'egovpod_by_number' => true,
	);

	$jahr = egovpod_current_year();
	if ( $jahr ) {
		$args['year'] = $jahr;
	}

	return $args;
}

/**
 * Hauptabfrage des Podlove-Archivs angleichen.
 *
 * @param WP_Query $query Abfrage.
 */
function egovpod_archive_pre_get_posts( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_post_type_archive( 'podcast' ) ) {
		return;
	}

	$query->set( 'posts_per_page', egovpod_archive_per_page() );
	$query->set( 'egovpod_by_number', true );

	$jahr = egovpod_current_year();
	if ( $jahr ) {
		$query->set( 'year', $jahr );
	}
}
add_action( 'pre_get_posts', 'egovpod_archive_pre_get_posts' );

/**
 * Tabellenname einer Podlove-Klasse, oder '' wenn es sie nicht gibt.
 *
 * @param string $klasse Vollständiger Klassenname.
 * @return string
 */
function egovpod_podlove_table( $klasse ) {
	if ( ! class_exists( $klasse ) || ! method_exists( $klasse, 'table_name' ) ) {
		return '';
	}
	try {
		$name = (string) call_user_func( array( $klasse, 'table_name' ) );
	} catch ( \Throwable $e ) {
		return '';
	}
	return preg_match( '/^[A-Za-z0-9_]+$/', $name ) ? $name : '';
}

/**
 * Soll diese Abfrage nach Folgennummer sortiert werden?
 *
 * @param WP_Query|null $query Abfrage.
 * @return string Tabellenname oder ''.
 */
function egovpod_number_sort_table( $query ) {
	if ( ! $query instanceof WP_Query || ! $query->get( 'egovpod_by_number' ) ) {
		return '';
	}
	return egovpod_podlove_table( '\Podlove\Model\Episode' );
}

/**
 * Join auf die Podlove-Episodentabelle.
 *
 * @param string   $join  Bisheriges JOIN.
 * @param WP_Query $query Abfrage.
 * @return string
 */
function egovpod_number_join( $join, $query ) {
	global $wpdb;

	$tabelle = egovpod_number_sort_table( $query );
	if ( '' === $tabelle ) {
		return $join;
	}

	return $join . " LEFT JOIN `{$tabelle}` AS egp_folge ON egp_folge.post_id = {$wpdb->posts}.ID ";
}
add_filter( 'posts_join', 'egovpod_number_join', 10, 2 );

/**
 * Nach Folgennummer absteigend sortieren.
 *
 * Veröffentlichungsdatum und Nummerierung laufen beim eGovernment Podcast
 * auseinander; nach Nummer liest sich die Liste sauber abwärts. Folgen ohne
 * Nummer rutschen ans Ende und werden dort nach Datum sortiert.
 *
 * @param string   $orderby Bisherige Sortierung.
 * @param WP_Query $query   Abfrage.
 * @return string
 */
function egovpod_number_orderby( $orderby, $query ) {
	global $wpdb;

	if ( '' === egovpod_number_sort_table( $query ) ) {
		return $orderby;
	}

	// Bei einer Suche bleibt die Relevanzsortierung von WordPress vorn.
	$vorn = $query->get( 's' ) ? $orderby . ', ' : '';

	return $vorn . "egp_folge.number IS NULL ASC, egp_folge.number + 0 DESC, {$wpdb->posts}.post_date DESC";
}
add_filter( 'posts_orderby', 'egovpod_number_orderby', 10, 2 );

/**
 * Jahre mit Episoden, absteigend, mit Anzahl.
 *
 * @return array<int, int> Jahr => Anzahl.
 */
function egovpod_episode_years() {
	$zwischenspeicher = get_transient( 'egovpod_episode_years' );
	if ( is_array( $zwischenspeicher ) ) {
		return $zwischenspeicher;
	}

	global $wpdb;

	$zeilen = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		"SELECT YEAR(post_date) AS jahr, COUNT(*) AS anzahl
		 FROM {$wpdb->posts}
		 WHERE post_type = 'podcast' AND post_status = 'publish'
		 GROUP BY jahr
		 ORDER BY jahr DESC"
	);

	$jahre = array();
	foreach ( (array) $zeilen as $zeile ) {
		$jahre[ (int) $zeile->jahr ] = (int) $zeile->anzahl;
	}

	set_transient( 'egovpod_episode_years', $jahre, DAY_IN_SECONDS );
	return $jahre;
}

/**
 * Jahresliste verwerfen, wenn sich eine Episode ändert.
 *
 * @param int $post_id Beitrags-ID.
 */
function egovpod_flush_episode_years( $post_id ) {
	if ( 'podcast' === get_post_type( $post_id ) ) {
		delete_transient( 'egovpod_episode_years' );
	}
}
add_action( 'save_post', 'egovpod_flush_episode_years' );
add_action( 'deleted_post', 'egovpod_flush_episode_years' );

/**
 * Adresse des Archivs ohne Seitennummer, für die Jahreslinks.
 *
 * @return string
 */
function egovpod_archive_base_url() {
	$url = egovpod_episode_archive_url();
	return $url ? $url : home_url( '/' );
}

/**
 * Jahresleiste ausgeben.
 */
function egovpod_year_nav() {
	$jahre = egovpod_episode_years();
	if ( count( $jahre ) < 2 ) {
		return;
	}

	$aktuell = egovpod_current_year();
	$basis   = egovpod_archive_base_url();
	$gesamt  = array_sum( $jahre );
	?>
	<nav class="egp-years" aria-label="<?php esc_attr_e( 'Nach Jahr filtern', 'egovpod' ); ?>">
		<ul class="egp-years__list">
			<li>
				<a class="egp-years__link<?php echo $aktuell ? '' : ' egp-years__link--aktiv'; ?>"
					href="<?php echo esc_url( $basis ); ?>"
					<?php echo $aktuell ? '' : 'aria-current="true"'; ?>>
					<?php esc_html_e( 'Alle', 'egovpod' ); ?>
					<span class="egp-years__zahl"><?php echo esc_html( number_format_i18n( $gesamt ) ); ?></span>
				</a>
			</li>
			<?php foreach ( $jahre as $jahr => $anzahl ) : ?>
				<li>
					<a class="egp-years__link<?php echo ( $aktuell === $jahr ) ? ' egp-years__link--aktiv' : ''; ?>"
						href="<?php echo esc_url( add_query_arg( 'egp_jahr', $jahr, $basis ) ); ?>"
						<?php echo ( $aktuell === $jahr ) ? 'aria-current="true"' : ''; ?>>
						<?php echo esc_html( $jahr ); ?>
						<span class="egp-years__zahl"><?php echo esc_html( number_format_i18n( $anzahl ) ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</nav>
	<?php
}

/**
 * Jahr an die Blätterlinks hängen.
 *
 * @param string $link Link.
 * @return string
 */
function egovpod_year_in_pagination( $link ) {
	$jahr = egovpod_current_year();
	return $jahr ? add_query_arg( 'egp_jahr', $jahr, $link ) : $link;
}

/**
 * Episodentabelle aus dem Seiteninhalt entfernen.
 *
 * Die Seite „Episodenübersicht" enthält als Inhalt nur den Podlove-Aufruf
 * [podlove-template template="podcast-archive"], der die lange Tabelle
 * ausgibt. Das Archiv ersetzt sie; als Vorspann wäre sie doppelt. Ein
 * Einleitungstext daneben bleibt stehen. An Podlove ändert sich nichts –
 * ohne dieses Seiten-Template steht die Tabelle unverändert wieder da.
 *
 * @param string $html Inhalt.
 * @return string
 */
function egovpod_strip_episode_table( $html ) {
	if ( false === stripos( $html, '<table' ) ) {
		return $html;
	}

	$ohne = preg_replace( '#<div class="egp-table-scroll">\s*<table\b.*?</table>\s*</div>#is', '', $html );
	if ( null === $ohne ) {
		$ohne = $html;
	}

	$ohne = preg_replace( '#<table\b.*?</table>#is', '', $ohne );

	return null === $ohne ? $html : $ohne;
}

/**
 * Zeile über dem Raster: wie viele Folgen, und der Umschalter.
 *
 * @param WP_Query|null $query Abfrage, aus der die Anzahl kommt.
 */
function egovpod_archive_toolbar( $query = null ) {
	$query  = $query instanceof WP_Query ? $query : $GLOBALS['wp_query'];
	$anzahl = (int) $query->found_posts;
	$jahr   = egovpod_current_year();
	$suche  = $query->get( 's' );

	if ( $suche ) {
		/* translators: 1: Anzahl, 2: Suchbegriff */
		$text = sprintf( _n( '%1$s Folge zu „%2$s"', '%1$s Folgen zu „%2$s"', $anzahl, 'egovpod' ), number_format_i18n( $anzahl ), $suche );
	} elseif ( $jahr ) {
		/* translators: 1: Anzahl, 2: Jahr */
		$text = sprintf( _n( '%1$s Folge aus %2$s', '%1$s Folgen aus %2$s', $anzahl, 'egovpod' ), number_format_i18n( $anzahl ), $jahr );
	} else {
		/* translators: %s: Anzahl */
		$text = sprintf( _n( '%s Folge', '%s Folgen', $anzahl, 'egovpod' ), number_format_i18n( $anzahl ) );
	}
	?>
	<div class="egp-archive-bar">
		<p class="kern-body kern-body--small egp-archive-bar__zahl"><?php echo esc_html( $text ); ?></p>
		<?php egovpod_view_switch(); ?>
	</div>
	<?php
}

/**
 * Umschalter Karten / Liste.
 *
 * Ohne JavaScript bleibt es bei den Karten; die Knöpfe werden erst
 * eingeblendet, wenn das Skript läuft.
 */
function egovpod_view_switch() {
	?>
	<div class="egp-viewswitch" role="group" aria-label="<?php esc_attr_e( 'Darstellung', 'egovpod' ); ?>" hidden>
		<button type="button" class="egp-viewswitch__btn" data-egp-view="karten" aria-pressed="true">
			<?php esc_html_e( 'Karten', 'egovpod' ); ?>
		</button>
		<button type="button" class="egp-viewswitch__btn" data-egp-view="liste" aria-pressed="false">
			<?php esc_html_e( 'Liste', 'egovpod' ); ?>
		</button>
	</div>
	<?php
}

/**
 * Skript für den Umschalter.
 */
function egovpod_view_switch_script() {
	if ( ! egovpod_is_episode_archive() ) {
		return;
	}
	?>
	<script>
	(function () {
		var schalter = document.querySelector('.egp-viewswitch');
		var raster = document.querySelector('.egp-grid');
		if (!schalter || !raster) { return; }

		function anwenden(ansicht) {
			raster.classList.toggle('egp-grid--liste', ansicht === 'liste');
			schalter.querySelectorAll('[data-egp-view]').forEach(function (b) {
				b.setAttribute('aria-pressed', String(b.dataset.egpView === ansicht));
			});
		}

		var gemerkt = 'karten';
		try { gemerkt = window.localStorage.getItem('egp-episoden-ansicht') || 'karten'; } catch (e) {}
		anwenden(gemerkt);

		schalter.hidden = false;
		schalter.addEventListener('click', function (ev) {
			var knopf = ev.target.closest('[data-egp-view]');
			if (!knopf) { return; }
			var ansicht = knopf.dataset.egpView;
			anwenden(ansicht);
			try { window.localStorage.setItem('egp-episoden-ansicht', ansicht); } catch (e) {}
		});
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'egovpod_view_switch_script' );

/**
 * Sind wir auf einem der beiden Episodenarchive?
 *
 * @return bool
 */
function egovpod_is_episode_archive() {
	if ( is_post_type_archive( 'podcast' ) ) {
		return true;
	}
	return is_page() && 'page-templates/template-episodes.php' === get_page_template_slug();
}

/**
 * Mitwirkende für mehrere Episoden auf einen Schlag holen.
 *
 * Eine Abfrage für die ganze Seite statt einer je Karte.
 *
 * @param array<int, int> $post_ids Beitrags-IDs.
 * @return array<int, array<int, array<string, string>>> Beitrags-ID => Liste.
 */
function egovpod_prime_episode_contributors( $post_ids ) {
	static $gespeichert = array();

	$post_ids = array_values( array_unique( array_map( 'intval', (array) $post_ids ) ) );
	$offen    = array_diff( $post_ids, array_keys( $gespeichert ) );

	if ( ! $offen ) {
		return $gespeichert;
	}

	foreach ( $offen as $id ) {
		$gespeichert[ $id ] = array();
	}

	$t_beitrag = egovpod_podlove_table( '\Podlove\Modules\Contributors\Model\EpisodeContribution' );
	$t_person  = egovpod_podlove_table( '\Podlove\Modules\Contributors\Model\Contributor' );
	$t_folge   = egovpod_podlove_table( '\Podlove\Model\Episode' );

	if ( ! $t_beitrag || ! $t_person || ! $t_folge ) {
		return $gespeichert;
	}

	global $wpdb;

	$platzhalter = implode( ',', array_fill( 0, count( $offen ), '%d' ) );

	$zeilen = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
		$wpdb->prepare(
			"SELECT f.post_id AS post_id, p.publicname, p.realname, p.nickname, p.avatar
			 FROM `{$t_beitrag}` AS b
			 INNER JOIN `{$t_folge}` AS f ON f.id = b.episode_id
			 INNER JOIN `{$t_person}` AS p ON p.id = b.contributor_id
			 WHERE f.post_id IN ({$platzhalter})
			 ORDER BY f.post_id ASC, b.position ASC, b.id ASC", // phpcs:ignore WordPress.DB.PreparedSQL
			$offen
		)
	);

	foreach ( (array) $zeilen as $zeile ) {
		$id   = (int) $zeile->post_id;
		$name = '';
		foreach ( array( 'publicname', 'realname', 'nickname' ) as $feld ) {
			if ( ! empty( $zeile->$feld ) ) {
				$name = trim( (string) $zeile->$feld );
				break;
			}
		}
		if ( '' === $name ) {
			continue;
		}

		// Doppelte Nennungen (mehrere Rollen in derselben Folge) nur einmal.
		foreach ( $gespeichert[ $id ] as $vorhanden ) {
			if ( $vorhanden['name'] === $name ) {
				continue 2;
			}
		}

		$bild = trim( (string) $zeile->avatar );
		$gespeichert[ $id ][] = array(
			'name'   => $name,
			'avatar' => preg_match( '#^https?://#i', $bild ) ? $bild : '',
		);
	}

	return $gespeichert;
}

/**
 * Mitwirkende einer Episode.
 *
 * @param int|WP_Post|null $post Beitrag.
 * @return array<int, array<string, string>>
 */
function egovpod_episode_contributors( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return array();
	}
	$alle = egovpod_prime_episode_contributors( array( $post->ID ) );
	return isset( $alle[ $post->ID ] ) ? $alle[ $post->ID ] : array();
}

/**
 * Mitwirkende als Avatarreihe ausgeben.
 *
 * @param int|WP_Post|null $post    Beitrag.
 * @param int              $maximal Wie viele Gesichter höchstens.
 */
function egovpod_episode_contributor_row( $post = null, $maximal = 5 ) {
	$leute = egovpod_episode_contributors( $post );
	if ( ! $leute ) {
		return;
	}

	$ziel    = egovpod_contributors_page_url();
	$zeigen  = array_slice( $leute, 0, $maximal );
	$weitere = count( $leute ) - count( $zeigen );
	?>
	<div class="egp-crew">
		<ul class="egp-crew__list">
			<?php foreach ( $zeigen as $person ) : ?>
				<li class="egp-crew__item">
					<?php if ( $ziel ) : ?>
						<a class="egp-crew__link" href="<?php echo esc_url( $ziel . '#' . egovpod_contributor_anchor( $person['name'] ) ); ?>" title="<?php echo esc_attr( $person['name'] ); ?>">
					<?php endif; ?>
					<?php if ( $person['avatar'] ) : ?>
						<img class="egp-crew__avatar" src="<?php echo esc_url( $person['avatar'] ); ?>" alt="<?php echo esc_attr( $person['name'] ); ?>" width="32" height="32" loading="lazy" decoding="async">
					<?php else : ?>
						<span class="egp-crew__avatar egp-crew__avatar--text" aria-hidden="true"><?php echo esc_html( egovpod_initials( $person['name'] ) ); ?></span>
						<span class="egp-sr-only"><?php echo esc_html( $person['name'] ); ?></span>
					<?php endif; ?>
					<?php if ( $ziel ) : ?>
						</a>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php if ( $weitere > 0 ) : ?>
			<span class="egp-crew__mehr">
				<?php
				/* translators: %s: Anzahl weiterer Mitwirkender */
				echo esc_html( sprintf( _n( '+%s weitere Person', '+%s weitere', $weitere, 'egovpod' ), number_format_i18n( $weitere ) ) );
				?>
			</span>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Initialen für Mitwirkende ohne Bild.
 *
 * @param string $name Name.
 * @return string
 */
function egovpod_initials( $name ) {
	$teile      = preg_split( '/\s+/u', trim( wp_strip_all_tags( $name ) ) );
	$initialen  = '';
	$funktionen = function_exists( 'mb_substr' );

	foreach ( array_slice( (array) $teile, 0, 2 ) as $teil ) {
		if ( '' === $teil ) {
			continue;
		}
		$zeichen    = $funktionen ? mb_substr( $teil, 0, 1, 'UTF-8' ) : substr( $teil, 0, 1 );
		$initialen .= $funktionen ? mb_strtoupper( $zeichen, 'UTF-8' ) : strtoupper( $zeichen );
	}

	return $initialen ? $initialen : '·';
}
