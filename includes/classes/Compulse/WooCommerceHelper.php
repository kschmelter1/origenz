<?php

namespace Compulse;

use Exception;
use WC_Cart;
use WC_Order;
use WC_Order_Item_Product;
use WC_Product;

/**
 * Custom WooCommerce actions such as sending WC orders to Printful.
 */
final class WooCommerceHelper {
    const WEBHOOK_KEY = '18d59b0d2ae57e2d5588bfbe551db12c';

    /**
     * @var PrintfulHelper
     */
    private $printful;

    private $cached_printful_costs;

    public function __construct( PrintfulHelper $printful ) {
        $this->printful = $printful;
        $this->cached_printful_costs = null;

        // Product admin
        add_action( 'woocommerce_product_after_variable_attributes', array($this, 'product_after_variable_attributes'), 10, 3 );
        add_action( 'woocommerce_product_options_advanced', array($this, 'product_options_advanced') );
        add_action( 'wp_ajax_reload_printful_data', array($this, 'ajax_reload_printful_data') );

        // Product pages
        add_filter( 'woocommerce_product_get_image', array($this, 'product_get_image'), 10, 6 );
        add_filter( 'woocommerce_product_add_to_cart_text', array($this, 'product_add_to_cart_text'), 10, 2 );

        // Add to Cart
        add_filter( 'woocommerce_add_cart_item_data', array($this, 'add_cart_item_data'), 10, 4 );

        // Cart/Checkout
        add_action( 'woocommerce_after_cart_item_name', array($this, 'after_cart_item_name'), 10, 2 );
        add_filter( 'woocommerce_before_calculate_totals', array($this, 'before_calculate_totals'), 10 );
        add_filter( 'woocommerce_package_rates', array($this, 'package_rates'), 999 );
        add_filter( 'woocommerce_cart_item_permalink', array($this, 'cart_item_permalink'), 10, 3 );

        // Order submitted
        add_action( 'woocommerce_checkout_create_order_line_item', array($this, 'checkout_create_order_line_item'), 10, 4 );
        add_action( 'woocommerce_checkout_create_order', array($this, 'checkout_create_order'), 10, 2 );
        add_action( 'woocommerce_checkout_update_order_meta', array($this, 'checkout_update_order_meta'), 10, 2 );
        add_filter( 'woocommerce_order_item_permalink', array($this, 'order_item_permalink'), 10, 3 );
        add_action( 'woocommerce_order_status_processing', array($this, 'order_status_processing'), 10, 2 );
    }

    /**
     * Display Printful information for each variation in the backend.
     * @param int $loop
     * @param array $variation_data
     * @param \WP_Post $variation
     */
    public function product_after_variable_attributes( $loop, $variation_data, $variation ) {
        $product_id = $variation->post_parent;
        $product_printful_data_string = get_post_meta( $product_id, '_printful_product_data', true );
        $product_printful_data = json_decode( $product_printful_data_string, true );

        if ( isset( $product_printful_data['product']['id'] ) ) {
            echo '<div><b>Printful Product ID:</b> ' . $product_printful_data['product']['id'] . '</div>';
        }

        echo '<div><b>Printful Variant ID:</b> ' . $variation_data['_printful_variant_id'][0] . '</div>';
        echo '<div>';
            echo '<b>Printful Template:</b>';
            echo '<div style="padding-left:15px;">';
                $template = unserialize( $variation_data['_printful_template'][0] );
                foreach ( $template as $k => $v ) {
                    if ( $k == 'image_url' || $k == 'background_url' ) {
                        $v = '<a href="' . $v . '" target="_blank">' . $v . '</a>';
                    }

                    echo '<div><b>' . $k . ':</b> ' . $v . '</div>';
                }
            echo '</div>';
        echo '</div>';
    }

    /**
     * Display option to reload product data from Printful for a single product.
     */
    public function product_options_advanced() {
        $product_id = get_the_ID();

        ?>

        <div style="padding:25px;">
            <div><button id="reload-printful-data" class="button" data-product-id="<?php echo esc_attr( $product_id ); ?>">Reload Product Data from Printful</button></div>
            <div style="padding-top:10px;">Page will refresh when this is complete.</div>
        </div>

        <script>
            (function($) {
                $('#reload-printful-data').click(function() {
                    $(this).prop('disabled', true);
                    $.post( '/wp-admin/admin-ajax.php', {
                        action: 'reload_printful_data',
                        product: $(this).data('product-id')
                    }, function( resp ) {
                        window.location.reload();
                    } );
                });
            })(jQuery);
        </script>

        <?php
    }

