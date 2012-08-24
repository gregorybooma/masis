<?php

$config = array();

# PostgreSQL database settings.
$config['pg']['host'] = '';
$config['pg']['username'] = '';
$config['pg']['password'] = '';
$config['pg']['dbname'] = '';

# Base path for the location of image data (must end with forward slash).
$config['image_path'] = '/public_html/masis/data/';

# Base URL for the location of image data (must end with forward slash).
$config['image_base_url'] = 'http://domain.com/data/';

# Whether to update existing species records in the database each time
# records are retrieved via the WoRMS web service.
$config['update_species_records'] = false;
