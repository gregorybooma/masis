<?php

// Set the base path. This is used as a base path to locate files.
$root = dirname( __FILE__ );
define('ROOT', $root);

// Mark valid web server entry point.
define('MASIS', true);

// Load settings. If the settings file doesn't exist, give directions
// to create one.
if ( is_file("{$root}/settings.php") ) {
    require_once("{$root}/settings.php");
} else {
    include("{$root}/includes/Setup.php");
    exit(1);
}

// Load main page.
include("{$root}/pages/main.php");

?>
