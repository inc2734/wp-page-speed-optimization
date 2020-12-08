<?php
/**
 * @package inc2734/wp-page-speed-optimization
 * @author inc2734
 * @license GPL-2.0+
 */

namespace Inc2734\WP_Page_Speed_Optimization;

class Bootstrap {

	/**
	 * Constructor.
	 */
	public function __construct() {
		include_once( __DIR__ . '/Helper/dynamic_sidebar.php' );
		include_once( __DIR__ . '/Helper/write_cache_control_setting.php' );

		new App\Controller\Assets();
		new App\Controller\HTTP2_Server_Push();
		new App\Controller\Sidebars();
		new App\Controller\Menu();
		new App\Controller\LazyLoad();
		new App\Controller\Prefetch();
	}
}
