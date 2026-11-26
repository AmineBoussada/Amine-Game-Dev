<?php
/**
 * Inline SVG icons.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stroke icon paths (24x24 grid), keyed by name.
 *
 * @return array<string,string>
 */
function tassili_icon_paths() {
	return array(
		'search'    => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
		'heart'     => '<path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10Z"/>',
		'pin'       => '<path d="M12 21s7-6.2 7-12a7 7 0 0 0-14 0c0 5.8 7 12 7 12Z"/><circle cx="12" cy="9" r="2.5"/>',
		'phone'     => '<path d="M5 4h3l2 5-2.5 1.5a11 11 0 0 0 6 6L15 14l5 2v3a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z"/>',
		'mail'      => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
		'menu'      => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'close'     => '<path d="M6 6l12 12M18 6 6 18"/>',
		'chev'      => '<path d="m9 6 6 6-6 6"/>',
		'arrow'     => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'area'      => '<rect x="4" y="4" width="16" height="16" rx="1"/><path d="M4 9h5V4M20 15h-5v5"/>',
		'bed'       => '<path d="M3 18v-7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v7M3 14h18M7 9V7a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v2"/>',
		'bath'      => '<path d="M4 12h16v3a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4v-3ZM6 12V6a2 2 0 0 1 3.5-1.3M7 19l-1 2M17 19l1 2"/>',
		'rooms'     => '<path d="M3 21V9l9-6 9 6v12"/><path d="M9 21v-7h6v7"/>',
		'layers'    => '<path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/>',
		'calendar'  => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
		'check'     => '<path d="m5 12 5 5 9-10"/>',
		'minus'     => '<path d="M6 12h12"/>',
		'share'     => '<circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="6" r="2.5"/><circle cx="18" cy="18" r="2.5"/><path d="m8.2 10.8 7.6-3.6M8.2 13.2l7.6 3.6"/>',
		'grid'      => '<rect x="4" y="4" width="7" height="7" rx="1"/><rect x="13" y="4" width="7" height="7" rx="1"/><rect x="4" y="13" width="7" height="7" rx="1"/><rect x="13" y="13" width="7" height="7" rx="1"/>',
		'list'      => '<rect x="4" y="5" width="6" height="5" rx="1"/><rect x="4" y="14" width="6" height="5" rx="1"/><path d="M13 7h7M13 16h7"/>',
		'filter'    => '<path d="M4 6h16M7 12h10M10 18h4"/>',
		'sun'       => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
		'moon'      => '<path d="M20 14.5A8 8 0 1 1 9.5 4a6.5 6.5 0 0 0 10.5 10.5Z"/>',
		'globe'     => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
		'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'camera'    => '<path d="M4 8h3l2-3h6l2 3h3v11H4z"/><circle cx="12" cy="13" r="3.5"/>',
		'building'  => '<path d="M5 21V4h10v17M15 9h4v12M3 21h18M8 8h4M8 12h4M8 16h4"/>',
		'villa'     => '<path d="M3 11 12 4l9 7M5 10v10h14V10M10 20v-6h4v6"/>',
		'land'      => '<path d="M3 18c3-4 6-6 9-6s6 2 9 6"/><path d="M7 11V6M7 6 5 8M7 6l2 2M17 9V5"/><path d="M3 21h18"/>',
		'shop'      => '<path d="M4 9h16l-1-5H5L4 9Zm0 0v11h16V9M10 20v-5h4v5"/>',
		'duplex'    => '<path d="M4 21V8l5-4 5 4M14 21V11l3-2 3 2v10M2 21h20M7 13h4M7 17h4"/>',
		'shield'    => '<path d="M12 3 5 6v6c0 4.5 3 7.5 7 9 4-1.5 7-4.5 7-9V6l-7-3Z"/><path d="m9 12 2 2 4-4"/>',
		'key'       => '<circle cx="8" cy="15" r="4"/><path d="m11 12 9-9M17 6l3 3M15 8l2 2"/>',
		'doc'       => '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4M9 12h6M9 16h6"/>',
		'bolt'      => '<path d="M13 3 5 14h6l-1 7 8-11h-6l1-7Z"/>',
		'quote'     => '<path d="M7 17c-2 0-3-1.5-3-4 0-3 2-6 5-7M17 17c-2 0-3-1.5-3-4 0-3 2-6 5-7"/>',
		'external'  => '<path d="M14 4h6v6M20 4l-9 9M18 14v6H4V6h6"/>',
		'users'     => '<circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-6 7-6s7 2.5 7 6M16 4a3.5 3.5 0 0 1 0 7M22 20c0-3-2-5-5-5.7"/>',
		'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1"/>',
		'facebook'  => '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8Z"/>',
	);
}

/**
 * Tags and attributes allowed in the icon SVG markup.
 *
 * @return array<string,array<string,bool>>
 */
function tassili_svg_allowed_html() {
	$shape = array(
		'd'               => true,
		'cx'              => true,
		'cy'              => true,
		'r'               => true,
		'x'               => true,
		'y'               => true,
		'rx'              => true,
		'width'           => true,
		'height'          => true,
		'fill'            => true,
		'stroke'          => true,
		'stroke-width'    => true,
		'stroke-linecap'  => true,
		'stroke-linejoin' => true,
		'transform'       => true,
	);
	return array(
		'svg'    => array(
			'class'           => true,
			'viewbox'         => true,
			'viewBox'         => true,
			'fill'            => true,
			'stroke'          => true,
			'stroke-width'    => true,
			'stroke-linecap'  => true,
			'stroke-linejoin' => true,
			'aria-hidden'     => true,
			'focusable'       => true,
			'xmlns'           => true,
			'width'           => true,
			'height'          => true,
		),
		'path'   => $shape,
		'circle' => $shape,
		'rect'   => $shape,
		'g'      => $shape,
	);
}

/**
 * SVG markup of an icon.
 *
 * @param string $name  Icon name.
 * @param string $extra_class Extra CSS classes.
 * @return string
 */
function tassili_icon( $name, $extra_class = '' ) {
	$classes = 'ic' . ( '' !== $extra_class ? ' ' . $extra_class : '' );

	if ( 'wa' === $name ) {
		$inner = '<path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.7.8-.8 1-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.3-.4.7-1.3a.5.5 0 0 0 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6a2.7 2.7 0 0 0 1.8-1.2 2.2 2.2 0 0 0 .1-1.3c0-.1-.2-.2-.4-.3Z"/>';
		return '<svg class="' . esc_attr( $classes ) . '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">' . $inner . '</svg>';
	}

	$paths = tassili_icon_paths();
	$inner = isset( $paths[ $name ] ) ? $paths[ $name ] : '';
	return '<svg class="' . esc_attr( $classes ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $inner . '</svg>';
}

/**
 * Print an icon.
 *
 * @param string $name  Icon name.
 * @param string $extra_class Extra CSS classes.
 */
function tassili_the_icon( $name, $extra_class = '' ) {
	echo wp_kses( tassili_icon( $name, $extra_class ), tassili_svg_allowed_html() );
}

/**
 * The logo mark.
 */
function tassili_the_logo_mark() {
	echo wp_kses(
		'<svg viewBox="0 0 34 34" aria-hidden="true" focusable="false"><path d="M3 29 L12 12 L17 20 L23 8 L31 29 Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><circle cx="26" cy="6" r="2.4" fill="#FFB703"/><path d="M3 29h28" stroke="#219EBC" stroke-width="2.4" stroke-linecap="round"/></svg>',
		tassili_svg_allowed_html()
	);
}
