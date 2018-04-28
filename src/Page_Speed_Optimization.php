<?php
/**
 * @package inc2734/wp-page-speed-optimization
 * @author inc2734
 * @license GPL-2.0+
 */

namespace Inc2734\WP_Page_Speed_Optimization;

class Page_Speed_Optimization {

	public function __construct() {
		add_filter( 'script_loader_tag', [ $this, '_set_defer' ], 10, 3 );
		add_filter( 'script_loader_tag', [ $this, '_set_async' ], 10, 3 );
		add_action( 'wp_enqueue_scripts', [ $this, '_http2_server_push' ], 99999 );
	}

	/**
	 * Set defer
	 *
	 * @param string $tag
	 * @param string handle
	 * @param string src
	 * @return string
	 */
	public function _set_defer( $tag, $handle, $src ) {
		$handles = apply_filters( 'inc2734_wp_page_speed_optimization_defer_scripts', [] );

		if ( ! in_array( $handle, $handles ) ) {
			return $tag;
		}

		return str_replace( ' src', ' defer src', $tag );
	}

	/**
	 * Set async
	 *
	 * @param string $tag
	 * @param string handle
	 * @param string src
	 * @return string
	 */
	public function _set_async( $tag, $handle, $src ) {
		$handles = apply_filters( 'inc2734_wp_page_speed_optimization_async_scripts', [] );

		if ( ! in_array( $handle, $handles ) ) {
			return $tag;
		}

		return str_replace( ' src', ' async src', $tag );
	}

	/**
	 * Send header
	 *
	 * @return void
	 */
	public function _http2_server_push() {
		$this->_send_http2_server_push_header( wp_styles(), 'style' );
		$this->_send_http2_server_push_header( wp_scripts(), 'script' );
	}

	/**
	 * Send header
	 *
	 * @param WP_Dependences $wp_dependences
	 * @param string $type style|script
	 * @return void
	 */
	protected function _send_http2_server_push_header( $wp_dependences, $type ) {
		global $wp_version;

		$registerd = $wp_dependences->registered;
		$wp_dependences->all_deps( $wp_dependences->queue );

		$handles = $wp_dependences->to_do;
		$handles = apply_filters( 'inc2734_wp_page_speed_optimization_http2_server_push_handles', $handles, $type );

		if ( ! $handles ) {
			return;
		}

		foreach ( $handles as $handle ) {
			if ( ! isset( $registerd[ $handle ] ) ) {
				continue;
			}

			$src = $registerd[ $handle ]->src;
			if ( ! $src ) {
				continue;
			}

			$ver = $registerd[ $handle ]->ver ? $registerd[ $handle ]->ver : $wp_version;
			$src = add_query_arg( 'ver', $ver, $src );

			header( sprintf( 'Link: <%s>; rel=preload; as=%s', esc_url_raw( $src ), $type ), false );
		}
	}
}