    /**
     * Handle ajax request to reload data for a single product.
     */
    public function ajax_reload_printful_data() {
        $product_id = filter_input( INPUT_POST, 'product', FILTER_VALIDATE_INT, [
            'options' => [
                'min_range' => 1
            ]
        ] );

        if ( !empty( $product_id ) ) {
            $printful_product_id = get_post_meta( $product_id, '_printful_product_id', true );

            if ( !empty( $printful_product_id ) ) {
                $this->printful->load_product_from_printful( $printful_product_id );
            }
        }
    }

    /**
     * Replace the product's image HTML with the external image.
     * @param string $image Image tag HTML
     * @param WC_Product $product
     * @param string $size
     * @param array $attr
     * @param string $placeholder
     * @param string $original_image Original image tag HTML before any filters ran on it.
     * @return string
     */
    public function product_get_image( $image, WC_Product $product, $size, $attr, $placeholder, $original_image ) {
        $product_id = $product->get_id();
        $external_image = get_post_meta( $product_id, '_external_image', true );

        if ( !empty( $external_image ) ) {
            $image = '<img src="' . $external_image . '" alt="' . esc_attr( $product->get_name() ) . '" class="wp-post-image img-fluid" />';
        }

        return $image;
    }

    /**
     * Change the add to cart button text to 'Customize'.
     * @param string $text
     * @param WC_Product $product
     * @return string
     */
    public function product_add_to_cart_text( $text, $product ) {
        $text = 'Customize';
        return $text;
    }

    /**
     * Add the current design choice and state to the cart item that is being added to the cart.
     * @param array $data
     * @param int $product_id
     * @param int $variation_id
     * @param int $quantity
     * @return array
     */
    public function add_cart_item_data( $data, $product_id, $variation_id, $quantity ) {
        $border_color = filter_input( INPUT_POST, 'border_color', FILTER_VALIDATE_REGEXP, [
            'options' => [
                'regexp' => '/^(black|white)$/'
            ]
        ] );

        $data['design_choice'] = origenz()->get_design_choice( $border_color ?: 'black' );

        $design_state = origenz()->get_design_state();
        $countries = origenz()->flatten_regions_to_countries( $design_state['regions'] );
        $shape = $design_state['shape'];

        $data['design_state'] = [
            'countries' => $countries,
            'shape' => $shape,
            'border_color' => $border_color ?: 'black'
        ];

        return $data;
    }

    /**
     * Show the design choice in the cart, after the product name.
     * @param array $cart_item
     * @param string $cart_item_key
     */
    public function after_cart_item_name( $cart_item, $cart_item_key ) {
        if ( isset( $cart_item['design_state']['border_color'] ) ) {
            echo '<div class="line-item-border-color"><b>Border Color:</b> ' . ucwords( $cart_item['design_state']['border_color'] ) . '</div>';
        }

        if ( isset( $cart_item['design_choice'] ) ) {
            echo '<div class="line-item-design-choice"><img src="' . $cart_item['design_choice']['image'] . '" alt="Design Choice" />' . $cart_item['design_choice']['name'] . '</div>';
        }
    }

    /**
     * Actions to be performed before cart totals are calculated.
     * @param WC_Cart $cart
     */
    public function before_calculate_totals( WC_Cart $cart ) {
        // Calculate tax via Printful and add it to the cart as a fee.
        $costs = $this->estimate_printful_costs();

        if ( isset( $costs['costs']['tax'] ) ) {
            $cart->add_fee( 'Tax', $costs['costs']['tax'] );
        }
    }

    /**
     * Retrieve the shipping costs from Printful and update the calculated Shipping costs with Printful's shipping rate.
     * @param array $rates
     * @return array
     */
    public function package_rates( $rates ) {
        // Get estimate from Printful.
        $costs = $this->estimate_printful_costs();

        //var_dump( $costs );

        if ( isset( $costs['retail_costs']['shipping'] ) ) {
            current( $rates )->set_cost( $costs['retail_costs']['shipping'] );
        } else {
            //$rates = [];
        }

        return $rates;
    }

