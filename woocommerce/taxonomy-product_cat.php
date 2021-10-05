<?php
/**
 * The Template for displaying products in a product category. Simply includes the archive template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/taxonomy-product_cat.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */

 defined( 'ABSPATH' ) || exit;

 get_header( 'shop' );

 $queried_object = get_queried_object();

 ?>

 <div class="woocommerce-product-header container-fluid">
 	<div class="woocommerce-product-search">
 		<?php get_product_search_form(); ?>
 	</div>
 </div>

<div class="container-fluid">
	<div class="row product-category-row">
		<div class="col-md-3">
			<ul class="shop-sidebar">
				<?php dynamic_sidebar( 'shop-sidebar' ); ?>
			</ul>
		</div>

		<div class="col-md-9">
			<h2><u><?php echo esc_html( $queried_object->name ); ?></u></h2>

			<?php
				if ( woocommerce_product_loop() ) {

					/**
					 * Hook: woocommerce_before_shop_loop.
					 *
					 * @hooked woocommerce_output_all_notices - 10
					 * @hooked woocommerce_result_count - 20
					 * @hooked woocommerce_catalog_ordering - 30
					 */
					remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
					remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
					do_action( 'woocommerce_before_shop_loop' );

					woocommerce_product_loop_start();

					if ( wc_get_loop_prop( 'total' ) ) {
						while ( have_posts() ) {
							the_post();

							/**
							 * Hook: woocommerce_shop_loop.
							 *
							 * @hooked WC_Structured_Data::generate_product_data() - 10
							 */
							do_action( 'woocommerce_shop_loop' );

							?>

							<div class="col-md-4">
								<?php wc_get_template_part( 'content', 'product' ); ?>
							</div>

							<?php
						}
					}

					woocommerce_product_loop_end();

					/**
					 * Hook: woocommerce_after_shop_loop.
					 *
					 * @hooked woocommerce_pagination - 10
					 */
					do_action( 'woocommerce_after_shop_loop' );
				} else {
					/**
					 * Hook: woocommerce_no_products_found.
					 *
					 * @hooked wc_no_products_found - 10
					 */
					do_action( 'woocommerce_no_products_found' );
				}

				/**
				 * Hook: woocommerce_after_main_content.
				 *
				 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
				 */
				do_action( 'woocommerce_after_main_content' );
			?>
		</div>
	</div>
 </div>

 <?php

 get_footer( 'shop' );
