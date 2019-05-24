<?php
/**
 * @package inc2734/wp-page-speed-optimization
 * @author inc2734
 * @license GPL-2.0+
 */

namespace Inc2734\WP_Page_Speed_Optimization\App\Controller;

class LazyLoad {

	public function __construct() {
		add_filter( 'wp_kses_allowed_html', [ $this, '_allow_decoding' ], 10, 2 );
		add_filter( 'wp_get_attachment_image_attributes', [ $this, '_async_attachment_images' ], 10, 3 );
		add_filter( 'the_content', [ $this, '_async_content_images' ] );
	}

	/**
	 * Return true when async attachment images
	 *
	 * @return boolean
	 */
	protected function _is_async_attachment_images() {
		return apply_filters( 'inc2734_wp_page_speed_async_attachment_images', false );
	}

	/**
	 * Return true when async content images
	 *
	 * @return boolean
	 */
	protected function _is_async_content_images() {
		return apply_filters( 'inc2734_wp_page_speed_async_content_images', false );
	}

	/**
	 * Allow img[decoding]
	 *
	 * @param array $tags
	 * @param string $context
	 * @return array
	 */
	public function _allow_decoding( $tags, $context ) {
		if ( 'post' !== $context ) {
			return $tags;
		}

		$tags['img'] = array_merge( $tags['img'], [ 'decoding' => true ] );
		return $tags;
	}

	/**
	 * Aync loading of attachment images
	 *
	 * @param array $atts
	 * @param WP_Post $attachment
	 * @param string|array $size
	 * @return array
	 */
	public function _async_attachment_images( $atts, $attachment, $size ) {
		if ( ! $this->_is_async_attachment_images() ) {
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
		if ( ! $this->_is_async_content_images() ) {
			return $content;
		}

		if ( ! preg_match_all( '/<img [^>]+>/', $content, $matches ) ) {
			return $content;
		}

		$selected_images = [];
		foreach ( $matches[0] as $image ) {
			if ( false === strpos( $image, ' decoding=' ) && preg_match( '/wp-image-([0-9]+)/i', $image, $reg ) ) {
				$selected_images[ $reg[1] ][] = $image;
			}
		}

		foreach ( $selected_images as $images ) {
			foreach ( $images as $image ) {
				$new_image = $this->_add_decoding_to_content_image( $image );
				$content = str_replace( $image, $new_image, $content );
			}
		}

		return $content;
	}

	/**
	 * Add decoding to content image
	 *
	 * @param string $image
	 * @return string
	 */
	protected function _add_decoding_to_content_image( $image ) {
		return str_replace( '<img ', '<img decoding="async" ', $image );
	}
}
