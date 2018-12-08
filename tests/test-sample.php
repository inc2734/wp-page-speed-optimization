<?php
// @todo
class Sample_Test extends WP_UnitTestCase {

	public function setup() {
		parent::setup();
	}

	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function sample() {
		new \Inc2734\WP_Page_Speed_Optimization\Bootstrap();

		$this->assertTrue( class_exists( '\Inc2734\WP_Page_Speed_Optimization\Bootstrap' ) );
		$this->assertTrue( function_exists( '\Inc2734\WP_Page_Speed_Optimization\Helper\dynamic_sidebar' ) );
		$this->assertTrue( function_exists( '\Inc2734\WP_Page_Speed_Optimization\Helper\write_cache_control_setting' ) );
	}
}
