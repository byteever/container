<?php

namespace Tests\functional;

use ByteEver\Container\Container;
use Tests\_support\FunctionalTester;

/**
 * Tests for configuration management functionality.
 *
 * @package ByteEver/Container
 */
class ConfigManagementCest {
	/**
	 * Get the test plugin file path.
	 */
	private function getTestPluginFile(): string {
		return codecept_data_dir() . 'test-plugin.php';
	}

	/**
	 * Get test config array.
	 */
	private function getTestConfig(): array {
		return [
//			'file'         => $this->getTestPluginFile(),
			'database'     => [
				'host'        => 'localhost',
				'port'        => 3306,
				'credentials' => [
					'username' => 'admin',
					'password' => 'secret'
				]
			],
			'cache'        => [
				'driver' => 'redis',
				'ttl'    => 3600
			],
			'simple_value' => 'test'
		];
	}

	public function testCanGetSimpleConfigValue( FunctionalTester $I ) {
		// Test getting simple config value
		$container = Container::create( $this->getTestConfig() );
		$I->assertEquals( 'test', $container->get_config( 'simple_value' ) );
	}

	public function testCanGetNestedConfigValue( FunctionalTester $I ) {
		// Test getting nested config values with dot notation
		$container = Container::create( $this->getTestConfig() );
		$I->assertEquals( 'localhost', $container->get_config( 'database.host' ) );
		$I->assertEquals( 3306, $container->get_config( 'database.port' ) );
		$I->assertEquals( 'admin', $container->get_config( 'database.credentials.username' ) );
	}

	public function testCanSetConfigValues( FunctionalTester $I ) {
		// Test setting config values
		$container = Container::create( $this->getTestConfig() );
		$container->set_config( 'new.config', 'new_value' );

		$I->assertEquals( 'new_value', $container->get_config( 'new.config' ) );
	}

	public function testCanSetNestedConfigValues( FunctionalTester $I ) {
		// Test setting nested config values
		$container = Container::create( $this->getTestConfig() );
		$container->set_config( 'api.v1.endpoint', 'https://api.example.com' );

		$I->assertEquals( 'https://api.example.com', $container->get_config( 'api.v1.endpoint' ) );
	}

	public function testCanCheckConfigExists( FunctionalTester $I ) {
		// Test checking if config exists
		$container = Container::create( $this->getTestConfig() );
		$I->assertTrue( $container->has_config( 'database' ) );
		$I->assertTrue( $container->has_config( 'database.host' ) );
		$I->assertTrue( $container->has_config( 'database.credentials.username' ) );
		$I->assertFalse( $container->has_config( 'non_existent' ) );
		$I->assertFalse( $container->has_config( 'database.non_existent' ) );
	}

	public function testReturnsDefaultForMissingConfig( FunctionalTester $I ) {
		// Test default values for missing config
		$container = Container::create( $this->getTestConfig() );
		$I->assertEquals( 'default', $container->get_config( 'non_existent', 'default' ) );
		$I->assertNull( $container->get_config( 'non_existent' ) );
	}

	public function testCanOverrideExistingConfig( FunctionalTester $I ) {
		// Test overriding existing config
		$container = Container::create( $this->getTestConfig() );
		$I->assertEquals( 'localhost', $container->get_config( 'database.host' ) );

		$container->set_config( 'database.host', '127.0.0.1' );

		$I->assertEquals( '127.0.0.1', $container->get_config( 'database.host' ) );
	}

	public function testCanAccessConfigViaGetMethod( FunctionalTester $I ) {
		// Test accessing config via generic get method
		$container = Container::create( $this->getTestConfig() );
		$I->assertEquals( 'localhost', $container->get( 'database.host' ) );
		$I->assertEquals( 'default', $container->get( 'non_existent', 'default' ) );
	}

	public function testCanAccessArrayConfig( FunctionalTester $I ) {
		// Test accessing array config
		$container   = Container::create( $this->getTestConfig() );
		$credentials = $container->get_config( 'database.credentials' );

		$I->assertIsArray( $credentials );
		$I->assertEquals( 'admin', $credentials['username'] );
		$I->assertEquals( 'secret', $credentials['password'] );
	}

	public function testSetConfigReturnsContainer( FunctionalTester $I ) {
		// Test that set_config returns container for chaining
		$container = Container::create( $this->getTestConfig() );
		$result    = $container->set_config( 'test', 'value' );

		$I->assertInstanceOf( Container::class, $result );
	}

	public function testCanUnsetConfigValues( FunctionalTester $I ) {
		// Test unsetting config values
		$container = Container::create( $this->getTestConfig() );
		$I->assertTrue( $container->has_config( 'simple_value' ) );

		$container->unset( 'simple_value' );

		$I->assertFalse( $container->has_config( 'simple_value' ) );
	}

	public function testCanUnsetNestedConfigValues( FunctionalTester $I ) {
		// Test unsetting nested config values
		$container = Container::create( $this->getTestConfig() );
		$I->assertTrue( $container->has_config( 'database.credentials.username' ) );

		$container->unset( 'database.credentials.username' );

		$I->assertFalse( $container->has_config( 'database.credentials.username' ) );
		$I->assertTrue( $container->has_config( 'database.credentials.password' ) );
	}
}
