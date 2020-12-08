<?php
/**
 * @package inc2734/wp-page-speed-optimization
 * @author inc2734
 * @license GPL-2.0+
 */

namespace Inc2734\WP_Page_Speed_Optimization\App\Controller;

class Sidebars {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'widget_update_callback', [ $this, '_widget_update_callback' ], 10, 4 );
		add_action( 'customize_save', [ $this, '_customize_save' ] );
		add_action( 'save_post', [ $this, '_save_post' ] );
	}

	/**
	 * Delete cache on customizer.
	 *
	 * @param WP_Customize_Manager $manager WP_Customize_Manager instance.
	 */
	public function _customize_save(
		// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$manager
		// phpcs:enable
	) {
		if ( ! static::_is_caching_sidebars() ) {
			return;
		}

		$this->_delete_all_cache();
	}

	/**
	 * Delete cache on post saving.
	 *
	 * @param int $post_id The post Id.
	 */
	public function _save_post(
		// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$post_id
		// phpcs:enable
	) {
		if ( ! static::_is_caching_sidebars() ) {
			return;
		}

		$this->_delete_all_cache();
	}

	/**
	 * Delete all cache.
	 */
	protected function _delete_all_cache() {
		$sidebars_widgets = wp_get_sidebars_widgets();
		$sidebars         = array_keys( $sidebars_widgets );

		foreach ( $sidebars as $sidebar_id ) {
			if ( ! static::_is_caching_sidebar( $sidebar_id ) ) {
				continue;
			}

			delete_transient( static::get_transient_id( $sidebar_id ) );
		}
	}

	/**
	 * Delete cache.
	 * On customizer, not deleted cache ( But fired ).
	 *
	 * @param array     $instance     The current widget instance's settings.
	 * @param array     $new_instance Array of new widget settings.
	 * @param array     $old_instance Array of old widget settings.
	 * @param WP_Widget $wp_widget    The current widget instance.
	 * @return array
	 */
	public function _widget_update_callback( $instance, $new_instance, $old_instance, $wp_widget ) {
		if ( ! static::_is_caching_sidebars() ) {
			return $instance;
		}

		$sidebars_widgets = wp_get_sidebars_widgets();
		foreach ( $sidebars_widgets as $_sidebar_id => $widgets ) {
			$widgets = array_flip( $widgets );
			if ( isset( $widgets[ $wp_widget->id ] ) ) {
				$sidebar_id = $_sidebar_id;
			}
		}

		if ( empty( $sidebar_id ) ) {
			return $instance;
		}

		if ( ! static::_is_caching_sidebar( $sidebar_id ) ) {
			return $instance;
		}

		$transient_id = static::get_transient_id( $sidebar_id );
		delete_transient( $transient_id );

		return $instance;
	}

	/**
	 * dynamic_sidebar() corresponding to cache.
	 *
	 * @param string $sidebar_id The sidebar Id.
	 */
	public static function dynamic_sidebar( $sidebar_id ) {
		if ( ! static::_is_caching_sidebars() ) {
			dynamic_sidebar( $sidebar_id );
			return;
		}

		if ( ! static::_is_caching_sidebar( $sidebar_id ) ) {
			dynamic_sidebar( $sidebar_id );
			return;
		}

		if ( is_customize_preview() ) {
			dynamic_sidebar( $sidebar_id );
			return;
		}

		$transient_id = static::get_transient_id( $sidebar_id );
		$transient    = get_transient( $transient_id );

		if ( false === $transient ) {
			ob_start();
			dynamic_sidebar( $sidebar_id );
			$transient = ob_get_clean();
			set_transient( $transient_id, $transient, HOUR_IN_SECONDS );
		}

		// @codingStandardsIgnoreStart
		echo '<!-- Cached sidebar ' . $sidebar_id . ' -->' . $transient . '<!-- /Cached sidebar ' . $sidebar_id . ' -->';
		// @codingStandardsIgnoreEnd
	}

	/**
	 * Create and return transient id.
	 *
	 * @param string $sidebar_id The sidebar Id.
	 * @return string
	 */
	protected static function get_transient_id( $sidebar_id ) {
		return '_sidebar_' . base64_encode( pack( 'H*', sha1( $sidebar_id ) ) );
	}

	/**
	 * return true when caching.
	 *
	 * @return boolean
	 */
	protected static function _is_caching_sidebars() {
		return apply_filters( 'inc2734_wp_page_speed_optimization_caching_sidebars', false );
	}

	/**
	 * return true when caching.
	 *
	 * @param string $sidebar_id The sidebar Id.
	 * @return boolean
	 */
	protected static function _is_caching_sidebar( $sidebar_id ) {
		return apply_filters( 'inc2734_wp_page_speed_optimization_caching_sidebar', static::_is_caching_sidebars(), $sidebar_id );
	}
}
