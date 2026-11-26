<?php
/**
 * Builds languages/tassili-immobilier.pot, fr_FR.po/.mo and ar.po/.mo.
 *
 *   php tools/build-translations.php
 *
 * Fails (exit 1) when a string in the theme has no translation, so a missing string is
 * caught before release instead of showing English to a visitor. No gettext tools needed.
 */

$root   = dirname( __DIR__ ) . '/tassili-immobilier';
$out    = $root . '/languages';
$tr     = require __DIR__ . '/translations.php';
$strings = json_decode( (string) shell_exec( 'php ' . escapeshellarg( __DIR__ . '/extract-strings.php' ) ), true );
if ( ! is_array( $strings ) ) {
	fwrite( STDERR, "Could not extract strings.\n" );
	exit( 1 );
}

$langs = array(
	'fr_FR' => array(
		'index'  => 0,
		'name'   => 'French',
		'plural' => 'nplurals=2; plural=(n > 1);',
		'code'   => 'fr',
	),
	'ar'    => array(
		'index'  => 1,
		'name'   => 'Arabic',
		'plural' => 'nplurals=6; plural=(n==0 ? 0 : n==1 ? 1 : n==2 ? 2 : n%100>=3 && n%100<=10 ? 3 : n%100>=11 ? 4 : 5);',
		'code'   => 'ar',
	),
);

$missing = array();
$used    = array();

/** Escape a string for a .po file. */
function po_quote( $s ) {
	$s = str_replace( array( '\\', '"', "\n", "\t" ), array( '\\\\', '\\"', '\\n', '\\t' ), $s );
	return '"' . $s . '"';
}

/** Header entry. */
function po_header( $lang, $meta ) {
	return array(
		'Project-Id-Version: Tassili Immobilier 1.0.0',
		'Report-Msgid-Bugs-To: ',
		'POT-Creation-Date: ' . gmdate( 'Y-m-d H:i+0000' ),
		'PO-Revision-Date: ' . gmdate( 'Y-m-d H:i+0000' ),
		'Language-Team: ' . $meta['name'],
		'Language: ' . $lang,
		'MIME-Version: 1.0',
		'Content-Type: text/plain; charset=UTF-8',
		'Content-Transfer-Encoding: 8bit',
		'Plural-Forms: ' . $meta['plural'],
		'X-Domain: tassili-immobilier',
	);
}

// POT (no translations).
$pot = "# Tassili Immobilier translation template.\n# Copyright (C) Tassili Immobilier. Licensed under the GPL v2 or later.\nmsgid \"\"\nmsgstr \"\"\n";
foreach ( po_header( 'en', array( 'name' => 'English', 'plural' => 'nplurals=2; plural=(n != 1);' ) ) as $line ) {
	$pot .= po_quote( $line . "\n" ) . "\n";
}
$pot .= "\n";

$entries = array(); // for mo: key => [ lang => msgstr ]
foreach ( $strings as $s ) {
	$ctx   = $s['context'];
	$key   = ( null !== $ctx ? $ctx . "\x04" : '' ) . $s['msgid'];
	$used[ $key ] = true;
	$entry = array(
		'msgid'  => $s['msgid'],
		'plural' => $s['plural'],
		'ctx'    => $ctx,
		'refs'   => $s['refs'],
	);

	foreach ( $s['refs'] as $ref ) {
		$pot .= '#: ' . $ref . "\n";
	}
	if ( null !== $ctx ) {
		$pot .= 'msgctxt ' . po_quote( $ctx ) . "\n";
	}
	$pot .= 'msgid ' . po_quote( $s['msgid'] ) . "\n";
	if ( null !== $s['plural'] ) {
		$pot .= 'msgid_plural ' . po_quote( $s['plural'] ) . "\nmsgstr[0] \"\"\nmsgstr[1] \"\"\n\n";
	} else {
		$pot .= "msgstr \"\"\n\n";
	}

	foreach ( $langs as $code => $meta ) {
		$i = $meta['index'];
		if ( null !== $s['plural'] ) {
			$forms = isset( $tr['plurals'][ $s['msgid'] ][ $meta['code'] ] ) ? $tr['plurals'][ $s['msgid'] ][ $meta['code'] ] : null;
			$need  = 'ar' === $code ? 6 : 2;
			if ( ! $forms || count( $forms ) !== $need ) {
				$missing[] = "$code plural: " . $s['msgid'];
				continue;
			}
			$entries[ $key ][ $code ] = array( 'plural' => $s['plural'], 'forms' => $forms );
		} elseif ( null !== $ctx ) {
			if ( ! isset( $tr['contexts'][ $key ] ) ) {
				$missing[] = "$code ctx: " . $s['msgid'];
				continue;
			}
			$entries[ $key ][ $code ] = array( 'plural' => null, 'forms' => array( $tr['contexts'][ $key ][ $i ] ) );
		} else {
			if ( ! isset( $tr['strings'][ $s['msgid'] ] ) || '' === $tr['strings'][ $s['msgid'] ][ $i ] ) {
				$missing[] = "$code: " . $s['msgid'];
				continue;
			}
			$entries[ $key ][ $code ] = array( 'plural' => null, 'forms' => array( $tr['strings'][ $s['msgid'] ][ $i ] ) );
		}
		// Placeholders must match the source.
		$src_ph = preg_match_all( '/%(\d+\$)?[sd]/', $s['msgid'] . ( $s['plural'] ?? '' ), $m1 );
		foreach ( $entries[ $key ][ $code ]['forms'] as $form ) {
			if ( null === $s['plural'] && preg_match_all( '/%(\d+\$)?[sd]/', $form ) !== preg_match_all( '/%(\d+\$)?[sd]/', $s['msgid'] ) ) {
				$missing[] = "$code placeholder mismatch: " . $s['msgid'];
			}
		}
	}
}

