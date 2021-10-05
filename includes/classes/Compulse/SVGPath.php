<?php

namespace Compulse;

use Iterator;

/**
 * Parses an SVG path definition into line commands/points that can be used to draw with
 * ImagickDraw.  Also provides utilities to calculate real shape bounds for different regions of the
 * shape.
 * Implements Iterator so objects can be iterated over to get line commands with their points.
 */
final class SVGPath implements Iterator {
    /**
     * @var array
     */
    private $commands;

    /**
     * @var int
     */
    private $iterator_index;

    /**
     * @var array
     */
    private $bounds_cache;

    /**
     * @param string $definition SVG path definition with one command per line (i.e. commands separated by new line character)
     * @param bool $normalize If the path should be normalized where its top-left corner is at (0, 0).
     */
    public function __construct( $definition, $normalize = true ) {
        $this->commands = $this->parse_commands( $definition );
        $this->iterator_index = 0;
        $this->bounds_cache = [];

        if ( $normalize ) {
            $this->normalize();
        }
    }

    /**
     * Parse SVG path defintion commands into an array of commands with their points.
     * Supports M, C, L, and Z commands. Commands must be separated by new lines.
     * @param string $definition The path definition.
     * @return array
     */
    private function parse_commands( $definition ) {
        $commands = [];

        $definition_lines = explode( "\n", $definition );

        foreach ( $definition_lines as $line ) {
            $point_string = substr( $line, 1 );
            $points = [];

            if ( $line[0] != 'Z' ) {
                // Split on space, then split on comma, then trim each value, then convert to floats.
                $points = array_map( function( $p ) {
                    return array_map( function( $coord ) {
                        return floatval( trim( $coord ) );
                    }, explode( ',', $p ) );
                }, explode( ' ', $point_string ) );
            }

            $commands[] = [
                'type' => $line[0],
                'points' => $points
            ];
        }

        return $commands;
    }

    /**
     * @param float $scale_factor_x
     * @param float $scale_factor_y
     * @param array $region [[x1,y1],[x2,y2]]
     * @return string The cache key for this scale factor/region.
     */
    private function get_cache_key( $scale_factor_x, $scale_factor_y, $region ) {
        return $scale_factor_x . ',' . $scale_factor_y . ',' . $region[0][0] . ',' . $region[0][1] . ',' . $region[1][0] . ',' . $region[1][1];
    }

    /**
     * Clear the pre-calculated bounds cache.
     */
    private function clear_bounds_cache() {
        $this->bounds_cache = [];
    }

    /**
     * Get the Bézier curve value at specified t with specified control points.
     * @param float $c0 Starting point
     * @param float $c1 Control point 1
     * @param float $c2 Control point 2
     * @param float $c3 Ending point
     * @param float $t 0.0 - 1.0
     * @return float
     */
    private function curve_value( $c0, $c1, $c2, $c3, $t ) {
        return (
            ( ($t * $t * $t) * ( (-$c0) + (3 * $c1) + (-3 * $c2) + $c3 ) ) +
            ( ($t * $t) * ( (3 * $c0 ) + ( -6 * $c1 ) + ( 3 * $c2 ) ) ) +
            ( $t * ( ( -3 * $c0 ) + ( 3 * $c1 ) ) ) +
            $c0
        );
    }

    /**
     * Get the line value at specified t with specified start and end point.
     * @param float $c0 Starting point
     * @param float $c1 Ending point
     * @param float $t 0.0 - 1.0
     * @return float
     */
    private function line_value( $c0, $c1, $t ) {
        return (($c1 - $c0) * $t) + $c0;
    }

    /**
     * Determine if a point is within a region.
     * @param array $p [x,y]
     * @param array $region [[x1,y1],[x2,y2]]
     */
    private function within_region( array $p, array $region ) {
        return ( $p[0] >= $region[0][0] && $p[0] <= $region[1][0] && $p[1] >= $region[0][1] && $p[1] <= $region[1][1] );
    }

    /**
     * Scale a point.
     * @param array $p [x,y]
     * @param float $scale_factor_x
     * @param float $scale_factor_y
     * @return array [x,y]
     */
    private function scale_point( array $p, $scale_factor_x, $scale_factor_y ) {
        return [ $p[0] * $scale_factor_x, $p[1] * $scale_factor_y ];
    }

    /**
     * Normalize the points in the path commands by calculating the bounds and shifting all of the
     * points so the shape's top-left corner is at (0,0).
     */
    public function normalize() {
        $bounds = $this->get_bounds();

        // Shift all of the points so they line up on the top left edge of the draw frame.
        foreach ( $this->commands as &$command ) {
            if ( !empty( $command['points'] ) ) {
                foreach ( $command['points'] as &$p ) {
                    $p[0] = $p[0] - $bounds[0][0];
                    $p[1] = $p[1] - $bounds[0][1];
                }
            }
        }

        // Any previously calculated bounds are now incorrect, so clear the cache.
        $this->clear_bounds_cache();
    }

