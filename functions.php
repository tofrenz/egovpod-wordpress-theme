<?php
/**
 * eGovPod – Child-Theme von KERN-UX für den eGovernment Podcast.
 *
 * Das Eltern-Theme „KERN-UX“ (Samtgemeinde Emlichheim, gitlab.opencode.de/
 * sgemlichheim/kern-ux-theme-for-wordpress) liefert KERN-Kit, Kopf, Fuß,
 * Navigation, Hell/Dunkel-Umschalter, Blog-Templates und Block-Brücke.
 * Dieses Child-Theme ergänzt nur, was ein Podcast mit Podlove braucht:
 * Episodenseite, Episodenarchiv, Podcast-Startseite, Logo und Markenfarbe.
 *
 * @package eGovPod
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EGOVPOD_VERSION', '2.5.1' );

require_once get_stylesheet_directory() . '/inc/podlove.php';
require_once get_stylesheet_directory() . '/inc/template-tags.php';
require_once get_stylesheet_directory() . '/inc/customizer.php';
require_once get_stylesheet_directory() . '/inc/contributors.php';

/**
 * Theme-Setup (ergänzend zum Eltern-Theme).
 */
function egovpod_setup() {
	load_child_theme_textdomain( 'egovpod', get_stylesheet_directory() . '/languages' );
	add_image_size( 'egovpod-cover', 600, 600, true );
}
add_action( 'after_setup_theme', 'egovpod_setup', 20 );

/**
 * Stylesheets.
 *
 * Das Eltern-Theme lädt get_stylesheet_uri() als 'kern-ux-style' – bei einem
 * Child-Theme ist das die style.css des Childs. Deshalb wird die Brücken-CSS
 * des Eltern-Themes hier ergänzt und vor die Child-CSS gehängt.
 */
function egovpod_enqueue_styles() {
	$parent = wp_get_theme( get_template() );

	wp_enqueue_style(
		'kern-ux-parent-style',
		get_template_directory_uri() . '/style.css',
		array( 'kern-ux-native' ),
		$parent->get( 'Version' )
	);

	$styles = wp_styles();
	if ( isset( $styles->registered['kern-ux-style'] ) ) {
		$styles->registered['kern-ux-style']->deps[] = 'kern-ux-parent-style';
		$styles->registered['kern-ux-style']->ver    = EGOVPOD_VERSION;
	}
}
add_action( 'wp_enqueue_scripts', 'egovpod_enqueue_styles', 20 );

/**
 * Markenfarbe Orange als Voreinstellung für den Akzent des Eltern-Themes.
 *
 * Genutzt wird dessen Modus „Wunschfarbe, exakt“ mit zwei geprüften Werten:
 *   hell   #A64F00 – 5,6:1 auf Weiß (WCAG AA), Schrift auf Buttons weiß
 *   dunkel #FF9400 – das Logo-Orange, 9,5:1 auf Schwarz, Schrift schwarz
 * Greift nur, solange im Customizer nichts anderes gespeichert ist.
 *
 * @return array<string, mixed>
 */
function egovpod_accent_defaults() {
	return array(
		'kern_ux_accent'             => 'custom',
		'kern_ux_accent_mode'        => 'exact',
		'kern_ux_accent_custom'      => '#a64f00',
		'kern_ux_accent_custom_dark' => '#ff9400',
	);
}

foreach ( array_keys( egovpod_accent_defaults() ) as $egovpod_mod ) {
	add_filter(
		'theme_mod_' . $egovpod_mod,
		static function ( $value ) use ( $egovpod_mod ) {
			$saved = get_theme_mods();
			if ( is_array( $saved ) && array_key_exists( $egovpod_mod, $saved ) ) {
				return $value;
			}
			$defaults = egovpod_accent_defaults();
			return $defaults[ $egovpod_mod ];
		}
	);
}
unset( $egovpod_mod );

/**
 * Logo des eGovernment Podcast als Voreinstellung.
 *
 * WordPress kennt für has_custom_logo() keinen Filter, und das Eltern-Theme
 * fragt genau das ab. Deshalb wird das mitgelieferte assets/images/logo.png
 * einmalig in die Mediathek übernommen und als Logo gesetzt – aber nur, wenn
 * noch keins gesetzt ist. Danach ist es ein ganz normales, im Customizer
 * austauschbares Logo.
 */
