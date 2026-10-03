<?php
/**
 * Customizer-Einstellungen.
 *
 * @package eGovPod
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Auswahlwerte bereinigen.
 */
function egovpod_sanitize_choice( $value, $setting ) {
	$choices = $setting->manager->get_control( $setting->id )->choices;
	return array_key_exists( $value, $choices ) ? $value : $setting->default;
}

function egovpod_sanitize_checkbox( $value ) {
	return (bool) $value;
}

/**
 * Customizer registrieren.
 */
function egovpod_customize_register( $wp_customize ) {

	$wp_customize->add_panel(
		'egovpod',
		array(
			'title'       => __( 'eGovPod (Podcast)', 'egovpod' ),
			'description' => __( 'Farben, Kopfzeile, Breiten und Hell/Dunkel stellt das Eltern-Theme KERN-UX ein (Customizer → KERN-UX). Hier geht es nur um Podcast-Funktionen.', 'egovpod' ),
			'priority' => 30,
		)
	);

	/* ---------- Podlove ---------- */
	$wp_customize->add_section(
		'egovpod_podlove',
		array(
			'title'       => __( 'Podlove-Integration', 'egovpod' ),
			'panel'       => 'egovpod',
			'description' => __( 'Steuert, wie Web Player, Mitwirkende, Shownotes und Abo-Button eingebunden werden.', 'egovpod' ),
		)
	);

	$wp_customize->add_setting( 'egovpod_player_mode', array( 'default' => 'auto', 'sanitize_callback' => 'egovpod_sanitize_choice' ) );
	$wp_customize->add_control(
		'egovpod_player_mode',
		array(
			'label'       => __( 'Episoden-Bausteine ausgeben durch …', 'egovpod' ),
			'description' => __( '„Automatisch“ nutzt das Theme-Layout, außer in Podlove ist unter „Templates“ eine automatische Template-Zuweisung aktiv – so gibt es nie doppelte Player.', 'egovpod' ),
			'section'     => 'egovpod_podlove',
			'type'        => 'select',
			'choices'     => array(
				'auto'    => __( 'Automatisch (empfohlen)', 'egovpod' ),
				'theme'   => __( 'Immer das Theme', 'egovpod' ),
				'podlove' => __( 'Nur Podlove-Templates', 'egovpod' ),
			),
		)
	);

	$wp_customize->add_setting( 'egovpod_contributor_preset', array( 'default' => 'list', 'sanitize_callback' => 'egovpod_sanitize_choice' ) );
	$wp_customize->add_control(
		'egovpod_contributor_preset',
		array(
			'label'   => __( 'Darstellung der Mitwirkenden', 'egovpod' ),
			'section' => 'egovpod_podlove',
			'type'    => 'select',
			'choices' => array(
				'list'  => __( 'Liste', 'egovpod' ),
				'table' => __( 'Tabelle', 'egovpod' ),
				'comma' => __( 'Kommagetrennt', 'egovpod' ),
			),
		)
	);

	$checkboxes = array(
		'egovpod_show_downloads'  => array( __( 'Download-Buttons auf Episodenseiten', 'egovpod' ), true ),
		'egovpod_show_transcript' => array( __( 'Transkript anzeigen (falls vorhanden)', 'egovpod' ), true ),
		'egovpod_show_related'    => array( __( 'Verwandte Episoden anzeigen', 'egovpod' ), true ),
		'egovpod_jsonld'          => array( __( 'Strukturierte Daten (schema.org) ausgeben', 'egovpod' ), true ),
	);
	foreach ( $checkboxes as $id => $conf ) {
		$wp_customize->add_setting( $id, array( 'default' => $conf[1], 'sanitize_callback' => 'egovpod_sanitize_checkbox' ) );
		$wp_customize->add_control( $id, array( 'label' => $conf[0], 'section' => 'egovpod_podlove', 'type' => 'checkbox' ) );
	}

	/* ---------- Dopplungen ---------- */
	$wp_customize->add_section(
		'egovpod_duplicates',
		array(
			'title'       => __( 'Doppelte Bausteine', 'egovpod' ),
			'panel'       => 'egovpod',
			'description' => __( 'Beim eGovernment Podcast stehen Zusammenfassung, Web Player und Mitwirkende bereits im Episodentext. Damit nichts zweimal erscheint, gibt das Theme sie standardmäßig nicht noch einmal aus. Wer seine Episodentexte anders aufbaut, schaltet sie hier wieder ein.', 'egovpod' ),
		)
	);

	$duplicates = array(
		'egovpod_show_summary'          => array(
			__( 'Zusammenfassung aus Podlove als Vorspann anzeigen', 'egovpod' ),
			false,
			__( 'Nur einschalten, wenn der Episodentext die Zusammenfassung nicht selbst enthält.', 'egovpod' ),
		),
		'egovpod_show_contributors'     => array(
			__( 'Mitwirkende als eigenen Abschnitt anzeigen', 'egovpod' ),
			false,
			__( 'Nur einschalten, wenn im Episodentext kein eigener Block mit den Beteiligten steht.', 'egovpod' ),
		),
		'egovpod_strip_content_player'  => array(
			__( 'Zusätzlichen Web Player aus dem Episodentext entfernen', 'egovpod' ),
			true,
			__( 'Das Theme zeigt den Player oben neben Cover und Titel. Steht im Episodentext ein zweiter, wird er bei der Ausgabe übersprungen – auch dann, wenn er über das Podlove-Template „default“ eingebunden ist, das beim eGovernment Podcast in jeder Folge steht und nichts als den Player enthält. Der gespeicherte Episodentext und die Podlove-Einstellungen bleiben unverändert: Wird diese Einstellung ausgeschaltet, sind die Player im Text sofort wieder da.', 'egovpod' ),
		),
	);
	foreach ( $duplicates as $id => $conf ) {
		$wp_customize->add_setting( $id, array( 'default' => $conf[1], 'sanitize_callback' => 'egovpod_sanitize_checkbox' ) );
		$wp_customize->add_control(
			$id,
			array(
				'label'       => $conf[0],
				'description' => $conf[2],
				'section'     => 'egovpod_duplicates',
				'type'        => 'checkbox',
			)
		);
	}

	/* ---------- Startseite ---------- */
	$wp_customize->add_section( 'egovpod_front', array( 'title' => __( 'Startseite & Archiv', 'egovpod' ), 'panel' => 'egovpod' ) );

	$wp_customize->add_setting( 'egovpod_hero_title', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
	$wp_customize->add_control( 'egovpod_hero_title', array( 'label' => __( 'Überschrift (leer = Podcast-Titel aus Podlove)', 'egovpod' ), 'section' => 'egovpod_front' ) );

	$wp_customize->add_setting( 'egovpod_hero_text', array( 'default' => '', 'sanitize_callback' => 'wp_kses_post' ) );
	$wp_customize->add_control( 'egovpod_hero_text', array( 'label' => __( 'Einleitungstext (leer = Podcast-Beschreibung aus Podlove)', 'egovpod' ), 'section' => 'egovpod_front', 'type' => 'textarea' ) );

	$wp_customize->add_setting( 'egovpod_front_count', array( 'default' => 6, 'sanitize_callback' => 'absint' ) );
	$wp_customize->add_control( 'egovpod_front_count', array( 'label' => __( 'Anzahl weiterer Episoden auf der Startseite', 'egovpod' ), 'section' => 'egovpod_front', 'type' => 'number', 'input_attrs' => array( 'min' => 0, 'max' => 24 ) ) );

	$wp_customize->add_setting( 'egovpod_front_posts', array( 'default' => true, 'sanitize_callback' => 'egovpod_sanitize_checkbox' ) );
	$wp_customize->add_control( 'egovpod_front_posts', array( 'label' => __( 'Neueste Blogbeiträge auf der Startseite', 'egovpod' ), 'section' => 'egovpod_front', 'type' => 'checkbox' ) );

	$wp_customize->add_setting( 'egovpod_archive_per_page', array( 'default' => 12, 'sanitize_callback' => 'absint' ) );
	$wp_customize->add_control( 'egovpod_archive_per_page', array( 'label' => __( 'Episoden pro Archivseite', 'egovpod' ), 'section' => 'egovpod_front', 'type' => 'number', 'input_attrs' => array( 'min' => 3, 'max' => 60 ) ) );
}
add_action( 'customize_register', 'egovpod_customize_register' );
