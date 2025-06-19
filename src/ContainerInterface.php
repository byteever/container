<?php

declare(strict_types=1);

namespace ByteEver\Container;

use Closure;
use RuntimeException;

/**
 * Container interface for dependency injection.
 *
 * Defines the contract for a dependency injection container with advanced features
 * like auto-wiring, service binding, configuration management, and service tagging.
 *
 * @since   1.0.0
 * @version 1.0.0
 * @author  Sultan Nasir Uddin <manikdrmc@gmail.com>
 * @package ByteEver/Container
 * @license MIT
 */
interface ContainerInterface {

	/**
	 * Create a new container instance.
	 *
	 * @param array|string $config The configuration array or plugin file path.
	 * @param string       $context The namespace context to use for auto-resolution.
	 *
	 * @return static
	 */
	public static function create( array|string $config = array(), string $context = __NAMESPACE__ ): static;

	/**
	 * Get config or service from the container.
	 *
	 * @param string $key     The key to get.
	 * @param mixed  $fallback Default value if not found.
	 *
	 * @return mixed
	 */
	public function get( string $key, mixed $fallback = null ): mixed;

	/**
	 * Set config or service in the container.
	 *
	 * @param string $key   The key to set.
	 * @param mixed  $value The value to set.
	 *
	 * @return self
	 */
	public function set( string $key, mixed $value ): self;

	/**
	 * Check if config or service exists in the container.
	 *
	 * @param string $key The key to check.
	 *
	 * @return bool
	 */
	public function has( string $key ): bool;

	/**
	 * Remove config or service from the container.
	 *
	 * @param string $key The key to remove.
	 *
	 * @return void
	 */
	public function unset( string $key ): void;

	/**
	 * Get configuration value
	 *
	 * @param string $key     The configuration key (dot notation supported).
	 * @param mixed  $fallback Default value if key not found.
	 *
	 * @return mixed
	 */
	public function get_config( string $key, mixed $fallback = null ): mixed;

	/**
	 * Set configuration value
	 *
	 * @param string $key   The configuration key (dot notation supported).
	 * @param mixed  $value The value to set.
	 *
	 * @return self
	 */
	public function set_config( string $key, mixed $value ): self;

	/**
	 * Check if configuration key exists
	 *
	 * @param string $key The configuration key (dot notation supported).
	 *
	 * @return bool
	 */
	public function has_config( string $key ): bool;

	/**
	 * Register a shared binding in the container.
	 *
	 * @param string              $id The abstract type.
	 * @param Closure|string|null $concrete The concrete implementation.
	 *
	 * @return self
	 */
	public function singleton( string $id, Closure|string|null $concrete = null ): self;

	/**
	 * Register an instance as a singleton
	 *
	 * @param string|array $id Unique identifier for the dependency or array of id => instance pairs.
	 * @param object|null  $instance The instance to register (not needed if $id is array or if auto-instantiating).
	 *
	 * @throws RuntimeException If alias conflict is detected.
	 * @return self
	 */
	public function instance( string|array $id, object $instance = null ): self;

	/**
	 * Register a binding with the container.
	 *
	 * @param string              $id The abstract type.
	 * @param Closure|string|null $concrete The concrete implementation.
	 * @param bool                $shared   Whether the binding should be shared.
	 *
	 * @throws RuntimeException If alias conflict is detected.
	 * @return self
	 */
	public function bind( string $id, Closure|string|null $concrete = null, bool $shared = false ): self;

	/**
	 * Resolve the given type from the container.
	 *
	 * @param string $id   The abstract type.
	 * @param array  $parameters The parameters to pass to the constructor.
	 *
	 * @throws RuntimeException If circular dependency or resolution fails.
	 * @return mixed
	 */
	public function make( string $id, array $parameters = array() ): mixed;

	/**
	 * Determine if the given abstract type has been bound.
	 *
	 * @param string $id The abstract type.
	 *
	 * @return bool
	 */
	public function bound( string $id ): bool;

	/**
	 * Register an alias for a type.
	 *
	 * @param string $id The abstract type.
	 * @param string $alias    The alias to register.
	 *
	 * @throws RuntimeException If alias conflict is detected.
	 * @return void
	 */
	public function alias( string $id, string $alias ): void;

	/**
	 * Tag services.
	 *
	 * @param string               $tag      The tag name.
	 * @param array<string>|string $services The service keys.
	 *
	 * @return void
	 */
	public function tag( string $tag, array|string $services ): void;

	/**
	 * Get all services with a specific tag.
	 *
	 * @param string $tag The tag name.
	 *
	 * @return array<mixed>
	 */
	public function tagged( string $tag ): array;

	/**
	 * Flush the container of all bindings and resolved instances.
	 *
	 * @return void
	 */
	public function flush(): void;
}
