<?php

namespace Compulse;

use Exception;
use WP_Term;
use WC_Product;

/**
 * Bootstrap class for the Origenz site.
 */
final class Origenz {
    /**
     * @var FlagImageRenderer
     */
    private $flag_image_renderer;

    /**
     * @var PrintfulHelper
     */
    private $prinful_helper;

    /**
     * @var WooCommerceHelper
     */
    private $woocommerce_helper;

    /**
     * @var array
     */
    private $admin_notices;

    public function __construct() {
        $this->flag_image_renderer = new FlagImageRenderer();
        $this->printful_helper = new PrintfulHelper();
        $this->woocommerce_helper = new WooCommerceHelper( $this->get_printful_helper() );

        $this->admin_notices = [];

        try {
            $this->flag_image_renderer->check_dependencies();
        } catch ( Exception $e ) {
            $this->add_admin_notice( $e->getMessage(), 'error' );
        }

        $this->register_hooks();
        add_theme_support( 'woocommerce' );
        register_sidebar( [
            'name' => 'Shop Sidebar',
            'id' => 'shop-sidebar'
        ] );

        if ( function_exists( 'acf_add_options_page' ) ) {
            acf_add_options_page( [
                'page_title' => 'Flag Generator Settings',
                'menu_title' => 'Flag Generator Settings',
                'capability' => 'manage_options',
                'position' => '56',
                'icon_url' => 'dashicons-flag'
            ] );

            acf_add_options_page( [
                'page_title' => 'Printful Integration Settings',
                'menu_title' => 'Printful Integration Settings',
                'capability' => 'manage_options',
                'position' => '56.5',
                'icon_url' => 'dashicons-admin-links'
            ] );
        }
    }

    private function register_hooks() {
        add_action( 'admin_notices', array($this, 'display_admin_notices') );

        add_action( 'admin_menu', array($this, 'admin_menu'), 999 );
        add_action( 'wp_ajax_clear_flag_preview_cache', array($this, 'ajax_clear_flag_preview_cache') );

        add_action( 'init', array($this, 'init') );
        add_action( 'wp_enqueue_scripts', array($this, 'enqueue_scripts') );
        add_action( 'template_redirect', array($this, 'template_redirect'), 1 ); // hook before redirect_canonical()
        add_filter( 'query_vars', array($this, 'query_vars') );

        add_action( 'wp_ajax_save_design_state', array($this, 'ajax_save_design_state') );
        add_action( 'wp_ajax_nopriv_save_design_state', array($this, 'ajax_save_design_state') );
    }

    public function init() {
        add_shortcode( 'origenz_design', array($this, 'design_shortcode') );
        add_shortcode( 'origenz_design_choice', array($this, 'design_choice_shortcode') );

        // Image rewrite rules.
        add_rewrite_rule( '^flag-preview/?', 'index.php?flag_render=preview', 'top' );
        add_rewrite_rule( '^flag-full/?', 'index.php?flag_render=full', 'top' );

        // Webhook rewrite rule.
        add_rewrite_rule( '^printful-webhook/?', 'index.php?printful_webhook=1', 'top' );

        if ( isset( $_POST['designState'] ) && !isset( $_POST['action'] ) ) {
            $this->save_design_state();
        }
    }

    public function query_vars( $vars ) {
        $vars[] = 'flag_render';
        $vars[] = 'printful_webhook';
        return $vars;
    }

    public function admin_menu() {
        add_submenu_page( 'acf-options-flag-generator-settings', 'Advanced Flag Generator Settings', 'Advanced', 'manage_options', 'advanced-flag-generator-settings', array($this, 'advanced_menu_page') );
    }

    public function ajax_clear_flag_preview_cache() {
        $directory = get_template_directory() . '/flag-preview-cache';

        $directory_contents = scandir( $directory );

        foreach ( $directory_contents as $file ) {
            if ( $file != '.' && $file != '..' && $file != 'index.php' ) {
                unlink( $directory . '/' . $file );
            }
        }

        wp_send_json([]);
        exit;
    }

