<?php

require('settings.php');
require('includes/Database.php');

$db = new Database();

// Connect to the database.
$db->connect();

$do = !empty($_GET['do']) ? $_GET['do'] : NULL;
switch ($do) {
    case 'save_selections':
        $rv = $db->save_vectors($_POST);
        print json_encode(array('result' => 'success'));
        break;
    default:
        if ( !isset($do) ) {
            die("Parameter `do` is not set.");
        } else {
            die("Value '{$do}' for parameter `do` is unknown.");
        }
}

?>
