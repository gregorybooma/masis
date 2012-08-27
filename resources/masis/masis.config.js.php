<?php

// Create a config object in JavaScript space.
require('../../settings.php');

print "var config = {};";
print "config.image_path = '". Config::read('image_path') ."';";
print "config.image_base_url = '". Config::read('image_base_url') ."';";
