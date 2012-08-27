<?php

$root = dirname( __FILE__ );
define('ROOT', $root);

/* Include Notice & Mailer Class */
require_once("notice.class.php");
$notice = new notice;

if (isset($_POST['setup'])) {

	$hostname            = $_POST['hostname'];
	$database            = $_POST['database'];
	$username            = $_POST['username'];
	$password            = $_POST['password'];

	$hash                = $_POST['hash'];
	$bcrypt_rounds       = $_POST['bcrypt_rounds'];
	$remember_me         = $_POST['remember_me'];
	$captcha             = $_POST['captcha'];

	$email_master        = $_POST['email_master'];
	$email_template      = $_POST['email_template'];
	$email_welcome       = $_POST['email_welcome'];
	$email_verification  = $_POST['email_verification'];

    $image_path = $_POST['image_path'];
    $image_base_url = $_POST['image_base_url'];

	/* e-mail templates */
	$dh = opendir("../includes/login/email_templates");
	while ( false !== ($filename = readdir($dh)) ) {
        $tmp = explode('.', $filename);
		$ext = strtolower(end($tmp));
		if ($ext == 'html') {
			$templates[] = ucfirst(substr($filename,0,-5));
		}
	}

	if (empty($hostname)) {
		$notice->add('error', 'Please fill in the hostname');
		$cantDB = true;
	} else {
		$cantDB = false;
	}
	if (empty($database)) {
		$notice->add('error', 'Please fill in the database name');
		$cantDB = true;
	} else {
		$cantDB = false;
	}
	if (empty($username)) {
		$notice->add('error', 'Please fill in the database username');
		$cantDB = true;
	} else {
		$cantDB = false;
	}

	if ($cantDB == false) {
		/* Try the connections */
		try {
			/* Create a connections with the supplied values */
			$pdo = new PDO("pgsql:host=" . $hostname . ";dbname=" . $database . "", $username, $password, array(PDO::ATTR_PERSISTENT => true));
		} catch(PDOException $e) {
			/* If any errors echo the out and kill the script */
			$notice->add('error', 'Database conncetion fail!<br />Make sure your database information is correct');
		}
	}

	if (empty($bcrypt_rounds)) {
		$bcrypt_rounds = 12;
	}


	if (empty($email_master)) {
		$notice->add('error', 'Please enter a E-Mail that will be used to contcat users.');
		$email_master = null;
	}

	if ( !is_dir($image_path) ) {
		$notice->add('error', "The image directory path is not set or the directory doesn't exist.");
	}
	if ( empty($image_base_url) ) {
		$notice->add('error', "The image directory URL is not set or the directory doesn't exist.");
	}

	if ($notice->errorsExist() == false) {
		$showForm = false;

		//Create Config File
		if ( $config_handle = @fopen('../settings.php', 'w') ) {
			$config_data = '<?php

require_once(\'includes/Config.php\');

Config::write(\'hostname\', \'' . $hostname . '\');
Config::write(\'database\', \'' . $database . '\');
Config::write(\'username\', \'' . $username . '\');
Config::write(\'password\', \'' . $password . '\');
Config::write(\'drivers\', array(PDO::ATTR_PERSISTENT => true));

Config::write(\'hash\', \'' . $hash . '\'); /* Once set DO NOT CHANGE (sha512/bcrypt) */
Config::write(\'bcryptRounds\', \'' . $bcrypt_rounds . '\');
Config::write(\'remember\', ' . $remember_me . ');
Config::write(\'captcha\', ' . $captcha . ');

Config::write(\'email_template\', \'' . $email_template . '\');
Config::write(\'email_master\', \''. $email_master . '\');
Config::write(\'email_welcome\', ' . $email_welcome . ');
Config::write(\'email_verification\', ' . $email_verification . ');

# Base path for the location of image data (must end with forward slash).
Config::write(\'image_path\', \'' . $image_path . '\');

# Base URL for the location of image data (must end with forward slash).
Config::write(\'image_base_url\', \'' . $image_base_url . '\');

# Whether to update existing species records in the database each time
# records are retrieved from the WoRMS web service.
Config::write(\'update_species_records\', false);

';
			fwrite($config_handle, $config_data);
			$notice->add('success', 'Config file created');
		} else {
			$notice->add('error', 'Could not create config file!<br />Check your folder permissions.');
		}

		//Run SQL

$mysql_users = 'CREATE TABLE IF NOT EXISTS users (
  id SERIAL,
  username VARCHAR NOT NULL,
  password VARCHAR NOT NULL,
  email VARCHAR NOT NULL,
  date DATE NOT NULL,

  PRIMARY KEY (id)
);';


$mysql_users_inactive = 'CREATE TABLE IF NOT EXISTS users_inactive (
  verCode VARCHAR NOT NULL,
  id SERIAL,
  username VARCHAR NOT NULL,
  password VARCHAR NOT NULL,
  email VARCHAR NOT NULL,
  date DATE NOT NULL,

  PRIMARY KEY (id)
);';

$mysql_users_logged = 'CREATE TABLE IF NOT EXISTS users_logged (
  id INTEGER NOT NULL,
  hash VARCHAR NOT NULL
);';

$mysql_users_logs = 'CREATE TABLE IF NOT EXISTS users_logs (
  id SERIAL,
  userid INTEGER NOT NULL,
  action VARCHAR NOT NULL,
  time TIMESTAMP NOT NULL DEFAULT NOW(),
  ip VARCHAR NOT NULL,

  PRIMARY KEY (id)
);';

$mysql_users_recover = 'CREATE TABLE IF NOT EXISTS users_recover (
  id SERIAL,
  "user" INTEGER NOT NULL,
  verCode VARCHAR NOT NULL,
  requestTime TIMESTAMP NOT NULL DEFAULT NOW(),

  PRIMARY KEY (id)
);';


		/* mysql_users */
		$statement = $pdo->prepare($mysql_users);
		if ($statement->execute()){
			$notice->add('success', 'Table `users` populated!');
		} else {
			$notice->add('error', 'Could not populate users!');
		}

		/* mysql_users_inactive */
		$statement = $pdo->prepare($mysql_users_inactive);
		if ($statement->execute()){
			$notice->add('success', 'Table `users_inactive` populated!');
		} else {
			$notice->add('error', 'Could not populate users_inactive!');
		}

		/* mysql_users_logged */
		$statement = $pdo->prepare($mysql_users_logged);
		if ($statement->execute()){
			$notice->add('success', 'Table `users_logged` populated!');
		} else {
			$notice->add('error', 'Could not populate users_logged!');
		}

		/* mysql_users_logs */
		$statement = $pdo->prepare($mysql_users_logs);
		if ($statement->execute()){
			$notice->add('success', 'Table `users_logs` populated!');
		} else {
			$notice->add('error', 'Could not populate users_logs!');
		}

        $pdo->beginTransaction();

        $statement = $pdo->prepare('CREATE OR REPLACE FUNCTION update_time() returns trigger as $$begin new.time := now(); return new; end;$$ language plpgsql;');
		if ($statement->execute()){
			$notice->add('success', 'Created function `update_time`!');
		} else {
			$notice->add('error', 'Could not created function `update_time`!');
		}

        $statement = $pdo->prepare('DROP TRIGGER IF EXISTS users_logs_update ON users_logs;');
		if (! $statement->execute()){
			$notice->add('error', 'Could not drop trigger users_logs_update!');
		}

        $statement = $pdo->prepare('CREATE TRIGGER users_logs_update  before update on users_logs for each row execute procedure update_time();');
		if ($statement->execute()){
			$notice->add('success', 'Created trigger `users_logs_update`!');
		} else {
			$notice->add('error', 'Could not created trigger `users_logs_update`!');
		}

        $pdo->commit();

		/* mysql_users_recover */
		$statement = $pdo->prepare($mysql_users_recover);
		if ($statement->execute()){
			$notice->add('success', 'Table `users_recover` populated!');
		} else {
			$notice->add('error', 'Could not populate users_recover!');
		}

        $pdo->beginTransaction();

        $statement = $pdo->prepare('CREATE OR REPLACE FUNCTION update_requesttime() returns trigger as $$begin new.requestTime := now(); return new; end;$$ language plpgsql;');
		if ($statement->execute()){
			$notice->add('success', 'Created function `update_requesttime`!');
		} else {
			$notice->add('error', 'Could not created function `update_requesttime`!');
		}

        $statement = $pdo->prepare('DROP TRIGGER IF EXISTS users_recover_update ON users_recover;');
		if (! $statement->execute()){
			$notice->add('error', 'Could not drop trigger users_recover_update!');
		}

        $statement = $pdo->prepare('CREATE TRIGGER users_recover_update  before update on users_recover for each row execute procedure update_requesttime();');
		if ($statement->execute()){
			$notice->add('success', 'Created trigger `users_recover_update`!');
		} else {
			$notice->add('error', 'Could not created trigger `users_recover_update`!');
		}

        $pdo->commit();


		//Notify that everything is done

		//Ask to delete setup folder

		if ($notice->errorsExist() == false) {
			$notice->add('success', 'MaSIS has been installed!');
			$finishing_up = '<hr />Please delete your /setup/ folder for security reasons.
<form action="index.php" method="post">
	<span style="text-align: left; margin: 10px 0px 10px 0px; float: left;"><input type="checkbox" name="delete_setup" value="true" /> Delete /setup/ folder</span>
	<input name="continue" type="submit" value="Continue" />
</form>
			';

		}

	} else {
		$showForm = true;
	}


} else {
	$notice->add('info', 'Welcome to the MaSIS setup.');

	/* Better Passwords? */
	if (defined("CRYPT_BLOWFISH") && CRYPT_BLOWFISH) {
		$hash = 'bcrypt';
	} else {
		$notice->add('info', 'Your server does not support Bcrypt!<br />Please use sha512 instead.');
		$hash = 'sha512';
	}

	/* Mail enabled? */
	if (function_exists('mail')) {
		$email_welcome = true;
		$email_verification = true;
	} else {
		$notice->add('error', 'Your server does not have mail() enabled.<br />You should keep e-mail verification and welcome e-mail to false however you will need it if you want users to have the ability to recover their passwords.');
		$email_welcome = false;
		$email_verification = false;
	}

	/* e-mail templates */
	$dh = opendir("../includes/login/email_templates");
	while(false !== ($filename = readdir($dh))) {
        $end = explode('.', $filename);
		$ext = strtolower(end($end));
		if ($ext == 'html') {
			$templates[] = ucfirst(substr($filename,0,-5));
		}
	}


	$hostname      = 'localhost';
	$database      = null;
	$username      = 'root';
	$password      = null;

	$bcrypt_rounds = 12;
	$remember_me   = true;
	$captcha       = true;

	$email_master  = null;
	$email_template = 'default';

    $image_path = str_replace('setup', '', dirname(__FILE__)) . 'data/';
    $image_base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/data/';

	$showForm = true;
}


if (isset($_POST['continue'])) {
	$showForm = false;
	if ( isset($_POST['delete_setup']) && $_POST['delete_setup'] == true ) {
		rrmdir('../setup');
		$notice->add('success', 'Setup folder removed');
		header('Location: ../index.php');
	}
}

function rrmdir($dir) {
	if (is_dir($dir)) {
		$objects = scandir($dir);
		foreach ($objects as $object) {
			if ($object != "." && $object != "..") {
				if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object);
			}
		}
		reset($objects);
		rmdir($dir);
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>MaSIS - Setup</title>
	<!--CSS Files-->
	<link rel="stylesheet" type="text/css" href="css/style.css" />
</head>
<body>
<div id="wrapper">
	<h1>MaSIS Setup</h1>
<?php
echo $notice->report();
if ($showForm == true) {
?>
	<form action="index.php" method="post">
		<fieldset>
			<legend>Database Connection</legend>

			<label>Hostname</label>
			<input name="hostname" type="text" value="<?php echo $hostname; ?>" />

			<label>Database Name</label>
			<input name="database" type="text" value="<?php echo $database; ?>" />

			<label>Username</label>
			<input name="username" type="text" value="<?php echo $username; ?>" />

			<label>Password</label>
			<input name="password" type="password" value="<?php echo $password; ?>" />
		</fieldset>

		<fieldset>
			<legend>Security</legend>

			<label>Hash Type</label>
			<select name="hash">
				<option value="bcrypt"<?php if ($hash == 'bcrypt') { echo ' selected="selected"'; } ?>>Bcrypt</option>
				<option value="sha512"<?php if ($hash == 'sha512') { echo ' selected="selected"'; } ?>>SHA512</option>
			</select>

			<label>Bcrypt rounds <i>(12 Rounds is recommended)</i></label>
			<input name="bcrypt_rounds" type="text" value="<?php echo $bcrypt_rounds; ?>"  />

			<label>Allow Remember me feature on login?</label>
			<select name="remember_me">
				<option value="true"<?php if ($remember_me == 'true') { echo ' selected="selected"'; } ?>>True</option>
				<option value="false"<?php if ($remember_me == 'false') { echo ' selected="selected"'; } ?>>False</option>
			</select>

			<label>Require Captcha on registration</label>
			<select name="captcha">
				<option value="true"<?php if ($captcha == 'true') { echo ' selected="selected"'; } ?>>True</option>
				<option value="false"<?php if ($captcha == 'false') { echo ' selected="selected"'; } ?>>False</option>
			</select>
		</fieldset>

		<fieldset>
			<legend>E-Mail</legend>

			<label>Master E-Mail (E-Mail used to contact users)</label>
			<input name="email_master" type="text" value="<?php echo $email_master; ?>"  />

			<label>Email Template</label>
			<select name="email_template">
				<?php
                foreach($templates as $template) {
                    if (strtolower($email_template) == strtolower($template)) {
                        $templateSelected = ' selected="selected"';
                    } else {
                        $templateSelected = null;
                    }
                    echo '				<option value="' . $template . '"' . $templateSelected . '>' . $template . '</option>';
                }
				?>
			</select>

			<label>Send a welcome E-Mail on registration?</label>
			<select name="email_welcome">
				<option value="true"<?php if ($email_welcome == 'true') { echo ' selected="selected"'; }?>>True</option>
				<option value="false"<?php if ($email_welcome == 'false') { echo ' selected="selected"'; }?>>False</option>
			</select>

			<label>Requre E-mail verification on registration?</label>
			<select name="email_verification">
				<option value="true"<?php if ($email_verification == 'true') { echo ' selected="selected"'; }?>>True</option>
				<option value="false"<?php if ($email_verification == 'false') { echo ' selected="selected"'; }?>>False</option>
			</select>
		</fieldset>

		<fieldset>
			<legend>MaSIS</legend>

			<label>Image Directory Path (must end with /)</label>
			<input name="image_path" type="text" value="<?php echo $image_path; ?>"  />

			<label>Image Directory URL (must end with /)</label>
			<input name="image_base_url" type="text" value="<?php echo $image_base_url; ?>"  />
		</fieldset>

		<input name="setup" type="submit" value="Setup" />
	</form>
<?php
} else {
	if (isset($finishing_up)) {
		echo $finishing_up;
	}
}
?>
</div>
</body>
</html>
