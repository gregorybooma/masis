<?php

$root = dirname( __FILE__ );
define('ROOT', $root);

require("$root/settings.php");
require("$root/includes/WebStart.php");

require("$root/includes/Database.php");
$db = new Database();
$db->connect();

require("$root/includes/login/member.inc.php");
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
            $count = $db->set_annotation_status($_GET['image_id'], $_GET['status']);
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
