<?php

namespace Tests\functional;

use ByteEver\Container\Container;
use Tests\_support\FunctionalTester;
use RuntimeException;

/**
 * Tests for service aliasing and tagging functionality.
 *
 * @package ByteEver/Container
 */
class AliasAndTagCest {
	/**
	 * Get the test plugin file path.
	 */
	private function getTestPluginFile(): string {
		return codecept_data_dir() . 'test-plugin.php';
	}

	public function testCanCreateAlias( FunctionalTester $I ) {
		// Test creating service alias
		$container = Container::create();
		$container->bind( 'original_service', TestService::class );
		$container->alias( 'original_service', 'service' );

		$original = $container->make( 'original_service' );
		$aliased  = $container->make( 'service' );

		$I->assertEquals( get_class( $original ), get_class( $aliased ) );
		$container->flush();
	}

	public function testCanCreateMultipleAliases( FunctionalTester $I ) {
		// Test creating multiple aliases for same service
		$container = Container::create();
		$container->bind( 'service', TestService::class );
		$container->alias( 'service', 'srv' );
		$container->alias( 'service', 'test_srv' );

		$service1 = $container->make( 'service' );
		$service2 = $container->make( 'srv' );
		$service3 = $container->make( 'test_srv' );

		$I->assertEquals( get_class( $service1 ), get_class( $service2 ) );
		$I->assertEquals( get_class( $service1 ), get_class( $service3 ) );
		$container->flush();
	}

	public function testThrowsExceptionForConflictingAlias( FunctionalTester $I ) {
		// Test alias conflict detection
		$container = Container::create();
		$container->bind( 'service1', TestService::class );
		$container->bind( 'service2', AlternativeService::class );

		$container->alias( 'service1', 'shared_alias' );

		$I->expectThrowable( RuntimeException::class, function () use ( $container ) {
			$container->alias( 'service2', 'shared_alias' );
		} );
		$container->flush();
	}

	public function testCanTagSingleService( FunctionalTester $I ) {
		// Test tagging single service
		$container = Container::create();
		$container->bind( 'email_handler', EmailHandler::class );
		$container->tag( 'handlers', 'email_handler' );

		$tagged = $container->tagged( 'handlers' );

		$I->assertCount( 1, $tagged );
		$I->assertInstanceOf( EmailHandler::class, $tagged[0] );
		$container->flush();
	}

	public function testCanTagMultipleServices( FunctionalTester $I ) {
		// Test tagging multiple services
		$container = Container::create();
		$container->bind( 'email_handler', EmailHandler::class );
		$container->bind( 'sms_handler', SmsHandler::class );
		$container->bind( 'push_handler', PushHandler::class );

		$container->tag( 'handlers', [
			'email_handler',
			'sms_handler',
			'push_handler'
		] );

		$tagged = $container->tagged( 'handlers' );

		$I->assertCount( 3, $tagged );
		$I->assertInstanceOf( EmailHandler::class, $tagged[0] );
		$I->assertInstanceOf( SmsHandler::class, $tagged[1] );
		$I->assertInstanceOf( PushHandler::class, $tagged[2] );
		$container->flush();
	}

	public function testCanAddServicesToExistingTag( FunctionalTester $I ) {
		// Test adding services to existing tag
		$container = Container::create();
		$container->bind( 'email_handler', EmailHandler::class );
		$container->bind( 'sms_handler', SmsHandler::class );

		$container->tag( 'handlers', 'email_handler' );
		$container->tag( 'handlers', 'sms_handler' );

		$tagged = $container->tagged( 'handlers' );

		$I->assertCount( 2, $tagged );
		$container->flush();
	}

	public function testTaggedReturnsEmptyArrayForUnknownTag( FunctionalTester $I ) {
		// Test unknown tag returns empty array
		$container = Container::create();
		$tagged    = $container->tagged( 'unknown_tag' );

		$I->assertIsArray( $tagged );
		$I->assertEmpty( $tagged );
		$container->flush();
	}

	public function testCanResolveAliasedServices( FunctionalTester $I ) {
		// Test resolving aliased singleton services
		$container = Container::create();
		$container->singleton( 'database', DatabaseService::class );
		$container->alias( 'database', 'db' );

		$database1 = $container->make( 'database' );
		$database2 = $container->make( 'db' );

		// Both should be the same instance (singleton)
		$I->assertSame( $database1, $database2 );
		$container->flush();
	}

	public function testAliasedServicesRespectBindingType( FunctionalTester $I ) {
		// Test that aliases respect binding type (regular vs singleton)
		$container = Container::create();
		$container->bind( 'service', TestService::class );
		$container->alias( 'service', 'srv' );

		$instance1 = $container->make( 'service' );
		$instance2 = $container->make( 'srv' );

		// Should be different instances (not singleton)
		$I->assertNotSame( $instance1, $instance2 );
		$I->assertEquals( get_class( $instance1 ), get_class( $instance2 ) );
		$container->flush();
	}
}

// Test helper classes
class TestService {
	public function getName(): string {
		return 'test_service';
	}
}

class AlternativeService {
	public function getName(): string {
		return 'alternative_service';
	}
}

class DatabaseService {
	public function connect(): bool {
		return true;
	}
}

class EmailHandler {
	public function handle( string $message ): bool {
		return true;
	}
}

class SmsHandler {
	public function handle( string $message ): bool {
		return true;
	}
}

class PushHandler {
	public function handle( string $message ): bool {
		return true;
	}
}
