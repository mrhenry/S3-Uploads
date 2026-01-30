<?php

class Test_WP_Image_Editor_Imagick extends WP_UnitTestCase {
	private $image_path = __DIR__ . '/data/canola.jpg';

	protected function setUp(): void {
		require_once ABSPATH . WPINC . '/class-wp-image-editor.php';
		require_once ABSPATH . WPINC . '/class-wp-image-editor-imagick.php';

		if ( ! WP_Image_Editor_Imagick::test() ) {
			$this->markTestSkipped( 'WP_Image_Editor_Imagick test failed' );
		}
	}

	public function test_save_image() {

		$upload_dir = wp_upload_dir();
		$path       = $upload_dir['basedir'] . '/canola.jpg';
		copy( $this->image_path, $path );

		$image_editor = new WP_Image_Editor_Imagick( $path );

		$image_editor->load();
		$image_editor->resize( 100, 120, true );
		$status = $image_editor->save( $upload_dir['basedir'] . '/canola-100x120.jpg' );

		$this->assertNotInstanceOf( 'WP_Error', $status );

		$this->assertEquals( $upload_dir['basedir'] . '/canola-100x120.jpg', $status['path'] );
		$this->assertEquals( 'canola-100x120.jpg', $status['file'] );
		$this->assertEquals( 100, $status['width'] );
		$this->assertEquals( 120, $status['height'] );

		$image = getimagesize( $status['path'] );

		$this->assertEquals(
			array(
				100,
				120,
				2,
				'width="100" height="120"',
				'bits'     => 8,
				'channels' => 3,
				'mime'     => 'image/jpeg',
			),
			$image
		);
	}
}
