<?php
/**
 * @package inc2734/wp-page-speed-optimization
 * @author inc2734
 * @license GPL-2.0+
 */

namespace Inc2734\WP_Page_Speed_Optimization\App\Model;

class Defer_Scripts {

	protected $has_after_handles = [];
	protected $after_dep_handles = [];
	protected $handles           = [];

	public function __construct() {
		$queue = array_diff( wp_scripts()->queue, wp_scripts()->done );
		foreach ( $queue as $handle ) {
			$this->_generate( $handle );
		}

		$this->handles = array_diff( $this->handles, $this->has_after_handles );
		$this->handles = array_diff( $this->handles, $this->after_dep_handles );

		if ( $this->_is_sync_jquery() ) {
			$this->handles = array_diff( $this->handles, [ 'jquery', 'jquery-core' ] );
		}

		$this->handles = array_diff( $this->handles, [ 'quicktags' ] );
	}

	public function get() {
		return $this->handles;
	}

	protected function _remove( $handle ) {
		if ( in_array( $handle, $this->after_dep_handles ) ) {
			return;
		}

		$this->handles = array_diff( $this->handles, [ $handle ] );
		$this->after_dep_handles[] = $handle;
		$dependency = wp_scripts()->query( $handle, 'registered' );
		if ( is_array( $dependency->deps ) ) {
			foreach ( $dependency->deps as $dep_handle ) {
				$this->_remove( $dep_handle );
			}
		}
	}

	protected function _generate( $handle ) {
		if ( in_array( $handle, $this->has_after_handles ) || in_array( $handle, $this->handles ) ) {
			return;
		}

		$dependency = wp_scripts()->query( $handle, 'registered' );
		if ( wp_scripts()->get_data( $handle, 'after' ) ) {
			$this->has_after_handles[] = $handle;
			$this->has_after_handles   = array_unique( $this->has_after_handles );

			if ( is_array( $dependency->deps ) ) {
				foreach ( $dependency->deps as $dep_handle ) {
					$this->_remove( $dep_handle );
				}
			}
		} else {
			$this->handles[] = $handle;
			$this->handles   = array_unique( $this->handles );

			if ( isset( $dependency->deps ) && is_array( $dependency->deps ) ) {
				foreach ( $dependency->deps as $dep_handle ) {
					$this->_generate( $dep_handle );
				}
			}
		}
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
			if ( bbp_use_wp_editor() && in_array( true, array_filter( $has_form_page ) ) ) {
				return true;
			}
		}

		return false;
	}
}
