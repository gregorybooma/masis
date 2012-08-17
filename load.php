<?php

require('settings.php');
require('includes/XML.php');
require('includes/JSON.php');
require('includes/HTML.php');
require('includes/Database.php');
require('includes/DataTable.php');

$db = new Database();
$xml = new XML();
$html = new HTML();
$json = new JSON();

// Connect to the database.
$db->connect();

// What to do.. what to do..
$do = !empty($_GET['do']) ? $_GET['do'] : NULL;
switch ($do) {
    case 'get_file_list':
        // Return file list.
        $xml->get_file_list($config['image_path']);
        break;
    case 'get_file_list_html':
        // Return file list.
        $dir = urldecode($_POST['dir']);
        $html->get_file_list($dir);
        break;
    case 'get_image_info':
        // Return image info.
        $path = !empty($_GET['path']) ? $_GET['path'] : NULL;
        if ( !isset($path) ) exit("Parameter `path` is not set.");
        $xml->get_image_info($path);
        break;
    case 'get_species':
        $json->html_select_species();
        break;
    case 'get_vectors':
        $image_id = !empty($_GET['image_id']) ? $_GET['image_id'] : NULL;
        if ( !isset($image_id) ) exit("Parameter `image_id` is not set.");
        $json->get_vectors($image_id);
        break;
    case 'table_image_vectors':
        $image_id = !empty($_GET['image_id']) ? $_GET['image_id'] : NULL;
        if ( !isset($image_id) ) exit("Parameter `image_id` is not set.");
        $table = new DataTable();
        $table->list_image_vectors($image_id);
        break;
    case 'table_species_coverage':
        $table = new DataTable();
        $table->species_coverage();
        break;
    default:
        if ( !isset($do) ) {
            exit("Parameter `do` is not set.");
        } else {
            exit("Value '{$do}' for parameter `do` is unknown.");
        }
}

?>
