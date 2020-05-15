<?php
/**
 * @package inc2734/wp-page-speed-optimization
 * @author inc2734
 * @license GPL-2.0+
 */

namespace Inc2734\WP_Page_Speed_Optimization\App\Controller;

class Assets {

	/**
	 * The handle of scripts that depends on jQuery
	 *
	 * @var array
	 */
	protected $jquery_depend_handles = [
		'jquery'      => 'jquery',
		'jquery-core' => 'jquery-core',
	];

	public function __construct() {
		if ( is_admin() ) {
			return;
		}

		add_action( 'wp_head', [ $this, '_optimize_jquery_loading' ], 2 );
		add_filter( 'script_loader_tag', [ $this, '_set_defer_jquery_depend' ], 10, 3 );

		add_action( 'wp_head', [ $this, '_update_in_footer' ], 2 );
		add_filter( 'script_loader_tag', [ $this, '_set_defer' ], 10, 3 );
		add_filter( 'script_loader_tag', [ $this, '_set_async' ], 10, 3 );

		add_filter( 'script_loader_tag', [ $this, '_builded' ], 10, 3 );
		add_filter( 'style_loader_tag', [ $this, '_set_preload_stylesheet' ], 10, 3 );
		add_action( 'wp_footer', [ $this, '_build_stylesheet_link' ], 99999 );
	}

	/**
	 * Optimize jQuery loading
	 *
	 * jQuery loads in footer and Invalidate jquery-migrate
	 *
	 * @return void
	 */
	public function _optimize_jquery_loading() {
		if ( ! $this->_is_optimize_jquery_loading() ) {
			return;
		}

		if ( is_admin() || in_array( $GLOBALS['pagenow'], [ 'wp-login.php', 'wp-register.php' ] ) ) {
			return;
		}

		$scripts = wp_scripts();

		$jquery = $scripts->registered['jquery-core'];
		$jquery_ver = $jquery->ver;
		$jquery_src = $jquery->src;

		wp_deregister_script( 'jquery' );
		wp_deregister_script( 'jquery-core' );
		wp_register_script( 'jquery', false, [ 'jquery-core' ], $jquery_ver, true );
		wp_register_script( 'jquery-core', $jquery_src, [], $jquery_ver, true );

		foreach ( $scripts->registered as $handle => $dependency ) {
			if ( in_array( 'jquery', $dependency->deps ) ) {
				$this->jquery_depend_handles[ $handle ] = $handle;
			}
		}
	}

	/**
	 * Set defer for scripts that depends on jQuery
	 *
	 * @param string $tag
	 * @param string handle
	 * @param string src
	 * @return string
	 */
	public function _set_defer_jquery_depend( $tag, $handle, $src ) {
		if ( ! $this->_is_optimize_jquery_loading() ) {
			return $tag;
		}

		if ( false !== strpos( $tag, ' defer' ) || false !== strpos( $tag, ' async' ) ) {
			return $tag;
		}

		if ( ! in_array( $handle, $this->jquery_depend_handles ) ) {
			return $tag;
		}

		$this->jquery_depend_handles[ $handle ] = $handle;
		return str_replace( ' src', ' defer src', $tag );
	}

	/**
	 * defer/async script move to head
	 *
	 * @return void
	 */
	public function _update_in_footer() {
		$handles = [];
		$handles = array_merge(
			$handles,
			$this->_get_defer_handles(),
			$this->_get_async_handles()
		);

		if ( ! $handles ) {
			return;
		}

		$scripts = wp_scripts();

		foreach ( $handles as $handle ) {
			if ( isset( $scripts->registered[ $handle ] ) ) {
				if ( ! empty( $scripts->registered[ $handle ]->extra['group'] ) ) {
					$scripts->registered[ $handle ]->extra['group'] = 0;
				}
			}
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
		if ( ! $this->_get_defer_handles() ) {
			return $tag;
		}

		if ( false !== strpos( $tag, ' defer' ) || false !== strpos( $tag, ' async' ) ) {
			return $tag;
		}

		if ( ! in_array( $handle, $this->_get_defer_handles() ) ) {
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
		if ( ! $this->_get_async_handles() ) {
			return $tag;
		}

		if ( false !== strpos( $tag, ' defer' ) || false !== strpos( $tag, ' async' ) ) {
			return $tag;
		}

		if ( ! in_array( $handle, $this->_get_async_handles() ) ) {
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
		if ( ! $handles ) {
			return $tag;
		}

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
		if ( in_array( $handle, $handles ) ) {
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
	 * Return defer script handles
	 *
	 * @return array
	 */
	protected function _is_optimize_jquery_loading() {
		return apply_filters( 'inc2734_wp_page_speed_optimization_optimize_jquery_loading', false );
	}

	/**
	 * Return defer script handles
	 *
	 * @return array
	 */
	protected function _get_defer_handles() {
		return apply_filters( 'inc2734_wp_page_speed_optimization_defer_scripts', [] );
	}

	/**
	 * Return async script handles
	 *
	 * @return array
	 */
	protected function _get_async_handles() {
		return apply_filters( 'inc2734_wp_page_speed_optimization_async_scripts', [] );
	}
}
