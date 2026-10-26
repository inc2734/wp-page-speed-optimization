<?php

use Inc2734\WP_Page_Speed_Optimization\App\Controller\Assets;

class Assets_Test extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		wp_register_script( 'wppso-test', 'https://example.org/script.js', array(), null, true );
	}

	public function tear_down() {
		wp_deregister_script( 'wppso-test' );
		parent::tear_down();
	}

	/**
	 * @dataProvider loading_attributes
	 */
	public function test_loading_attribute_is_added_only_when_safe( $attribute, $existing, $inline, $expected ) {
		$assets = new Assets();
		wp_script_add_data( 'wppso-test', $attribute, true );
		if ( $inline ) {
			wp_add_inline_script( 'wppso-test', 'window.wppsoTest = true;', 'after' );
		}

		$tag    = '<script' . $existing . ' src="https://example.org/script.js"></script>';
		$method = '_set_' . $attribute;
		$this->assertSame( $expected, $assets->$method( $tag, 'wppso-test' ) );
	}

	public static function loading_attributes() {
		$plain = '<script src="https://example.org/script.js"></script>';
		$defer = '<script defer src="https://example.org/script.js"></script>';
		$async = '<script async src="https://example.org/script.js"></script>';
		$core  = '<script data-wp-strategy="defer" src="https://example.org/script.js"></script>';

		return array(
			'add defer'             => array( 'defer', '', false, $defer ),
			'add async'             => array( 'async', '', false, $async ),
			'preserve defer'        => array( 'defer', ' defer', false, $defer ),
			'preserve async'        => array( 'async', ' async', false, $async ),
			'avoid mixed defer'     => array( 'defer', ' async', false, $async ),
			'avoid mixed async'     => array( 'async', ' defer', false, $defer ),
			'preserve core defer'   => array( 'defer', ' data-wp-strategy="defer"', false, $core ),
			'preserve core async'   => array( 'async', ' data-wp-strategy="defer"', false, $core ),
			'inline prevents defer' => array( 'defer', '', true, $plain ),
			'inline prevents async' => array( 'async', '', true, $plain ),
		);
	}

	public function test_unconfigured_scripts_keep_their_loading_attributes() {
		$assets = new Assets();
		$tag    = '<script src="https://example.org/script.js"></script>';
		$this->assertSame( $tag, $assets->_set_defer( $tag, 'wppso-test' ) );
		$this->assertSame( $tag, $assets->_set_async( $tag, 'wppso-test' ) );
	}
}
