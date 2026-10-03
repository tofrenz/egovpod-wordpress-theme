<?php
/**
 * Teilnehmer:innen-Seite: aus der Podlove-Tabelle werden Karten.
 *
 * Die Seite „Teilnehmer" gibt über ein Podlove-Template eine Tabelle mit
 * 268 Zeilen aus: Avatar, Name samt aller Folgen, Dienste-Symbole und eine
 * vierte, durchgehend leere Spalte. Als Tabelle ist das fast 50.000 Pixel
 * hoch und kaum zu überblicken.
 *
 * Dieses Modul liest die Tabelle und baut daraus ein Raster aus Karten,
 * gruppiert nach Anfangsbuchstaben, mit einem mitlaufenden Register. An der
 * Quelle – dem Podlove-Template – ändert sich nichts: Wird das Seiten-Template
 * gewechselt, steht die Tabelle unverändert wieder da.
 *
 * @package eGovPod
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Anfangsbuchstaben für das Register bestimmen.
 *
 * Umlaute und ß werden eingedeutscht einsortiert (Ä unter A, Ö unter O,
 * Ü unter U, ß unter S), alles andere landet unter „#".
 *
 * @param string $name Anzeigename.
 * @return string Ein Zeichen A–Z oder '#'.
 */
function egovpod_contributor_letter( $name ) {
	$name = trim( wp_strip_all_tags( $name ) );

	if ( '' === $name ) {
		return '#';
	}

	$erstes = function_exists( 'mb_substr' ) ? mb_substr( $name, 0, 1, 'UTF-8' ) : substr( $name, 0, 1 );
	$erstes = function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $erstes, 'UTF-8' ) : strtoupper( $erstes );

	$karte = array(
		'Ä' => 'A',
		'Á' => 'A',
		'À' => 'A',
		'Â' => 'A',
		'Å' => 'A',
		'Ö' => 'O',
		'Ó' => 'O',
		'Ò' => 'O',
		'Ô' => 'O',
		'Ø' => 'O',
		'Ü' => 'U',
		'Ú' => 'U',
		'Ù' => 'U',
		'É' => 'E',
		'È' => 'E',
		'Ê' => 'E',
		'Í' => 'I',
		'Ï' => 'I',
		'Ç' => 'C',
		'Ñ' => 'N',
		'Ś' => 'S',
		'Š' => 'S',
		'ß' => 'S',
		'Ž' => 'Z',
		'Ł' => 'L',
	);

	if ( isset( $karte[ $erstes ] ) ) {
		$erstes = $karte[ $erstes ];
	}

	return preg_match( '/^[A-Z]$/', $erstes ) ? $erstes : '#';
}

/**
 * Inneres HTML eines Knotens als Zeichenkette.
 *
 * @param DOMNode $knoten Knoten.
 * @return string
 */
