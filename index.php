<?php

$root = dirname( __FILE__ );
define('ROOT', $root);

require("$root/includes/WebStart.php");

$masis = new MaSIS();
$masis->start();
