<?php

if ( !isset($db) ) {
    require("settings.php");
    require("includes/Database.php");
    $db = new Database();
    $db->connect();
}

require_once("member.class.php");
$member = new member();

?>
