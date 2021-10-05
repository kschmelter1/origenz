<?php

namespace Compulse;

use Exception;
use Imagick;
use ImagickDraw;
use ImagickPixel;

/**
 * Handles rendering the flag collage image.
 */
final class FlagImageRenderer {
    const PREVIEW_IMAGE_NONCE = 'origenz_flag_image_preview_18d59b0d2ae57e2d5588bfbe551db12c';
    const FULL_IMAGE_NONCE = 'origenz_flag_image_full_18d59b0d2ae57e2d5588bfbe551db12c';
    const FULL_IMAGE_KEY = '18d59b0d2ae57e2d5588bfbe551db12c';
    const USE_CACHE = true;

    /**
     * @var array An assoc array of flag data indexed by country name.
     */
    private $flag_data;

    /**
     * @var array An assoc array of shape data indexed by shape name.
     */
    private $shape_data;

    /**
     * @var array Holds Imagick, ImagickDraw, ImagickPixel, etc. objects that can be destroy()'d at the end of the render process.
     */
    private $imagick_objects;

    /**
     * Loads flag and shape data.
     */
    public function __construct() {
        $flag_data = get_field( 'flags', 'options' );

        foreach ( $flag_data as $flag ) {
            $this->flag_data[ $flag['country']->post_title ] = $flag;
        }

        $shape_data = get_field( 'shapes', 'options' );

        foreach ( $shape_data as $shape ) {
            if ( !($shape['disable'] ?? false) ) {
                $this->shape_data[ $shape['shape_name'] ] = $shape;
            }
        }

        $this->imagick_objects = [];
    }

    /**
     * Create and store a new Imagick object that can be destroyed later.
     * @param string $class The name of the class to instantiate.
     * @param mixed ...$args Additional args to pass to the constructor.
     * @return mixed The created object.
     */
    private function create_imagick_object( $class, ...$args ) {
        $obj = new $class( ...$args );
        $this->imagick_objects[] = $obj;
        return $obj;
    }

    /**
     * Destroy all objects created with create_imagick_object().
     */
    private function destroy_imagick_objects() {
        foreach ( $this->imagick_objects as $obj ) {
            $obj->destroy();
        }
    }

    /**
     * Check the PHP dependencies for the renderer.
     * @return bool true if dependencies are OK.
     * @throws Exception Throws an exception if a dependency is incorrect, with the error message in the exception.
     */
    public function check_dependencies() {
        if ( !class_exists( 'Imagick' ) ) {
            throw new Exception( 'The Imagick PHP module is required to run this site.' );
            return false;
        } else {
            $required_version = '6.8.0';
            $version = Imagick::getVersion();

            $matches = [];
            if ( preg_match( '/^ImageMagick ([0-9]+\.[0-9]+\.[0-9]+)/', $version['versionString'], $matches ) ) {
                if ( version_compare( $matches[1], $required_version ) < 0 ) {
                    throw new Exception( 'Imagick must be compiled with at least ImageMagick version ' . $required_version . ' to run this site.' );
                    return false;
                }
            } else {
                throw new Exception( 'Failed to determine ImageMagick version. Please check the Imagick PHP module.' );
            }
        }

        return true;
    }

    /**
     * Get the flag data for a country.
     * @param string $country
     * @return array The flag data or null if the flag data doesn't exist.
     */
    public function get_flag_data( $country ) {
        return $this->has_flag_data( $country ) ? $this->flag_data[$country] : null;
    }

    public function has_flag_data( $country ) {
        return isset( $this->flag_data[$country] );
    }

    /**
     * Get the shape data for a shape.
     * @param string $shape
     * @return array The shape data or null if the shape data doesn't exist.
     */
    public function get_shape_data( $shape ) {
        return isset( $this->shape_data[$shape] ) ? $this->shape_data[$shape] : null;
    }

    public function get_shapes() {
        return array_keys( $this->shape_data );
    }

    /**
     * Retrieve the directory path for where the flag images are stored (no trailing slash).
     */
    public function get_flag_path() {
        return get_template_directory() . '/flags';
    }

