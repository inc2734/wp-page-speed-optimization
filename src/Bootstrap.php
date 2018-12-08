<?php
/**
 * @package inc2734/wp-page-speed-optimization
 * @author inc2734
 * @license GPL-2.0+
 */

namespace Inc2734\WP_Page_Speed_Optimization;

class Bootstrap {

	public function __construct() {
		foreach ( glob( __DIR__ . '/Helper/*.php' ) as $file ) {
			require_once( $file );
		}

		new App\Controller\Assets();
		new App\Controller\HTTP2_Server_Push();
		new App\Controller\Sidebars();
		new App\Controller\Menu();
		new App\Controller\LazyLoad();
	}
}
