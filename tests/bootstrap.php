<?php
/**
 * Bootstrap the plugin unit testing environment.
 *
 * @package WordPress
 * @subpackage JSON API
 */

// Support for:
// 1. `WP_DEVELOP_DIR` environment variable
// 2. Plugin installed inside of WordPress.org developer checkout
// 3. Tests checked out to /tmp
if ( false !== getenv( 'WP_DEVELOP_DIR' ) ) {
	$test_root = getenv( 'WP_DEVELOP_DIR' );
} elseif ( file_exists( '../../../../tests/phpunit/includes/bootstrap.php' ) ) {
	$test_root = '../../../../tests/phpunit';
} elseif ( file_exists( './wp-test-root/wordpress-tests-lib/includes/bootstrap.php' ) ) {
	$test_root = './wp-test-root/wordpress-tests-lib';
}

require $test_root . '/includes/functions.php';

function _manually_load_plugin() {
	require __DIR__ . '/../gs-uploads.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

if ( getenv( 'GS_UPLOADS_BUCKET' ) ) {
	define( 'GS_UPLOADS_BUCKET', getenv( 'GS_UPLOADS_BUCKET' ) );
}

if ( getenv( 'GS_UPLOADS_BUCKET_URL' ) ) {
	define( 'GS_UPLOADS_BUCKET_URL', getenv( 'GS_UPLOADS_BUCKET_URL' ) );
}

define( 'GS_UPLOADS_INSECURE_CREDENTIALS', true );

require $test_root . '/includes/bootstrap.php';
