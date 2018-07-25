# WP Page Speed Optimization

[![Build Status](https://travis-ci.org/inc2734/wp-page-speed-optimization.svg?branch=master)](https://travis-ci.org/inc2734/wp-page-speed-optimization)
[![Latest Stable Version](https://poser.pugx.org/inc2734/wp-page-speed-optimization/v/stable)](https://packagist.org/packages/inc2734/wp-page-speed-optimization)
[![License](https://poser.pugx.org/inc2734/wp-page-speed-optimization/license)](https://packagist.org/packages/inc2734/wp-page-speed-optimization)

## Install
```
$ composer require inc2734/wp-page-speed-optimization
```

## How to use
```
<?php
// When Using composer auto loader
new Inc2734\WP_Page_Speed_Optimization\Page_Speed_Optimization();

add_filter( 'inc2734_wp_page_speed_optimization_defer_scripts', function( $handles ) {
	return array_merge( $handles, [
		get_template(),
		get_stylesheet(),
	] );
} );

add_filter( 'inc2734_wp_page_speed_optimization_async_scripts', function( $handles ) {
	return array_merge( $handles, [
		'comment-reply',
		'wp-embed',
	] );
} );

add_filter( 'inc2734_wp_page_speed_optimization_do_http2_server_push', '__return_true' );

add_filter( 'inc2734_wp_page_speed_optimization_http2_server_push_handles', function( $handles ) {
	return $handles;
} );

add_filter( 'inc2734_wp_page_speed_optimization_output_head_styles', function( $handles ) {
	return array_merge( $handles, [
		get_template(),
		get_stylesheet(),
	] );
} );

add_filter( 'inc2734_wp_page_speed_optimization_preload_stylesheets', function( $handles ) {
	$wp_styles = wp_styles();
	$preload_handles = $wp_styles->queue;

	if ( in_array( get_template(), $preload_handles ) ) {
		unset( $preload_handles[ get_template() ] );
	}

	if ( in_array( get_stylesheet(), $preload_handles ) ) {
		unset( $preload_handles[ get_stylesheet() ] );
	}

	return array_merge( $handles, $preload_handles );
} );

add_filter( 'inc2734_wp_page_speed_optimization_optimize_jquery_loading', '__return_true' );

// If `set-expires-header` customize setting
add_action( 'customize_save_set-expires-header', function( $customize_setting ) {
	if ( $customize_setting->post_value() === $customize_setting->value() ) {
		return;
	}

	\Inc2734\WP_Page_Speed_Optimization\Page_Speed_Optimization::write_cache_control_setting( (bool) $customize_setting->post_value() );
} );

add_filter( 'inc2734_wp_page_speed_optimization_caching_nav_menus', '__return_true' );

add_filter( 'inc2734_wp_page_speed_optimization_caching_sidebars', '__return_true' );
```