    /**
     * Add the selected border color to the product permalinks in the cart.
     * @param string $permalink
     * @param array $cart_item
     * @param string $cart_item_key
     * @return string
     */
    public function cart_item_permalink( $permalink, $cart_item, $cart_item_key ) {
        if ( !empty( $permalink ) ) {
            $design_state = $cart_item['design_state'];

            if ( isset( $design_state['border_color'] ) ) {
                $permalink = add_query_arg( 'border_color', $design_state['border_color'], $permalink );
            }
        }

        return $permalink;
    }

    /**
     * When a line item is created, before it is saved to the database.
     * @param WC_Order_Item_Product $item
     * @param string $cart_item_key
     * @param array $values
     * @param WC_Order $order
     */
    public function checkout_create_order_line_item( $item, $cart_item_key, $values, $order ) {
        // Save design choice and state from cart data to line item meta.
        $data_items = ['design_choice','design_state'];

        foreach ( $data_items as $data_item ) {
            if ( isset( $values[ $data_item ] ) ) {
                $item->update_meta_data( $data_item, $values[ $data_item ] );
            }
        }
    }

    /**
     * Before order is saved to the database.
     * @param WC_Order $order
     * @param array $data
     */
    public function checkout_create_order( $order, $data ) {

    }

    /**
     * After order is saved to the database.
     * @param int $order_id
     * @param array $data
     */
    public function checkout_update_order_meta( $order_id, $data ) {
        $existing_order_data = get_post_meta( $order_id, '_printful_order', true );

        if ( empty( $existing_order_data ) ) {
            // Set up the order in Printful and save the order data to the order.
            $result = $this->create_printful_order( $order_id );
            $order = wc_get_order( $order_id );

            if ( isset( $result['id'] ) ) {
                update_post_meta( $order_id, '_printful_order', $result );
                $order->add_order_note( 'Order successfully created as draft in Printful.  Printful Order ID: ' . $result['id'] );
                $order->add_order_note( 'Printful Order Costs: ' . print_r( $result['costs'] , true ) );

                // Make sure the Printful webhook has been set up, so we can get updates about the order.
                $this->printful->ensure_webhook();
            } else {
                $order->add_order_note( 'Failed to create order in Printful.' );
                $order->add_order_note( $this->printful->get_last_api_exception()->getMessage() );
            }
        }
    }

    /**
     * Add the border color to product permalinks in an order.
     * @param string $permalink
     * @param \WC_Order_Item $item
     * @param \WC_Order $order
     * @return string
     */
    public function order_item_permalink( $permalink, $item, $order ) {
        if ( !empty( $permalink ) ) {
            $design_state = $item->get_meta( 'design_state' );

            if ( isset( $design_state['border_color'] ) ) {
                $permalink = add_query_arg( 'border_color', $design_state['border_color'], $permalink );
            }
        }

        return $permalink;
    }

    /**
     * When the order is marked as complete.
     * @param int $order_id
     * @param WC_Order $order
     */
    public function order_status_processing( $order_id, $order ) {
        // Confirm the order in Printful, if it hasn't already been done.
        $printful_order_confirmed = get_post_meta( $order_id, '_printful_order_confirmed', true );

        if ( !$printful_order_confirmed ) {
            $printful_order = get_post_meta( $order_id, '_printful_order', true );

            if ( isset( $printful_order ) ) {
                $result = $this->printful->confirm_order( $printful_order['id'] );
                // var_dump( $result ); exit;
            }

            // update_post_meta( $order_id, '_printful_order_confirmed', true );
        }
    }

