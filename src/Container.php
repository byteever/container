<?php

declare( strict_types=1 );

namespace ByteEver\Container;

use Closure;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

/**
 * Container class for dependency injection.
 *
 * A powerful dependency injection container for PHP applications with advanced features
 * like auto-wiring, service binding, configuration management, and service tagging.
 *
 * @since   1.0.0
 * @version 1.0.0
 * @author  Sultan Nasir Uddin <manikdrmc@gmail.com>
 * @package ByteEver/Container
 * @license MIT
 */
class Container implements ContainerInterface {
	/**
	 * The container's configuration.
	 *
	 * @var array
	 */
	protected array $config = array();

	/**
	 * The container's context.
	 *
	 * @var string
	 */
	protected string $context = __NAMESPACE__;

	/**
	 * An array of the types that have been resolved.
	 *
	 * @var array
	 */
	protected array $resolved = array();

	/**
	 * Registry of service bindings
	 *
	 * @var array
	 */
	protected array $bindings = array();

	/**
	 * Cache for shared instances
	 *
	 * @var array
	 */
	protected array $instances = array();

	/**
	 * Registry of aliases
	 *
	 * @var array
	 */
	protected array $aliases = array();

	/**
	 * Tagged services
	 *
	 * @var array
	 */
	protected array $tags = array();

