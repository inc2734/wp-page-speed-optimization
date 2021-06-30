<?php
/**
 * @package inc2734/wp-page-speed-optimization
 * @author inc2734
 * @license GPL-2.0+
 */

namespace Inc2734\WP_Page_Speed_Optimization\App\Controller;

use Inc2734\WP_Page_Speed_Optimization\App\Model\Defer_Scripts;

class Assets {

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( is_admin() || is_customize_preview() ) {
			return;
		}

		// wp_enqueue_scripts hook is called in wp_head:1
		add_action( 'wp_head', [ $this, '_optimize_jquery_loading' ], 2 );

		// Printing footer scripts in wp_print_footer_scripts:10
		add_action( 'wp_print_footer_scripts', [ $this, '_optimize_jquery_loading_for_footer' ], 9 );

		add_action( 'wp_head', [ $this, '_optimize_snow_monkey_scripts' ], 2 );
		add_filter( 'script_loader_tag', [ $this, '_set_defer' ], 11, 2 );
		add_filter( 'script_loader_tag', [ $this, '_set_async' ], 11, 2 );

		add_filter( 'script_loader_tag', [ $this, '_builded' ], 10, 3 );
		add_filter( 'style_loader_tag', [ $this, '_set_preload_stylesheet' ], 10, 3 );
		add_action( 'wp_footer', [ $this, '_build_stylesheet_link' ], 99999 );
	}

	/**
	 * Add defer to script.
	 */
	public function _optimize_jquery_loading() {
		if ( ! $this->_is_optimize_jquery_loading() ) {
			return;
		}

		if ( in_array( $GLOBALS['pagenow'], [ 'wp-login.php', 'wp-register.php' ], true ) ) {
			return;
		}

		$jquery     = wp_scripts()->query( 'jquery-core', 'registered' );
		$jquery_ver = $jquery->ver;
		$jquery_src = $jquery->src;

		wp_deregister_script( 'jquery' );
		wp_deregister_script( 'jquery-core' );
		wp_register_script( 'jquery', false, [ 'jquery-core' ], $jquery_ver );
		wp_register_script( 'jquery-core', $jquery_src, [], $jquery_ver );

		$defer_scripts = new Defer_Scripts();
		$handles       = $defer_scripts->get();

		foreach ( $handles as $handle ) {
			if ( wp_scripts()->get_data( $handle, 'after' ) ) {
				continue;
			}

			if ( wp_scripts()->get_data( $handle, 'async' ) ) {
				continue;
			}

			wp_scripts()->add_data( $handle, 'defer', true );

			// Remove in_footer
			$dependency = wp_scripts()->query( $handle, 'registered' );
			if ( $dependency ) {
				$dependency->args = null;
				wp_scripts()->add_data( $handle, 'group', null );
			}
		}
	}

	/**
	 * Add defer to script.
	 */
	public function _optimize_jquery_loading_for_footer() {
		if ( ! $this->_is_optimize_jquery_loading() ) {
			return;
		}

		$defer_scripts = new Defer_Scripts();
		$handles       = $defer_scripts->get();

		foreach ( $handles as $handle ) {
			if ( wp_scripts()->get_data( $handle, 'after' ) ) {
				continue;
			}

			if ( wp_scripts()->get_data( $handle, 'async' ) ) {
				continue;
			}

			wp_scripts()->add_data( $handle, 'defer', true );
		}
	}

	/**
	 * defer/async script move to head.
	 */
	public function _optimize_snow_monkey_scripts() {
		$handles = array_merge(
			$this->_get_defer_handles(),
			$this->_get_async_handles()
		);

		if ( ! $handles ) {
			return;
		}

		foreach ( $handles as $handle ) {
			// Remove in_footer
			$dependency = wp_scripts()->query( $handle, 'registered' );
			if ( $dependency ) {
				$dependency->args = null;
				wp_scripts()->add_data( $handle, 'group', null );
			}
		}

		foreach ( $this->_get_defer_handles() as $handle ) {
			wp_scripts()->add_data( $handle, 'defer', true );
		}

		foreach ( $this->_get_async_handles() as $handle ) {
			wp_scripts()->add_data( $handle, 'async', true );
		}
	}

	/**
	 * Set defer.
	 *
	 * @param string $tag    The link tag for the enqueued style.
	 * @param string $handle The style's registered handle.
	 * @return string
	 */
	public function _set_defer( $tag, $handle ) {
		if ( false !== strpos( $tag, ' defer' ) || false !== strpos( $tag, ' async' ) ) {
			return $tag;
		}

		if ( ! wp_scripts()->get_data( $handle, 'defer' ) ) {
			return $tag;
		}

		if ( wp_scripts()->get_data( $handle, 'after' ) ) {
			return $tag;
		}

		return str_replace( ' src', ' defer src', $tag );
	}

	/**
	 * Set async.
	 *
	 * @param string $tag    The link tag for the enqueued style.
	 * @param string $handle The style's registered handle.
	 * @return string
	 */
	public function _set_async( $tag, $handle ) {
		if ( false !== strpos( $tag, ' defer' ) || false !== strpos( $tag, ' async' ) ) {
			return $tag;
		}

		if ( ! wp_scripts()->get_data( $handle, 'async' ) ) {
			return $tag;
		}

		if ( wp_scripts()->get_data( $handle, 'after' ) ) {
			return $tag;
		}

		return str_replace( ' src', ' async src', $tag );
	}

	/**
	 * Re-build script tag.
	 * only in_footer param is true.
	 *
	 * @param string $tag    The link tag for the enqueued style.
	 * @param string $handle The style's registered handle.
	 * @param string $src    The stylesheet's source URL.
	 * @return string
	 */
	public function _builded( $tag, $handle, $src ) {
		$handles = apply_filters( 'inc2734_wp_page_speed_optimization_builded_scripts', [] );
		if ( ! $handles ) {
			return $tag;
		}

		if ( ! in_array( $handle, $handles, true ) ) {
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
	 * Set rel="preload" for stylesheet.
	 *
	 * @param string $tag    The link tag for the enqueued style.
	 * @param string $handle The style's registered handle.
	 * @param string $src    The stylesheet's source URL.
	 * @return string
	 */
	public function _set_preload_stylesheet( $tag, $handle, $src ) {
		$handles = apply_filters( 'inc2734_wp_page_speed_optimization_output_head_styles', [] );
		if ( in_array( $handle, $handles, true ) && 0 === strpos( $src, site_url() ) ) {
			$sitepath = site_url( '', 'relative' );
			$abspath  = untrailingslashit( ABSPATH );

			if ( $sitepath ) {
				$abspath = preg_replace( '|(.*?)' . preg_quote( $sitepath ) . '$|', '$1', $abspath );
			}

			$target = $abspath . str_replace( site_url(), '', $src );
			if ( false !== strpos( $target, '?' ) ) {
				$exploded_target = explode( '?', $target );
				$target          = $exploded_target[0];
			}
			if ( ! file_exists( $target ) ) {
				return $tag;
			}

			$buffer = \file_get_contents( $target );
			if ( ! $buffer ) {
				return $tag;
			}

			$parse  = parse_url( $src );
			$buffer = preg_replace( '|(url\(\s*?[\'"]?)./|', '$1' . dirname( $parse['path'] ) . '/', $buffer );
			$buffer = preg_replace( '|(url\(\s*?[\'"]?)../|', '$1' . dirname( $parse['path'] ) . '/../', $buffer );
			$buffer = preg_replace( '|(url\(\s*?[\'"]?)//|', '$1/', $buffer );
			$buffer = str_replace( [ "\n\r", "\n", "\r", "\t" ], '', $buffer );
			$buffer = preg_replace( '|{\s*|', '{', $buffer );
			$buffer = preg_replace( '|}\s*|', '}', $buffer );
			$buffer = preg_replace( '|;\s*|', ';', $buffer );
			$buffer = preg_replace( '|@charset .+?;|', '', $buffer );
			$buffer = preg_replace( '|/\*.*?\*/|', '', $buffer );

			$media = preg_match( '|media=\'([^\']*?)\'|', $tag, $match )
				? $match[1]
				: 'all';
			?>
			<!-- <?php echo $tag; // xss ok. ?> -->
			<style media="<?php echo esc_attr( $media ); ?>"><?php echo $buffer; // xss ok. ?></style>
			<?php
			return;
		}

		$handles = apply_filters( 'inc2734_wp_page_speed_optimization_preload_stylesheets', [] );
		if ( in_array( $handle, $handles, true ) ) {
			?>
			<!-- <?php echo $tag; // xss ok. ?> -->
			<?php
			$preload_for_legacy = str_replace( 'media=\'all\'', 'media="print"', $tag );
			$preload_for_legacy = str_replace( '/>', 'onload="this.media=\'all\'" />', $preload_for_legacy );
			$preload_for_modern = str_replace( 'rel=\'stylesheet\'', 'rel="preload"', $tag );
			$preload_for_modern = str_replace( '/>', 'as="style" />', $preload_for_modern );
			return $preload_for_legacy . $preload_for_modern;
		}

		return $tag;
	}

	/**
	 * Builed stylesheet link tag.
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
	 * Return defer script handles.
	 *
	 * @return array
	 */
	protected function _is_optimize_jquery_loading() {
		return apply_filters( 'inc2734_wp_page_speed_optimization_optimize_jquery_loading', false );
	}

	/**
	 * Return defer script handles.
	 *
	 * @return array
	 */
	protected function _get_defer_handles() {
		return apply_filters( 'inc2734_wp_page_speed_optimization_defer_scripts', [] );
	}

	/**
	 * Return async script handles.
	 *
	 * @return array
	 */
	protected function _get_async_handles() {
		return apply_filters( 'inc2734_wp_page_speed_optimization_async_scripts', [] );
	}
}
