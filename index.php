<?php

$root = dirname( __FILE__ );
define('ROOT', $root);

if( !file_exists("$root/settings.php") ) {
	echo "File settings.php was not found. Please <a href=\"/setup/\">run setup</a> to create one.";
    exit();
}

require("$root/settings.php");

require("$root/includes/Database.php");
$db = new Database();
$db->connect();

require_once("$root/includes/Member.php");
$member = new Member();

require("$root/includes/WebStart.php");
$masis = new MaSIS();
$masis->start();
