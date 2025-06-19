<?php

/**
 * Test bootstrap file for Codeception.
 *
 * @package ByteEver/Container
 */

// Autoload composer dependencies
require_once __DIR__ . '/../vendor/autoload.php';

// Mock WordPress functions for testing
if ( ! function_exists( 'plugin_basename' ) ) {
	function plugin_basename( $file ) {
		return basename( dirname( $file ) ) . '/' . basename( $file );
	}
}

if ( ! function_exists( 'plugin_dir_path' ) ) {
	function plugin_dir_path( $file ) {
		return dirname( $file ) . '/';
	}
}

if ( ! function_exists( 'plugin_dir_url' ) ) {
	function plugin_dir_url( $file ) {
		return 'http://example.com/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
	}
}

if ( ! function_exists( 'get_file_data' ) ) {
	function get_file_data( $file, $headers, $context = '' ) {
		// Mock implementation for testing - reads actual plugin headers
		if ( ! file_exists( $file ) ) {
			return array_fill_keys( array_values( $headers ), '' );
		}

		$file_data = file_get_contents( $file );
		$result    = [];

		foreach ( $headers as $key => $header ) {
			$pattern = '/^[ \t\/*#@]*' . preg_quote( $header, '/' ) . ':(.*)$/mi';
			if ( preg_match( $pattern, $file_data, $matches ) ) {
				$result[ $key ] = trim( preg_replace( '/\s*(?:\*\/|\?>).*/', '', $matches[1] ) );
			} else {
				$result[ $key ] = '';
			}
		}

		return $result;
	}
}

if ( ! function_exists( 'wp_cache_get' ) ) {
	function wp_cache_get( $key, $group = '' ) {
		return false; // Always return false for testing
	}
}

if ( ! function_exists( 'wp_cache_set' ) ) {
	function wp_cache_set( $key, $data, $group = '', $expire = 0 ) {
		return true;
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

// Define ABSPATH constant
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wordpress/' );
}