    /**
     * Utility to draw the specified path on the specified ImagickDraw object.
     * @param ImagickDraw $draw The draw object.
     * @param SVGPath $path
     */
    private function draw_shape_path( ImagickDraw $draw, SVGPath $path ) {
        $draw->pathStart();

        foreach ( $path as $command ) {
            switch ( $command['type'] ) {
                case 'M':
                    $draw->pathMoveToAbsolute( $command['points'][0][0], $command['points'][0][1] );
                    break;

                case 'C':
                    $draw->pathCurveToAbsolute(
                        $command['points'][0][0],
                        $command['points'][0][1],
                        $command['points'][1][0],
                        $command['points'][1][1],
                        $command['points'][2][0],
                        $command['points'][2][1]
                    );
                    break;

                case 'L':
                    $draw->pathLineToAbsolute( $command['points'][0][0], $command['points'][0][1] );
                    break;

                case 'Z':
                    $draw->pathClose();
                    break;
            }
        }

        $draw->pathFinish();
    }

    /**
     * Render the image.
     * @param array $config [
     *      'percentages' => array - Assoc array where key is the country name and value is the decimal percent of that flag.
     *          They will be rendered in order.  If the percentages don't add up to 100%, they will be normalized to make them equal 100%.
     *      'shape' => string - The name of the shape to use.
     *      'rotation' => float - The degrees to rotate the image.
     *      'background_color' => string - The background color to use on the image.
     *      'preview' => boolean - true to render a lower-quality thumbnail to use on the site
     *          or false to render the full-size image that should be sent to the printer.
     *      'display' => boolean - true to display the image and exit, false to return the raw image data
     * ]
     * @return string The raw image data blob if $config['display'] is false.
     * @throws Exception Throws an exception containing the error message, if an error occurs
     */
    public function render( $config = [] ) {
        // Merge config with defaults.
        $config = map_merge_recursive( [
            'percentages' => [],
            'shape' => 'Fire',
            'rotation' => null, // overridden once shape data is loaded
            'background_color' => 'transparent',
            'border_color' => 'black', // black or white
            'outline_color' => 'transparent', // transparent or white
            'preview' => false,
            'display' => false
        ], $config );



        if ( empty( $config['percentages'] ) ) {
            throw new Exception( 'percentages cannot be empty.' );
        }

        $num_percentages = count( $config['percentages'] );

        // Make sure percentages equal 100%.
        $percentages_sum = array_sum( $config['percentages'] );
        if ( $percentages_sum != 1.0 ) {
            $diff = $percentages_sum - 1.0;
            $diff_per_percentage = $diff / $num_percentages;

            // Equally add/subtract the difference from all flags.
            foreach ( $config['percentages'] as &$percentage ) {
                $percentage -= $diff_per_percentage;
            }
        }


        $main_image_width = $config['preview'] ? 600.0 : 3000.0; // Main image width should be at least 1000 px.
        $main_image_height = 1000.0;  // This is changed based on the shape below.
        $main_image_padding = 500.0; // Padding added to the left and bottom of the image, in case we need to rotate.  This will be trimmed at the end.


        // Load shape data.
        $shape_data = $this->get_shape_data( $config['shape'] );

        if ( $shape_data == null ) {
            throw new Exception( 'Shape data does not exist for shape: ' . $config['shape'] );
        }

        if ( $config['rotation'] === null ) {
            $config['rotation'] = floatval( $shape_data['default_rotation'] ?? 0.0 );
        }

        $shape_path = new SVGPath( $shape_data['path_definition'] );

        // Determine the scale factor to make the image fit 1000px.
        $scale_factor = $main_image_width / $shape_path->get_width();
        $main_image_height = $shape_path->get_height() * $scale_factor;

        $shape_bounds = $shape_path->get_bounds( $scale_factor, $scale_factor );
        $shape_width = $shape_path->get_width( $scale_factor, $scale_factor );
        $shape_height = $shape_path->get_height( $scale_factor, $scale_factor );


        // Load flag data and create the images for them.
        $flag_path = $this->get_flag_path();

        $total_percentage = 0.0;

        $flag_data = [];

        try {
            foreach ( $config['percentages'] as $country => $percentage ) {
                $flag_data[$country] = $this->get_flag_data( $country );

                if ( $flag_data[$country] == null ) {
                    throw new Exception( 'Flag data does not exist for country: ' . $country );
                }

                $flag_data[$country]['percentage'] = $percentage;

                $flag_filename = $flag_path . '/' . $flag_data[$country]['file_name'];

                if ( !is_readable( $flag_filename ) ) {
                    throw new Exception( 'Cannot read flag file ' . $flag_data[$country]['file_name'] . ' from ' . $flag_path );
                }

                // Load the flag image into Imagick.
                $image = $this->create_imagick_object( 'Imagick' );

                if ( !$image->readImage( $flag_filename ) ) {
                    throw new Exception( 'Failed to read image: ' . $flag_filename );
                }

                // Resize and reposition the image based on the percent that should be shown.
                $x = 0;
                $y = 0;
                $resize_width = 0;
                $resize_height = 0;

                if ( $shape_data['orientation'] == 'x' ) {
                    // Shape shows flags from left to right.
                    // Region of the original shape that we're looking at.
                    $shape_region = [
                        [ $shape_width * $total_percentage, 0 ],
                        [ ($shape_width * $total_percentage) + ($shape_width * $percentage), $shape_height ]
                    ];
                } else {
                    // Shape shows flags from top to bottom.
                    // Region of the original shape that we're looking at.
                    $shape_region = [
                        [ 0, $shape_height * $total_percentage ],
                        [ $shape_width, ($shape_height * $total_percentage) + ($shape_height * $percentage) ]
                    ];
                }

                // Get the bounds that the shape path actually occupies within this region.
                $shape_flag_bounds = $shape_path->get_bounds( $scale_factor, $scale_factor, $shape_region );

                // var_dump($shape_flag_bounds);

                // var_dump($country, $shape_region, $shape_flag_bounds);

                $x = $shape_flag_bounds[0][0];
                $y = $shape_flag_bounds[0][1];
                $width = 0;
                $height = 0;

                if ( $flag_data[$country]['smush'] ) {
                    // The flag can be smushed, so ignore proportionality and make it fit in its bounds.
                    $width = $shape_flag_bounds[1][0] - $shape_flag_bounds[0][0] + 7.0; // with padding
                    $height = $shape_flag_bounds[1][1] - $shape_flag_bounds[0][1] + 7.0; // with padding

                    $image->adaptiveResizeImage( $width, $height );
                } else {
                    // The flag cannot be smushed.
                    // Resize and reposition around the anchor point and extract a "slice" of the flag.
                    $anchor = [$flag_data[$country]['anchor']['x'], $flag_data[$country]['anchor']['y']];
                    $height = $shape_flag_bounds[1][1] - $shape_flag_bounds[0][1] + 10.0; // with padding
                    $width = $shape_flag_bounds[1][0] - $shape_flag_bounds[0][0] + 10.0; // with padding

                    $crop_x = 0;
                    $crop_y = 0;

                    // Make the flag fit the width or height, whichever is larger.
                    if ( $height >= $width ) {
                        // resize to fit height.
                        $resize_height = $height;
                        $resize_width = $image->getImageWidth() * ($height / $image->getImageHeight());

                        // If the resized width doesn't fit, resize to fit height instead.
                        if ( $resize_width < $width ) {
                            $resize_width = $width;
                            $resize_height = $image->getImageHeight() * ($width / $image->getImageWidth());
                        }
                    } else {
                        // resize to fit width.
                        $resize_width = $width;
                        $resize_height = $image->getImageHeight() * ($width / $image->getImageWidth());

                        // If the resized height doesn't fit, resize to fit height instead.
                        if ( $resize_height < $height ) {
                            $resize_height = $height;
                            $resize_width = $image->getImageWidth() * ($height / $image->getImageHeight());
                        }
                    }

                    // Add a certain percent in width/height to account for flags with things in extreme-left/right or extreme-top/bottom.
                    $anchor_scale = max( 1 + abs( $anchor[0] - 0.5 ), 1 + abs( $anchor[1] - 0.5 ) );
                    $resize_width *= $anchor_scale;
                    $resize_height *= $anchor_scale;

                    $image->adaptiveResizeImage( $resize_width, $resize_height );

                    // crop around the anchor point.
                    $crop_x = max( ($resize_width * $anchor[0]) - ($width / 2), 0 );
                    $crop_y = max( ($resize_height * $anchor[1]) - ($height / 2), 0 );

                    $image->cropImage( $width, $height, $crop_x, $crop_y );
                }

                $flag_data[$country]['width'] = $width;
                $flag_data[$country]['height'] = $height;
                $flag_data[$country]['x'] = $x;
                $flag_data[$country]['y'] = $y;
                $flag_data[$country]['image'] = $image;

                // var_dump( $flag_data[$country] );

                $total_percentage += $percentage;
            }
        } catch ( Exception $e ) {
            // If an exception is thrown while creating flag images, destroy everything that was created thus far
            // and throw the exception again so the caller can handle it.
            $this->destroy_imagick_objects();
            throw $e;
        }

        // header( 'Content-Type: text/html' ); exit;

        // Create pixels.
        $background_pixel = $this->create_imagick_object( 'ImagickPixel', $config['background_color'] );
        $white_pixel = $this->create_imagick_object( 'ImagickPixel', 'white' );
        $black_pixel = $this->create_imagick_object( 'ImagickPixel', 'black' );
        $border_pixel = $this->create_imagick_object( 'ImagickPixel', $config['border_color'] == 'white' ? '#dddddd' : '#222222' );
        $outline_pixel = $this->create_imagick_object( 'ImagickPixel', $config['outline_color'] );
        $transparent_pixel = $this->create_imagick_object( 'ImagickPixel', 'transparent' );


        // Draw the shape, translated and scaled to match the flag image.
        // Used for the flag collage clip mask.
        $shape_draw = $this->create_imagick_object( 'ImagickDraw' );
        $shape_draw->setFillColor( $white_pixel );
        $shape_draw->translate( 5.0, 5.0 );
        $shape_draw->scale( $scale_factor, $scale_factor );
        $this->draw_shape_path( $shape_draw, $shape_path );


        // Create the image for the flag collage.
        $flag_image = $this->create_imagick_object( 'Imagick' );
        $flag_image->newImage( $main_image_width + 10.0, $main_image_height + 10.0, $background_pixel );


        // Create and apply the clip mask for the flag collage.
        $flag_clip_mask_image = $this->create_imagick_object( 'Imagick' );
        $flag_clip_mask_image->newPseudoImage( $main_image_width + 10.0, $main_image_height + 10.0, 'canvas:transparent' );
        $flag_clip_mask_image->drawImage( $shape_draw );
        $flag_clip_mask_image->negateImage( true ); // Negate the image so everything except the filled shape is clipped.
        $flag_image->setImageClipMask( $flag_clip_mask_image );

        // Images composited onto the flag image will now be copied based on the shape's clip mask.
        // Add the flags.

        $separator_lines = []; // Holds coords for where separator lines should be drawn.

        $i = 0;

        foreach ( $flag_data as $country => $data ) {
            // Copy the flag image onto the flag collage.
            $flag_image->compositeImage( $data['image'], Imagick::COMPOSITE_COPY, $data['x'], $data['y'] );

            // Determine where separator lines should be.
            if ( $i > 0 ) { // Do this for everything except the first one.
                if ( $shape_data['orientation'] == 'x' ) {
                    // Shape is left to right, so separators go top to bottom.
                    $separator_lines[] = [
                        [ $data['x'], 0 ],
                        [ $data['x'], $main_image_height ]
                    ];
                } else {
                    // Shape is top to bottom, so separators go left to right.
                    $separator_lines[] = [
                        [ 0, $data['y'] ],
                        [ $main_image_width, $data['y'] ]
                    ];
                }
            }

            $i++;
        }

        // Black lines that go at the top and bottom/left and right of gaps.
        // Gaps are drawn with a border at the top and bottom/left and right, but it
        // gives the effect that the border is wrapping around the individual shape pieces.
        $separator_line_draw = $this->create_imagick_object( 'ImagickDraw' );
        $separator_line_draw->setFillColor( $background_pixel );
        $separator_line_draw->setStrokeColor( $border_pixel );
        $separator_line_draw->setStrokeWidth( 3.0 * $scale_factor );
        // $separator_line_draw->scale( $scale_factor, $scale_factor );

        // Transparent separators that will be used as a clip mask at the very end.
        // This can't be added to the clip mask for the flag collage because the
        // main outline around the entire shape needs to be masked as well.
        $separator_gap_draw = $this->create_imagick_object( 'ImagickDraw' );
        $separator_gap_draw->setFillColor( $white_pixel );

        $gap_width = .2;

        if ( $shape_data['orientation'] == 'x' ) {
            // Left to right
            $separator_gap_padding_x = $gap_width * $scale_factor;
            $separator_gap_padding_y = 5 * $scale_factor;
        } else {
            // Top to bottom.
            $separator_gap_padding_x = 5 * $scale_factor;
            $separator_gap_padding_y = $gap_width * $scale_factor;
        }


        foreach ( $separator_lines as $line ) {
            $separator_line_draw->rectangle(
                $line[0][0] - ($gap_width * $scale_factor),
                $line[0][1] - ($gap_width * $scale_factor),
                $line[1][0] + ($gap_width * $scale_factor),
                $line[1][1] + ($gap_width * $scale_factor)
            );

            $separator_gap_draw->rectangle(
                $line[0][0] - $separator_gap_padding_x,
                $line[0][1] - $separator_gap_padding_y,
                $line[1][0] + $separator_gap_padding_x,
                $line[1][1] + $separator_gap_padding_y
            );
        }

        // Draw the black lines in the gaps between flags.
        $flag_image->drawImage( $separator_line_draw );

        // Draw the main black border.
        $border_draw = $this->create_imagick_object( 'ImagickDraw' );
        $border_draw->setFillColor( $transparent_pixel );
        $border_draw->setStrokeColor( $border_pixel );
        $border_draw->translate(5, 5);
        $border_draw->setStrokeWidth( 1 );
        $border_draw->scale( $scale_factor, $scale_factor );
        $this->draw_shape_path( $border_draw, $shape_path );

        // Draw the outline.
        $outline_draw = $this->create_imagick_object( 'ImagickDraw' );
        $outline_draw->setFillColor( $transparent_pixel );
        $outline_draw->setStrokeColor( $outline_pixel );
        $outline_draw->translate(5, 5);
        $outline_draw->setStrokeWidth( 5 );

        // Add 5 additional pixels to the scale factor.
        $outline_scale_factor = (($scale_factor * $shape_path->get_width()) + 5) / $shape_path->get_width();

        $outline_draw->scale( $outline_scale_factor, $outline_scale_factor );
        $this->draw_shape_path( $outline_draw, $shape_path );

        // Create the main image which combines everything created above.
        $main_image = $this->create_imagick_object( 'Imagick' );
        $main_image->newImage( $main_image_width + $main_image_padding, $main_image_height + $main_image_padding, $background_pixel );

        // Set 300 PPI if it's not a preview.
        if ( !$config['preview'] ) {
            $main_image->setImageUnits( Imagick::RESOLUTION_PIXELSPERINCH );
            $main_image->setImageResolution( 300, 300 );
        }

        // Set png stuff
        $main_image->setImageFormat( 'png' );
        $main_image->setImageVirtualPixelMethod( Imagick::VIRTUALPIXELMETHOD_TRANSPARENT );
        $main_image->setImageArtifact( 'compose:args', '1,0,-0.5,0.5' );

        // Mask to clip out the gaps between flags.
        $separator_gap_mask = $this->create_imagick_object( 'Imagick' );
        $separator_gap_mask->newPseudoImage( $main_image_width + $main_image_padding, $main_image_height + $main_image_padding, 'canvas:transparent' );
        $separator_gap_mask->drawImage( $separator_gap_draw );
        $main_image->setImageClipMask( $separator_gap_mask );

        // Put the flag collage on first.
        // This is already clipped to match the shape and already has black lines in gaps.
        $main_image->compositeImage( $flag_image, Imagick::COMPOSITE_COPY, 0, 0 );

        // Draw the outline.
        $main_image->drawImage( $outline_draw );

        // Draw the black border.
        $main_image->drawImage( $border_draw );

        // Apply rotation.
        if ( $config['rotation'] != 0 ) {
            $main_image->rotateImage( $background_pixel, $config['rotation'] );
        }

        // Trim extra space around the image.
        $main_image->trimImage( 0 );

        // Done!
        $image_blob = $main_image->getImageBlob();

        // Clean up.
        $this->destroy_imagick_objects();

        // Display/return the image data.
        if ( $config['display'] ) {
            header( 'Content-Type: image/png' );
            echo $image_blob;
            exit;
        }

        return $image_blob;
    }

