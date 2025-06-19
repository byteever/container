# ByteEver Container

A powerful dependency injection container specifically designed for WordPress plugin development. This container provides advanced features like auto-wiring, service binding, configuration management, and service tagging to help you build maintainable and testable WordPress plugins.

[![PHP Version](https://img.shields.io/badge/php-%5E7.4-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

## Features

- **🚀 Dependency Injection**: Automatic dependency resolution and injection
- **📦 Service Binding**: Bind interfaces to implementations
- **🔧 Configuration Management**: Built-in configuration handling with dot notation
- **🏷️ Service Tagging**: Group and retrieve related services
- **💡 Auto-wiring**: Automatic class instantiation and dependency resolution
- **🎯 Singleton Support**: Register shared instances across your application
- **🔄 Alias System**: Create shortcuts for your services
- **🎪 WordPress Integration**: Designed specifically for WordPress plugin architecture

## Installation

Install via Composer:

```bash
composer require byteever/container
```

Or add to your `composer.json`:

```json
{
    "require": {
        "byteever/container": "^1.0"
    }
}
```

## Quick Start

### Basic Usage

```php
use ByteEver\Container\Container;

// Create a container instance
$container = Container::create();

// Bind a service
$container->bind('logger', MyLogger::class);

// Resolve the service
$logger = $container->make('logger');

// Register an instance
$container->instance('database', new Database($config));

// Get the instance
$db = $container->get('database');
```

### WordPress Plugin Integration

**Important**: For WordPress plugin development, you must pass the main plugin file path to extract plugin metadata.

```php
// In your main plugin file
use ByteEver\Container\Container;

class MyPlugin {
    private Container $container;
    
    public function __construct() {
        // REQUIRED: Pass the main plugin file (__FILE__)
        $this->container = Container::create(__FILE__);
        
        $this->registerServices();
    }
    
    private function registerServices(): void {
        // Register your plugin services
        $this->container->singleton('admin', Admin::class);
        $this->container->singleton('frontend', Frontend::class);
        $this->container->bind('api', API::class);
    }
    
    public function getContainer(): Container {
        return $this->container;
    }
}
```

## Configuration Management

The container includes a powerful configuration system with dot notation support:

```php
$container = Container::create([
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'credentials' => [
            'username' => 'admin',
            'password' => 'secret'
        ]
    ],
    'cache' => [
        'driver' => 'redis',
        'ttl' => 3600
    ]
]);

// Get configuration values
$host = $container->get_config('database.host'); // 'localhost'
$username = $container->get_config('database.credentials.username'); // 'admin'
$ttl = $container->get_config('cache.ttl', 1800); // 3600 (with default fallback)

// Set configuration values
$container->set_config('api.key', 'your-api-key');
$container->set_config('features.beta', true);

// Check if configuration exists
if ($container->has_config('database.credentials')) {
    // Configuration exists
}
```

## Service Binding

### Basic Binding

```php
// Bind a concrete class
$container->bind('logger', FileLogger::class);

// Bind with a closure
$container->bind('mailer', function($container) {
    return new Mailer($container->get_config('smtp'));
});

// Bind interface to implementation
$container->bind(LoggerInterface::class, FileLogger::class);
```

### Singleton Binding

```php
// Register as singleton
$container->singleton('database', Database::class);

// Both calls return the same instance
$db1 = $container->make('database');
$db2 = $container->make('database');
// $db1 === $db2 (true)
```

### Instance Registration

```php
// Register existing instance
$logger = new Logger('/path/to/log');
$container->instance('logger', $logger);

// Auto-instantiation (new feature!)
$container->instance(Database::class); // Automatically creates and registers instance

// Bulk registration
$container->instance([
    'cache' => new RedisCache(),
    'session' => new SessionManager()
]);
```

## Auto-wiring

The container automatically resolves dependencies through constructor injection:

```php
class UserService {
    public function __construct(
        private Database $database,
        private Logger $logger,
        private CacheInterface $cache
    ) {}
}

class Database {
    public function __construct(private string $host = 'localhost') {}
}

// Register dependencies
$container->bind(CacheInterface::class, RedisCache::class);
$container->instance('logger', new Logger());

// Auto-wiring in action - resolves all dependencies automatically
$userService = $container->make(UserService::class);
```

## Service Tagging

Group related services with tags:

```php
// Tag services
$container->tag('handlers', [
    'user_handler',
    'post_handler', 
    'comment_handler'
]);

// Or tag individually
$container->bind('user_handler', UserHandler::class);
$container->tag('handlers', 'user_handler');

// Get all services with a tag
$handlers = $container->tagged('handlers');
foreach ($handlers as $handler) {
    $handler->process();
}
```

## Aliases

Create convenient shortcuts for your services:

```php
$container->bind(DatabaseInterface::class, MySQLDatabase::class);
$container->alias(DatabaseInterface::class, 'db');

// Now you can use either
$database = $container->make(DatabaseInterface::class);
$database = $container->make('db'); // Same instance
```

## WordPress-Specific Features

### Plugin File Integration

```php
// The container can extract plugin metadata
$container = Container::create(__FILE__); // Pass your main plugin file

// Access plugin information
$pluginName = $container->get_config('name');
$version = $container->get_config('version');
$textDomain = $container->get_config('text_domain');
```

### Hook Integration

```php
class MyPlugin {
    public function __construct() {
        $this->container = Container::create(__FILE__);
        $this->registerHooks();
    }
    
    private function registerHooks(): void {
        add_action('init', [$this, 'init']);
        add_action('admin_init', [$this, 'adminInit']);
    }
    
    public function init(): void {
        $frontend = $this->container->make('frontend');
        $frontend->init();
    }
    
    public function adminInit(): void {
        $admin = $this->container->make('admin');
        $admin->init();
    }
}
```

## Advanced Usage

### Custom Service Providers

```php
class DatabaseServiceProvider {
    public function register(Container $container): void {
        $container->singleton('database', function($container) {
            return new Database(
                $container->get_config('database.host'),
                $container->get_config('database.port'),
                $container->get_config('database.credentials')
            );
        });
        
        $container->alias('database', 'db');
    }
}

// Register the service provider
$provider = new DatabaseServiceProvider();
$provider->register($container);
```

### Conditional Binding

```php
// Bind different implementations based on environment
if ($container->get_config('app.debug')) {
    $container->bind(LoggerInterface::class, DebugLogger::class);
} else {
    $container->bind(LoggerInterface::class, ProductionLogger::class);
}
```

### Method Injection

```php
// You can pass additional parameters when resolving
$userService = $container->make(UserService::class, [
    'connection' => 'mysql-read-replica'
]);
```

## API Reference

### Container Methods

#### Creation
- `Container::create(array|string $config = [], string $namespace = __NAMESPACE__): static`

#### Service Resolution
- `make(string $abstract, array $parameters = []): mixed`
- `get(string $key, mixed $default = null): mixed`

#### Service Binding
- `bind(string $abstract, Closure|string|null $concrete = null, bool $shared = false): self`
- `singleton(string $abstract, Closure|string|null $concrete = null): self`
- `instance(string|array $abstract, object $instance = null): self`

#### Configuration
- `get_config(string $key, mixed $default = null): mixed`
- `set_config(string $key, mixed $value): self`
- `has_config(string $key): bool`

#### Service Management
- `bound(string $abstract): bool`
- `alias(string $abstract, string $alias): void`
- `tag(string $tag, array|string $services): void`
- `tagged(string $tag): array`
- `flush(): void`

## Best Practices

### 1. Use Interfaces

```php
// Good
$container->bind(LoggerInterface::class, FileLogger::class);

// Instead of
$container->bind('logger', FileLogger::class);
```

### 2. Register Services Early

```php
class MyPlugin {
    public function __construct() {
        $this->container = Container::create(__FILE__);
        $this->registerServices(); // Do this early
        $this->registerHooks();
    }
}
```

### 3. Use Service Providers for Complex Setup

```php
class PluginServiceProvider {
    public function register(Container $container): void {
        // Group related service registrations
        $this->registerDatabase($container);
        $this->registerCache($container);
        $this->registerLogging($container);
    }
}
```

### 4. Leverage Auto-wiring

```php
// Let the container handle dependencies
class AdminController {
    public function __construct(
        private UserRepository $users,
        private Logger $logger,
        private Validator $validator
    ) {}
}

// Just resolve - dependencies are auto-injected
$controller = $container->make(AdminController::class);
```

## Testing

The container makes testing easier by allowing dependency injection:

```php
class UserServiceTest extends TestCase {
    public function testUserCreation(): void {
        $container = Container::create();
        
        // Mock dependencies
        $mockDatabase = $this->createMock(Database::class);
        $mockLogger = $this->createMock(Logger::class);
        
        $container->instance('database', $mockDatabase);
        $container->instance('logger', $mockLogger);
        
        $userService = $container->make(UserService::class);
        
        // Test your service with mocked dependencies
        $this->assertInstanceOf(UserService::class, $userService);
    }
}
```

## Common Patterns

### Plugin Architecture

```php
// Main plugin class
class MyAwesomePlugin {
    private Container $container;
    
    public function __construct() {
        $this->container = Container::create(__FILE__);
        $this->bootstrap();
    }
    
    private function bootstrap(): void {
        // Register core services
        $this->registerCore();
        
        // Register WordPress hooks
        $this->registerHooks();
        
        // Initialize modules
        $this->initializeModules();
    }
    
    private function registerCore(): void {
        $this->container->singleton('plugin', $this);
        $this->container->singleton('loader', HookLoader::class);
        $this->container->singleton('admin', AdminModule::class);
        $this->container->singleton('frontend', FrontendModule::class);
        $this->container->singleton('api', APIModule::class);
    }
    
    private function registerHooks(): void {
        $loader = $this->container->make('loader');
        $loader->run();
    }
    
    private function initializeModules(): void {
        if (is_admin()) {
            $this->container->make('admin')->init();
        } else {
            $this->container->make('frontend')->init();
        }
    }
}
```

## Requirements

- PHP 7.4 or higher
- WordPress 5.0 or higher (for WordPress-specific features)

## Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

- **Documentation**: [Full Documentation](https://docs.byteever.com/container)
- **Issues**: [GitHub Issues](https://github.com/byteever/container/issues)
- **Discussions**: [GitHub Discussions](https://github.com/byteever/container/discussions)

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of changes.

---

**ByteEver Container** - Powering modern WordPress plugin development with clean, maintainable dependency injection.