    public function advanced_menu_page() { ?>
        <div class="wrap">
            <h1>Advanced Flag Generator Settings</h1>
            <div>
                <div>
                    <?php submit_button( 'Clear Flag Preview Cache', 'primary', 'submit_clear_flag_preview_cache' ); ?>
                </div>
            </div>

            <div style="margin-top:30px;">
                <div>
                    <div><b>Current Nonce Values:</b></div>
                    <div>PREVIEW_IMAGE_NONCE: <?php echo wp_create_nonce( FlagImageRenderer::PREVIEW_IMAGE_NONCE ); ?></div>
                    <div>FULL_IMAGE_NONCE: <?php echo wp_create_nonce( FlagImageRenderer::FULL_IMAGE_NONCE ); ?></div>
                </div>
            </div>
        </div>

        <script>
            (function($) {
                $('#submit_clear_flag_preview_cache').click( function(e) {
                    console.log( 'clear flag preview cache' );

                    var btn = $(this);
                    var originalValue = btn.val();
                    btn.prop('disabled', true).val( 'Clearing...' );

                    $.post( '/wp-admin/admin-ajax.php', {
                        action: 'clear_flag_preview_cache'
                    }, function( resp ) {
                        btn.prop('disabled', false).val( originalValue + ' [Done]' );
                    } );
                    e.preventDefault();
                } );
            })(jQuery);
        </script>
    <?php }


    // Scripts and script data.
    public function enqueue_scripts() {
      wp_register_style( 'Swiper', 'https://cdnjs.cloudflare.com/ajax/libs/Swiper/4.3.5/css/swiper.min.css' );
      wp_enqueue_style('Swiper');
      wp_register_script( 'Swiper', 'https://cdnjs.cloudflare.com/ajax/libs/Swiper/4.3.5/js/swiper.min.js', null, null, true );
      wp_enqueue_script('Swiper');

    	wp_enqueue_style( 'origenz', get_stylesheet_directory_uri() . '/style.css', [], '1.0.0' );

    	wp_enqueue_script( 'origenz-vendor', get_stylesheet_directory_uri() . '/js/vendor.js', array(), '', true );
    	wp_enqueue_script( 'origenz-app', get_stylesheet_directory_uri() . '/js/app.js', array(), '', true );

        wp_localize_script( 'origenz-app', 'origenzAppData', $this->get_script_data() );
        wp_localize_script( 'origenz-app', 'origenzVariationTemplateData', $this->get_variation_template_data() );
    }

    private function get_variation_template_data() {
        if ( is_product() ) {
            $product = wc_get_product( get_the_ID() );
            $variations = $product->get_available_variations();
            $data = [];

            foreach ( $variations as $variation ) {
                $data[ $variation['variation_id'] ] = [
                    'black' => $this->get_variation_mockup_html( $variation['variation_id'], 'black' ),
                    'white' => $this->get_variation_mockup_html( $variation['variation_id'], 'white' )
                ];
            }

            return $data;
        } else {
            return [];
        }
    }

    private function get_script_data() {
        $data = [
            'regions' => $this->get_regions(),
            'shapes' => $this->get_flag_image_renderer()->get_shapes(),
            'designState' => $this->get_design_state(),
            'products' => $this->get_design_products(),
            'siteUrl' => site_url(),
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'flagPreviewNonce' => wp_create_nonce( FlagImageRenderer::PREVIEW_IMAGE_NONCE )
        ];

        return $data;
    }

    public function get_variation_mockup_html( $variation_id, $image_border_color = 'black', $design_choice = null ) {
        $template = get_post_meta( $variation_id, '_printful_template', true );

        if ( $design_choice === null ) {
            $design_choice = $this->get_design_choice( $image_border_color );
        }

        if ( isset( $template['template_id'] ) ) {
            $design_layer = sprintf( '<div class="product-mockup-design" style="background-image:url(\'%s\');"></div>', $design_choice['image'] ?? '' );
            $template_layer = sprintf( '<div class="product-mockup-template" style="background-image:url(\'%s\');"></div>', $template['image_url'] );

            return sprintf(
                '<div class="product-mockup-wrapper" data-template-dimensions="%d,%d" data-print-area-dimensions="%d,%d" data-print-area-offset="%d,%d" style="background-image:url(%s); background-color:%s;">%s%s</div>',
                $template['template_width'],
                $template['template_height'],
                $template['print_area_width'],
                $template['print_area_height'],
                $template['print_area_left'],
                $template['print_area_top'],
                $template['background_url'],
                !empty( $template['background_color'] ) ? $template['background_color'] : '#ffffff',
                $template['is_template_on_front'] ? $design_layer : $template_layer,
                $template['is_template_on_front'] ? $template_layer : $design_layer
            );
        } else {
            return '';
        }
    }

