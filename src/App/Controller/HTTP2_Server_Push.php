<?php
/**
 * @package inc2734/wp-page-speed-optimization
 * @author inc2734
 * @license GPL-2.0+
 */

namespace Inc2734\WP_Page_Speed_Optimization\App\Controller;

class HTTP2_Server_Push {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'send_headers', [ $this, '_http2_server_push' ], 99999 );
	}

	/**
	 * Send header.
	 */
	public function _http2_server_push() {
		if ( headers_sent() ) {
			return;
		}

		$do_http2_server_push = apply_filters( 'inc2734_wp_page_speed_optimization_do_http2_server_push', false );
		if ( ! $do_http2_server_push ) {
			return;
		}

		$this->_send_http2_server_push_header( wp_styles(), 'style' );
		$this->_send_http2_server_push_header( wp_scripts(), 'script' );
	}

	/**
	 * Send header.
	 *
	 * @param WP_Dependences $wp_dependences WP_Styles|WP_Scripts instance.
	 * @param string         $type           style|script.
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
