<?php
/**
 * JSやCSS、画像などに関する設定
 * 
 * 
 * @since 1.0
 */



/**
 * JSもしくはスタイルを登録する
 */
function _lwper_register_assets(){
	//親テーマのCSS
	$theme = wp_get_theme('twentytwelve');
	wp_register_style('parent-style', get_template_directory_uri().'/style.css', array(), $theme->headers['Version']);
}
add_action('init', '_lwper_register_assets', 10000);

/**
 * JSおよびCSSを登録する
 */
function _lwper_load_css(){
	//TwentyTwelveによって登録されるデフォルトCSSを解除
	wp_dequeue_style('twentytwelve-style');
	//オリジナルのCSSを登録
	$current = wp_get_theme();
	wp_enqueue_style('lwper-style', get_stylesheet_directory_uri()."/compass/stylesheets/screen.css", array('parent-style'), $current->headers['Version']);
}
add_action('wp_print_styles', '_lwper_load_css', 1000);

/**
 * ファビコンを表示する
 */
function _lwper_favicon(){
?>
<link rel="shortcut icon" href="<?php echo get_stylesheet_directory_uri(); ?>/images/lwper-logo.ico" />
<?php
}
add_action('wp_head', '_lwper_favicon', 10000);
add_action('admin_head', '_lwper_favicon', 10000);