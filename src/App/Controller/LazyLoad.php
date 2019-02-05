<?php
/**
 * @package inc2734/wp-page-speed-optimization
 * @author inc2734
 * @license GPL-2.0+
 */

namespace Inc2734\WP_Page_Speed_Optimization\App\Controller;

class LazyLoad {

	public function __construct() {
		add_action( 'after_setup_theme', [ $this, '_add_minimum_thumanil' ] );
		add_action( 'wp_enqueue_scripts', [ $this, '_wp_enqueue_scripts' ] );
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
	 * Add dummy image size
	 *
	 * @return void
	 */
	public function _add_minimum_thumanil() {
		add_image_size( 'wppso-minimum-thumbnail', 100, 100, false );
	}

	/**
	 * Enqueue assets
	 *
	 * @return void
	 */
	public function _wp_enqueue_scripts() {
		if ( ! $this->_is_async_attachment_images() && ! $this->_is_async_content_images() ) {
			return;
		}

		$relative_path = '/vendor/inc2734/wp-page-speed-optimization/src/assets/js/lazyload.min.js';
		wp_enqueue_script(
			'wp-page-speed-optimization-lazyload',
			get_template_directory_uri() . $relative_path,
			[],
			filemtime( get_template_directory() . $relative_path ),
			true
		);
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

		$atts['data-src'] = $atts['src'];
		$atts['src']      = wp_get_attachment_image_url( $attachment->ID, 'wppso-minimum-thumbnail' );

		if ( isset( $atts['srcset'] ) ) {
			$atts['data-srcset'] = $atts['srcset'];
			$atts['srcset']      = null;
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

		foreach ( $selected_images as $image_id => $images ) {
			foreach ( $images as $image ) {
				$new_image = $this->_add_decoding_to_content_image( $image );
				$new_image = $this->_add_data_src_to_content_image( $new_image, $image_id );
				$new_image = $this->_add_data_srcset_to_content_image( $new_image, $image_id );

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

	/**
	 * Add data-src to content image
	 *
	 * @param string $image
	 * @param int $image_id
	 * @return string
	 */
	protected function _add_data_src_to_content_image( $image, $image_id ) {
		return preg_replace_callback(
			'@(<img decoding="async"[^>]*?) src="([^"]+?)"([^>]*?>)@m',
			function( $matches ) use ( $image_id ) {
				return sprintf(
					'%s src="%s" data-src="%s" %s',
					$matches[1],
					wp_get_attachment_image_url( $image_id, 'wppso-minimum-thumbnail' ),
					$matches[2],
					$matches[3]
				);
			},
			$image
		);
	}

	/**
	 * Add data-srcset to content image
	 *
	 * @param string $image
	 * @param int $image_id
	 * @return string
	 */
	protected function _add_data_srcset_to_content_image( $image, $image_id ) {
		return preg_replace_callback(
			'@(<img decoding="async"[^>]*?)srcset="([^"]+?)"([^>]*?>)@m',
			function( $matches ) use ( $image_id ) {
				return sprintf(
					'%s srcset="" data-srcset="%s" %s',
					$matches[1],
					$matches[2],
					$matches[3]
				);
			},
			$image
		);
	}
}