    public function get_regions( $parent = 0 ) {
        $regions_by_provider = [];

        // Get childless terms and then their parent names.
        $providers = [ 'AncestryDNA','23andMe','Somewhere else' ];

        foreach ( $providers as $provider ) {
            $regions = get_terms( 'region', [
                'childless' => true,
                'orderby' => 'name',
                'meta_query' => [
                    [
                        'key' => 'dna_test_provider',
                        'value' => $provider,
                        'compare' => 'LIKE'
                    ]
                ]
            ] );

            // Get region data for each country and filter out the ones that are empty.
            $regions = array_filter( array_map( array($this, 'get_region_data'), $regions ) );

            // Sort by group, then by name
            usort( $regions, function( $region1, $region2 ) {
                if ( $region1['group'] == $region2['group'] ) {
                    return strcmp( $region1['name'], $region2['name'] );
                } else {
                    return strcmp( $region1['group'], $region2['group'] );
                }
            } );

            $regions_by_provider[ $provider ] = $regions;
        }

        return $regions_by_provider;
    }

    public function get_region_data( WP_Term $region ) {
        $countries = get_posts( [
            'post_type' => 'country',
            'status' => 'publish',
            'posts_per_page' => -1,
            'tax_query' => [
                [
                    'taxonomy' => 'region',
                    'field' => 'term_id',
                    'terms' => $region->term_id
                ]
            ],
            'orderby' => 'title',
            'order' => 'asc'
        ] );

        // Filter out countries that don't have flag data.
        $countries = array_filter( $countries, function( $country ) {
            return $this->get_flag_image_renderer()->has_flag_data( $country->post_title );
        } );

        // Don't include regions with no countries.
        if ( empty( $countries ) ) {
            return false;
        }

        $group_name_pieces = [];
        $parent_region_id = $region->parent;

        while ( $parent_region_id != 0 ) {
            $parent_region = get_term( $parent_region_id );
            array_unshift( $group_name_pieces, $parent_region->name );
            $parent_region_id = $parent_region->parent;
        }

        $region_name = $region->name;
        $group_name = !empty( $group_name_pieces ) ? implode( ' - ', $group_name_pieces ) : $region_name;

        return [
            'id' => $region->term_id,
            'name' => html_entity_decode( $region_name ),
            'group' => html_entity_decode( $group_name ),
            'countries' => wp_list_pluck( $countries, 'post_title' )
        ];
    }





    public function template_redirect() {
        global $wp_query;

        // Flag preview request.
        if ( isset( $wp_query->query_vars['flag_render'] ) ) {
            if ( $wp_query->query_vars['flag_render'] == 'full' ) {
                $this->get_flag_image_renderer()->handle_full_size_request();
            } else {
                $this->get_flag_image_renderer()->handle_preview_request();
            }
        }

        // Webhook
        if ( isset( $wp_query->query_vars['printful_webhook'] ) ) {
            $this->woocommerce_helper->handle_webhook_request();
        }

        // If they're viewing a single product page, but haven't created their design yet, redirect to the design page.
        if ( is_product() ) {
            $design_choice = $this->get_design_choice();

            if ( empty( $design_choice ) ) {
                wc_add_notice( 'Please create your design before customizing your product.', 'error' );
                wp_redirect( site_url() . '/design' );
                exit;
            }
        }
    }






    // Flag designer.
    public function ajax_save_design_state() {
        $out = [];

        $this->save_design_state();

        wp_send_json( $out );
        exit;
    }

    public function save_design_state() {
        $in = filter_input_array( INPUT_POST, [
            'designState' => FILTER_DEFAULT
        ] );

        WC()->session->set_customer_session_cookie( true );

        if ( !empty( $in['designState'] ) ) {
            $data = json_decode( $in['designState'], true );

            if ( !empty( $data ) ) {
                $whitelist = ['regions','shape','step','dnaProcessedBy'];

                $session_data = [];
                foreach ( $whitelist as $key ) {
                    if ( isset( $data[$key] ) ) {
                        $session_data[$key] = $data[$key];
                    }
                }

                WC()->session->set( 'flag_designer_state', $session_data );

                // Do anything that needs to be done server-side during a step change.
                if ( $session_data['step'] == 2 ) {
                    // Create the design preview images so they'll be in cache when the front-end loads them (if they're not there already).
                    $percentages = [];
                    foreach ( $session_data['regions'] as $region ) {
                        foreach ( $region['countries'] as $country ) {
                            $percentages[ $country['country'] ] = $country['percent'] / 100;
                        }
                    }

                    $shapes = $this->get_flag_image_renderer()->get_shapes();
                    set_time_limit( 60 );

                    foreach ( $shapes as $shape ) {
                        $this->get_flag_image_renderer()->render_preview( $percentages, $shape );
                    }
                }
            }
        }
    }

