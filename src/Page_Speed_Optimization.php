<?php
/**
 * @package inc2734/wp-page-speed-optimization
 * @author inc2734
 * @license GPL-2.0+
 */

namespace Inc2734\WP_Page_Speed_Optimization;

class Page_Speed_Optimization {

	public function __construct() {
		new App\Controller\Assets();
		new App\Controller\HTTP2_Server_Push();
		new App\Controller\Sidebars();
		new App\Controller\Menu();
	}

	/**
	 * dynamic_sidebar() corresponding to cache
	 *
	 * @param string $sidebar_id
	 * @return void
	 */
	public static function dynamic_sidebar( $sidebar_id ) {
		App\Controller\Sidebars::dynamic_sidebar( $sidebar_id );
	}

	/**
	 * Write ache control setting into .htaccess
	 *
	 * @param bool $enable
	 * @return int|false bytes
	 */
	public static function write_cache_control_setting( $enable ) {
		$home_path = get_home_path();
		$htaccess  = $home_path . '.htaccess';

		if ( ! file_exists( $htaccess ) || ! is_writable( $home_path ) || ! is_writable( $htaccess ) ) {
			return false;
		}

		if ( $enable ) {
			$rules[] = '<ifModule mod_expires.c>';
			$rules[] = 'ExpiresActive On';
			$rules[] = '<FilesMatch "\.(css|js)$">';
			$rules[] = 'ExpiresDefault "access plus 1 weeks"';
			$rules[] = 'Header set Cache-Control "max-age=1209600, public"';
			$rules[] = '</FilesMatch>';
			$rules[] = '<FilesMatch "\.(gif|jpg|jpeg|png|svg|ico|bmp)$">';
			$rules[] = 'ExpiresDefault "access plus 1 weeks"';
			$rules[] = 'Header set Cache-Control "max-age=1209600, public"';
			$rules[] = '</FilesMatch>';
			$rules[] = '<FilesMatch "\.(ttf|otf|woff|eot)$">';
			$rules[] = 'ExpiresDefault "access plus 1 weeks"';
			$rules[] = 'Header set Cache-Control "max-age=1209600, public"';
			$rules[] = '</FilesMatch>';
			$rules[] = '</IfModule>';
		} else {
			$rules = [];
		}

		return insert_with_markers( $htaccess, 'inc2734/wp-page-speed-optimization', $rules );
	}
}
