<?php

namespace Tests\functional;

use ByteEver\Container\Container;
use Tests\_support\FunctionalTester;

/**
 * Tests for container creation and initialization.
 *
 * @package ByteEver/Container
 */
class ContainerCreationCest {
	/**
	 * Get the test plugin file path.
	 */
	private function getTestPluginFile(): string {
		return codecept_data_dir() . 'test-plugin.php';
	}

	public function testCanCreateContainerWithPluginFile( FunctionalTester $I ) {
		// Test creating container with plugin file
		$pluginFile = $this->getTestPluginFile();
		$container  = Container::create( $pluginFile );

		$I->assertInstanceOf( Container::class, $container );
		$I->assertEquals( 'Test Plugin', $container->get_config( 'name' ) );
		$I->assertEquals( '1.0.0', $container->get_config( 'version' ) );
		$I->assertEquals( 'test-plugin', $container->get_config( 'text_domain' ) );
	}

	public function testCanCreateContainerWithConfigArray( FunctionalTester $I ) {
		// Test creating container with config array
		$config    = [
			'file' => $this->getTestPluginFile(),
			'test' => 'value'
		];
		$container = Container::create( $config );

		$I->assertEquals( 'value', $container->get_config( 'test' ) );
		$I->assertEquals( 'Test Plugin', $container->get_config( 'name' ) );
	}

	public function testContainerExtractsPluginMetadata( FunctionalTester $I ) {
		// Test that container properly extracts plugin metadata
		$container = Container::create( $this->getTestPluginFile() );

		$I->assertEquals( 'Test Plugin', $container->get_config( 'name' ) );
		$I->assertEquals( '1.0.0', $container->get_config( 'version' ) );
		$I->assertEquals( 'A test plugin for container testing', $container->get_config( 'description' ) );
		$I->assertEquals( 'Test Author', $container->get_config( 'author' ) );
		$I->assertEquals( 'test-plugin', $container->get_config( 'text_domain' ) );
		$I->assertEquals( '/languages', $container->get_config( 'domain_path' ) );

		// Should also have computed values
		$I->assertNotEmpty( $container->get_config( 'plugin_file' ) );
		$I->assertNotEmpty( $container->get_config( 'plugin_dir' ) );
		$I->assertNotEmpty( $container->get_config( 'plugin_url' ) );
		$I->assertNotEmpty( $container->get_config( 'plugin_basename' ) );
	}

	public function testContainerWorksWithoutWordPress( FunctionalTester $I ) {
		// Test creating container without WordPress functions (pure config)
		$config    = [ 'test' => 'value', 'no_file' => true ];
		$container = Container::create( $config );

		$I->assertInstanceOf( Container::class, $container );
		$I->assertEquals( 'value', $container->get_config( 'test' ) );
		$I->assertFalse( $container->has_config( 'name' ) ); // No plugin metadata
	}
}