    /**
     * Format Printful order data from the current WooCommerce cart.
     * @return array
     */
    private function get_printful_order_data_from_cart() {
        $cart = WC()->cart;
        $items = [];

        $design_state = origenz()->get_design_state();
        $countries = origenz()->flatten_regions_to_countries( $design_state['regions'] );
        $shape = $design_state['shape'];

        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            $variation = $cart_item['data'];
            $variation_id = $variation->get_id();
            $variation_template = get_post_meta( $variation_id, '_printful_template', true );

            $design_state = $cart_item['design_state'];

            $items[] = [
                'external_id' => $variation_id,
                'variant_id' => get_post_meta( $variation_id, '_printful_variant_id', true ),
                'quantity' => $cart_item['quantity'],
                'retail_price' => $variation->get_price(),
                'name' => $variation->get_name(),
                'files' => [
                    [
                        'type' => isset( $variation_template['placement'] ) ? $variation_template['placement'] : 'default',
                        'url' => origenz()->get_flag_image_renderer()->get_full_size_image_url( $design_state['countries'], $design_state['shape'], $design_state['border_color'] ) // the url of the full size printful image
                    ]
                ],
                'packing_slip' => [
                    'email' => 'test@example.com', // Origenz email
                    'phone' => '111-222-3333', // Origenz phone
                    'message' => '' // Custom message.
                ]
            ];
        }

        // Get the customer's address info.
        $customer = WC()->customer;

        $data = [
            'shipping' => 'STANDARD', // shipping method
            'recipient' => [
                'name' => $customer->get_shipping_first_name() . ' ' . $customer->get_shipping_last_name(),
                'company' => $customer->get_shipping_company(),
                'address1' => $customer->get_shipping_address_1(),
                'address2' => $customer->get_shipping_address_2(),
                'city' => $customer->get_shipping_city(),
                'state_code' => $customer->get_shipping_state(),
                'state_name' => $customer->get_shipping_state(),
                'country_code' => $customer->get_shipping_country(),
                'country_name' => $customer->get_shipping_country(),
                'zip' => $customer->get_shipping_postcode(),
                'phone' => $customer->get_billing_phone(),
                'email' => $customer->get_billing_email()
            ],
            'items' => $items
        ];

