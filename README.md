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

add_filter( 'inc2734_wp_page_speed_optimization_http2_server_push_handles', function( $handles ) {
	return $handles;
} );
```