// Translations that no longer match any string.
$unused = array();
foreach ( array_keys( $tr['strings'] ) as $k ) {
	if ( ! isset( $used[ $k ] ) ) {
		$unused[] = $k;
	}
}
foreach ( array_keys( $tr['plurals'] ) as $k ) {
	$found = false;
	foreach ( $strings as $s ) {
		if ( $s['msgid'] === $k && null !== $s['plural'] ) {
			$found = true;
		}
	}
	if ( ! $found ) {
		$unused[] = 'plural ' . $k;
	}
}

file_put_contents( $out . '/tassili-immobilier.pot', $pot );

/** Write a GNU .mo file. */
function write_mo( $path, $messages ) {
	ksort( $messages, SORT_STRING );
	$ids = '';
	$strs = '';
	$offsets = array();
	foreach ( $messages as $id => $str ) {
		$offsets[] = array( strlen( $ids ), strlen( $id ), strlen( $strs ), strlen( $str ) );
		$ids  .= $id . "\0";
		$strs .= $str . "\0";
	}
	$n        = count( $messages );
	$keystart = 7 * 4 + 16 * $n;
	$valstart = $keystart + strlen( $ids );
	$table1   = '';
	$table2   = '';
	foreach ( $offsets as $o ) {
		$table1 .= pack( 'VV', $o[1], $keystart + $o[0] );
		$table2 .= pack( 'VV', $o[3], $valstart + $o[2] );
	}
	// Hash table: size 0, placed right after the two tables (readers rely on that offset).
	$mo = pack( 'Iiiiiii', 0x950412de, 0, $n, 7 * 4, 7 * 4 + 8 * $n, 0, 7 * 4 + 16 * $n ) . $table1 . $table2 . $ids . $strs;
	file_put_contents( $path, $mo );
}

foreach ( $langs as $code => $meta ) {
	$po  = "# Tassili Immobilier, {$meta['name']} translation.\nmsgid \"\"\nmsgstr \"\"\n";
	$msg = array();
	$hdr = '';
	foreach ( po_header( $code, $meta ) as $line ) {
		$po  .= po_quote( $line . "\n" ) . "\n";
		$hdr .= $line . "\n";
	}
	$po .= "\n";
	$msg[''] = $hdr;

	foreach ( $strings as $s ) {
		$key = ( null !== $s['context'] ? $s['context'] . "\x04" : '' ) . $s['msgid'];
		if ( ! isset( $entries[ $key ][ $code ] ) ) {
			continue;
		}
		$e = $entries[ $key ][ $code ];
		foreach ( $s['refs'] as $ref ) {
			$po .= '#: ' . $ref . "\n";
		}
		if ( null !== $s['context'] ) {
			$po .= 'msgctxt ' . po_quote( $s['context'] ) . "\n";
		}
		$po .= 'msgid ' . po_quote( $s['msgid'] ) . "\n";
		if ( null !== $s['plural'] ) {
			$po .= 'msgid_plural ' . po_quote( $s['plural'] ) . "\n";
			foreach ( $e['forms'] as $n => $form ) {
				$po .= "msgstr[$n] " . po_quote( $form ) . "\n";
			}
			$msg[ $key . "\0" . $s['plural'] ] = implode( "\0", $e['forms'] );
		} else {
			$po .= 'msgstr ' . po_quote( $e['forms'][0] ) . "\n";
			$msg[ $key ] = $e['forms'][0];
		}
		$po .= "\n";
	}
	file_put_contents( $out . '/' . $code . '.po', $po );
	write_mo( $out . '/' . $code . '.mo', $msg );
	echo sprintf( "%s: %d messages\n", $code, count( $msg ) - 1 );
}

echo count( $strings ) . " strings in the theme.\n";
if ( $unused ) {
	echo "Unused translations (can be removed):\n  - " . implode( "\n  - ", $unused ) . "\n";
}
if ( $missing ) {
	echo "MISSING:\n  - " . implode( "\n  - ", $missing ) . "\n";
	exit( 1 );
}
echo "All strings translated.\n";
