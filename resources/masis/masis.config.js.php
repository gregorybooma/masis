<?php

// Create a config object in JavaScript space.
require('../../settings.php');

print "var config = {};";
print "config.base_path = '". Config::read('base_path') ."';";
print "config.base_url = '". Config::read('base_url') ."';";
