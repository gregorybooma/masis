<?php

$root = dirname( __FILE__ );
define('ROOT', $root);

require("$root/settings.php");
require("$root/includes/WebStart.php");

require("$root/includes/Database.php");
$db = new Database();
$db->connect();

$do = !empty($_GET['do']) ? $_GET['do'] : NULL;
switch ($do) {
    case 'get_file_list':
        $dir = urldecode($_POST['dir']);
        require("$root/includes/HTML.php");
        $html = new HTML();
        $html->get_file_list($dir);
        break;
    case 'get_image_info':
        if ( empty($_GET['path']) ) exit("Parameter `path` is not set.");
        require("$root/includes/JSON.php");
        $json = new JSON();
        $json->get_image_info($_GET['path']);
        break;
    case 'get_species':
        if ( !empty($_GET['term']) ) {
            $searchpar = !empty($_GET['searchpar']) ? $_GET['searchpar'] : 0;
            require("$root/includes/JSON.php");
            $json = new JSON();
            $json->get_species_from_worms($_GET['term'], $searchpar);
        }
        break;
    case 'get_vectors':
        if ( empty($_GET['image_id']) ) exit("Parameter `image_id` is not set.");
        require("$root/includes/JSON.php");
        $json = new JSON();
        $json->get_vectors($_GET['image_id']);
        break;
    case 'table_image_vectors':
        if ( empty($_GET['image_id']) ) exit("Parameter `image_id` is not set.");
        require("$root/includes/DataTable.php");
        $table = new DataTable();
        $table->list_image_vectors($_GET['image_id']);
        break;
    case 'table_species_coverage_where_present':
        require("$root/includes/DataTable.php");
        $table = new DataTable();
        $table->species_coverage_where_present();
        break;
    case 'table_species_coverage_overall':
        require("$root/includes/DataTable.php");
        $table = new DataTable();
        $table->species_coverage_overall();
        break;
    default:
        if ( !isset($do) ) {
            exit("Parameter `do` is not set.");
        } else {
            exit("Value '{$do}' for parameter `do` is unknown.");
        }
}
