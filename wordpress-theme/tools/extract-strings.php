<?php
/**
 * Lists every translatable string of the theme (a small stand-in for xgettext / WP-CLI i18n,
 * which are not always installed). Usage: php tools/extract-strings.php > strings.json
 *
 * Recognises __, _e, esc_html__, esc_html_e, esc_attr__, esc_attr_e, _x, _ex, esc_html_x,
 * esc_attr_x, _n and _nx with the text domain tassili-immobilier.
 */

$theme = dirname( __DIR__ ) . '/tassili-immobilier';
$domain = 'tassili-immobilier';

// function => [index of msgid, index of plural or null, index of context or null, index of domain]
$funcs = array(
	'__'            => array( 0, null, null, 1 ),
	'_e'            => array( 0, null, null, 1 ),
	'esc_html__'    => array( 0, null, null, 1 ),
	'esc_html_e'    => array( 0, null, null, 1 ),
	'esc_attr__'    => array( 0, null, null, 1 ),
	'esc_attr_e'    => array( 0, null, null, 1 ),
	'_x'            => array( 0, null, 1, 2 ),
	'_ex'           => array( 0, null, 1, 2 ),
	'esc_html_x'    => array( 0, null, 1, 2 ),
	'esc_attr_x'    => array( 0, null, 1, 2 ),
	'_n'            => array( 0, 1, null, 3 ),
	'_nx'           => array( 0, 1, 3, 4 ),
);

$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $theme, FilesystemIterator::SKIP_DOTS ) );
$found    = array();

foreach ( $iterator as $file ) {
	if ( 'php' !== $file->getExtension() ) {
		continue;
	}
	$path   = $file->getPathname();
	$rel    = ltrim( str_replace( $theme, '', $path ), '/' );
	$tokens = token_get_all( file_get_contents( $path ) );
	$count  = count( $tokens );

	for ( $i = 0; $i < $count; $i++ ) {
		if ( ! is_array( $tokens[ $i ] ) || T_STRING !== $tokens[ $i ][0] || ! isset( $funcs[ $tokens[ $i ][1] ] ) ) {
			continue;
		}
		// Next significant token must be "(".
		$j = $i + 1;
		while ( $j < $count && is_array( $tokens[ $j ] ) && in_array( $tokens[ $j ][0], array( T_WHITESPACE, T_COMMENT ), true ) ) {
			++$j;
		}
		if ( '(' !== $tokens[ $j ] ) {
			continue;
		}
		// Collect top-level arguments until the matching ")".
		$depth = 0;
		$args  = array();
		$cur   = array();
		for ( $k = $j; $k < $count; $k++ ) {
			$tok = $tokens[ $k ];
			if ( '(' === $tok || '[' === $tok ) {
				++$depth;
				if ( 1 === $depth ) {
					continue;
				}
			} elseif ( ')' === $tok || ']' === $tok ) {
				--$depth;
				if ( 0 === $depth ) {
					$args[] = $cur;
					break;
				}
			} elseif ( ',' === $tok && 1 === $depth ) {
				$args[] = $cur;
				$cur    = array();
				continue;
			}
			$cur[] = $tok;
		}

		$def  = $funcs[ $tokens[ $i ][1] ];
		$text = static function ( $arg ) {
			$out = '';
			foreach ( $arg as $tok ) {
				if ( is_array( $tok ) && T_CONSTANT_ENCAPSED_STRING === $tok[0] ) {
					$raw = $tok[1];
					$out .= ( "'" === $raw[0] ) ? str_replace( array( "\\'", '\\\\' ), array( "'", '\\' ), substr( $raw, 1, -1 ) ) : stripcslashes( substr( $raw, 1, -1 ) );
				} elseif ( is_array( $tok ) && T_WHITESPACE === $tok[0] ) {
					continue;
				} elseif ( '.' === $tok ) {
					continue;
				} else {
					return null; // not a plain string literal
				}
			}
			return $out;
		};
		if ( ! isset( $args[ $def[0] ] ) || ! isset( $args[ $def[3] ] ) ) {
			continue;
		}
		$dom = $text( $args[ $def[3] ] );
		if ( $domain !== $dom ) {
			continue;
		}
		$msgid = $text( $args[ $def[0] ] );
		if ( null === $msgid || '' === $msgid ) {
			fwrite( STDERR, "Skipped non-literal string in $rel line " . ( is_array( $tokens[ $i ] ) ? $tokens[ $i ][2] : '?' ) . "\n" );
			continue;
		}
		$plural  = null !== $def[1] && isset( $args[ $def[1] ] ) ? $text( $args[ $def[1] ] ) : null;
		$context = null !== $def[2] && isset( $args[ $def[2] ] ) ? $text( $args[ $def[2] ] ) : null;
		$key     = ( null !== $context ? $context . "\x04" : '' ) . $msgid;
		if ( ! isset( $found[ $key ] ) ) {
			$found[ $key ] = array(
				'msgid'   => $msgid,
				'plural'  => $plural,
				'context' => $context,
				'refs'    => array(),
			);
		}
		$found[ $key ]['refs'][] = $rel . ':' . ( is_array( $tokens[ $i ] ) ? $tokens[ $i ][2] : 0 );
	}
}

ksort( $found );
echo json_encode( array_values( $found ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ), "\n";
