<?php
use PHPUnit\Framework\TestCase;
use TopSolution\Delivery\CsvImporter;

class CsvImporterTest extends TestCase {

	public function test_geography_with_bom_and_semicolons() {
		$csv = "\xEF\xBB\xBFwilaya_code;wilaya_name_ar;commune_code;commune_name_ar\n01;ولاية أ;0101;بلدية أ\n1;ولاية أ;0102;بلدية ب\n";
		$out = CsvImporter::parse( 'geography', $csv );
		$this->assertSame( '', $out['header_error'] );
		$this->assertSame( array(), $out['errors'] );
		$this->assertCount( 2, $out['rows'] );
		$this->assertSame( '1', $out['rows'][0]['wilaya_code'] );
		$this->assertSame( '101', $out['rows'][0]['commune_code'] );
		$this->assertSame( 'بلدية ب', $out['rows'][1]['commune_name_ar'] );
	}

	public function test_geography_wilaya_only_rows() {
		$out = CsvImporter::parse( 'geography', "wilaya_code,wilaya_name_ar\n2,ولاية ب\n" );
		$this->assertCount( 1, $out['rows'] );
		$this->assertSame( '', $out['rows'][0]['commune_code'] );
	}

	public function test_missing_required_column() {
		$out = CsvImporter::parse( 'geography', "wilaya_code,name\n1,x\n" );
		$this->assertSame( 'missing:wilaya_name_ar', $out['header_error'] );
	}

	public function test_bad_rows_are_reported_with_line_numbers_and_good_rows_kept() {
		$csv = "wilaya_code,wilaya_name_ar,commune_code,commune_name_ar\nabc,x,,\n2,,,\n3,ولاية,301,\n4,ولاية د,,\n";
		$out = CsvImporter::parse( 'geography', $csv );
		$this->assertSame( 'bad_wilaya_code', $out['errors'][2] );
		$this->assertSame( 'missing_wilaya_name', $out['errors'][3] );
		$this->assertSame( 'commune_code_and_name_go_together', $out['errors'][4] );
		$this->assertCount( 1, $out['rows'] );
		$this->assertSame( 5, $out['rows'][0]['line'] );
	}

	public function test_duplicates_are_rejected() {
		$out = CsvImporter::parse( 'geography', "wilaya_code,wilaya_name_ar\n1,أ\n01,أ\n" );
		$this->assertCount( 1, $out['rows'] );
		$this->assertSame( 'duplicate_of_line:2', $out['errors'][3] );
	}

	public function test_html_is_stripped_from_names() {
		$out = CsvImporter::parse( 'geography', "wilaya_code,wilaya_name_ar\n1,<b>ولاية</b><script>x</script>\n" );
		$this->assertSame( 'ولايةx', $out['rows'][0]['wilaya_name_ar'] );
	}

	public function test_rates() {
		$csv = "method,wilaya_code,commune_code,fee,active\nhome,1,,400,1\noffice,1,,\"1 200\",\nhome,1,101,0,0\nboat,1,,5,\nhome,2,,-3,\nhome,2,,abc,\n";
		$out = CsvImporter::parse( 'rates', $csv, 0 );
		$this->assertCount( 3, $out['rows'] );
		$this->assertSame( 400, $out['rows'][0]['fee_minor'] );
		$this->assertSame( 1200, $out['rows'][1]['fee_minor'] );
		$this->assertTrue( $out['rows'][1]['active'], 'Empty "active" means active.' );
		$this->assertFalse( $out['rows'][2]['active'] );
		$this->assertSame( 'bad_method', $out['errors'][5] );
		$this->assertSame( 'bad_fee', $out['errors'][6] );
		$this->assertSame( 'bad_fee', $out['errors'][7] );
	}

	public function test_rates_respect_store_decimals() {
		$this->assertSame( 40050, CsvImporter::parse( 'rates', "method,wilaya_code,fee\nhome,1,400.50\n", 2 )['rows'][0]['fee_minor'] );
		$this->assertSame( 'bad_fee', CsvImporter::parse( 'rates', "method,wilaya_code,fee\nhome,1,400.50\n", 0 )['errors'][2] );
	}

	public function test_pickups() {
		$csv = "wilaya_code,commune_code,carrier,name,address,hours\n1,101,شركة,مكتب تجريبي,عنوان,9-17\n1,,,,عنوان,\n";
		$out = CsvImporter::parse( 'pickups', $csv );
		$this->assertCount( 1, $out['rows'] );
		$this->assertSame( 'مكتب تجريبي', $out['rows'][0]['name'] );
		$this->assertSame( 'missing_name', $out['errors'][3] );
	}

	public function test_bad_active_flag() {
		$out = CsvImporter::parse( 'rates', "method,wilaya_code,fee,active\nhome,1,4,maybe\n" );
		$this->assertSame( 'bad_active', $out['errors'][2] );
	}

	public function test_empty_unknown_and_oversized_input() {
		$this->assertSame( 'empty', CsvImporter::parse( 'rates', "  \n" )['header_error'] );
		$this->assertSame( 'unknown_type', CsvImporter::parse( 'nope', "a\n" )['header_error'] );
		$this->assertSame( 'too_large', CsvImporter::parse( 'rates', str_repeat( 'x', CsvImporter::MAX_BYTES + 1 ) )['header_error'] );
		$this->assertSame( 'not_utf8', CsvImporter::parse( 'rates', "method\xff,wilaya_code,fee\n" )['header_error'] );
	}

	public function test_crlf_line_endings() {
		$out = CsvImporter::parse( 'geography', "wilaya_code,wilaya_name_ar\r\n1,أ\r\n2,ب\r\n" );
		$this->assertCount( 2, $out['rows'] );
	}
}
