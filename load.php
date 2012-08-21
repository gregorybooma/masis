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
        require("$root/includes/XML.php");
        $xml = new XML();
        $xml->get_file_list($config['image_path']);
        break;
    case 'get_file_list_html':
        $dir = urldecode($_POST['dir']);
        require("$root/includes/HTML.php");
        $html = new HTML();
        $html->get_file_list($dir);
        break;
    case 'get_image_info':
        if ( empty($_GET['path']) ) exit("Parameter `path` is not set.");
        require("$root/includes/XML.php");
        $xml = new XML();
        $xml->get_image_info($_GET['path']);
        break;
    case 'get_species':
        require("$root/includes/JSON.php");
        $json = new JSON();
        $json->html_select_species();
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
    case 'table_species_coverage':
        require("$root/includes/DataTable.php");
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
