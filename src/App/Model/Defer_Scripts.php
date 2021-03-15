<?php
/**
 * @package inc2734/wp-page-speed-optimization
 * @author inc2734
 * @license GPL-2.0+
 */

namespace Inc2734\WP_Page_Speed_Optimization\App\Model;

class Defer_Scripts {

	/**
	 * Handles to load defer.
	 *
	 * @var array
	 */
	protected $handles = [];

	/**
	 * Constructor.
	 */
	public function __construct() {
		$all_handles = [];
		$queue       = array_diff( wp_scripts()->queue, wp_scripts()->done );
		foreach ( $queue as $handle ) {
			$all_handles = array_merge( $all_handles, $this->_generate( $handle ) );
		}

		$has_before_handles      = [];
		$has_before_handles_deps = [];
		$has_after_handles       = [];
		$has_after_handles_deps  = [];

		foreach ( $all_handles as $handle ) {
			if ( wp_scripts()->get_data( $handle, 'after' ) ) {
				$has_after_handles[ $handle ] = $handle;

				$deps = $this->_get_deps( $handle );
				foreach ( $deps as $dep_handle ) {
					$has_after_handles_deps = array_merge( $has_after_handles_deps, $this->_generate( $dep_handle ) );
				}
			}

			if ( wp_scripts()->get_data( $handle, 'data' ) ) {
				$has_before_handles[ $handle ] = $handle;

				$deps = $this->_get_deps( $handle );
				foreach ( $deps as $dep_handle ) {
					$has_before_handles_deps = array_merge( $has_before_handles_deps, $this->_generate( $dep_handle ) );
				}
			}
		}

		$this->handles = $all_handles;
		$this->handles = array_diff( $this->handles, $has_before_handles );
		$this->handles = array_diff( $this->handles, $has_before_handles_deps );
		$this->handles = array_diff( $this->handles, $has_after_handles );
		$this->handles = array_diff( $this->handles, $has_after_handles_deps );

		if ( $this->_is_sync_jquery() ) {
			$this->handles = array_diff( $this->handles, [ 'jquery', 'jquery-core' ] );
		}
	}

	/**
	 * Return all handles.
	 *
	 * @return array
	 */
	public function get() {
		return $this->handles;
	}

	/**
	 * Remove deps handles.
	 *
	 * @param string $handle Name of the script. Should be unique.
	 * @return array
	 */
	protected function _remove( $handle ) {
		if ( in_array( $handle, $this->after_dep_handles, true ) ) {
			return;
		}

		$this->handles             = array_diff( $this->handles, [ $handle ] );
		$this->after_dep_handles[] = $handle;
		$dependency                = wp_scripts()->query( $handle, 'registered' );

		if ( is_array( $dependency->deps ) ) {
			foreach ( $dependency->deps as $dep_handle ) {
				$this->_remove( $dep_handle );
			}
		}
	}

	/**
	 * Generate deps handles.
	 *
	 * @param string $handle Name of the script. Should be unique.
	 * @return array
	 */
	protected function _get_deps( $handle ) {
		$dependency = wp_scripts()->query( $handle, 'registered' );
		if ( ! isset( $dependency->deps ) || ! is_array( $dependency->deps ) ) {
			return [];
		}

		return $dependency->deps;
	}

	/**
	 * Generate related handles.
	 *
	 * @param string $handle Name of the script. Should be unique.
	 * @return array
	 */
	protected function _generate( $handle ) {
		$all_handles            = [];
		$all_handles[ $handle ] = $handle;
		$deps                   = $this->_get_deps( $handle );

		foreach ( $deps as $dep_handle ) {
			$all_handles = array_merge( $all_handles, $this->_generate( $dep_handle ) );
		}

		return $all_handles;
	}

	/**
	 * TThere are plugins that enqueue after wp_enqueue_scripts().
	 * The enqueued script depends on jQuery and the defer is set to even if the situation should be deleted,
	 * the timing is too late and the jQuery Cannot remove the defer.
	 * Therefore, manually describe the conditions here.
	 *
	 * @return boolean
	 */
	protected function _is_sync_jquery() {
		if ( class_exists( 'bbPress' ) ) {
			$has_form_page = [
				bbp_is_reply_edit(),
				bbp_is_topic_edit(),
				bbp_is_single_reply(),
				bbp_is_single_topic(),
				bbp_is_single_forum(),
				bbp_is_topic_edit(),
				bbp_is_forum_archive(),
			];

			if ( bbp_use_wp_editor() && in_array( true, array_filter( $has_form_page ), true ) ) {
				return true;
			}
		}

		if ( class_exists( 'WooCommerce' ) ) {
			$handles = [
				'wp-util',
				'wc-add-to-cart-variation',
			];

			foreach ( $handles as $handle ) {
				if ( wp_scripts()->query( $handle, 'registered' ) ) {
					return true;
				}
			}
		}

		return false;
	}
}
