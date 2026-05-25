<?php
/**
 * Theme functions and definitions
 *
 * @package HelloElementorChild
 */

/**
 * Load child theme css and optional scripts
 *
 * @return void
 */
function hello_elementor_child_enqueue_scripts() {
	wp_enqueue_style(
		'hello-elementor-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		[
			'hello-elementor-theme-style',
		],
		'1.0.0'
	);
}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_enqueue_scripts' );
function dpgo() { ?>

<style type="text/css">

#login h1 a, .login h1 a {

background-image: url(https://www.digitalpartners.co.il/wp-content/uploads/2022/06/website-credit_black.png);

height:65px;

width:320px;

background-size: 320px 65px;

background-repeat: no-repeat;

padding-bottom: 30px;

}

</style>

<?php }

add_action( 'login_enqueue_scripts', 'dpgo' );

function builder() {

return "www.digitalpartners.co.il";

}

add_filter( 'login_headerurl', 'builder' );