<?php

namespace Tests\functional;

use ByteEver\Container\Container;
use RuntimeException;
use Tests\_support\FunctionalTester;

/**
 * Tests for service binding and resolution.
 *
 * @package ByteEver/Container
 */
class ServiceBindingCest
{
    private Container $container;

    public function _before(FunctionalTester $I)
    {
        $this->container = Container::create($I->getTestPluginFile());
    }

    public function _after(FunctionalTester $I)
    {
        $this->container->flush();
    }

    public function testCanBindClassString(FunctionalTester $I)
    {
        // Test binding a class string
        $this->container->bind('logger', TestLogger::class);

        $instance = $this->container->make('logger');

        $I->assertInstanceOf(TestLogger::class, $instance);
    }

    public function testCanBindInterfaceToImplementation(FunctionalTester $I)
    {
        // Test binding interface to implementation
        $this->container->bind(TestLoggerInterface::class, TestLogger::class);

        $instance = $this->container->make(TestLoggerInterface::class);

        $I->assertInstanceOf(TestLogger::class, $instance);
    }

    public function testCanBindClosure(FunctionalTester $I)
    {
        // Test binding a closure
        $this->container->bind('custom_logger', function($container) {
            return new TestLogger('custom_log');
        });

        $instance = $this->container->make('custom_logger');

        $I->assertInstanceOf(TestLogger::class, $instance);
        $I->assertEquals('custom_log', $instance->getName());
    }

    public function testSingletonReturnsSameInstance(FunctionalTester $I)
    {
        // Test singleton binding
        $this->container->singleton('logger', TestLogger::class);

        $instance1 = $this->container->make('logger');
        $instance2 = $this->container->make('logger');

        $I->assertSame($instance1, $instance2);
    }

    public function testRegularBindingReturnsNewInstances(FunctionalTester $I)
    {
        // Test regular binding returns different instances
        $this->container->bind('logger', TestLogger::class);

        $instance1 = $this->container->make('logger');
        $instance2 = $this->container->make('logger');

        $I->assertNotSame($instance1, $instance2);
        $I->assertEquals(get_class($instance1), get_class($instance2));
    }

    public function testCanRegisterExistingInstance(FunctionalTester $I)
    {
        // Test registering an existing instance
        $logger = new TestLogger('test_logger');
        $this->container->instance('logger', $logger);

        $resolved = $this->container->make('logger');

        $I->assertSame($logger, $resolved);
    }

    public function testCanAutoInstantiateWithInstanceMethod(FunctionalTester $I)
    {
        // Test auto-instantiation
        $this->container->instance(TestLogger::class);

        $I->assertTrue($this->container->bound(TestLogger::class));

        $instance = $this->container->make(TestLogger::class);
        $I->assertInstanceOf(TestLogger::class, $instance);
    }

    public function testCanResolveDependenciesAutomatically(FunctionalTester $I)
    {
        // Test automatic dependency resolution
        $this->container->bind(TestLoggerInterface::class, TestLogger::class);

        $service = $this->container->make(TestServiceWithDependency::class);

        $I->assertInstanceOf(TestServiceWithDependency::class, $service);
        $I->assertInstanceOf(TestLogger::class, $service->getLogger());
    }

    public function testThrowsExceptionForCircularDependency(FunctionalTester $I)
    {
        // Test circular dependency detection
        $this->container->bind('service_a', TestCircularServiceA::class);
        $this->container->bind('service_b', TestCircularServiceB::class);

        $I->expectThrowable(RuntimeException::class, function() {
            $this->container->make('service_a');
        });
    }

    public function testCanCheckIfServiceIsBound(FunctionalTester $I)
    {
        // Test checking if service is bound
        $I->assertFalse($this->container->bound('logger'));

        $this->container->bind('logger', TestLogger::class);

        $I->assertTrue($this->container->bound('logger'));
    }

    public function testInstanceMethodReturnsContainer(FunctionalTester $I)
    {
        // Test that instance method returns container for chaining
        $result = $this->container->instance(TestLogger::class);

        $I->assertInstanceOf(Container::class, $result);
    }
}

// Test helper classes
interface TestLoggerInterface
{
    public function log(string $message): void;
}

class TestLogger implements TestLoggerInterface
{
    private string $name;

    public function __construct(string $name = 'default')
    {
        $this->name = $name;
    }

    public function log(string $message): void
    {
        // Mock implementation
    }

    public function getName(): string
    {
        return $this->name;
    }
}

class TestServiceWithDependency
{
    private TestLoggerInterface $logger;

    public function __construct(TestLoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getLogger(): TestLoggerInterface
    {
        return $this->logger;
    }
}

class TestCircularServiceA
{
    public function __construct(TestCircularServiceB $serviceB)
    {
        // Circular dependency
    }
}

class TestCircularServiceB
{
    public function __construct(TestCircularServiceA $serviceA)
    {
        // Circular dependency
    }
}
