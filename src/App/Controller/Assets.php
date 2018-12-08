<?php
/**
 * @package inc2734/wp-page-speed-optimization
 * @author inc2734
 * @license GPL-2.0+
 */

namespace Inc2734\WP_Page_Speed_Optimization\App\Controller;

class Assets {

	public function __construct() {
		add_filter( 'script_loader_tag', [ $this, '_set_defer' ], 10, 3 );
		add_filter( 'script_loader_tag', [ $this, '_set_async' ], 10, 3 );
		add_filter( 'script_loader_tag', [ $this, '_builded' ], 10, 3 );
		add_action( 'init', [ $this, '_optimize_jquery_loading' ] );

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

		return sprintf(
			'<script>
			document.addEventListener("DOMContentLoaded", function(event) {
				var s=document.createElement("script");
				s.src="%s";
				s.async=true;document.body.appendChild(s);
			});
			</script>',
			$src
		);
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
		if ( in_array( $handle, $handles ) && 0 === strpos( $src, site_url() ) ) {
			$sitepath = site_url( '', 'relative' );
			$abspath  = untrailingslashit( ABSPATH );

			if ( $sitepath ) {
				$abspath = preg_replace( '|(.*?)' . preg_quote( $sitepath ) . '$|', '$1', $abspath );
			}

			$parse  = parse_url( $src );
			$buffer = \file_get_contents( $abspath . $parse['path'] );
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

	/**
	 * Optimize jQuery loading
	 *
	 * jQuery loads in footer and Invalidate jquery-migrate
	 *
	 * @return void
	 */
	public function _optimize_jquery_loading() {
		$optimize_loading = apply_filters( 'inc2734_wp_page_speed_optimization_optimize_jquery_loading', false );
		if ( ! $optimize_loading ) {
			return;
		}

		if ( is_admin() || in_array( $GLOBALS['pagenow'], [ 'wp-login.php', 'wp-register.php' ] ) ) {
			return;
		}

		global $wp_scripts;

		$jquery = $wp_scripts->registered['jquery-core'];
		$jquery_ver = $jquery->ver;
		$jquery_src = $jquery->src;

		wp_deregister_script( 'jquery' );
		wp_deregister_script( 'jquery-core' );

		wp_register_script( 'jquery', false, [ 'jquery-core' ], $jquery_ver, true );
		wp_register_script( 'jquery-core', $jquery_src, [], $jquery_ver, true );
	}
}
