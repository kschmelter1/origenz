<?php
/**
 * Single Product Image
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/product-image.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.5.1
 */

defined( 'ABSPATH' ) || exit;

// Note: `wc_get_gallery_image_html` was added in WC 3.3.2 and did not exist prior. This check protects against theme overrides being used on older versions of WC.
if ( ! function_exists( 'wc_get_gallery_image_html' ) ) {
	return;
}



global $product;

$columns           = apply_filters( 'woocommerce_product_thumbnails_columns', 4 );
$post_thumbnail_id = $product->get_image_id();
$wrapper_classes   = apply_filters( 'woocommerce_single_product_image_gallery_classes', array(
	'woocommerce-product-gallery',
	'woocommerce-product-gallery--' . ( $product->get_image_id() ? 'with-images' : 'without-images' ),
	'woocommerce-product-gallery--columns-' . absint( $columns ),
	'images',
) );

$external_image_url = get_post_meta( $product->get_id(), '_external_image', true );

if ( !empty( $external_image_url ) ) {
    $image_url = $external_image_url;
} elseif ( $post_thumbnail_id ) {
    $image_url = wp_get_attachment_image_url( $post_thumbnail_id, 'full' );
} else {
    $image_url = wc_placeholder_img_src( 'full' );
}

// Get the first variation and its mockup.

$variations = $product->get_available_variations();



if ( !empty( $variations ) ) {
	foreach ( $variations as $variation ) {
		$mockup_html = origenz()->get_variation_mockup_html( $variation['variation_id'] );
		if ( !empty( $mockup_html ) ) {
			echo $mockup_html;
			break;
		}
	}
}

?>
