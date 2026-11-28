<?php
use PHPUnit\Framework\TestCase;
use DecoStore\Core\Autoloader;

class AutoloaderTest extends TestCase {

	public function test_resolves_a_class_inside_the_prefix() {
		$this->assertSame(
			'/base/Core/Settings.php',
			Autoloader::resolve( 'DecoStore\\Core\\Settings', 'DecoStore\\', '/base/' )
		);
	}

	public function test_resolves_nested_namespaces() {
		$this->assertSame(
			'/base/Admin/Sub/Page.php',
			Autoloader::resolve( 'DecoStore\\Admin\\Sub\\Page', 'DecoStore\\', '/base/' )
		);
	}

	public function test_ignores_a_leading_backslash() {
		$this->assertSame(
			'/base/Core/Plugin.php',
			Autoloader::resolve( '\\DecoStore\\Core\\Plugin', 'DecoStore\\', '/base/' )
		);
	}

	public function test_returns_null_outside_the_prefix() {
		$this->assertNull( Autoloader::resolve( 'Other\\Thing', 'DecoStore\\', '/base/' ) );
		$this->assertNull( Autoloader::resolve( 'DecoStoreExtra\\Thing', 'DecoStore\\', '/base/' ) );
	}

	public function test_real_classes_load_through_the_registered_autoloader() {
		$this->assertTrue( class_exists( 'DecoStore\\Core\\Requirements' ) );
		$this->assertTrue( class_exists( 'DecoStore\\Core\\Settings' ) );
	}
}
