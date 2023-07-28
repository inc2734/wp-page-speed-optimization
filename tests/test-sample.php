<?php
// @todo
class Sample_Test extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
	}

	public function tear_down() {
		parent::tear_down();
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
