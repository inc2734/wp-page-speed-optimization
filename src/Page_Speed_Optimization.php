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
		add_filter( 'script_loader_tag', [ $this, '_builded' ], 10, 3 );
		add_action( 'send_headers', [ $this, '_http2_server_push' ], 99999 );

		if ( ! is_admin() ) {
			add_filter( 'style_loader_tag', [ $this, '_set_preload_stylesheet' ], 10, 3 );
			add_action( 'wp_footer', [ $this, '_build_stylesheet_link' ], 99999 );
		}
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
	 * Re-build script tag
	 * only in_footer param is true
	 *
	 * @param string $tag
	 * @param string handle
	 * @param string src
	 * @return string
	 */
	public function _builded( $tag, $handle, $src ) {
		$handles = apply_filters( 'inc2734_wp_page_speed_optimization_builded_scripts', [] );

		if ( ! in_array( $handle, $handles ) ) {
			return $tag;
		}

		return sprintf( '<script>var s=document.createElement("script");s.src="%s";s.async=true;document.body.appendChild(s);</script>', $src );
	}

	/**
	 * Send header
	 *
	 * @return void
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

	/**
	 * Set rel="preload" for stylesheet
	 *
	 * @param string $tag
	 * @param string handle
	 * @param string src
	 * @return string
	 */
	public function _set_preload_stylesheet( $tag, $handle, $src ) {
		$handles = apply_filters( 'inc2734_wp_page_speed_optimization_output_head_styles', [] );
		if ( in_array( $handle, $handles ) && 0 === strpos( $src, home_url() ) ) {
			$parse = parse_url( $src );
			$buffer = \file_get_contents( ABSPATH . $parse['path'] );
			$buffer = str_replace( 'url(../', 'url(' . dirname( $parse['path'] ) . '/../', $buffer );
			$buffer = str_replace( 'url(//', 'url(/', $buffer );
			// @codingStandardsIgnoreStart
			?>
			<style><?php echo $buffer; ?></style>
			<?php
			// @codingStandardsIgnoreEnd
			return;
		}

		$handles = apply_filters( 'inc2734_wp_page_speed_optimization_preload_stylesheets', [] );
		if ( in_array( $handle, $handles ) ) {
			return str_replace( '\'stylesheet\'', '\'preload\' as="style"', $tag );
		}

		return $tag;
	}

	/**
	 * Builed stylesheet link tag
	 *
	 * @return void
	 */
	public function _build_stylesheet_link() {
		if ( ! apply_filters( 'inc2734_wp_page_speed_optimization_preload_stylesheets', [] ) ) {
			return;
		}

		// @codingStandardsIgnoreStart
		?>
<script>
var l = document.getElementsByTagName('link');
for (var i = l.length - 1; i > 0; i--) {
if ('style' === l[i].getAttribute('as') && 'preload' === l[i].getAttribute('rel')) {
var s = document.createElement('link');
s.setAttribute('rel', 'stylesheet');
s.setAttribute('href', l[i].getAttribute('href'));
s.setAttribute('media', l[i].getAttribute('media'));
document.head.appendChild(s);
}
}
</script>
		<?php
		// @codingStandardsIgnoreEnd
	}
}
