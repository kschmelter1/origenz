<?php

ini_set( 'max_input_vars', '2000' );

register_nav_menus( array(
    'primary' => 'Primary Menu',
    'footer1' => 'Footer Menu 1',
    'footer2' => 'Footer Menu 2',
    'footer3' => 'Footer Menu 3'
) );


require_once 'bs4Navwalker.php';
require_once 'includes/origenz.php';
require_once 'includes/tinymce-styles.php';


//Advanced Custom Fields Options page
if( function_exists('acf_add_options_page') ) {

	$option_page = acf_add_options_page(array(
		'page_title' => 'Site Options',
		'menu_slug' => 'options',
		'position' => '2.5',
		'post_id' => 'options',
		'icon_url' => 'dashicons-smiley'
	));

}

function get_product_img($prodID) {
  $external_image_url = get_post_meta( $prodID, '_external_image', true );

  if ( !empty( $external_image_url ) ) {
      $image_url = $external_image_url;
  } elseif ( $post_thumbnail_id ) {
      $image_url = wp_get_attachment_image_url( $post_thumbnail_id, 'full' );
  } else {
      $image_url = wc_placeholder_img_src( 'full' );
  }
  return $image_url;
}

/**
 * Ensure cart contents update when products are added to the cart via AJAX
 */
function my_header_add_to_cart_fragment( $fragments ) {

    ob_start();
    $count = WC()->cart->cart_contents_count;
    ?><a class="cart-contents" href="<?php echo wc_get_cart_url(); ?>" title="<?php _e( 'View your shopping cart' ); ?>"><?php
    if ( $count > 0 ) {
        ?>
        <span class="cart-contents-count"><?php echo esc_html( $count ); ?></span>
        <?php
    }
        ?></a><?php

    $fragments['a.cart-contents'] = ob_get_clean();

    return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'my_header_add_to_cart_fragment' );

//Helper function to easily echo images from AFC
function echo_image($img, $class = "img-fluid") {
	return '<img class="'.$class.'" src="'.$img['url'].'" alt="'.$img['alt'].'>">';
}

//Adds button classes to a link in the word editor
function make_btn($atts = [], $content = null){
$substr = '<a ';
$class = ' class="btn btn-primary" ';

return str_replace($substr, $substr.$class, $content);
}
add_shortcode( 'button', 'make_btn' );

//Change default job manager logo
add_filter( 'job_manager_default_company_logo', 'smyles_custom_job_manager_logo' );
function smyles_custom_job_manager_logo( $logo_url ){
	// Change the value below to match the filename of the custom logo you want to use
	// Place the file in a /images/ directory in your child theme's root directory.
	// The example provided assumes "/images/custom_logo.png" exists in your child theme
	$filename = 'favicon.png';

	$logo_url = get_stylesheet_directory_uri() . '/img/' . $filename;

	return $logo_url;

}
