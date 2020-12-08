<?php
/**
 * @package inc2734/wp-page-speed-optimization
 * @author inc2734
 * @license GPL-2.0+
 */

namespace Inc2734\WP_Page_Speed_Optimization\Helper;

use Inc2734\WP_Page_Speed_Optimization\App;

/**
 * dynamic_sidebar() corresponding to cache.
 *
 * @param string $sidebar_id The sidebar Id.
 */
function dynamic_sidebar( $sidebar_id ) {
	App\Controller\Sidebars::dynamic_sidebar( $sidebar_id );
}
