<?php
/**
 * The Template for displaying product archives, including the main shop page which is a post type archive
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.4.0
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

global $wp_query;

?>

<div class="woocommerce-product-header container-fluid">
	<div class="woocommerce-product-search">
		<?php get_product_search_form(); ?>
	</div>
</div>

<?php if (is_shop()) {
	//Get Hero from "Products page"
	//get_template_part('templates/theme/content');
	$content = get_field('sections', 4667);
		if ($content) {
			$order = 0;
			foreach ($content as $block) :
				include( locate_template( 'templates/theme/block-'.$block['acf_fc_layout'].'.php', false, false ) );
				$order++;
			endforeach;
		}
	}
?>

<div class="container-fluid">


	<?php if ( is_search() ): ?>
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

			?>

			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="col">
							<h2>search results: <span class="search-query">"<?php echo esc_html( get_query_var( 's' ) ); ?>"</span></h2>
							<h3><?php echo $wp_query->found_posts . ' ' . _n( 'item', 'items', $wp_query->found_posts ); ?> found</h3>
						</div>
					</div>
					<div class="row shop-loop">
						<?php
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

									<div class="col-sm-6 col-lg-3">
										<?php wc_get_template_part( 'content', 'product' ); ?>
									</div>

									<?php
								}
							}
						?>
					</div>
				</div>
			</div>

			<?php

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

		?>
	<?php else: ?>
		<?php

		global $post, $product;
		$categories = get_terms( [
			'taxonomy' => 'product_cat',
			'hide_empty' => true
		] );

		foreach ( $categories as $category ): ?>
			<div class="shop-category-wrapper row">
				<div class="col-md-12">
					<div class="cat-name-wrapper">
						<h2><?php echo esc_html( $category->name ); ?></h2>
						<a href="<?php echo get_term_link( $category ); ?>">Browse all <?php echo esc_html( $category->name ); ?></a>
					</div>

					<div class="row">
						<?php

						$category_products = wc_get_products( [
							'category' => [ $category->slug ],
							'limit' => 4
						] );

						foreach ( $category_products as $category_product ): ?>
							<div class="col-sm-6 col-lg-3">
								<?php

								$post = get_post( $category_product->get_id() );
								setup_postdata( $post );
								$product = $category_product;
								wc_get_template_part( 'content', 'product' );

								?>
							</div>
						<?php endforeach;

						wp_reset_postdata();

						?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>
</div>

<?php

get_footer( 'shop' );