    /**
     * *Generator* for retrieving points along this path using the specified resolution and optionally scaled.
     * @param float $scale_factor_x The scale factor by which to multiply each x coordinate
     * @param float $scale_factor_y The scale factor by which to multiply each y coordinate.
     * @param int $resolution The number of segments into which each curve/line should be separated to get the points.
     *      Higher resolution is more accurate, but takes more time.
     * @param array $region Limit points retrieved to the specified region. Region should already be scaled. $region = [[x0,y0],[x1,y1]]
     */
    public function get_points( $scale_factor_x = 1.0, $scale_factor_y = 1.0, $resolution = 100, array $region = [] ) {
        if ( empty( $region ) ) {
            $region = [
                [PHP_INT_MIN, PHP_INT_MIN],
                [PHP_INT_MAX, PHP_INT_MAX]
            ];
        }

        $start_point = [];

        $last_m_command = false;

        $delta_t = 1.0 / $resolution;

        // Loop through each command and yield the points that the command creates on its curve/line.
        // Also scales each point after it has been calculated and determines if it's in the
        // region before yielding.
        foreach ( $this as $i => $command ) {
            $num_points = count( $command['points'] );

            if ( $command['type'] == 'M' ) {
                $last_m_command = $command;
            } else {
                if ( $command['type'] == 'Z' ) {
                    // Get path starting point.
                    $end_point = $last_m_command['points'][0];
                } else {
                    $end_point = $command['points'][ $num_points - 1 ];
                }

                // t = 0.0
                $p = $this->scale_point( $start_point, $scale_factor_x, $scale_factor_y );
                if ( $this->within_region( $p, $region ) ) {
                    yield $p;
                }

                // 0.0 < t < 1.0
                for ( $t = $delta_t; $t < 1.0; $t += $delta_t ) {
                    if ( $command['type'] == 'C' ) {
                        $p = [
                             $this->curve_value( $start_point[0], $command['points'][0][0], $command['points'][1][0], $end_point[0], $t ), // x
                             $this->curve_value( $start_point[1], $command['points'][0][1], $command['points'][1][1], $end_point[1], $t ) // y
                        ];
                    } else { // L or Z
                        $p = [
                            $this->line_value( $start_point[0], $end_point[0], $t ), // x
                            $this->line_value( $start_point[1], $end_point[1], $t ) // y
                        ];
                    }

                    $p = $this->scale_point( $p, $scale_factor_x, $scale_factor_y );
                    if ( $this->within_region( $p, $region ) ) {
                        yield $p;
                    }
                }

                // t = 1.0
                $p = $this->scale_point( $end_point, $scale_factor_x, $scale_factor_y );
                if ( $this->within_region( $p, $region ) ) {
                    yield $p;
                }
            }

            if ( $command['type'] != 'Z' ) {
                $start_point = $command['points'][ $num_points - 1 ];
            }
        }
    }

    /**
     * Calculate the bounds of this shape with the scale factor and pre-scaled region.
     * This function caches its results based on scale factor and region, to reduce the number
     * of calculations.
     * @param float $scale_factor_x
     * @param float $scale_factor_y
     * @param array $region [[x1,y1],[x2,y2]]
     */
    public function get_bounds( $scale_factor_x = 1.0, $scale_factor_y = 1.0, array $region = [] ) {
        $cache_key = $this->get_cache_key( $scale_factor_x, $scale_factor_y, $region );
        if ( isset( $this->bounds_cache[ $cache_key ] ) ) {
            return $this->bounds_cache[ $cache_key ];
        }

        $min_x = $min_y = PHP_INT_MAX;
        $max_x = $max_y = PHP_INT_MIN;

        foreach ( $this->get_points( $scale_factor_x, $scale_factor_y, 100, $region ) as $p ) {
            if ( $p[0] < $min_x ) {
                $min_x = $p[0];
            }

            if ( $p[0] > $max_x ) {
                $max_x = $p[0];
            }

            if ( $p[1] < $min_y ) {
                $min_y = $p[1];
            }

            if ( $p[1] > $max_y ) {
                $max_y = $p[1];
            }
        }

        $bounds = [
            [$min_x, $min_y],
            [$max_x, $max_y]
        ];
        $this->bounds_cache[ $cache_key ] = $bounds;

        return $bounds;
    }

    /**
     * Get the width of the shape when scaled by the specified x and y factors.
     * @param float $scale_factor_x
     * @param float $scale_factor_y
     * @return float
     */
    public function get_width( $scale_factor_x = 1.0, $scale_factor_y = 1.0 ) {
        $bounds = $this->get_bounds( $scale_factor_x, $scale_factor_y );
        return $bounds[1][0] - $bounds[0][0];
    }

    /**
     * Get the height of the shape when scaled by the specified x and y factors.
     * @param float $scale_factor_x
     * @param float $scale_factor_y
     * @return float
     */
    public function get_height( $scale_factor_x = 1.0, $scale_factor_y = 1.0 ) {
        $bounds = $this->get_bounds( $scale_factor_x, $scale_factor_y );
        return $bounds[1][1] - $bounds[0][1];
    }

    /**
     * Return the current command in the iterator.
     * Iterator implementation.
     * @return array
     */
    public function current() {
        return $this->commands[ $this->iterator_index ];
    }

    /**
     * Return the index of the current command in the iterator.
     * Iterator implementation
     * @return int
     */
    public function key() {
        return $this->iterator_index;
    }

    /**
     * Advance the interator to the next command.
     * Iterator implementation.
     */
    public function next() {
        $this->iterator_index++;
    }

    /**
     * Rewind the iterator back to the first command.
     * Iterator implementation.
     */
    public function rewind() {
        $this->iterator_index = 0;
    }

    /**
     * Determine if the current iterator index is valid.
     * Iterator implementation.
     * @return bool
     */
    public function valid() {
        return isset( $this->commands[ $this->iterator_index ] );
    }
}
