<?php

declare(strict_types=1);

namespace ByteEver\Container;

use ArrayAccess;

/**
 * NullProxy class for null-safe operations.
 *
 * This class acts as a proxy that intercepts method calls and property access
 * on null values, preventing fatal errors and returning null for all operations.
 *
 * @since   1.0.0
 * @version 0.0.2
 * @author  Sultan Nasir Uddin <manikdrmc@gmail.com>
 * @package ByteEver/Container
 * @license MIT
 */
class NullProxy implements ArrayAccess {
	/**
	 * Intercept method calls and return null.
	 *
	 * @param string $method The method name.
	 * @param array  $arguments The method arguments.
	 *
	 * @return mixed
	 */
	public function __call( string $method, array $arguments ): mixed {
		return null;
	}

	/**
	 * Intercept static method calls and return null.
	 *
	 * @param string $method The method name.
	 * @param array  $arguments The method arguments.
	 *
	 * @return mixed
	 */
	public static function __callStatic( string $method, array $arguments ): mixed {
		return null;
	}

	/**
	 * Intercept property access and return null.
	 *
	 * @param string $property The property name.
	 *
	 * @return mixed
	 */
	public function __get( string $property ): mixed {
		return null;
	}

	/**
	 * Intercept property setting and do nothing.
	 *
	 * @param string $property The property name.
	 * @param mixed  $value The property value.
	 *
	 * @return void
	 */
	public function __set( string $property, mixed $value ): void {
		// Do nothing - silently ignore property setting.
	}

	/**
	 * Intercept isset() calls and return false.
	 *
	 * @param string $property The property name.
	 *
	 * @return bool
	 */
	public function __isset( string $property ): bool {
		return false;
	}

	/**
	 * Intercept unset() calls and do nothing.
	 *
	 * @param string $property The property name.
	 *
	 * @return void
	 */
	public function __unset( string $property ): void {
		// Do nothing - silently ignore property unsetting.
	}

	/**
	 * Return empty string when object is used as string.
	 *
	 * @return string
	 */
	public function __toString(): string {
		return '';
	}

	/**
	 * Return null when object is invoked as function.
	 *
	 * @return mixed
	 */
	public function __invoke(): mixed {
		return null;
	}

	/**
	 * Return debug info array.
	 *
	 * @return array
	 */
	public function __debugInfo(): array {
		return array( 'null_proxy' => true );
	}

	/**
	 * Handle array access getter.
	 *
	 * @param mixed $offset The array offset.
	 *
	 * @return mixed
	 */
	public function offsetGet( mixed $offset ): mixed {
		return null;
	}

	/**
	 * Handle array access setter.
	 *
	 * @param mixed $offset The array offset.
	 * @param mixed $value The value to set.
	 *
	 * @return void
	 */
	public function offsetSet( mixed $offset, mixed $value ): void {
		// Do nothing - silently ignore array setting.
	}

	/**
	 * Handle array access isset.
	 *
	 * @param mixed $offset The array offset.
	 *
	 * @return bool
	 */
	public function offsetExists( mixed $offset ): bool {
		return false;
	}

	/**
	 * Handle array access unset.
	 *
	 * @param mixed $offset The array offset.
	 *
	 * @return void
	 */
	public function offsetUnset( mixed $offset ): void {
		// Do nothing - silently ignore array unsetting.
	}

	/**
	 * Check if this is a null proxy instance.
	 *
	 * @return bool
	 */
	public function is_null_proxy(): bool {
		return true;
	}

	/**
	 * Get the actual value (always null for NullProxy).
	 *
	 * @return mixed
	 */
	public function get_value(): mixed {
		return null;
	}
}
