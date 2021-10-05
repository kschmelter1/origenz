<?php

namespace Compulse;

use Exception;
use WC_Product;
use WC_Product_Variable;
use WC_Product_Variation;
use Printful\PrintfulApiClient;

/**
 * Handles data communication with Printful.
 */
final class PrintfulHelper {
    private $api_client;

    private $last_api_exception;

    private $cached_placements;

    public function __construct() {
        $this->api_client = null;
        $this->last_api_exception = null;
        $this->cached_placements = null;

        add_action( 'init', array($this, 'init') );
        add_action( 'admin_menu', array($this, 'admin_menu'), 999 );
        add_action( 'wp_ajax_reload_product_data', array($this, 'ajax_reload_product_data') );
    }

    public function init() {

    }





    // Admin menu settings

    public function admin_menu() {
        add_submenu_page( 'acf-options-printful-integration-settings', 'Advanced Printful Integration Settings', 'Advanced', 'manage_options', 'advanced-printful-integration-settings', array($this, 'advanced_menu_page') );
    }

    public function advanced_menu_page() { ?>
        <div class="wrap">
            <h1>Advanced Printful Integration Settings</h1>

            <div>
                <div>
                    <?php submit_button( 'Reload Product Data From Printful', 'primary', 'submit_reload_product_data' ); ?>
                    <div class="reload-products-status">This will take a while.</div>
                </div>
            </div>

            <div style="margin-top:25px;">
                <b>First Variation Mockup Templates:</b><br /><br />
                <?php

                $products = wc_get_products( [
                    'limit' => -1
                ] );

                foreach ( $products as $product ) {
                    $variations = get_posts( [
                        'post_parent' => $product->get_id(),
                        'posts_per_page' => 1,
                        'post_type' => 'product_variation'
                    ] );

                    if ( !empty( $variations ) ) {
                        $variation_id = $variations[0]->ID;
                        $template = get_post_meta( $variation_id, '_printful_template', true );

                        echo '<b><a href="' . $product->get_permalink() . '">' . $product->get_name() . '</a> (Printful ID ' . get_post_meta( $product->get_id(), '_printful_product_id', true ) . '):</b><br />';
                        echo 'status=' . $product->get_status() . '<br />';
                        echo '<img style="width:200px; height:auto;" src="' . $template['image_url'] . '"><br /><br />';
                    }

                    // break;
                }

                ?>
            </div>
        </div>

        <script>
            (function($) {
                var running = false;

                $('#submit_reload_product_data').click( function(e) {
                    if ( running ) {
                        return;
                    }

                    console.log( 'reload products' );

                    var status = $('.reload-products-status');
                    function updateStatusPercent(percent) {
                        status.html( '<i class="fa fa-spin fa-spinner"></i> Please keep this window open while products are loaded from Printful. This will take a while. (' + percent + '% complete)' );
                    }

                    updateStatusPercent('0.00');

                    function loadProductData(offset) {
                        $.post( '/wp-admin/admin-ajax.php', {
                            action: 'reload_product_data',
                            offset: offset
                        }, function( resp ) {
                            if ( resp.success ) {
                                if ( resp.offset + 5 < resp.total ) {
                                    updateStatusPercent( resp.percent );
                                    loadProductData( resp.offset + 5 );
                                } else {
                                    // Done
                                    running = false;
                                    status.html( 'Successfully loaded products from Printful.' );
                                }
                            } else {
                                running = false;
                                status.html( 'Failed to update products (offset ' + offset + ')' );
                            }
                        } );
                    }

                    running = true;
                    loadProductData(0);

                    e.preventDefault();
                } );
            })(jQuery);
        </script>
    <?php }






    private function get_placements() {
        if ( $this->cached_placements === null ) {
            $this->cached_placements = [];

            $placements = get_field( 'placements', 'options' );

            foreach ( $placements as $placement ) {
                $this->cached_placements[ $placement['printful_product_id'] ] = $placement['placement'];
            }
        }

        return $this->cached_placements;
    }







    /**
     * @return PrintfulApiClient
     */
    public function get_api_client() {
        if ( $this->api_client === null ) {
            $api_key = get_field( 'printful_api_key', 'options' );
            $this->api_client = new PrintfulApiClient( $api_key );
        }

        return $this->api_client;
    }

    /**
     * @return Exception
     */
    public function get_last_api_exception() {
        return $this->last_api_exception;
    }

    /**
     * Handle an admin AJAX request to reload product data starting at an offset.
     */
    public function ajax_reload_product_data() {
        $in = filter_input_array( INPUT_POST, [
            'offset' => [
                'filter' => FILTER_VALIDATE_INT,
                'options' => [
                    'min_range' => 0
                ]
            ]
        ] );
        $out = [
            'offset' => 0,
            'total' => 0,
            'percent' => 0,
            'success' => false,
            'error' => ''
        ];

        if ( !empty( $in['offset'] ) || $in['offset'] === 0 ) {
            $out['offset'] = $in['offset'];

            $result = $this->load_products_from_printful( $in['offset'] );
            if ( $result['success'] ) {
                $out = array_merge( $out, [
                    'offset' => $in['offset'],
                    'total' => $result['total_products'],
                    'percent' => number_format( (($in['offset'] + 5) / $result['total_products']) * 100, 2 ),
                    'success' => true,
                    'error' => $result['error']
                ] );
            } else {
                $out['error'] = $result['error'];
            }
        }

        wp_send_json( $out );
        exit;
    }