        return $data;
    }

    /**
     * Format Printful order data from the specified WC Order.
     * @param int $order_id
     * @return array
     */
    private function get_printful_order_data_from_wc_order( $order_id ) {
        $order = wc_get_order( $order_id );
        $items = [];

        foreach ( $order->get_items() as $order_item ) {
            if ( $order_item instanceof WC_Order_Item_Product ) {
                $variation_id = $order_item->get_variation_id();
                $variation_template = get_post_meta( $variation_id, '_printful_template', true );

                $design_state = $order_item->get_meta( 'design_state' );

                $items[] = [
                    'external_id' => $variation_id,
                    'variant_id' => get_post_meta( $variation_id, '_printful_variant_id', true ),
                    'quantity' => $order_item->get_quantity(),
                    'retail_price' => $order_item->get_total(),
                    'name' => $order_item->get_name(),
                    'files' => [
                        [
                            'type' => isset( $variation_template['placement'] ) ? $variation_template['placement'] : 'default',
                            'url' => origenz()->get_flag_image_renderer()->get_full_size_image_url( $design_state['countries'], $design_state['shape'], $design_state['border_color'] ) // the url of the full size printful image
                        ]
                    ],
                    'packing_slip' => [
                        'email' => 'test@example.com', // Origenz email
                        'phone' => '111-222-3333', // Origenz phone
                        'message' => '' // Custom message.
                    ]
                ];
            }
        }

        // Get the 'Tax' fee to add to the retail costs.
        $tax_fee = 0.00;

        foreach ( $order->get_fees() as $fee ) {
            if ( $fee->get_name() == 'Tax' ) {
                $tax_fee = $fee->get_amount();
                break;
            }
        }

        $data = [
            'external_id' => origenz()->is_local_environment() ? 'TEST-' . $order_id : 'WC-' . $order_id,
            'shipping' => 'STANDARD', // shipping method
            'recipient' => [
                'name' => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
                'company' => $order->get_shipping_company(),
                'address1' => $order->get_shipping_address_1(),
                'address2' => $order->get_shipping_address_2(),
                'city' => $order->get_shipping_city(),
                'state_code' => $order->get_shipping_state(),
                'state_name' => $order->get_shipping_state(),
                'country_code' => $order->get_shipping_country(),
                'country_name' => $order->get_shipping_country(),
                'zip' => $order->get_shipping_postcode(),
                'phone' => $order->get_billing_phone(),
                'email' => $order->get_billing_email()
            ],
            'items' => $items,
            'retail_costs' => [
                'tax' => $tax_fee
            ]
        ];

        return $data;
    }

    /**
     * Create an order in Printful for a WC Order.
     * @param int $wc_order_id
     */
    public function create_printful_order( $wc_order_id ) {
        $existing_order_data = get_post_meta( $wc_order_id, '_printful_order', true );
        $data = $this->get_printful_order_data_from_wc_order( $wc_order_id );
        $order = wc_get_order( $wc_order_id );

        $images = [];

        foreach ( $data['items'] as $item ) {
            $images[] = $item['files'][0]['url'];
        }

        $order->add_order_note( 'These images were sent to Printful: ' . implode( ' , ', $images ) );

        return $this->printful->create_order( $data );
    }

    /**
     * Make a request to Printful to calculate costs for the current cart.  Used to calculate shipping and taxes.
     * @return array
     */
    public function estimate_printful_costs() {
        if ( $this->cached_printful_costs === null ) {
            $order_data = $this->get_printful_order_data_from_cart();

            try {
                $this->cached_printful_costs = $this->printful->estimate_costs( $order_data );
                //var_dump( $this->cached_printful_costs );
            } catch (Exception $e) {
                //var_dump($e);
                return false;
            }
        }

        return $this->cached_printful_costs;
    }

    /**
     * Parse an external ID to an order post ID.
     * @param string $external_id
     * @param bool $exclude_tests If test order IDs should be excluded and return null for them.
     * @return string The order ID or null if it couldn't be parsed/was excluded.
     */
    private function get_wc_order_id_from_external_id( $external_id, $exclude_tests = true ) {
        $pieces = explode( '-', $external_id );

        if ( $exclude_tests && $pieces[0] == 'TEST' && !origenz()->is_local_environment() ) {
            // Don't process webhooks for test orders on the live site.
            return null;
        }

        return count( $pieces ) == 2 ? $pieces[1] : $external_id;
    }

    /**
     * Log webhook data to theme/webhook-log.txt
     * @param array $data;
     */
    private function log_webhook( $data ) {
        $log_file = get_stylesheet_directory() . '/webhook-log.txt';

        error_log( 'Received Webhook at ' . date( 'F j Y, H:i:s' ) . "\n", 3, $log_file );
        error_log( print_r( $data, true ) . "\n==========\n", 3, $log_file );
    }

    /**
     * Handle the package_shipped webhook.
     * @param array $webhook_data
     */
    private function handle_package_shipped_webhook( $webhook_data ) {
        // Package shipped.
        $data = $webhook_data['data'];
        $external_order_id = $data['order']['external_id'];
        $order_id = $this->get_wc_order_id_from_external_id( $external_order_id );
        $shipment = $data['shipment'];

        // Test
        // $order_id = 4676; // TODO: remove

        if ( $order_id ) {
            $order = wc_get_order( $order_id );

            if ( $order && isset( $shipment['service'] ) ) {
                $note = 'Your order has been shipped via ' . $shipment['service'] . '.';

                if ( isset( $shipment['tracking_number'] ) ) {
                    $note .= ' Your tracking number is ' . $shipment['tracking_number'] . '.';
                }

                $order->add_order_note( $note, 1 );
                $order->set_status( 'complete' );
                $order->save();
            }
        }
    }

    /**
     * Handle the package_returned webhook.
     * @param array $webhook_data
     */
    private function handle_package_returned_webhook( $webhook_data ) {
        // Package returned.
    }

    /**
     * Handle the order_failed webhook.
     * @param array $webhook_data
     */
    private function handle_order_failed_webhook( $webhook_data ) {
        // Order failed.
        $data = $webhook_data['data'];
        $order_id = $this->get_wc_order_id_from_external_id( $data['order']['external_id'] );
        // $order_id = 4676; // TODO: remove

        if ( $order_id ) {
            $order = wc_get_order( $order_id );

            if ( $order ) {
                $order->set_status( 'failed', 'Printful order failed: ' . $data['reason'] . '.' );
                $order->save();
            }
        }
    }

    /**
     * Handle the order_canceled webhook.
     * @param array $webhook_data
     */
    private function handle_order_canceled_webhook( $webhook_data ) {
        // Order canceled.
    }

    /**
     * Handle the order_put_hold webhook.
     * @param array $webhook_data
     */
    private function handle_order_put_hold_webhook( $webhook_data ) {
        // Order put on hold.
    }

    /**
     * Handle the order_remove_hold webhook.
     * @param array $webhook_data
     */
    private function handle_order_remove_hold_webhook( $webhook_data ) {
        // Order removed from hold.
    }

    /**
     * Handle an incoming webhook request.
     */
    public function handle_webhook_request() {
        $out = [
            'success' => false
        ];

        if ( isset( $_GET['key'] ) && $_GET['key'] == self::WEBHOOK_KEY ) {
            $request_body = file_get_contents( 'php://input' );
            $in = json_decode( $request_body, true );

            if ( !empty( $in ) ) {
                if ( !origenz()->is_local_environment() || true ) { // TODO: Remove || true once they're all working.
                    $this->log_webhook( $in );

                    if ( in_array( $in['type'], $this->printful->get_webhook_types() ) ) {
                        $this->{'handle_' . $in['type'] . '_webhook'}( $in );
                    }
                }

                $out['success'] = true;
            }

            if ( !$out['success'] ) {
                http_response_code( 400 );
            }
        }

        wp_send_json( $out );
        exit;
    }
}

