<?php
use PHPUnit\Framework\TestCase;
use DecoStore\Core\Text;

class TextTest extends TestCase {

	public function test_line_removes_tags_controls_and_collapses_spaces() {
		$this->assertSame( 'a b c', Text::line( "  <b>a</b>\n\t b   c  " ) );
		$this->assertSame( 'x', Text::line( "x\x00\x1F" ) );
	}

	public function test_line_truncates_by_characters_not_bytes() {
		$this->assertSame( 'اااا', Text::line( 'ااااااا', 4 ) );
	}

	public function test_non_scalars_become_empty() {
		$this->assertSame( '', Text::line( array( 'x' ) ) );
		$this->assertSame( '', Text::block( null ) );
	}

	public function test_block_keeps_new_lines_but_limits_blank_runs() {
		$this->assertSame( "a\nb\n\nc", Text::block( "a\r\nb\n\n\n\n\nc" ) );
		$this->assertSame( 'ab', Text::block( '<i>ab</i>' ) );
	}

	public function test_western_digits() {
		$this->assertSame( '0123456789', Text::western_digits( '٠١٢٣٤٥٦٧٨٩' ) );
		$this->assertSame( '0123456789', Text::western_digits( '۰۱۲۳۴۵۶۷۸۹' ) );
		$this->assertSame( 'a1', Text::western_digits( 'a1' ) );
	}

	public function test_length_counts_characters() {
		$this->assertSame( 3, Text::length( 'ابج' ) );
	}
}
