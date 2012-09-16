<?php

$root = dirname( __FILE__ );
define('ROOT', $root);

require("$root/settings.php");
require("$root/includes/WebStart.php");

require("$root/includes/Database.php");
$db = new Database();
$db->connect();

require_once("$root/includes/Member.php");
$member = new Member();

if ( $member->sessionIsSet() != true ) {
    exit("You must be logged in to use this page.");
}

$do = !empty($_GET['do']) ? $_GET['do'] : NULL;
switch ($do) {
    case 'save_vectors':
        try {
            $db->save_vectors($_POST);
            print json_encode(array('result' => 'success'));
        }
        catch (Exception $e) {
            print json_encode( array('result' => 'fail', 'exception' => $e->getMessage()) );
        }
        break;
    case 'delete_vector':
        try {
            if ( empty($_GET['image_id']) ) throw new Exception( "Parameter `image_id` is not set." );
            if ( empty($_GET['vector_id']) ) throw new Exception( "Parameter `vector_id` is not set." );

            $db->delete_vector($_GET['image_id'], $_GET['vector_id']);
            print json_encode( array('result' => 'success') );
        }
        catch (Exception $e) {
            print json_encode( array('result' => 'fail', 'exception' => $e->getMessage()) );
        }
        break;
    case 'set_areas':
        try {
            $count = $db->set_areas();
            print json_encode( array('result' => 'success', 'count' => $count) );
        }
        catch (Exception $e) {
            print json_encode( array('result' => 'fail', 'exception' => $e->getMessage()) );
        }
        break;
    case 'set_annotation_status':
        try {
            if ( empty($_GET['image_id']) ) throw new Exception( "Parameter `image_id` is not set." );
            if ( empty($_GET['status']) ) throw new Exception( "Parameter `status` is not set." );

            $count = $db->set_annotation_status($_GET['image_id'], $_GET['status']);
            print json_encode( array('result' => 'success') );
        }
        catch (Exception $e) {
            print json_encode( array('result' => 'fail', 'exception' => $e->getMessage()) );
        }
        break;
    case 'set_substrate_annotations':
        try {
            if ( empty($_POST['image_id']) ) throw new Exception( "Parameter `image_id` is not set." );
            $annotations = isset($_POST['annotations']) ? $_POST['annotations'] : array();

            $db->set_substrate_annotations($_POST['image_id'], $annotations);
            print json_encode( array('result' => 'success') );
        }
        catch (Exception $e) {
            print json_encode( array('result' => 'fail', 'exception' => $e->getMessage()) );
        }
        break;
    case 'set_image_tags':
        try {
            if ( empty($_POST['image_id']) ) throw new Exception( "Parameter `image_id` is not set." );
            $tags = isset($_POST['tags']) ? $_POST['tags'] : array();

            $db->set_image_tags($_POST['image_id'], $tags);
            print json_encode( array('result' => 'success') );
        }
        catch (Exception $e) {
            print json_encode( array('result' => 'fail', 'exception' => $e->getMessage()) );
        }
        break;
    default:
        if ( !isset($do) ) {
            exit("Parameter `do` is not set.");
        } else {
            exit("Value '{$do}' for parameter `do` is unknown.");
        }
}
