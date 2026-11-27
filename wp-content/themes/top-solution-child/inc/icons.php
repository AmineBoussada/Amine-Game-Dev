<?php
/**
 * Inline SVG icon set (stroke icons, 24px grid). Static theme-owned markup.
 *
 * @package TopSolutionChild
 */

defined( 'ABSPATH' ) || exit;

/**
 * Icon path data by name.
 *
 * Names listed in ts_child_directional_icons() are mirrored in RTL.
 *
 * @return array<string,string>
 */
function ts_child_icon_paths() {
	return array(
		'menu'      => '<path d="M4 6h16M4 12h16M4 18h16"/>',
		'close'     => '<path d="M6 6l12 12M18 6L6 18"/>',
		'search'    => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>',
		'cart'      => '<circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.6 12.4a1 1 0 0 0 1 .8h8.8a1 1 0 0 0 1-.8L20 8H6"/>',
		'phone'     => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/>',
		'whatsapp'  => '<path d="M3 21l1.6-4.8A8.5 8.5 0 1 1 8 19.5z"/><path d="M9 9.5c.5 2 2.5 4 4.5 4.5l1.3-1.3-2-1-.8.8c-.8-.4-1.6-1.2-2-2l.8-.8-1-2z"/>',
		'mail'      => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
		'pin'       => '<path d="M12 21s7-6.2 7-11a7 7 0 0 0-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/>',
		'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'chevron'   => '<path d="M6 9l6 6 6-6"/>',
		'arrow-end' => '<path d="M9 6l6 6-6 6"/>',
		'truck'     => '<path d="M3 6h11v10H3zM14 9h4l3 3v4h-7"/><circle cx="7" cy="18" r="1.8"/><circle cx="17" cy="18" r="1.8"/>',
		'wallet'    => '<rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18M16 14.5h2"/>',
		'store'     => '<path d="M4 9l1.5-5h13L20 9M4 9v10h16V9M4 9a2.7 2.7 0 0 0 5.3 0 2.7 2.7 0 0 0 5.4 0A2.7 2.7 0 0 0 20 9M10 19v-5h4v5"/>',
		'check'     => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
	);
}

/**
 * Icons whose direction must be mirrored in RTL.
 *
 * @return string[]
 */
function ts_child_directional_icons() {
	return array( 'arrow-end' );
}

/**
 * Allowed tags/attributes for the icon markup.
 *
 * @return array<string,array<string,bool>>
 */
function ts_child_svg_allowed_html() {
	return array(
		'svg'    => array(
			'class'           => true,
			'viewbox'         => true,
			'width'           => true,
			'height'          => true,
			'fill'            => true,
			'stroke'          => true,
			'stroke-width'    => true,
			'stroke-linecap'  => true,
			'stroke-linejoin' => true,
			'aria-hidden'     => true,
			'focusable'       => true,
			'xmlns'           => true,
		),
		'path'   => array( 'd' => true ),
		'circle' => array(
			'cx' => true,
			'cy' => true,
			'r'  => true,
		),
		'rect'   => array(
			'x'      => true,
			'y'      => true,
			'width'  => true,
			'height' => true,
			'rx'     => true,
		),
	);
}

/**
 * Get an icon's SVG markup.
 *
 * @param string $name  Icon name.
 * @param string $extra_class Extra CSS class.
 * @return string Safe SVG markup, or empty string for unknown icons.
 */
function ts_child_icon( $name, $extra_class = '' ) {
	$paths = ts_child_icon_paths();
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	$classes = 'ts-icon';
	if ( in_array( $name, ts_child_directional_icons(), true ) ) {
		$classes .= ' ts-icon--dir';
	}
	if ( '' !== $extra_class ) {
		$classes .= ' ' . $extra_class;
	}
	$svg = sprintf(
		'<svg class="%s" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%s</svg>',
		esc_attr( $classes ),
		$paths[ $name ]
	);
	return wp_kses( $svg, ts_child_svg_allowed_html() );
}

/**
 * Echo an icon.
 *
 * @param string $name  Icon name.
 * @param string $extra_class Extra CSS class.
 * @return void
 */
function ts_child_the_icon( $name, $extra_class = '' ) {
	echo ts_child_icon( $name, $extra_class ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped through wp_kses() in ts_child_icon().
}