function egovpod_dom_inner_html( $knoten ) {
	$html = '';
	foreach ( $knoten->childNodes as $kind ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
		$html .= $knoten->ownerDocument->saveHTML( $kind ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName
	}
	return $html;
}

/**
 * Teilnehmer:innen aus der Tabelle lesen.
 *
 * @param string $html Inhalt der Seite.
 * @return array<int, array<string, mixed>>|false Liste oder false ohne Tabelle.
 */
function egovpod_parse_contributor_table( $html ) {
	if ( ! class_exists( 'DOMDocument' ) || false === stripos( $html, '<table' ) ) {
		return false;
	}

	$vorher = libxml_use_internal_errors( true );
	$doc    = new DOMDocument();
	$doc->loadHTML( '<?xml encoding="utf-8" ?><div>' . $html . '</div>' );
	libxml_clear_errors();
	libxml_use_internal_errors( $vorher );

	$tabellen = $doc->getElementsByTagName( 'table' );
	if ( ! $tabellen->length ) {
		return false;
	}

	$leute = array();

	foreach ( $tabellen->item( 0 )->getElementsByTagName( 'tr' ) as $zeile ) {
		$zellen = array();
		foreach ( $zeile->childNodes as $kind ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			if ( XML_ELEMENT_NODE === $kind->nodeType && in_array( strtolower( $kind->nodeName ), array( 'td', 'th' ), true ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
				$zellen[] = $kind;
			}
		}

		if ( count( $zellen ) < 2 ) {
			continue;
		}

		// Name: der Text vor den Folgen-Links, ohne den Doppelpunkt.
		$name = '';
		foreach ( $zellen[1]->childNodes as $kind ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			if ( XML_TEXT_NODE === $kind->nodeType ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
				$name .= $kind->textContent; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			}
		}
		$name = trim( preg_replace( '/\s+/u', ' ', $name ) );
		$name = rtrim( $name, " \t\n\r\0\x0B:" );

		if ( '' === $name ) {
			continue;
		}

		$avatar = '';
		$bilder = $zellen[0]->getElementsByTagName( 'img' );
		if ( $bilder->length ) {
			$avatar = $doc->saveHTML( $bilder->item( 0 ) );
		}

		$folgen = array();
		foreach ( $zellen[1]->getElementsByTagName( 'a' ) as $link ) {
			$folgen[] = $doc->saveHTML( $link );
		}

		$dienste = array();
		if ( isset( $zellen[2] ) ) {
			foreach ( $zellen[2]->getElementsByTagName( 'a' ) as $link ) {
				$dienste[] = $doc->saveHTML( $link );
			}
		}

		$leute[] = array(
			'name'      => $name,
			'buchstabe' => egovpod_contributor_letter( $name ),
			'avatar'    => $avatar,
			'folgen'    => $folgen,
			'dienste'   => $dienste,
		);
	}

	return $leute ? $leute : false;
}

/**
 * Karten und Register ausgeben.
 *
 * @param array<int, array<string, mixed>> $leute Teilnehmer:innen.
 * @return string
 */
function egovpod_render_contributor_cards( $leute ) {
	$gruppen = array();
	foreach ( $leute as $person ) {
		$gruppen[ $person['buchstabe'] ][] = $person;
	}

	$alphabet = range( 'A', 'Z' );
	if ( isset( $gruppen['#'] ) ) {
		$alphabet[] = '#';
	}

	ob_start();
	?>
	<div class="egp-people">

		<nav class="egp-az" aria-label="<?php esc_attr_e( 'Alphabetisches Register', 'egovpod' ); ?>">
			<ul class="egp-az__list">
				<?php foreach ( $alphabet as $buchstabe ) : ?>
					<li>
						<?php if ( isset( $gruppen[ $buchstabe ] ) ) : ?>
							<a class="egp-az__link" href="#egp-gruppe-<?php echo esc_attr( strtolower( '#' === $buchstabe ? 'rest' : $buchstabe ) ); ?>">
								<?php echo esc_html( $buchstabe ); ?>
							</a>
						<?php else : ?>
							<span class="egp-az__link egp-az__link--leer" aria-hidden="true"><?php echo esc_html( $buchstabe ); ?></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>

		<?php foreach ( $alphabet as $buchstabe ) : ?>
			<?php
			if ( ! isset( $gruppen[ $buchstabe ] ) ) {
				continue;
			}
			$anker = 'egp-gruppe-' . strtolower( '#' === $buchstabe ? 'rest' : $buchstabe );
			?>
			<section class="egp-people__group" id="<?php echo esc_attr( $anker ); ?>" aria-labelledby="<?php echo esc_attr( $anker . '-titel' ); ?>">
				<h2 class="kern-heading-medium egp-people__letter" id="<?php echo esc_attr( $anker . '-titel' ); ?>">
					<?php echo esc_html( '#' === $buchstabe ? __( 'Übrige', 'egovpod' ) : $buchstabe ); ?>
				</h2>

				<ul class="egp-people__grid">
					<?php foreach ( $gruppen[ $buchstabe ] as $person ) : ?>
						<li class="kern-card kern-card--hug egp-person">
							<div class="kern-card__container">

								<div class="egp-person__head">
									<?php if ( $person['avatar'] ) : ?>
										<span class="egp-person__avatar"><?php echo wp_kses_post( $person['avatar'] ); ?></span>
									<?php endif; ?>
									<div class="egp-person__ident">
										<h3 class="kern-title kern-title--small egp-person__name"><?php echo esc_html( $person['name'] ); ?></h3>
										<p class="kern-body kern-body--small egp-person__count">
											<?php
											$anzahl = count( $person['folgen'] );
											/* translators: %s: Anzahl der Folgen */
											echo esc_html( sprintf( _n( '%s Folge', '%s Folgen', $anzahl, 'egovpod' ), number_format_i18n( $anzahl ) ) );
											?>
										</p>
									</div>
								</div>

								<?php if ( $person['dienste'] ) : ?>
									<div class="egp-person__services">
										<?php foreach ( $person['dienste'] as $dienst ) : ?>
											<?php echo wp_kses_post( $dienst ); ?>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>

								<?php if ( $person['folgen'] ) : ?>
									<details class="egp-person__episodes">
										<summary class="egp-person__summary">
											<?php esc_html_e( 'Folgen anzeigen', 'egovpod' ); ?>
										</summary>
										<ul class="egp-person__list">
											<?php foreach ( $person['folgen'] as $folge ) : ?>
												<li><?php echo wp_kses_post( $folge ); ?></li>
											<?php endforeach; ?>
										</ul>
									</details>
								<?php endif; ?>

							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endforeach; ?>

	</div>
	<?php
	return ob_get_clean();
}
