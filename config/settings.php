<?php
/**
 * Settings file for MaSIS.
 *
 * Put a copy of this file in the root folder of the web site and change the
 * settings below.
 */

require_once('includes/Config.php');

// Database settings
Config::write('hostname', 'localhost');
Config::write('database', 'masis');
Config::write('username', '');
Config::write('password', '');
Config::write('drivers', array(PDO::ATTR_PERSISTENT => true));

// Base path for the location of image data (must end with forward slash).
Config::write('image_path', '/public_html/masis/data/');

// Base URL for the location of image data (must end with forward slash).
Config::write('image_base_url', 'http://masis/data/');

// Whether to update existing species records in the database each time
// records are retrieved from the WoRMS web service.
Config::write('update_species_records', false);
