<?php
/**
 * @package inc2734/wp-page-speed-optimization
 * @author inc2734
 * @license GPL-2.0+
 */

namespace Inc2734\WP_Page_Speed_Optimization\App\Controller;

class Menu {

	public function __construct() {
		add_action( 'wp_update_nav_menu', [ $this, '_wp_update_nav_menu' ] );
		add_filter( 'wp_nav_menu', [ $this, '_set_cache' ], 10, 2 );
		add_filter( 'pre_wp_nav_menu', [ $this, '_pre_wp_nav_menu' ], 10, 2 );
		add_action( 'customize_save', [ $this, '_customize_save' ] );
		add_filter( 'wp_nav_menu_objects', [ $this, '_remove_current_classes' ], 10, 2 );
	}

	/**
	 * Delete cache on customizer
	 *
	 * @param WP_Customize_Manager $manager
	 * @return void
	 */
	public function _customize_save( $manager ) {
		if ( ! $this->_is_caching_nav_menus() ) {
			return;
		}

		$this->_delete_all_cache();
	}

	/**
	 * Delete cache
	 * On customizer, not deleted cache ( Not fired )
	 *
	 * @param int $menu_id
	 * @return void
	 */
	public function _wp_update_nav_menu( $menu_id ) {
		if ( ! $this->_is_caching_nav_menus() ) {
			return;
		}

		$this->_delete_all_cache();
	}

	/**
	 * Set cache
	 *
	 * @param string $nav_menu HTML
	 * @param array $args
	 * @return string
	 */
	public function _set_cache( $nav_menu, $args ) {
		if ( ! $this->_is_caching_nav_menus() ) {
			return $nav_menu;
		}

		if ( $this->_is_caching_nav_menu( $args->theme_location ) ) {
			set_transient( $this->_get_transient_id( $args->theme_location ), $nav_menu, HOUR_IN_SECONDS );
		}

		return $nav_menu;
	}

	/**
	 * Delete all cache
	 *
	 * @return void
	 */
	protected function _delete_all_cache() {
		$locations = get_registered_nav_menus();

		if ( $locations && is_array( $locations ) ) {
			$locations = array_keys( $locations );
			foreach ( $locations as $location_id ) {
				if ( ! $this->_is_caching_nav_menu( $location_id ) ) {
					continue;
				}

				delete_transient( $this->_get_transient_id( $location_id ) );
			}
		}
	}

	/**
	 * Output nav menu
	 *
	 * @param string $output HTML
	 * @param array $args
	 * @return string
	 */
	public function _pre_wp_nav_menu( $output, $args ) {
		if ( is_customize_preview() ) {
			return $output;
		}

		// For the nav menu widget.
		if ( ! $args->theme_location ) {
			return $output;
		}

		if ( ! $this->_is_caching_nav_menus() ) {
			return $output;
		}

		if ( ! $this->_is_caching_nav_menu( $args->theme_location ) ) {
			return $output;
		}

		$transient = get_transient( $this->_get_transient_id( $args->theme_location ) );
		if ( false !== $transient ) {
			return '<!-- Cached menu ' . $args->theme_location . ' -->' . $transient . '<!-- /Cached menu ' . $args->theme_location . ' -->';
		}

		return $output;
	}

	/**
	 * Remove current classes
	 *
	 * @param string $items The menu items, sorted by each menu item's menu order.
	 * @param stdClass $args  An object containing wp_nav_menu() arguments.
	 * @return string
	 */
	public function _remove_current_classes( $items, $args ) {
		if ( is_customize_preview() ) {
			return $items;
		}

		if ( ! static::_is_caching_nav_menus() ) {
			return $items;
		}

		if ( ! $this->_is_caching_nav_menu( $args->theme_location ) ) {
			return $items;
		}

		foreach ( $items as $items_index => $item ) {
			foreach ( $item->classes as $index => $class ) {
				if ( false === strpos( $class, 'current' ) ) {
					continue;
				}
				unset( $items[ $items_index ]->classes[ $index ] );
			}
		}
		return $items;
	}

	/**
	 * Create and return transient id
	 *
	 * @param string $location
	 * @return string
	 */
	protected function _get_transient_id( $location ) {
		return '_nav_menu_' . $location;
	}

	/**
	 * return true when caching
	 *
	 * @return boolean
	 */
	protected function _is_caching_nav_menus() {
		return apply_filters( 'inc2734_wp_page_speed_optimization_caching_nav_menus', false );
	}

	/**
	 * return true when caching
	 *
	 * @param string $location
	 * @return boolean
	 */
	protected function _is_caching_nav_menu( $location ) {
		return apply_filters( 'inc2734_wp_page_speed_optimization_caching_nav_menus', static::_is_caching_nav_menus(), $location );
	}
}
