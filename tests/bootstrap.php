<?php
/**
 * PHPUnit bootstrap for AI Connector plugin.
 *
 * Loads WordPress if available, then loads the plugin.
 *
 * Usage:
 *   - Inside Docker: vendor/bin/phpunit
 *   - From host: docker exec wordpress-wordpress-1 bash -c \
 *       "cd /var/www/html/wp-content/plugins/ai-connector && vendor/bin/phpunit"
 */

$wp_loaded = false;

// Suppress output buffering to catch WP errors cleanly.
ob_start();

$candidates = array(
	getenv( 'WP_LOAD_PATH' ),
	__DIR__ . '/../../../wp-load.php',         // deployed: wp-content/plugins/ai-connector/tests/
	__DIR__ . '/../../../../wp-load.php',
	__DIR__ . '/../../../wp/wp-load.php',       // dev: plugins/AI-Connector/tests/ → wp/
);

foreach ( $candidates as $candidate ) {
	if ( $wp_loaded || ! $candidate || ! file_exists( $candidate ) ) {
		continue;
	}

	// Try loading WordPress — catch DB failures.
	try {
		require_once $candidate;
		if ( defined( 'ABSPATH' ) ) {
			$wp_loaded = true;
		}
	} catch ( \Throwable $e ) {
		fwrite( STDERR, "WP load failed: " . $e->getMessage() . "\n" );
	}
}

// Discard any HTML output from WP (e.g. DB error page).
$buffered = ob_get_clean();
if ( ! $wp_loaded && ! empty( $buffered ) && stripos( $buffered, 'base de données' ) !== false ) {
	fwrite( STDERR, "WordPress loaded but DB is unreachable. Run tests inside Docker.\n" );
	exit( 1 );
}

// Force wp_die to exit cleanly during tests.
if ( function_exists( 'add_filter' ) ) {
	add_filter( 'wp_die_handler', function () {
		return function ( $message ) {
			if ( is_scalar( $message ) ) {
				$message = strip_tags( (string) $message );
			} else {
				$message = 'wp_die() was called.';
			}
			fwrite( STDERR, "WP died during tests: {$message}\n" );
			exit( 1 );
		};
	} );
}

if ( $wp_loaded ) {
	// Load the plugin (if not already loaded by WP).
	if ( ! defined( 'AI_CONNECTOR_PATH' ) ) {
		require_once __DIR__ . '/../ai-connector.php';
	}
}

define( 'AIC_WP_LOADED', $wp_loaded );

if ( ! AIC_WP_LOADED ) {
	fwrite( STDERR, "WordPress not loaded. Run tests inside Docker or set WP_LOAD_PATH.\n" );
	exit( 1 );
}
