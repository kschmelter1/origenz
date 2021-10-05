<?php

namespace Compulse;

/**
 * Recursively merge assoc arrays.
 * Replacement for array_merge_recursive(), specifically for assoc arrays, because it doesn't work exactly how we need it to.
 * @param array ...$maps The maps to merge together.
 */
function map_merge_recursive( array ...$maps ) {
    $result = [];

    foreach ( $maps as $map ) {
        foreach ( $map as $key => $value ) {
            if ( isset( $result[$key] ) && is_array( $result[$key] ) && is_array( $value ) ) {
                $result[$key] = map_merge_recursive( $result[$key], $value );
            } else {
                $result[$key] = $value;
            }
        }
    }

    return $result;
}
