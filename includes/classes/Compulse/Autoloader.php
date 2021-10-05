<?php

namespace Compulse;

final class Autoloader {
    private $class_path;

    public function __construct() {
        $this->class_path = realpath( dirname( __FILE__ ) . '/..' );
    }

    public function register() {
        spl_autoload_register( array($this, 'autoload') );
    }

    public function autoload( $class_name ) {
        $file_name = $this->class_path . '/' . str_replace( '\\', '/', $class_name ) . '.php';

        if ( is_readable( $file_name ) ) {
            include $file_name;
        }
    }
}
