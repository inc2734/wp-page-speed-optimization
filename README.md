# WP Page Speed Optimization

![CI](https://github.com/inc2734/wp-page-speed-optimization/workflows/CI/badge.svg)
[![Latest Stable Version](https://poser.pugx.org/inc2734/wp-page-speed-optimization/v/stable)](https://packagist.org/packages/inc2734/wp-page-speed-optimization)
[![License](https://poser.pugx.org/inc2734/wp-page-speed-optimization/license)](https://packagist.org/packages/inc2734/wp-page-speed-optimization)

## Install
```
$ composer require inc2734/wp-page-speed-optimization
```

## How to use
### Initialize (Require)
```
<?php
// When Using composer auto loader
new Inc2734\WP_Page_Speed_Optimization\Bootstrap();
```

### Add defer attribute
```
add_filter( 'inc2734_wp_page_speed_optimization_defer_scripts', function( $handles ) {
	return array_merge( $handles, [
		get_template(),
		get_stylesheet(),
	] );
} );
```

### Add async attribute
```
add_filter( 'inc2734_wp_page_speed_optimization_async_scripts', function( $handles ) {
	return array_merge( $handles, [
		'comment-reply',
		'wp-embed',
	] );
} );
```

### Optimize jQuery loading

Load jQuery and other scripts as defer + head as much as possible.

```
add_filter( 'inc2734_wp_page_speed_optimization_optimize_jquery_loading', '__return_true' );
```

### Use HTTP/2 Server Push
```
add_filter( 'inc2734_wp_page_speed_optimization_do_http2_server_push', '__return_true' );
add_filter( 'inc2734_wp_page_speed_optimization_http2_server_push_handles', function( $handles ) {
	return $handles;
} );
```

### Use link prefetching
```
add_filter( 'inc2734_wp_page_speed_optimization_link_prefetching', '__return_true' );
add_filter( 'inc2734_wp_page_speed_optimization_link_prefetching_selector', '.l-header, .l-contents__main' );
add_filter( 'inc2734_wp_page_speed_optimization_link_prefetching_interval', 2000 );
add_filter( 'inc2734_wp_page_speed_optimization_link_prefetching_connections', 1 );
```

### Output CSS to head
```
add_filter( 'inc2734_wp_page_speed_optimization_output_head_styles', function( $handles ) {
	return array_merge( $handles, [
		get_template(),
		get_stylesheet(),
	] );
} );
```

### Preload Styles
```
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
```

### Use browser cache with .htaccess
```
// If `set-expires-header` customize setting
add_action( 'customize_save_set-expires-header', function( $customize_setting ) {
	if ( $customize_setting->post_value() === $customize_setting->value() ) {
		return;
	}

	\Inc2734\WP_Page_Speed_Optimization\Helper\write_cache_control_setting( (bool) $customize_setting->post_value() );
} );
```

### Cache nav menus
```
// If using nav menu caching, remove all current classes.
add_filter( 'inc2734_wp_page_speed_optimization_caching_nav_menus', '__return_true' );
```

### Cache sidebars
```
// in functions.php
add_filter( 'inc2734_wp_page_speed_optimization_caching_sidebars', '__return_true' );

// in template
\Inc2734\WP_Page_Speed_Optimization\Page_Speed_Optimization\Helper\dynamic_sidebar( 'footer-widget-area' );
```

### Async loading of attachment image
```
add_filter( 'inc2734_wp_page_speed_async_attachment_images', '__return_true' );
```

### Async loading of content image
```
add_filter( 'inc2734_wp_page_speed_async_content_images', '__return_true' );
```
