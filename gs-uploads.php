<?php
/*
Plugin Name: GS Uploads
Description: Store uploads in Google Cloud Storage
Author: Mr. Henry
Version: 1.0.0
*/

require 'vendor/autoload.php';
require_once __DIR__ . '/inc/class-gs-uploads.php';

add_action(
	'plugins_loaded',
	function () {
		if ( ! defined( 'GS_UPLOADS_BUCKET' ) ) {
			return;
		}

		$instance = GS_Uploads::get_instance();
		$instance->setup();
	}
);

spl_autoload_register(
	function ( $class ) {
		$mapping = array(
			'GS_Uploads_Image_Editor_Imagick' => __DIR__ . '/inc/class-gs-uploads-image-editor-imagick.php',
		);

		if ( isset( $mapping[ $class ] ) ) {
			require $mapping[ $class ];
		}
	},
	true
);