    public function get_image_url_data( $countries, $shape, $border_color = 'black' ) {
        $country_data = [];

        foreach ( $countries as $k => $v ) {
            if ( isset( $v['country'], $v['percent'] ) ) {
                // Data is already formatted correctly.
                $country_data[] = $v;
            } else {
                $country_data[] = [
                    'country' => $k,
                    'percent' => $v
                ];
            }
        }

        return [
            'shape' => $shape,
            'countries' => $country_data,
            'border_color' => $border_color
        ];
    }

    public function get_preview_image_url( $countries, $shape, $border_color = 'black' ) {
        $image_data = $this->get_image_url_data( $countries, $shape, $border_color );
        return site_url() . '/flag-preview/?nonce=' . wp_create_nonce( self::PREVIEW_IMAGE_NONCE ) . '&data=' . urlencode( json_encode( $image_data ) );
    }

    public function get_full_size_image_url( $countries, $shape, $border_color = 'black' ) {
        $image_data = $this->get_image_url_data( $countries, $shape, $border_color );

        $site_url = site_url();
        if ( origenz()->is_local_environment() ) {
            $site_url = 'http://kutv97447site.wpengine.com';
        }

        return $site_url . '/flag-full/?key=' . self::FULL_IMAGE_KEY . '&data=' . urlencode( json_encode( $image_data ) );
    }