    /**
     * Load a single product from Printful.
     * @param int $printful_product_id
     */
    public function load_product_from_printful( $printful_product_id ) {
        $product_data = $this->get_api_client()->get( 'products/' . $printful_product_id );
        $templates = $this->get_api_client()->get( 'mockup-generator/templates/' . $printful_product_id );

        $templates_by_id = [];
        $variant_templates = [];

        foreach ( $templates['templates'] as $template ) {
            $templates_by_id[ $template['template_id'] ] = $template;
        }

        // Some products have specific mappings.
        $override_placements = $this->get_placements();

        if ( isset( $override_placements[$printful_product_id] ) ) {
            $placement_order = [ $override_placements[$printful_product_id] ];
        } else {
            // Look for image placements, in this order...
            $placement_order = ['default','front','embroidery_front','embroidery_apparel_front','embroidery_chest_left'];
        }

        foreach ( $templates['variant_mapping'] as $variant_map ) {
            foreach ( $placement_order as $placement ) {
                foreach ( $variant_map['templates'] as $variant_template ) {
                    if ( $variant_template['placement'] == $placement ) {
                        $variant_templates[ $variant_map['variant_id'] ] = $templates_by_id[ $variant_template['template_id'] ];
                        $variant_templates[ $variant_map['variant_id'] ]['placement'] = $placement;
                        break 2; // Go to next variant mapping.
                    }
                }
            }
        }

        // Check for an existing product with this printful ID.
        $existing_product = get_posts( [
            'post_type' => 'product',
            'meta_query' => [
                [
                    'key' => '_printful_product_id',
                    'value' => $product_data['product']['id']
                ]
            ],
            'post_status' => 'all',
            'fields' => 'ids'
        ] );
        $is_existing_product = !empty( $existing_product );

        // var_dump($product_data, $variant_templates);

        $product_obj = $is_existing_product ? wc_get_product( $existing_product[0] ) : new WC_Product_Variable();

        // Update the data on the product object.
        if ( !$is_existing_product ) {
            $product_obj->set_name( $product_data['product']['model'] );
            $product_obj->set_description( $product_data['product']['description'] );
        }
        $product_obj->save();
        $product_id = $product_obj->get_id();

        // Product attributes.
        $attributes = [
            'color' => [],
            'size' => []
        ];

        // Update variations.

        $variant_taxonomy_names = [
            'color' => 'pa_color',
            'size' => 'pa_size'
        ];

        // Create assoc array where keys are taxonomy names and values are empty arrays.
        $attributes = array_combine( array_values( $variant_taxonomy_names ), array_fill( 0, count( $variant_taxonomy_names ), [] ) );

        // Add new variations.
        foreach ( $product_data['variants'] as $variant ) {
            $existing_variation = get_posts( [
                'post_type' => 'product_variation',
                'meta_query' => [
                    [
                        'key' => '_printful_variant_id',
                        'value' => $variant['id']
                    ]
                ],
                'fields' => 'ids'
            ] );
            $is_existing_variation = !empty( $existing_variation );

            $variation_obj = new WC_Product_Variation( $is_existing_variation ? $existing_variation[0] : 0 );
            $variation_obj->set_parent_id( $product_id );
            if ( !$is_existing_variation ) {
                $variation_obj->set_regular_price( $variant['price'] );
            }
            $variation_obj->save();

            $variation_id = $variation_obj->get_id();
            foreach ( $variant_taxonomy_names as $variant_key => $taxonomy ) {
                if ( !empty( $variant[ $variant_key ] ) ) {
                    $existing_term = get_term_by( 'name', $variant[ $variant_key ], $taxonomy );
                    if ( !$existing_term ) {
                        $inserted_term = wp_insert_term( $variant[ $variant_key ], $taxonomy );

                        if ( !is_wp_error( $inserted_term ) ) {
                            $existing_term = get_term( $inserted_term['term_id'] );
                        }
                    }

                    if ( $existing_term ) {
                        $attributes[ $taxonomy ][] = $existing_term->term_id; // Add to product attributes
                        update_post_meta( $variation_id, 'attribute_' . $taxonomy, $existing_term->slug );
                    }
                }
            }

            // Save Printful data to the variation.
            update_post_meta( $variation_id, '_printful_variant_id', $variant['id'] );
            update_post_meta( $variation_id, '_printful_template', $variant_templates[ $variant['id'] ] ?? [] );
        }


        // Assign attribute terms to product and update the product's attribute meta.
        $product_attributes = [];

        $position = 0;

        foreach ( $attributes as $taxonomy => $term_ids ) {
            if ( !empty( $term_ids ) ) {
                wp_set_post_terms( $product_id, $term_ids, $taxonomy );

                // a:2:{
                //  s:8:"pa_color";a:6:{s:4:"name";s:8:"pa_color";s:5:"value";s:0:"";s:8:"position";s:1:"0";s:10:"is_visible";s:1:"0";s:12:"is_variation";s:1:"1";s:11:"is_taxonomy";s:1:"1";}
                //  s:7:"pa_size";a:6:{s:4:"name";s:7:"pa_size";s:5:"value";s:0:"";s:8:"position";s:1:"1";s:10:"is_visible";s:1:"0";s:12:"is_variation";s:1:"1";s:11:"is_taxonomy";s:1:"1";}
                // }
                $product_attributes[ $taxonomy ] = [
                    'name' => $taxonomy,
                    'value' => '',
                    'position' => $position,
                    'is_visible' => '0',
                    'is_variation' => '1',
                    'is_taxonomy' => '1'
                ];

                $position++;
            }
        }

        update_post_meta( $product_id, '_product_attributes', $product_attributes );

        // Additional product meta.
        update_post_meta( $product_id, '_printful_product_id', $product_data['product']['id'] );
        update_post_meta( $product_id, '_external_image', $product_data['product']['image'] );
        update_post_meta( $product_id, '_printful_product_data', json_encode( $product_data ) );
        update_post_meta( $product_id, '_printful_product_templates', json_encode( $templates_by_id ) );
    }