    public function get_design_state() {
        $session_state = WC()->session->get( 'flag_designer_state' ) ?: [];

        if ( $session_state ) {
            $input_step = filter_input( INPUT_GET, 'step', FILTER_VALIDATE_INT, [
                'filter' => FILTER_VALIDATE_INT,
                'options' => [
                    'min_range' => 1,
                    'max_range' => 3
                ]
            ] );

            if ( !empty( $input_step ) ) {
                $session_state['step'] = $input_step;
            }
        }

        return $session_state;
    }

    public function get_design_choice( $image_border_color = 'black' ) {
        $state = $this->get_design_state();

        $choice = [];

        if ( !empty( $state ) && !empty( $state['regions'] ) && !empty( $state['shape'] ) ) {
            $percentages = [];
            $total_percent = 0;

            foreach ( $state['regions'] as $region ) {
                foreach ( $region['countries'] as $country ) {
                    $percentages[$country['country']] = $country['percent'];
                    $total_percent += $country['percent'];
                }
            }

            if ( $total_percent > 0 ) {
                $choice = [
                    'name' => implode( ' / ', array_keys( $percentages ) ) . ' ' . $state['shape'],
                    'image' => $this->get_flag_image_renderer()->get_preview_image_url( $percentages, $state['shape'], $image_border_color )
                ];
            }
        }

        return $choice;
    }

    public function design_shortcode( $atts = [], $content = '' ) {
        get_template_part( 'templates/design/design' );
    }

    public function design_choice_shortcode( $atts = [], $content = '' ) {
        get_template_part( 'templates/design/design-choice' );
    }

    public function get_design_products() {
        $parent_categories = get_terms( [
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
            'parent' => 0
        ] );

        $products = [];

        foreach ( $parent_categories as $category ) {
            $category_products = array_map( function( WC_Product $product ) {
                $image_id = $product->get_image_id();

                $external_image = get_post_meta( $product->get_id(), '_external_image', true );

                if ( !empty( $external_image ) ) {
                    $image_url = $external_image;
                } elseif ( $image_id ) {
                    $image_url = wp_get_attachment_image_url( $image_id );
                } else {
                    $image_url = wc_placeholder_img_src( 'full' );
                }

                return [
                    'id' => $product->get_id(),
                    'name' => html_entity_decode( $product->get_name() ),
                    'permalink' => $product->get_permalink(),
                    'image' => $image_url,
                    'price' => !empty( $product->get_price() ) ? number_format( $product->get_price(), 2 ) : ''
                ];
            }, wc_get_products( [
                'category' => $category->slug,
                'limit' => 4
            ] ) );

            $products[] = [
                'category' => [
                    'name' => html_entity_decode( $category->name ),
                    'slug' => $category->slug,
                    'permalink' => get_term_link( $category )
                ],
                'products' => $category_products
            ];
        }

        return $products;
    }






    // Utils

    public function add_admin_notice( $message, $type = 'success', $dismissible = true ) {
        $this->admin_notices[] = [
            'message' => $message,
            'type' => $type,
            'dismissible' => $dismissible
        ];
    }

    public function display_admin_notices() {
        if ( !empty( $this->admin_notices ) ) {
            foreach ( $this->admin_notices as $notice ) {
                printf(
                    '<div class="notice notice-%s%s"><p>%s</p></div>',
                    $notice['type'],
                    $notice['dismissible'] ? ' is-dismissible' : '',
                    esc_html( $notice['message'] )
                );
            }
        }
    }

    /**
     * Flatten an array of regions with their countries to just a list of countries.
     * @param array $regions
     * @return array
     */
    public function flatten_regions_to_countries( $regions ) {
        $countries = [];

        foreach ( $regions as $region ) {
            if ( isset( $region['countries'] ) ) {
                $countries = array_merge( $countries, $region['countries'] );
            }
        }

        return $countries;
    }

    public function is_local_environment() {
        return ( strpos( site_url(), 'localhost' ) !== false );
    }





    // Getters

    /**
     * @return FlagImageRenderer
     */
    public function get_flag_image_renderer() {
        return $this->flag_image_renderer;
    }

    /**
     * @return PrintfulHelper
     */
    public function get_printful_helper() {
        return $this->printful_helper;
    }

    /**
     * @return WooCommerceHelper
     */
    public function get_woocommerce_helper() {
        return $this->woocommerce_helper;
    }
}