function egovpod_install_logo() {
	if ( get_theme_mod( 'custom_logo' ) || get_option( 'egovpod_logo_installed' ) ) {
		return;
	}

	$source = get_stylesheet_directory() . '/assets/images/logo.png';
	if ( ! is_readable( $source ) ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$tmp = wp_tempnam( 'egovpod-logo.png' );
	if ( ! $tmp || ! copy( $source, $tmp ) ) {
		return;
	}

	$id = media_handle_sideload(
		array(
			'name'     => 'egovpod-logo.png',
			'tmp_name' => $tmp,
		),
		0,
		__( 'Logo eGovernment Podcast', 'egovpod' )
	);

	if ( is_wp_error( $id ) ) {
		wp_delete_file( $tmp );
		return;
	}

	update_post_meta( $id, '_wp_attachment_image_alt', egovpod_podcast_title() );
	set_theme_mod( 'custom_logo', $id );
	update_option( 'egovpod_logo_installed', $id, false );
}
add_action( 'after_switch_theme', 'egovpod_install_logo' );
add_action( 'admin_init', 'egovpod_install_logo' );

/**
 * Body-Klassen.
 */
function egovpod_body_classes( $classes ) {
	if ( egovpod_podlove_active() ) {
		$classes[] = 'has-podlove';
	}
	if ( is_singular( 'podcast' ) ) {
		$classes[] = 'is-episode';
	}
	if ( has_custom_logo() ) {
		$classes[] = 'egp-has-logo';
	}
	return $classes;
}
add_filter( 'body_class', 'egovpod_body_classes' );

/**
 * Suche optional auf Episoden einschränken (?post_type=podcast).
 */
function egovpod_search_filter( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
		return;
	}
	if ( isset( $_GET['post_type'] ) && 'podcast' === sanitize_key( wp_unslash( $_GET['post_type'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$query->set( 'post_type', 'podcast' );
	}
}
add_action( 'pre_get_posts', 'egovpod_search_filter' );

/**
 * Episodenarchiv: Anzahl pro Seite.
 */
function egovpod_archive_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( $query->is_post_type_archive( 'podcast' ) ) {
		$query->set( 'posts_per_page', (int) get_theme_mod( 'egovpod_archive_per_page', 12 ) );
	}
}
add_action( 'pre_get_posts', 'egovpod_archive_query' );

/**
 * Hinweis im Backend, falls das Eltern-Theme fehlt (Sicherheitsnetz –
 * WordPress aktiviert ein Child ohne Eltern-Theme ohnehin nicht).
 */
function egovpod_parent_notice() {
	if ( function_exists( 'kern_ux_setup' ) || ! current_user_can( 'switch_themes' ) ) {
		return;
	}
	echo '<div class="notice notice-error"><p>' . wp_kses_post( __( '<strong>eGovPod</strong> benötigt das Eltern-Theme <strong>KERN-UX</strong> im Ordner <code>wp-content/themes/kern-ux</code>.', 'egovpod' ) ) . '</p></div>';
}
add_action( 'admin_notices', 'egovpod_parent_notice' );

/**
 * Tabellen im Inhalt in einen scrollbaren Rahmen legen.
 *
 * Das Eltern-Theme macht breite Tabellen scrollbar, indem es die Tabelle
 * selbst auf `display: block` setzt. Damit verliert sie ihr Tabellenlayout
 * (siehe style.css). Das Theme nimmt das zurück und legt das Scrollen
 * stattdessen in einen Rahmen um die Tabelle – so bleibt beides erhalten.
 *
 * Verschachtelte Tabellen kommen in diesen Inhalten nicht vor; gäbe es sie,
 * bliebe die innere ohne Rahmen. Das ist harmlos.
 *
 * @param string $content Beitragsinhalt.
 * @return string
 */
function egovpod_wrap_tables( $content ) {
	if ( is_admin() || is_feed() || false === stripos( $content, '<table' ) ) {
		return $content;
	}

	$umhuellt = preg_replace(
		'#<table\b(?:[^>]*)>.*?</table>#is',
		'<div class="egp-table-scroll">$0</div>',
		$content
	);

	return null === $umhuellt ? $content : $umhuellt;
}
add_filter( 'the_content', 'egovpod_wrap_tables', 30 );

/**
 * Nur die Rahmen fokussierbar machen, die tatsächlich scrollen.
 *
 * Ein Rahmen, der nichts zu scrollen hat, wäre sonst eine Tabstation ohne
 * Zweck. Scrollt er, muss die Tastatur hineinkommen – deshalb bekommt er
 * dann tabindex, Rolle und einen Namen.
 */
function egovpod_table_scroll_script() {
	if ( is_admin() ) {
		return;
	}
	?>
<script>
(function () {
	var pruefe = function () {
		document.querySelectorAll('.egp-table-scroll').forEach(function (r) {
			var scrollt = r.scrollWidth > r.clientWidth + 1;
			if (scrollt) {
				r.setAttribute('tabindex', '0');
				r.setAttribute('role', 'region');
				r.setAttribute('aria-label', <?php echo wp_json_encode( __( 'Tabelle, waagerecht scrollbar', 'egovpod' ) ); ?>);
			} else {
				r.removeAttribute('tabindex');
				r.removeAttribute('role');
				r.removeAttribute('aria-label');
			}
		});
	};
	document.addEventListener('DOMContentLoaded', pruefe);
	window.addEventListener('resize', pruefe);
})();
</script>
	<?php
}
add_action( 'wp_footer', 'egovpod_table_scroll_script' );
