<?php
/**
 * @package inc2734/wp-page-speed-optimization
 * @author inc2734
 * @license GPL-2.0+
 */

namespace Inc2734\WP_Page_Speed_Optimization\App\Controller;

class LazyLoad {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'wp_kses_allowed_html', [ $this, '_allow_decoding' ], 10, 2 );
		add_filter( 'wp_kses_allowed_html', [ $this, '_allow_loading' ], 10, 2 );
		add_filter( 'post_thumbnail_html', [ $this, '_async_thumbnail' ], 10 );
		add_filter( 'post_thumbnail_html', [ $this, '_lazyload_thumbnail' ], 10 );
		add_filter( 'wp_get_attachment_image_attributes', [ $this, '_async_attachment_images' ] );
		add_filter( 'the_content', [ $this, '_async_content_images' ] );
		add_filter( 'the_content', [ $this, '_lazyload_content_images' ] );
	}

	/**
	 * Return true when async attachment images.
	 *
	 * @return boolean
	 */
	protected function _is_async_attachment_images() {
		return apply_filters( 'inc2734_wp_page_speed_async_attachment_images', false );
	}

	/**
	 * Return true when async content images.
	 *
	 * @return boolean
	 */
	protected function _is_async_content_images() {
		return apply_filters( 'inc2734_wp_page_speed_async_content_images', false );
	}

	/**
	 * Allow img[decoding].
	 *
	 * @param array  $tags    Context to judge allowed tags by.
	 * @param string $context Context name.
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
	 * Allow img[loading].
	 *
	 * @param array  $tags    Context to judge allowed tags by.
	 * @param string $context Context name.
	 * @return array
	 */
	public function _allow_loading( $tags, $context ) {
		if ( 'post' !== $context ) {
			return $tags;
		}

		$tags['img'] = array_merge( $tags['img'], [ 'loading' => true ] );
		return $tags;
	}

	/**
	 * Async decoding of custom thumbnail.
	 *
	 * @param string $html The post thumbnail HTML.
	 * @return string
	 */
	public function _async_thumbnail( $html ) {
		if ( ! $this->_is_async_attachment_images() ) {
			return $html;
		}

		if ( $html && false === strpos( $html, ' decoding=' ) ) {
			$html = $this->_add_decoding_to_content_image( $html );
		}

		return $html;
	}

	/**
	 * Lazyloade decoding of custom thumbnail.
	 *
	 * @param string $html The post thumbnail HTML.
	 * @return string
	 */
	public function _lazyload_thumbnail( $html ) {
		if ( ! $this->_is_async_attachment_images() ) {
			return $html;
		}

		if ( $html && false === strpos( $html, ' loading=' ) ) {
			$html = $this->_add_loading_to_content_image( $html );
		}

		return $html;
	}

	/**
	 * Aync decoding of attachment images.
	 *
	 * @param array $atts Array of attribute values for the image markup, keyed by attribute name. See wp_get_attachment_image().
	 * @return array
	 */
	public function _async_attachment_images( $atts ) {
		if ( ! $this->_is_async_attachment_images() ) {
			return $atts;
		}

		$atts['decoding'] = 'async';
		$atts['loading']  = 'lazy';
		return $atts;
	}

	/**
	 * Aync decoding of content images.
	 *
	 * @param string $content The content.
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
				$selected_images[ $reg[1] ] = $image;
			}
		}

		foreach ( $selected_images as $image ) {
			$new_image = $this->_add_decoding_to_content_image( $image );
			$content   = str_replace( $image, $new_image, $content );
		}

		return $content;
	}

	/**
	 * Lazy loading of content images.
	 *
	 * @param string $content The content.
	 * @return string
	 */
	public function _lazyload_content_images( $content ) {
		if ( ! $this->_is_async_content_images() ) {
			return $content;
		}

		if ( ! preg_match_all( '/<img [^>]+>/', $content, $matches ) ) {
			return $content;
		}

		$selected_images = [];
		foreach ( $matches[0] as $image ) {
			if ( false === strpos( $image, ' loading=' ) && preg_match( '/wp-image-([0-9]+)/i', $image, $reg ) ) {
				$selected_images[ $reg[1] ] = $image;
			}
		}

		foreach ( $selected_images as $image ) {
			$new_image = $this->_add_loading_to_content_image( $image );
			$content   = str_replace( $image, $new_image, $content );
		}

		return $content;
	}

	/**
	 * Add decoding to content image.
	 *
	 * @param string $image The img tag.
	 * @return string
	 */
	protected function _add_decoding_to_content_image( $image ) {
		return str_replace( '<img ', '<img decoding="async" ', $image );
	}

	/**
	 * Add loading to content image.
	 *
	 * @param string $image The img tag.
	 * @return string
	 */
	protected function _add_loading_to_content_image( $image ) {
		return str_replace( '<img ', '<img loading="lazy" ', $image );
	}
}
