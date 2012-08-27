<?php

require("includes/login/member.inc.php");

$action = isset($_GET['action']) ? $_GET['action'] : null;
$subaction = isset($_GET['subaction']) ? $_GET['subaction'] : null;

if ($action == 'logout') {
	echo $member->logout();
	$title = 'Logging user out';
	$content = '<div class="notice info">You are being logged out...</div>';
}
elseif ($action == 'settings') {
	$member->LoggedIn();
	$user = $member->data();
	if ($subaction == 'password') {
		$title   = 'Change Password';
		$content = $member->changePassword($user->id);
	} elseif ($subaction == 'email') {
		$title   = 'Change E-Mail';
		$content = $member->changeEmail($user->id);
	} elseif ($subaction == 'delete') {
		$title   = 'Delete Account';
		$content = $member->deleteAccount($user->id);
	} else {
		$title   = 'Settings';
		$content = '<a href="member.php?action=settings&amp;subaction=password" class="button full">Change Password</a><a href="member.php?action=settings&amp;subaction=email" class="button full">Change E-Mail</a><a href="member.php?action=settings&amp;subaction=delete" class="button full">Delete Account</a>';
	}
}
elseif ($action == 'register') {
	$title   = 'Create an account';
	$content = $member->register() . '<p class="options group"><a href="member.php?action=login">Already have an account?</a> &bull; <a href="member.php?action=recover-password">Recover Password</a></p>';
}
elseif ($action == 'recover-password') {
	$title   = 'Recover your password';
	$content = $member->recoverPassword() . '<p class="options group"><a href="member.php?action=login">Already have an account?</a></p>';
}
elseif ($action == 'verification') {
	$title   = 'Your account has been verified';
	$content = $member->verification() . '<p class="options group"><a href="member.php?action=login">Already have an account?</a></p>';
}
else {
	$title   = 'Login to MaSIS';
	$content =  $member->login() . '<p class="options group"><a href="member.php?action=register">Register</a> &bull; <a href="member.php?action=recover-password">Recover Password</a></p>';
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php echo $title; ?></title>
	<link rel="stylesheet" type="text/css" href="styles/login.css" />
</head>
<body>
<div id="members" class="group">
	<h1><?php echo $title; ?></h1>
	<?php echo $content; ?>
</div>
</body>
</html>
