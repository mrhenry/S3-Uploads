<?php

/*
Plugin Name: S3 Uploads
Description: Store uploads in S3
Author: Human Made Limited
Version: 1.1.0
Author URI: http://hmn.md
*/

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once dirname( __FILE__ ) . '/inc/class-s3-uploads-wp-cli-command.php';
}

add_action( 'plugins_loaded', 's3_uploads_init' );

function s3_uploads_init() {
	if ( ! s3_uploads_check_requirements() ) {
		return;
	}

	if ( ! defined( 'S3_UPLOADS_BUCKET' ) ) {
		return;
	}

	if ( ( ! defined( 'S3_UPLOADS_KEY' ) || ! defined( 'S3_UPLOADS_SECRET' ) ) && ! defined( 'S3_UPLOADS_USE_INSTANCE_PROFILE' ) ) {
		return;
	}

	if ( ! s3_uploads_enabled() ) {
		return;
	}

	$instance = S3_Uploads::get_instance();
	$instance->setup();
}

/**
 * Check whether the environment meets the plugin's requirements, like the minimum PHP version.
 *
 * @return bool True if the requirements are met, else false.
 */
function s3_uploads_check_requirements() {
	if ( version_compare( '5.3.3', PHP_VERSION, '>' ) ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			add_action( 'admin_notices', 's3_uploads_outdated_php_version_notice' );
		}

		return false;
	}

	return true;
}

/**
 * Print an admin notice when the PHP version is not high enough.
 *
 * This has to be a named function for compatibility with PHP 5.2.
 */
function s3_uploads_outdated_php_version_notice() {
	printf( '<div class="error"><p>The S3 Uploads plugin requires PHP version 5.3.3 or higher. Your server is running PHP version %s.</p></div>',
		PHP_VERSION
	);
}

/**
 * Check if URL rewriting is enabled.
 *
 * Define S3_UPLOADS_AUTOENABLE to false in your wp-config to disable, or use the
 * s3_uploads_enabled option.
 *
 * @return bool
 */
function s3_uploads_enabled() {
	// Make sure the plugin is enabled when autoenable is on
	$constant_autoenable_off = ( defined( 'S3_UPLOADS_AUTOENABLE' ) && false === S3_UPLOADS_AUTOENABLE );

	if ( $constant_autoenable_off && 'enabled' !== get_option( 's3_uploads_enabled' ) ) {                         // If the plugin is not enabled, skip
		return false;
	}

	return true;
}

/**
 * Autoload callback.
 *
 * @param $class_name Name of the class to load.
 */
function s3_uploads_autoload($class) {
	$mapping = array(
		'S3_Uploads_ChangedFilesIterator' => __DIR__ . '/inc/class-s3-uploads-changed-files-iterator.php',
		'S3_Uploads_Image_Editor_Imagick' => __DIR__ . '/inc/class-s3-uploads-image-editor-imagick.php',
		'S3_Uploads_Local_Stream_Wrapper' => __DIR__ . '/inc/class-s3-uploads-local-stream-wrapper.php',
		'S3_Uploads_Stream_Wrapper' => __DIR__ . '/inc/class-s3-uploads-stream-wrapper.php',
		'S3_Uploads_UploadSyncBuilder' => __DIR__ . '/inc/class-s3-uploads-uploadsyncbuilder.php',
		'S3_Uploads_WP_CLI_Command' => __DIR__ . '/inc/class-s3-uploads-wp-cli-command.php',
		'S3_Uploads' => __DIR__ . '/inc/class-s3-uploads.php',
	);
	
	if (isset($mapping[$class])) {
		require $mapping[$class];
	}
}

spl_autoload_register('s3_uploads_autoload', true);

// Require AWS Autoloader file.
require_once dirname( __FILE__ ) . '/lib/aws-sdk/aws-autoloader.php';