    /**
     * Render/load the preview image and store it in cache.
     * @param array $percentages Assoc array where key is the country name and value is percent (in decimal form).
     * @param string $shape
     * @param string $border_color
     * @return string The raw image data.
     */
    public function render_preview( $percentages, $shape, $border_color = 'black' ) {
        $cache_key = md5( $shape . '-' . serialize( $percentages ) . '-' . $border_color );

        $cache_file = get_template_directory() . '/flag-preview-cache/' . $cache_key . '.png';

        if ( self::USE_CACHE && is_readable( $cache_file ) ) {
            return file_get_contents( $cache_file );
        } else {
            // Create the image and cache it.
            try {
                $result = $this->render( [
                    'percentages' => $percentages,
                    'shape' => $shape,
                    'background_color' => 'transparent',
                    'border_color' => $border_color,
                    'preview' => true,
                    'display' => false
                ] );

                // Save the image to local cache.
                file_put_contents( $cache_file, $result );

                // Display the image.
                return $result;
            } catch (Exception $e) {
                var_dump($e);
            }
        }

        return null;
    }

    /**
     * Handle a request for the image preview.
     */
    public function handle_preview_request() {
        $in = filter_input_array( INPUT_GET, [
            'nonce' => FILTER_DEFAULT,
            'data' => FILTER_DEFAULT
        ] );

        header( 'Content-Type: image/png' );

        // Make sure everything looks good in the request.
        if ( !empty( $in['nonce'] ) && !empty( $in['data'] ) ) {
            //if ( wp_verify_nonce( $in['nonce'], self::PREVIEW_IMAGE_NONCE ) ) {
                $data = json_decode( urldecode( $in['data'] ), true );

                if ( !empty( $data ) && isset( $data['shape'], $data['countries'] ) ) {
                    $percentages = [];

                    foreach ( $data['countries'] as $country ) {
                        $percentages[ $country['country'] ] = $country['percent'] / 100;
                    }

                    $border_color = ($data['border_color'] && in_array( $data['border_color'], ['black','white'] )) ? $data['border_color'] : 'black';

                    $image = $this->render_preview( $percentages, $data['shape'], $border_color );
                    if ( !empty( $image ) ) {
                        echo $image;
                    }
                }
            //}
        }

        exit;
    }

