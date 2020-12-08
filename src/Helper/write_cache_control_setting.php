<?php
/**
 * @package inc2734/wp-page-speed-optimization
 * @author inc2734
 * @license GPL-2.0+
 */

namespace Inc2734\WP_Page_Speed_Optimization\Helper;

/**
 * Write cache control setting into .htaccess.
 *
 * @param bool $enable Wether true if you want to enable it.
 * @return int|false bytes.
 */
function write_cache_control_setting( $enable ) {
	if ( ! function_exists( 'get_home_path' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
	}

	$home_path = get_home_path();
	$htaccess  = $home_path . '.htaccess';

	if ( ! file_exists( $htaccess ) || ! is_writable( $home_path ) || ! is_writable( $htaccess ) ) {
		return false;
	}

	if ( $enable ) {
		$rules   = [];
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
