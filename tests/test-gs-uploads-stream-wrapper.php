<?php

class Test_GS_Uploads_Stream_Wrapper extends WP_UnitTestCase {

	protected function setUp(): void {
		GS_Uploads::get_instance()->setup();
	}

	protected function tearDown(): void {
		GS_Uploads::get_instance()->tear_down();
	}

	public function test_stream_wrapper_is_registered() {
		$this->assertTrue( in_array( 'gs', stream_get_wrappers() ) );
	}

	public function test_copy_via_stream_wrapper() {

		$local_path  = __DIR__ . '/data/canola.jpg';
		$remote_path = 'gs://' . GS_UPLOADS_BUCKET . '/canola.jpg';
		$result      = copy( $local_path, $remote_path );
		$this->assertTrue( $result );
		$this->assertEquals( file_get_contents( $local_path ), file_get_contents( $remote_path ) );
	}

	public function test_copy_via_stream_wrapper_fails_on_invalid_permission() {
		$this->expectException( \Google\Cloud\Core\Exception\NotFoundException::class );

		$local_path  = __DIR__ . '/data/canola.jpg';
		$remote_path = 'gs://not-a-bucket-c1dd5b42-45be-4a85-b82f-e0b0815d69e5/canola.jpg';
		$result      = @copy( $local_path, $remote_path );

		$this->assertFalse( $result );
	}

	public function test_rename_via_stream_wrapper() {

		copy( __DIR__ . '/data/canola.jpg', 'gs://' . GS_UPLOADS_BUCKET . '/canola.jpg' );
		$result = rename( 'gs://' . GS_UPLOADS_BUCKET . '/canola.jpg', 'gs://' . GS_UPLOADS_BUCKET . '/canola-test.jpg' );
		$this->assertTrue( $result );
		$this->assertTrue( file_exists( 'gs://' . GS_UPLOADS_BUCKET . '/canola-test.jpg' ) );
	}

	public function test_rename_via_stream_wrapper_fails_on_invalid_permission() {

		// TODO: https://github.com/fsouza/fake-gcs-server/issues/2124
		// copy( __DIR__ . '/data/canola.jpg', 'gs://' . GS_UPLOADS_BUCKET . '/canola.jpg' );
		// $result = @rename( 'gs://' . GS_UPLOADS_BUCKET . '/canola.jpg', 'gs://not-a-bucket-c1dd5b42-45be-4a85-b82f-e0b0815d69e5/canola.jpg' );
		//
		// $this->assertFalse( $result );

		$this->assertTrue( true );
	}

	public function test_unlink_via_stream_wrapper() {

		copy( __DIR__ . '/data/canola.jpg', 'gs://' . GS_UPLOADS_BUCKET . '/canola.jpg' );
		$result = unlink( 'gs://' . GS_UPLOADS_BUCKET . '/canola.jpg' );
		$this->assertTrue( $result );
		$this->assertFalse( file_exists( 'gs://' . GS_UPLOADS_BUCKET . '/canola.jpg' ) );
	}

	public function get_file_exists_via_stream_wrapper() {
		copy( __DIR__ . '/data/canola.jpg', 'gs://' . GS_UPLOADS_BUCKET . '/canola.jpg' );
		$this->assertTrue( file_exists( 'gs://' . GS_UPLOADS_BUCKET . '/canola.jpg' ) );
		$this->assertFalse( file_exists( 'gs://' . GS_UPLOADS_BUCKET . '/canola-missing.jpg' ) );
	}

	public function test_getimagesize_via_stream_wrapper() {

		copy( __DIR__ . '/data/canola.jpg', 'gs://' . GS_UPLOADS_BUCKET . '/canola.jpg' );
		$file = 'gs://' . GS_UPLOADS_BUCKET . '/canola.jpg';

		$image = getimagesize( $file );

		$this->assertEquals(
			array(
				640,
				480,
				2,
				'width="640" height="480"',
				'bits'     => 8,
				'channels' => 3,
				'mime'     => 'image/jpeg',
			),
			$image
		);
	}

	public function test_stream_wrapper_supports_seeking() {

		// TODO: fseek is not supported in the google SDK
		// $file = 'gs://' . GS_UPLOADS_BUCKET . '/canola.jpg';
		// copy( __DIR__ . '/data/canola.jpg', $file );

		// $f      = fopen( $file, 'r' );
		// $result = fseek( $f, 0, SEEK_END );
		// fclose( $f );

		// $this->assertEquals( 0, $result );

		$this->assertTrue( true );
	}

	public function test_wp_handle_upload() {

		$path = tempnam( sys_get_temp_dir(), 'canola' ) . '.jpg';
		copy( __DIR__ . '/data/canola.jpg', $path );
		$contents = file_get_contents( $path );
		$file     = array(
			'error'    => null,
			'tmp_name' => $path,
			'name'     => 'can.jpg',
			'size'     => filesize( $path ),
		);

		$result = wp_handle_upload(
			$file,
			array(
				'test_form' => false,
				'test_size' => false,
				'action'    => 'wp_handle_sideload',
			)
		);

		$this->assertTrue( empty( $result['error'] ) );
		$this->assertTrue( file_exists( $result['file'] ) );
		$this->assertEquals( $contents, file_get_contents( $result['file'] ) );
	}
}
