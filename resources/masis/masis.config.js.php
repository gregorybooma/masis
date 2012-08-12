<?php

// Create a config object in JavaScript space.
require('../../settings.php');

print "var config = {};\n";
$exclude = array('pg');
foreach ($config as $key => $val) {
    if ( !in_array($key, $exclude) ) {
        print "config.{$key} = '{$val}';\n";
    }
}
