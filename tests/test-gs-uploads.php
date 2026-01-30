<?php

class Test_GS_Uploads extends WP_UnitTestCase {

	protected function setUp(): void {
	}

	protected function tearDown(): void {
	}

	/**
	 * Test gs uploads sets up all the necessary hooks
	 */
	public function test_setup() {

		GS_Uploads::get_instance()->setup();

		$this->assertEquals( 10, has_action( 'upload_dir', array( GS_Uploads::get_instance(), 'filter_upload_dir' ) ) );
		$this->assertEquals( 9, has_action( 'wp_image_editors', array( GS_Uploads::get_instance(), 'filter_editors' ) ) );

		$this->assertTrue( in_array( 'gs', stream_get_wrappers() ) );
		GS_Uploads::get_instance()->tear_down();
	}

	/**
	 * Test gs uploads sets up all the necessary hooks
	 */
	public function test_tear_down() {

		GS_Uploads::get_instance()->setup();
		GS_Uploads::get_instance()->tear_down();

		$this->assertFalse( has_action( 'upload_dir', array( GS_Uploads::get_instance(), 'filter_upload_dir' ) ) );
		$this->assertFalse( has_action( 'wp_image_editors', array( GS_Uploads::get_instance(), 'filter_editors' ) ) );

		$this->assertFalse( in_array( 'gs', stream_get_wrappers() ) );
	}

	public function test_generate_attachment_metadata() {
		GS_Uploads::get_instance()->setup();
		$upload_dir = wp_upload_dir();
		copy( __DIR__ . '/data/canola.jpg', $upload_dir['path'] . '/canola.jpg' );
		$test_file     = $upload_dir['path'] . '/canola.jpg';
		$attachment_id = WP_UnitTestCase_Base::factory()->attachment->create_object(
			$test_file,
			0,
			array(
				'post_mime_type' => 'image/jpeg',
				'post_excerpt'   => 'A sample caption',
			)
		);

		$meta_data = wp_generate_attachment_metadata( $attachment_id, $test_file );

		$this->assertEquals(
			array(
				'file'      => 'canola-150x150.jpg',
				'width'     => 150,
				'height'    => 150,
				'mime-type' => 'image/jpeg',
				'filesize'  => 5102,
			),
			$meta_data['sizes']['thumbnail']
		);

		$wp_upload_dir = wp_upload_dir();
		$this->assertTrue( file_exists( $wp_upload_dir['path'] . '/canola-150x150.jpg' ) );
	}

	public function test_image_sizes_are_deleted_on_attachment_delete() {
		GS_Uploads::get_instance()->setup();
		$upload_dir = wp_upload_dir();
		copy( __DIR__ . '/data/canola.jpg', $upload_dir['path'] . '/canola.jpg' );
		$test_file     = $upload_dir['path'] . '/canola.jpg';
		$attachment_id = WP_UnitTestCase_Base::factory()->attachment->create_object(
			$test_file,
			0,
			array(
				'post_mime_type' => 'image/jpeg',
				'post_excerpt'   => 'A sample caption',
			)
		);

		$meta_data = wp_generate_attachment_metadata( $attachment_id, $test_file );
		wp_update_attachment_metadata( $attachment_id, $meta_data );
		foreach ( $meta_data['sizes'] as $size ) {
			$this->assertTrue( file_exists( $upload_dir['path'] . '/' . $size['file'] ) );
		}

		wp_delete_attachment( $attachment_id, true );
		foreach ( $meta_data['sizes'] as $size ) {
			$this->assertFalse( file_exists( $upload_dir['path'] . '/' . $size['file'] ), sprintf( 'File %s was not deleted.', $upload_dir['path'] . '/' . $size['file'] ) );
		}
	}
}