	/**
	 * Create a new container instance.
	 *
	 * @param array|string $config The configuration array or plugin file path.
	 * @param string       $context The namespace context to use for auto-resolution.
	 *
	 * @return static
	 */
	public static function create( array|string $config = array(), string $context = __NAMESPACE__ ): static {
		$instance = new static();

		$config = is_scalar( $config ) ? array( 'file' => $config ) : $config;

		// WordPress-specific functionality.
		if ( ! empty( $config['file'] ) && is_string( $config['file'] ) && file_exists( $config['file'] ) ) {
			if ( ! function_exists( 'get_file_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$file        = $config['file'];
			$plugin_data = wp_cache_get( $file, 'plugin_data' );

			if ( false === $plugin_data ) {
				$headers = array(
					'name'             => 'Plugin Name',
					'plugin_uri'       => 'Plugin URI',
					'version'          => 'Version',
					'description'      => 'Description',
					'author'           => 'Author',
					'author_uri'       => 'Author URI',
					'text_domain'      => 'Text Domain',
					'domain_path'      => 'Domain Path',
					'network'          => 'Network',
					'requires_wp'      => 'Requires at least',
					'requires_php'     => 'Requires PHP',
					'requires_plugins' => 'Requires Plugins',
					'support_url'      => 'Support URL',
					'docs_url'         => 'Docs URL',
					'api_url'          => 'API URL',
					'review_url'       => 'Review URL',
					'settings_url'     => 'Settings URL',
					'item_id'          => 'Item ID',
				);

				$plugin_data                    = array_change_key_case( get_file_data( $file, $headers, 'plugin' ) );
				$plugin_data['prefix']          = empty( $plugin_data['prefix'] ) ? str_replace( '-', '_', dirname( plugin_basename( $file ) ) ) : $plugin_data['prefix'];
				$plugin_data['version']         = empty( $plugin_data['version'] ) ? '1.0.0' : $plugin_data['version'];
				$plugin_data['plugin_file']     = $file;
				$plugin_data['plugin_dir']      = plugin_dir_path( $file );
				$plugin_data['plugin_url']      = plugin_dir_url( $file );
				$plugin_data['plugin_basename'] = plugin_basename( $file );

				// Cache the plugin data.
				wp_cache_set( $file, $plugin_data, 'plugin_data' );
			}
			$config = array_merge( $plugin_data, $config );
		}

		$instance->config  = $config;
		$instance->context = $context;

		return $instance;
	}

	/**
	 * Private constructor to prevent direct instantiation.
	 */
	private function __construct() {
		// Empty constructor - initialization happens in create() method.
	}

	/**
	 * Get config or service from the container.
	 *
	 * @param string $key The key to get.
	 * @param mixed  $fallback Default value if not found.
	 *
	 * @return mixed
	 */
	public function get( string $key, mixed $fallback = null ): mixed {
		// First try to get from config.
		if ( $this->has_config( $key ) ) {
			return $this->get_config( $key, $fallback );
		}

		// Then try to resolve as service.
		if ( $this->bound( $key ) ) {
			return $this->make( $key );
		}

		// Return default if not found.
		return $fallback;
	}

	/**
	 * Set config or service in the container.
	 *
	 * @param string $key The key to set.
	 * @param mixed  $value The value to set.
	 *
	 * @return self
	 */
	public function set( string $key, mixed $value ): self {
		// If it's an object, register as instance.
		if ( is_object( $value ) ) {
			$this->instance( $key, $value );
		} elseif ( $value instanceof Closure || ( is_string( $value ) && class_exists( $value ) ) ) {
			// If it's a closure or class name, bind as service.
			$this->bind( $key, $value );
		} else {
			// Otherwise set as config.
			$this->set_config( $key, $value );
		}

		return $this;
	}

	/**
	 * Check if config or service exists in the container.
	 *
	 * @param string $key The key to check.
	 *
	 * @return bool
	 */
	public function has( string $key ): bool {
		return $this->has_config( $key ) || $this->bound( $key );
	}

	/**
	 * Remove config or service from the container.
	 *
	 * @param string $key The key to remove.
	 *
	 * @return void
	 */
	public function unset( string $key ): void {
		// Remove from services.
		$this->forget( $key );

		// Remove from config if it exists.
		if ( $this->has_config( $key ) ) {
			$keys   = explode( '.', $key );
			$config = &$this->config;

			// Navigate to parent and unset the key.
			$keys_count = count( $keys ) - 1;
			for ( $i = 0; $i < $keys_count; $i++ ) {
				if ( ! isset( $config[ $keys[ $i ] ] ) ) {
					return;
				}
				$config = &$config[ $keys[ $i ] ];
			}

			unset( $config[ end( $keys ) ] );
		}
	}

	/**
	 * Get configuration value
	 *
	 * @param string $key The configuration key (dot notation supported).
	 * @param mixed  $fallback Default value if key not found.
	 *
	 * @return mixed
	 */
	public function get_config( string $key, mixed $fallback = null ): mixed {
		$keys  = explode( '.', $key );
		$value = $this->config;

		foreach ( $keys as $k ) {
			if ( ! is_array( $value ) || ! array_key_exists( $k, $value ) ) {
				return $fallback;
			}
			$value = $value[ $k ];
		}

		return $value;
	}

	/**
	 * Set configuration value
	 *
	 * @param string $key The configuration key (dot notation supported).
	 * @param mixed  $value The value to set.
	 *
	 * @return self
	 */
	public function set_config( string $key, mixed $value ): self {
		$keys   = explode( '.', $key );
		$config = &$this->config;

		foreach ( $keys as $k ) {
			if ( ! isset( $config[ $k ] ) || ! is_array( $config[ $k ] ) ) {
				$config[ $k ] = array();
			}
			$config = &$config[ $k ];
		}

		$config = $value;

		return $this;
	}

	/**
	 * Check if configuration key exists
	 *
	 * @param string $key The configuration key (dot notation supported).
	 *
	 * @return bool
	 */
	public function has_config( string $key ): bool {
		$keys  = explode( '.', $key );
		$value = $this->config;

		foreach ( $keys as $k ) {
			if ( ! is_array( $value ) || ! array_key_exists( $k, $value ) ) {
				return false;
			}
			$value = $value[ $k ];
		}

		return true;
	}

	/**
	 * Register a shared binding in the container.
	 *
	 * @param string              $id The abstract type.
	 * @param Closure|string|null $concrete The concrete implementation.
	 *
	 * @return self
	 */
	public function singleton( string $id, Closure|string|null $concrete = null ): self {
		return $this->bind( $id, $concrete, true );
	}

	/**
	 * Register an instance as a singleton
	 *
	 * @param string|array $id Unique identifier for the dependency or array of abstract => instance pairs.
	 * @param object|null  $instance The instance to register (not needed if $id is array or if auto-instantiating).
	 *
	 * @throws RuntimeException If alias conflict is detected or if class cannot be auto-instantiated.
	 * @return self
	 */
	public function instance( string|array $id, object $instance = null ): self {
		// If id is an array, loop through and register each instance.
		if ( is_array( $id ) ) {
			foreach ( $id as $key => $value ) {
				if ( is_object( $value ) ) {
					$this->instance( $key, $value );
				}
			}

			return $this;
		}

		// If no instance provided, try to auto-instantiate the class.
		if ( null === $instance ) {
			if ( class_exists( $id ) ) {
				$instance = $this->make( $id );
			} else {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new RuntimeException( "Cannot auto-instantiate '{$id}': class does not exist." );
			}
		}

		// Single instance registration.
		// Generate and set automatic alias if the instance is a class.
		$class_name = get_class( $instance );
		$auto_alias = $this->generate_alias( $class_name );

		// Check if alias already exists for a different class.
		if ( isset( $this->aliases[ $auto_alias ] ) && $this->aliases[ $auto_alias ] !== $class_name ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new RuntimeException( "Alias '{$auto_alias}' is already registered for a different class." );
		}

		// Set the alias if it doesn't exist.
		if ( ! isset( $this->aliases[ $auto_alias ] ) ) {
			$this->aliases[ $auto_alias ] = $class_name;
		}

		$this->instances[ $id ] = $instance;
		$this->bindings[ $id ]  = array(
			'concrete' => $instance,
			'shared'   => true,
		);

		return $this;
	}

	/**
	 * Register a binding with the container.
	 *
	 * @param string              $id The abstract type.
	 * @param Closure|string|null $concrete The concrete implementation.
	 * @param bool                $shared Whether the binding should be shared.
	 *
	 * @throws RuntimeException If alias conflict is detected.
	 * @return self
	 */
	public function bind( string $id, Closure|string|null $concrete = null, bool $shared = false ): self {
		// If concrete is not provided, use the abstract class name.
		if ( null === $concrete && class_exists( $id ) ) {
			$concrete = $id;
		}

		// Generate and set automatic alias if the concrete is a class.
		if ( is_string( $concrete ) && class_exists( $concrete ) ) {
			$auto_alias = $this->generate_alias( $concrete );

			// Check if alias already exists for a different class.
			if ( isset( $this->aliases[ $auto_alias ] ) && $this->aliases[ $auto_alias ] !== $concrete ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new RuntimeException( "Alias '{$auto_alias}' is already registered for a different class." );
			}

			// Set the alias if it doesn't exist.
			if ( ! isset( $this->aliases[ $auto_alias ] ) ) {
				$this->aliases[ $auto_alias ] = $concrete;
			}
		}

		$this->bindings[ $id ] = compact( 'concrete', 'shared' );

		// Remove any existing instance and resolved state to force recreation.
		unset( $this->instances[ $id ], $this->resolved[ $id ] );

		return $this;
	}

	/**
	 * Resolve the given type from the container.
	 *
	 * @param string $id The abstract type.
	 * @param array  $parameters The parameters to pass to the constructor.
	 *
	 * @throws RuntimeException If circular dependency or resolution fails.
	 * @return mixed
	 */
	public function make( string $id, array $parameters = array() ): mixed {
		$id = $this->get_alias( $id );

		// If we have a shared instance, return it.
		if ( isset( $this->instances[ $id ] ) && empty( $parameters ) ) {
			return $this->instances[ $id ];
		}

		// Check for circular dependencies.
		if ( isset( $this->resolved[ $id ] ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new RuntimeException( "Circular dependency detected for: {$id}" );
		}

		// Mark as being resolved.
		$this->resolved[ $id ] = true;

		$concrete = $this->get_concrete( $id );

		// If we can build the concrete type, do so.
		if ( $concrete === $id || $concrete instanceof Closure ) {
			$object = $this->build( $concrete, $parameters );
		} else {
			$object = $this->make( $concrete, $parameters );
		}

		// If the binding is shared, store the instance.
		if ( isset( $this->bindings[ $id ]['shared'] ) && $this->bindings[ $id ]['shared'] && empty( $parameters ) ) {
			$this->instances[ $id ] = $object;
		}

		// Always unmark as resolved when done.
		unset( $this->resolved[ $id ] );

		return $object;
	}

	/**
	 * Determine if the given abstract type has been bound.
	 *
	 * @param string $id The abstract type.
	 *
	 * @return bool
	 */
	public function bound( string $id ): bool {
		$id = $this->get_alias( $id );

		return isset( $this->bindings[ $id ] ) || isset( $this->instances[ $id ] );
	}

	/**
	 * Register an alias for a type.
	 *
	 * @param string $id The abstract type.
	 * @param string $alias The alias to register.
	 *
	 * @throws RuntimeException If alias conflict is detected.
	 * @return void
	 */
	public function alias( string $id, string $alias ): void {
		// Check if alias already exists for a different class.
		if ( isset( $this->aliases[ $alias ] ) && $this->aliases[ $alias ] !== $id ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new RuntimeException( "Alias '{$alias}' is already registered for a different class." );
		}

		$this->aliases[ $alias ] = $id;
	}

	/**
	 * Tag services.
	 *
	 * @param string               $tag The tag name.
	 * @param array<string>|string $services The service keys.
	 *
	 * @return void
	 */
	public function tag( string $tag, array|string $services ): void {
		$services = (array) $services;

		if ( ! isset( $this->tags[ $tag ] ) ) {
			$this->tags[ $tag ] = array();
		}

		$this->tags[ $tag ] = array_merge( $this->tags[ $tag ], $services );
	}

	/**
	 * Get all services with a specific tag.
	 *
	 * @param string $tag The tag name.
	 *
	 * @return array<mixed>
	 */
	public function tagged( string $tag ): array {
		if ( ! isset( $this->tags[ $tag ] ) ) {
			return array();
		}

		$services = array();
		foreach ( $this->tags[ $tag ] as $service ) {
			$services[] = $this->get( $service );
		}

		return $services;
	}

	/**
	 * Flush the container of all bindings and resolved instances.
	 *
	 * @return void
	 */
	public function flush(): void {
		$this->config    = array();
		$this->resolved  = array();
		$this->bindings  = array();
		$this->instances = array();
		$this->tags      = array();
		$this->aliases   = array();
	}

	/**
	 * Instantiate a concrete instance of the given type.
	 *
	 * @param string|Closure $concrete The concrete implementation.
	 * @param array          $parameters The parameters to pass to the constructor.
	 *
	 * @throws RuntimeException If class is not instantiable or dependencies cannot be resolved.
	 * @return mixed
	 */
	protected function build( mixed $concrete, array $parameters = array() ): mixed {
		// If the concrete type is a closure, execute it.
		if ( $concrete instanceof Closure ) {
			return $concrete( $this, $parameters );
		}

		$reflector = new ReflectionClass( $concrete );

		if ( ! $reflector->isInstantiable() ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new RuntimeException( "Class {$concrete} is not instantiable." );
		}

		$constructor = $reflector->getConstructor();

		if ( is_null( $constructor ) ) {
			return new $concrete();
		}

		$dependencies = $constructor->getParameters();
		$instances    = $this->resolve_dependencies( $dependencies, $parameters );

		return $reflector->newInstanceArgs( $instances );
	}

	/**
	 * Remove a binding from the container.
	 *
	 * @param string $id The abstract type.
	 *
	 * @return void
	 */
	protected function forget( string $id ): void {
		$id = $this->get_alias( $id );
		unset( $this->bindings[ $id ], $this->instances[ $id ], $this->resolved[ $id ] );
	}

	/**
	 * Resolve all dependencies from ReflectionParameters.
	 *
	 * @param array $dependencies The constructor dependencies.
	 * @param array $parameters Optional parameters.
	 *
	 * @throws RuntimeException If dependencies cannot be resolved.
	 * @return array
	 */
	protected function resolve_dependencies( array $dependencies, array $parameters = array() ): array {
		$results = array();

		foreach ( $dependencies as $dependency ) {
			$name = $dependency->getName();
			$type = $dependency->getType();

			if ( array_key_exists( $name, $parameters ) ) {
				$results[] = $parameters[ $name ];
			} elseif ( $type instanceof ReflectionNamedType && ! $type->isBuiltin() ) {
				if ( $this->bound( $type->getName() ) || class_exists( $type->getName() ) ) {
					$results[] = $this->make( $type->getName() );
				} elseif ( $dependency->allowsNull() ) {
					$results[] = null;
				} elseif ( $dependency->isDefaultValueAvailable() ) {
					$results[] = $dependency->getDefaultValue();
				} else {
					// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
					throw new RuntimeException( "Cannot resolve dependency {$name} of type {$type->getName()}" );
				}
			} elseif ( $dependency->isDefaultValueAvailable() ) {
				$results[] = $dependency->getDefaultValue();
			} else {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new RuntimeException( "Cannot resolve primitive dependency: {$name}" );
			}
		}

		return $results;
	}

	/**
	 * Get the concrete type for a given abstract.
	 *
	 * @param string $id The abstract type.
	 *
	 * @return mixed
	 */
	protected function get_concrete( string $id ): mixed {
		$id = $this->get_alias( $id );

		if ( isset( $this->bindings[ $id ] ) ) {
			return $this->bindings[ $id ]['concrete'];
		}

		return $id;
	}

	/**
	 * Get the alias for an abstract if available.
	 *
	 * @param string $id The service identifier.
	 *
	 * @return string
	 */
	protected function get_alias( string $id ): string {
		if ( ! isset( $this->aliases[ $id ] ) ) {
			return $id;
		}

		return $this->get_alias( $this->aliases[ $id ] );
	}

	/**
	 * Generate an alias from a class name.
	 *
	 * @param string $class_name The fully qualified class name.
	 *
	 * @return string
	 */
	protected function generate_alias( string $class_name ): string {
		// Remove the base namespace.
		$relative_class = str_replace( $this->context . '\\', '', $class_name );

		// Convert to lowercase and replace backslashes with dots.
		return strtolower( str_replace( '\\', '.', $relative_class ) );
	}
}
