<?php

$root = dirname( __FILE__ );
define('ROOT', $root);

require("$root/includes/Database.php");
$db = new Database();

require_once("$root/includes/Member.php");
$member = new Member();

require("$root/includes/WebStart.php");
$masis = new MaSIS();
$masis->start();
