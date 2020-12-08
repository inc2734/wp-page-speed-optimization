<?php
/**
 * @package inc2734/wp-page-speed-optimization
 * @author inc2734
 * @license GPL-2.0+
 */

namespace Inc2734\WP_Page_Speed_Optimization\App\Controller;

class Prefetch {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, '_wp_enqueue_scripts' ] );
	}

	/**
	 * Enqueue assets.
	 */
	public static function _wp_enqueue_scripts() {
		$link_prefetching = apply_filters( 'inc2734_wp_page_speed_optimization_link_prefetching', false );
		if ( ! $link_prefetching ) {
			return;
		}

		$relative_path = '/vendor/inc2734/wp-page-speed-optimization/src/assets/js/prefetch.js';
		wp_enqueue_script(
			'wp-page-speed-optimization@prefetch',
			get_template_directory_uri() . $relative_path,
			[],
			filemtime( get_template_directory() . $relative_path ),
			true
		);

		$selector    = apply_filters( 'inc2734_wp_page_speed_optimization_link_prefetching_selector', '.l-header, .l-contents__main' );
		$interval    = apply_filters( 'inc2734_wp_page_speed_optimization_link_prefetching_interval', 2000 );
		$connections = apply_filters( 'inc2734_wp_page_speed_optimization_link_prefetching_connections', 1 );

		$data  = "if ('undefined' === typeof WPPSO) { var WPPSO = {}; }";
		$data .= 'WPPSO.prefetch = {};';
		$data .= "WPPSO.prefetch.selector='" . esc_js( $selector ) . "';";
		$data .= 'WPPSO.prefetch.interval=' . esc_js( $interval ) . ';';
		$data .= 'WPPSO.prefetch.connections=' . esc_js( $connections ) . ';';
		wp_add_inline_script(
			'wp-page-speed-optimization@prefetch',
			$data,
			'before'
		);
	}
}
