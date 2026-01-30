<?php

class GS_Uploads {
	private static ?self $instance = null;

	private string $bucket;
	private string|null $bucket_url = null;
	private Google\Cloud\Storage\StorageClient $storage;

	public array $original_upload_dir = array();

	/**
	 * Get instance
	 *
	 * @return GS_Uploads
	 */
	public static function get_instance() {
		if ( self::$instance ) {
			return self::$instance;
		}

		self::$instance = new GS_Uploads(
			defined( 'GS_UPLOADS_BUCKET' ) ? GS_UPLOADS_BUCKET : null,
			defined( 'GS_UPLOADS_BUCKET_URL' ) ? GS_UPLOADS_BUCKET_URL : null,
			new Google\Cloud\Storage\StorageClient(
				array(
					'apiEndpoint'        => defined( 'GS_UPLOADS_BUCKET_URL' ) ? GS_UPLOADS_BUCKET_URL : null,
					'credentialsFetcher' => defined( 'GS_UPLOADS_INSECURE_CREDENTIALS' ) && GS_UPLOADS_INSECURE_CREDENTIALS ? new Google\Auth\Credentials\InsecureCredentials() : null,
				)
			)
		);

		return self::$instance;
	}

	public function __construct( string $bucket, string|null $bucket_url, Google\Cloud\Storage\StorageClient $storage ) {
		$this->bucket     = $bucket;
		$this->bucket_url = $bucket_url;
		$this->storage    = $storage;
	}


	/**
	 * Setup the hooks, urls filtering etc for GS Uploads
	 */
	public function setup() {
		self::$instance->storage->registerStreamWrapper();

		add_filter( 'upload_dir', array( $this, 'filter_upload_dir' ) );
		add_filter( 'wp_image_editors', array( $this, 'filter_editors' ), 9 );
		add_action( 'delete_attachment', array( $this, 'delete_attachment_files' ) );
		remove_filter( 'admin_notices', 'wpthumb_errors' );
	}

	/**
	 * Tear down the hooks, url filtering etc for GS Uploads
	 */
	public function tear_down() {
		self::$instance->storage->unregisterStreamWrapper();

		remove_filter( 'upload_dir', array( $this, 'filter_upload_dir' ) );
		remove_filter( 'wp_image_editors', array( $this, 'filter_editors' ), 9 );
		remove_filter( 'delete_attachment', array( $this, 'delete_attachment_files' ) );
	}


	public function filter_upload_dir( $dirs ) {
		$this->original_upload_dir = $dirs;

		// dirs
		$dirs['path']    = str_replace( WP_CONTENT_DIR, 'gs://' . $this->bucket, $dirs['path'] );
		$dirs['basedir'] = str_replace( WP_CONTENT_DIR, 'gs://' . $this->bucket, $dirs['basedir'] );

		// urls
		$dirs['url']     = str_replace( 'gs://' . $this->bucket, $this->get_gs_url(), $dirs['path'] );
		$dirs['baseurl'] = str_replace( 'gs://' . $this->bucket, $this->get_gs_url(), $dirs['basedir'] );

		return $dirs;
	}

	/**
	 * Delete all attachment files from GS when an attachment is deleted.
	 *
	 * WordPress Core's handling of deleting files for attachments via
	 * wp_delete_attachment_files is not compatible with remote streams, as
	 * it makes many assumptions about local file paths. The hooks also do
	 * not exist to be able to modify their behavior. As such, we just clean
	 * up the gs files when an attachment is removed, and leave WordPress to try
	 * a failed attempt at mangling the gs:// urls.
	 *
	 * @param number $post_id the post id
	 */
	public function delete_attachment_files( $post_id ) {
		$meta = wp_get_attachment_metadata( $post_id );
		$file = get_attached_file( $post_id );

		$deleted = array();

		if ( ! empty( $meta['sizes'] ) ) {
			foreach ( $meta['sizes'] as $sizeinfo ) {
				$intermediate_file = str_replace( basename( $file ), $sizeinfo['file'], $file );

				$intermediate_file = \apply_filters( 'gs_delete_attachment_file', $intermediate_file );
				if ( empty( $intermediate_file ) ) {
					continue;
				}

				// Prevent duplicate deletes caused by sizes hacks
				if ( $deleted[ $intermediate_file ] ?? false ) {
					continue;
				}

				unlink( $intermediate_file );
				$deleted[ $intermediate_file ] = true;
			}
		}

		$file = \apply_filters( 'gs_delete_attachment_file', $file );
		if ( empty( $file ) ) {
			return;
		}

		if ( $deleted[ $file ] ?? false ) {
			return;
		}

		unlink( $file );
		$deleted[ $file ] = true;

		// Prevent duplicate deletes caused by wp delete actions
		\add_filter(
			'wp_delete_file',
			function ( $file_wp_wants_to_delete ) use ( $deleted ) {
				// File already deleted, prevent duplicate 'unlink' calls
				if ( $deleted[ $file_wp_wants_to_delete ] ?? false ) {
					return false;
				}

				return $file_wp_wants_to_delete;
			},
			10,
			1
		);
	}

	public function get_gs_url() {
		if ( $this->bucket_url ) {
			return $this->bucket_url;
		}

		return apply_filters( 'gs_uploads_bucket_url', 'https://storage.googleapis.com/' . $this->bucket );
	}

	public function filter_editors( $editors ) {

		$position = array_search( 'WP_Image_Editor_Imagick', $editors, true );
		if ( $position !== false ) {
			unset( $editors[ $position ] );
		}

		array_unshift( $editors, 'GS_Uploads_Image_Editor_Imagick' );

		return $editors;
	}
}
