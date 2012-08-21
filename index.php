<?php

$root = dirname( __FILE__ );
define('ROOT', $root);

require(ROOT."/includes/WebStart.php");

$masis = new MaSIS();
$masis->start();