    /**
     * Handle a request for the full-size image (from Printful).
     */
    public function handle_full_size_request() {
        $in = filter_input_array( INPUT_GET, [
            'key' => FILTER_DEFAULT,
            'data' => FILTER_DEFAULT
        ] );

        header( 'Content-Type: image/png' );

        // Make sure everything looks good in the request.
        if ( !empty( $in['key'] ) && !empty( $in['data'] ) ) {
            if ( $in['key'] == self::FULL_IMAGE_KEY ) {
                $data = json_decode( urldecode( $in['data'] ), true );

                if ( !empty( $data ) && isset( $data['shape'], $data['countries'] ) ) {
                    $shape = $data['shape'];
                    $percentages = [];

                    foreach ( $data['countries'] as $country ) {
                        $percentages[ $country['country'] ] = $country['percent'] / 100;
                    }

                    $border_color = ($data['border_color'] && in_array( $data['border_color'], ['black','white'] )) ? $data['border_color'] : 'black';

                    try {
                        $this->render( [
                            'percentages' => $percentages,
                            'shape' => $shape,
                            'background_color' => 'transparent',
                            'preview' => false,
                            'display' => true,
                            'border_color' => $border_color
                        ] );
                    } catch (Exception $e) {
                        // var_dump($e);
                    }
                }
            }
        }

        exit;
    }
}






























/////
