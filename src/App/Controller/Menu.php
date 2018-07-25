<?php
/**
 * @package inc2734/wp-page-speed-optimization
 * @author inc2734
 * @license GPL-2.0+
 */

namespace Inc2734\WP_Page_Speed_Optimization\App\Controller;

class Menu {

	public function __construct() {
		add_action( 'wp_update_nav_menu', [ $this, '_delete_cache' ] );
		add_filter( 'wp_nav_menu', [ $this, '_set_cache' ], 10, 2 );
		add_filter( 'pre_wp_nav_menu', [ $this, '_pre_wp_nav_menu' ], 10, 2 );
		add_action( 'customize_save', [ $this, '_customize_save' ] );
	}

	/**
	 * Delete cache on customizer
	 *
	 * @param WP_Customize_Manager $manager
	 * @return void
	 */
	public function _customize_save( $manager ) {
		$locations = get_registered_nav_menus();

		if ( $locations && is_array( $locations ) ) {
			$locations = array_keys( $locations );
			foreach ( $locations as $location_id ) {
				if ( ! $this->_is_caching_nav_menus( $location_id ) ) {
					continue;
				}

				delete_transient( $this->_get_transient_id( $location_id ) );
			}
		}
	}

	/**
	 * Delete cache
	 * On customizer, not deleted cache ( Not fired )
	 *
	 * @param int $menu_id
	 * @return void
	 */
	public function _delete_cache( $menu_id ) {
		$locations = get_registered_nav_menus();

		if ( $locations && is_array( $locations ) ) {
			$locations = array_keys( $locations );
			foreach ( $locations as $location_id ) {
				if ( ! $this->_is_caching_nav_menus( $location_id ) ) {
					continue;
				}

				delete_transient( $this->_get_transient_id( $location_id ) );
			}
		}
	}

	/**
	 * Set cache
	 *
	 * @param string $nav_menu HTML
	 * @param array $args
	 * @return string
	 */
	public function _set_cache( $nav_menu, $args ) {
		if ( $this->_is_caching_nav_menus( $args->theme_location ) ) {
			set_transient( $this->_get_transient_id( $args->theme_location ), $nav_menu, HOUR_IN_SECONDS );
		}

		return $nav_menu;
	}

	/**
	 * Output nav menu
	 *
	 * @param string $output HTML
	 * @param array $args
	 * @return string
	 */
	public function _pre_wp_nav_menu( $output, $args ) {
		if ( ! $this->_is_caching_nav_menus( $args->theme_location ) || is_customize_preview() ) {
			return $output;
		}

		$transient = get_transient( $this->_get_transient_id( $args->theme_location ) );
		if ( false !== $transient ) {
			return $transient;
		}

		return $output;
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
	 * @param string $location
	 * @return boolean
	 */
	protected function _is_caching_nav_menus( $location ) {
		return apply_filters( 'inc2734_wp_page_speed_optimization_caching_nav_menus', false, $location );
	}
}