    /**
     * Load products from Printful.
     * @param int $offset Start at this offset.
     * @param int $limit Only load this many products starting at the offset.
     */
    public function load_products_from_printful( $offset = 0, $limit = 5 ) {
        set_time_limit( 120 );

        $return_value = [
            'offset' => $offset,
            'total_products' => 0,
            'success' => false,
            'error' => ''
        ];

        $start_time = microtime( true );

        try {
            $product_list = $this->get_api_client()->get( 'products' );
        } catch ( Exception $e ) {
            $return_value['success'] = false;
            $return_value['error'] = 'Failed to retrieve products.';
            return $return_value;
        }

        $return_value['total_products'] = count( $product_list );

        // Skip to the offset.
        $product_list = array_slice( $product_list, $offset, $limit );

        //var_dump($product_list); exit;

        foreach ( $product_list as $i => $product ) {
            try {
                $this->load_product_from_printful( $product['id'] );
            } catch ( Exception $e ) {
                $return_value['error'] .= 'Failed to load Printful product ' . $product['id'] . ' (' . $e->getMessage() . ').';
                continue;
            }
        }

        $return_value['success'] = true;
        return $return_value;
    }

    /**
     * Create an order in Printful using specified data for the request body.
     * @param array $data
     * @return array|bool array of order data from Printful or false if the request failed.
     */
    public function create_order( $data ) {
        try {
            return $this->get_api_client()->post( 'orders', $data );
        } catch ( Exception $e ) {
            $this->last_api_exception = $e;
            return false;
        }
    }

    /**
     * Confirm an existing order in Printful after payment is received so it gets shipped.
     * @param int $order_id
     * @return array|bool
     */
    public function confirm_order( $order_id ) {
        try {
            if ( !get_field( 'skip_order_confirmation', 'options' ) ) {
                return $this->get_api_client()->post( 'orders/' . $order_id . '/confirm' );
            }
        } catch ( Exception $e ) {
            $this->last_api_exception = $e;
        }

        return false;
    }

    /**
     * Estimate costs from Printful
     * @param array $data
     * @return array|bool array of order data from Printful or false if the request failed.
     */
    public function estimate_costs( $data ) {
        try {
            return $this->get_api_client()->post( 'orders/estimate-costs', $data );
        } catch ( Exception $e ) {
          //var_dump( $e );
            return false;
        }
    }

    public function get_webhook_key() {
        return WooCommerceHelper::WEBHOOK_KEY;
    }

    public function get_webhook_types() {
        return ['package_shipped','package_returned','order_failed','order_canceled','order_put_hold','order_remove_hold'];
    }

    public function get_webhook_url() {
        $url = site_url( '/printful-webhook/?key=' . $this->get_webhook_key() );
        $url = preg_replace( '/localhost(:[0-9]+)?/', 'kutv97447site.wpengine.com', $url );
        return $url;
    }

    /**
     * Ensure the Printful webhook for the site is set up correctly.
     */
    public function ensure_webhook() {
        $webhook = $this->get_api_client()->get( 'webhooks' );
        $webhook_url = $this->get_webhook_url();
        $webhook_types = $this->get_webhook_types();

        // Check if webhook is empty or is incorrect or not all required webhook types have been registered.
        if ( empty( $webhook['url'] ) || $webhook['url'] != $webhook_url || !empty( array_diff( $webhook_types, $webhook['types'] ) ) ) {
            $this->get_api_client()->post( 'webhooks', [
                'url' => $webhook_url,
                'types' => $webhook_types,
                'params' => [
                    'key' => $this->get_webhook_key()
                ]
            ] );
        }
    }
}
