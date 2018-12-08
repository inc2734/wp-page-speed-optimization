<?php
/**
 * @package inc2734/wp-page-speed-optimization
 * @author inc2734
 * @license GPL-2.0+
 */

namespace Inc2734\WP_Page_Speed_Optimization\App\Controller;

class LazyLoad {

	public function __construct() {
		add_filter( 'wp_get_attachment_image_attributes', [ $this, '_async_attachment_images' ] );
		add_filter( 'the_content', [ $this, '_async_content_images' ] );
	}

	/**
	 * Aync loading of attachment images
	 *
	 * @param array $atts
	 * @return array
	 */
	public function _async_attachment_images( $atts ) {
		if ( ! apply_filters( 'inc2734_wp_page_speed_async_attachment_images', false ) ) {
			return $atts;
		}

		$atts['decoding'] = 'async';
		return $atts;
	}

	/**
	 * Aync loading of content images
	 *
	 * @param string $content
	 * @return string
	 */
	public function _async_content_images( $content ) {
		if ( ! apply_filters( 'inc2734_wp_page_speed_async_content_images', false ) ) {
			return $content;
		}

		if ( ! preg_match_all( '/<img [^>]+>/', $content, $matches ) ) {
			return $content;
		}

		$selected_images = [];
		foreach ( $matches[0] as $image ) {
			if ( false === strpos( $image, ' decoding=' ) && preg_match( '/wp-image-([0-9]+)/i', $image ) ) {
				$selected_images[] = $image;
			}
		}

		foreach ( $selected_images as $image ) {
			$new_image = str_replace( '<img ', '<img decoding="async" ', $image );
			$content = str_replace( $image, $new_image, $content );
		}

		return $content;
	}
}
