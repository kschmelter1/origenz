<?php

require_once 'classes/Compulse/Autoloader.php';
require_once 'utils.php';
require_once 'vendor/autoload.php';

(new Compulse\Autoloader())->register();

$_origenz = new Compulse\Origenz();

/**
 * @return Compulse\Origenz
 */
function origenz() {
    global $_origenz;
    return $_origenz;
}