/*

Example webhook data:

{
    "type": "package_shipped",
    "created": 1559136059,
    "retries": 10,
    "store": 290,
    "data": {
        "shipment": {
            "id": 41452742,
            "status": "started",
            "carrier": "USPS",
            "service": "USPS Priority Mail",
            "tracking_number": "9405536895357169314393",
            "tracking_url": "https://www.printful.com/",
            "created": 1559136059,
            "ship_date": "1971-01-24",
            "shipped_at": 1559136059,
            "reshipment": false,
            "location": "USA",
            "estimated_delivery_dates": {
                "from": 1559136059,
                "to": 1559136059
            },
            "items": [
                {
                    "item_id": 719152,
                    "quantity": 1,
                    "picked": 1,
                    "printed": 1,
                    "is_started": true
                },
                {
                    "item_id": 866566,
                    "quantity": 2,
                    "picked": 2,
                    "printed": 2,
                    "is_started": true
                }
            ],
            "packing_slip_url": "https://www.printful.com/"
        },
        "order": {
            "id": 8146,
            "external_id": 7,
            "store": 3812,
            "status": "fulfilled",
            "error": null,
            "shipping": "USPS_PRIORITY",
            "created": 1559136059,
            "updated": 1559136059,
            "recipient": {
                "name": "Vita Haag",
                "company": "Goldner-Turner",
                "address1": "23858 Larkin Drives\nBergeview, CT 03010-2693",
                "address2": "385 Kirlin View\nNew Krystinahaven, NY 48108-5940",
                "city": "Altenwerthberg",
                "state_code": "MA",
                "state_name": "Massachusetts",
                "country_code": "US",
                "country_name": "United States",
                "zip": "80418",
                "phone": "1-438-296-9169 x1322",
                "email": "pgorczany@gislason.com"
            },
            "estimated_fulfillment": 1559136059,
            "notes": null,
            "activities": [
                {
                    "type": "started",
                    "time": 1559136059,
                    "note": null,
                    "message": "Fulfillment was started"
                },
                {
                    "type": "transaction",
                    "time": 1559136059,
                    "note": null,
                    "message": "Printful Wallet (via Credit Card) was charged for $100.00"
                },
                {
                    "type": "created",
                    "time": 1559136059,
                    "note": null,
                    "message": "Order placed automatically via Shopify"
                }
            ],
            "items": [
                {
                    "id": 683418,
                    "external_id": 9280451,
                    "variant_id": 69,
                    "quantity": 60,
                    "price": "7.00",
                    "retail_price": "2.00",
                    "name": "BAE Black and Educated - Sweatshirt - White / M",
                    "product": {
                        "variant_id": 2789115,
                        "product_id": 35505,
                        "image": "https://www.printful.com/",
                        "name": "Gildan 18000 Heavy Blend Crewneck Sweatshirt (White / M)"
                    },
                    "files": [
                        {
                            "id": 205,
                            "type": "default",
                            "hash": "9bd8d3e9c198a23ed8cd643ead4e901d",
                            "url": null,
                            "filename": "Untitled-2-Recovered.psd",
                            "mime_type": "image/x-psd",
                            "size": 2936091,
                            "width": 5100,
                            "height": 4500,
                            "dpi": 300,
                            "status": "ok",
                            "created": 1559136059,
                            "thumbnail_url": "https://www.printful.com/",
                            "preview_url": "https://www.printful.com/",
                            "visible": true
                        },
                        {
                            "id": 9,
                            "type": "preview",
                            "hash": "df5d2845f6d1d71b2b945626b5c61f44",
                            "url": null,
                            "filename": "mockup-a3ca32cb.jpg",
                            "mime_type": "image/jpeg",
                            "size": 70512,
                            "width": 1000,
                            "height": 1000,
                            "dpi": 72,
                            "status": "ok",
                            "created": 1559136059,
                            "thumbnail_url": "https://www.printful.com/",
                            "preview_url": "https://www.printful.com/",
                            "visible": false
                        }
                    ],
                    "options": [],
                    "sku": null,
                    "discontinued": false,
                    "out_of_stock": false
                },
                {
                    "id": 200,
                    "external_id": 68549864,
                    "variant_id": 8174,
                    "quantity": 50,
                    "price": "39.00",
                    "retail_price": "22.00",
                    "name": "BAE Black and Educated - Sweatshirt - White / L",
                    "product": {
                        "variant_id": 350,
                        "product_id": 9021449,
                        "image": "https://www.printful.com/",
                        "name": "Gildan 18000 Heavy Blend Crewneck Sweatshirt (White / L)"
                    },
                    "files": [
                        {
                            "id": 1409,
                            "type": "default",
                            "hash": "9bd8d3e9c198a23ed8cd643ead4e901d",
                            "url": null,
                            "filename": "Untitled-2-Recovered.psd",
                            "mime_type": "image/x-psd",
                            "size": 2936091,
                            "width": 5100,
                            "height": 4500,
                            "dpi": 300,
                            "status": "ok",
                            "created": 1559136059,
                            "thumbnail_url": "https://www.printful.com/",
                            "preview_url": "https://www.printful.com/",
                            "visible": true
                        },
                        {
                            "id": 9,
                            "type": "preview",
                            "hash": "df5d2845f6d1d71b2b945626b5c61f44",
                            "url": null,
                            "filename": "mockup-a3ca32cb.jpg",
                            "mime_type": "image/jpeg",
                            "size": 70512,
                            "width": 1000,
                            "height": 1000,
                            "dpi": 72,
                            "status": "ok",
                            "created": 1559136059,
                            "thumbnail_url": "https://www.printful.com/",
                            "preview_url": "https://www.printful.com/",
                            "visible": false
                        }
                    ],
                    "options": [],
                    "sku": null,
                    "discontinued": false,
                    "out_of_stock": false
                }
            ],
            "is_sample": false,
            "needs_approval": false,
            "not_synced": false,
            "has_discontinued_items": false,
            "can_change_hold": false,
            "costs": {
                "subtotal": "5.00",
                "discount": "51.00",
                "shipping": "3.00",
                "digitization": "18.00",
                "additional_fee": "92.00",
                "fulfillment_fee": "76.00",
                "tax": "27.00",
                "vat": "39.00",
                "total": "81.00"
            },
            "retail_costs": {
                "subtotal": "94.00",
                "discount": "60.00",
                "shipping": "95.00",
                "tax": "78.00",
                "vat": "42.00",
                "total": "29.00"
            },
            "shipments": [
                {
                    "id": 382474,
                    "status": "shipped",
                    "carrier": "USPS",
                    "service": "USPS Priority Mail",
                    "tracking_number": "2057308745865",
                    "tracking_url": "https://www.printful.com/",
                    "created": 1559136059,
                    "ship_date": "1991-05-01",
                    "shipped_at": 1559136059,
                    "reshipment": false,
                    "location": "USA",
                    "estimated_delivery_dates": {
                        "from": 1559136059,
                        "to": 1559136059
                    },
                    "items": [
                        {
                            "item_id": 34668804,
                            "quantity": 1,
                            "picked": 1,
                            "printed": 1,
                            "is_started": true
                        },
                        {
                            "item_id": 23521792,
                            "quantity": 2,
                            "picked": 2,
                            "printed": 2,
                            "is_started": true
                        }
                    ],
                    "packing_slip_url": "https://www.printful.com/"
                }
            ],
            "gift": null,
            "packing_slip": null,
            "dashboard_url": "https://www.printful.com/"
        }
    }
}

*/
