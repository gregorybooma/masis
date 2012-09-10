<?php
/*
 * Originally part of Tutis Login <http://www.firedartstudios.com/labs/tutis-login>
 * Author: FireDart
 * License: CC-BY-SA 3.0 <http://creativecommons.org/licenses/by-sa/3.0/>
 *
 * Modified by Serrano Pereira for MaSIS
 */

class Member {
	/*
	 * Member Construct
	 *
	 * Sets some basic settings for extra security
	 * Starts the session
	 * Checks last know ip to prevent hijacking
	 */
	public function __construct() {
		/* Prevent JavaScript from reaidng Session cookies */
		ini_set('session.cookie_httponly', true);
		/* Start Session */
		session_start();
		/* Check if last session is fromt he same pc */
		if (!isset($_SESSION['last_ip'])) {
			$_SESSION['last_ip'] = $_SERVER['REMOTE_ADDR'];
		}
		if ($_SESSION['last_ip'] !== $_SERVER['REMOTE_ADDR']) {
			/* Clear the SESSION */
			$_SESSION = array();
			/* Destroy the SESSION */
			session_unset();
			session_destroy();
		}

		/* Include Notice & Mailer Class */
		require_once("Notice.php");
	}


	/*
	 * Basic functions
	 *
	 * This area contains basic functions that are very usfull
	 */
	/*
	 * CurrentPath functions
	 *
	 * Returns the current path of the url
	 */
	public function currentPath() {
		$currentPath  = 'http';
		if (isset($_SERVER["HTTPS"]) == "on") {$currentPage .= "s";}
		$currentPath .= "://";
		$currentPath .= dirname($_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]) . '/';
		return $currentPath;
	}
	/*
	 * CurrentPage functions
	 *
	 * Returns the current page of the url
	 */
	public function currentPage() {
		/* Current Page */
		$currentPage  = 'http';
		if (isset($_SERVER["HTTPS"]) == "on") {$currentPage .= "s";}
		$currentPage .= "://";
		$currentPage .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
		return $currentPage;
	}


	/*
	 * User Authentication
	 *
	 * This section contains all the user authentication
	 */
	/*
	 * genSalt
	 *
	 * This generates a random salt to be used in a password hasing
	 */
	public function genSalt() {
		/* openssl_random_pseudo_bytes(16) Fallback */
		$seed = '';
		for($i = 0; $i < 16; $i++) {
			$seed .= chr(mt_rand(0, 255));
		}
		/* GenSalt */
		$salt = substr(strtr(base64_encode($seed), '+', '.'), 0, 22);
		/* Return */
		return $salt;
	}
	/*
	 * genHash
	 *
	 * This creates a hash of the selected password and
	 * uses a unique salt provided by genSalt function
	 *
	 * @param string $salt The random salt for the password
	 * @param string $password The provided password
	 */
	public function genHash($salt, $password) {
		/* If Sha512 */
		if (Config::read('hash') == 'sha512') {
			/* Hash Password with sha256 */
			$hash   = $salt . $password;
			/* ReHash the password */
			for($i = 0; $i < 100000; $i ++) {
				$hash = hash('sha512', $hash);
			}
			/* Salt + hash = smart */
			$hash   = $salt . $hash;
		/* Else Bcrypt by default */
		} else {
			/* Explain '$2y$' . $this->rounds . '$' */
				/* 2a selects bcrypt algorithm */
				/* $this->rounds is the workload factor */
			/* GenHash */
			$hash = crypt($password, '$2y$' . Config::read('bcryptRounds') . '$' . $this->genSalt());
		}
		/* Return */
		return $hash;
	}
	/*
	 * verify
	 *
	 * This checks if the suppled password is equal
	 * to the current stored hashed password
	 *
	 * @param string $password The provided password
	 * @param string $existingHash The current stored hashed password
	 */
	public function verify($password, $existingHash) {
		/* If Sha512 */
		if (Config::read('hash') == 'sha512') {
			$salt = substr($existingHash, 0, 22);
			$hash = $this->genHash($salt, $password);
		/* Else Bcrypt by default */
		} else {
			/* Hash new password with old hash */
			$hash = crypt($password, $existingHash);
			/* Do Hashs match? */
		}

		if ($hash === $existingHash) {
			return true;
		} else {
			return false;
		}
	}
	/*
	 * login
	 *
	 * Returns a login form that user can login with
	 * It then checks to see if the login is successful
	 *
	 * If so create session and/or remember cookie
	 */
	public function login() {
		global $db;

		$notice = new Notice;
        $username = !empty($_POST['username']) ? $_POST['username'] : null;
        $password = !empty($_POST['password']) ? $_POST['password'] : null;

		if (Config::read('remember') == true) {
			$remember = '<div class="clearer"> </div><p class="remember_me"><input type="checkbox" name="remember_me" id="remember_me" value="1" /><label for="remember_me">Remember me</label></p>';
		} else {
			$remember = "";
		}

		$form = <<<END
<form name="login" action="{$this->currentPage()}" method="post" class="group">
	<label>
		<span>Username</span>
		<input type="text" name="username" value="$username" />
	</label>
	<label>
		<span>Password</span>
		<input type="password" name="password" />
	</label>
	$remember
	<input name="login" type="submit" value="Login" class="button" />
</form>
END;

		if (isset($_POST['login'])) {
			if ($username && $password) {
				$user = $db->query('SELECT id, password FROM users WHERE username = :username', array(':username' => $username), 'FETCH_OBJ');

				if ($db->sth->rowCount() >= '1') {
					if ( $this->verify($password, $user->password) ) {
                        // Set the user session if verified successfully.
						session_regenerate_id();
						$_SESSION['member_id'] = $user->id;
						$_SESSION['member_valid'] = 1;

						// Use rember me feature?
						$this->createNewCookie($user->id);

						// Report Status
						$notice->add('success', 'Authentication Success');
						$return_form = 0;

						// Redirect
						$redirect = isset($_COOKIE['redirect']) ? $_COOKIE['redirect'] : '';
						echo '<meta http-equiv="refresh" content="2;url=' . $redirect . '" />';
					}
                    else {
						// Report Status
						$notice->add('error', 'Authentication Failed');
						$return_form = 1;
					}
				}
                else {
					/* Report Status */
					$notice->add('error', 'Authentication Failed');
					$return_form = 1;
				}
			}
            else {
				/* Report Status */
				$notice->add('error', 'Authentication Failed');
				$return_form = 1;
			}
		}
        else {
			/* Report Status */
			$notice->add('info', 'Please authenticate yourself');
			$return_form = 1;
		}

		$data = "";

		// Display the login form?
		if ($return_form == 1) $data .= $form;

		// Return page content
		return $notice->report() . $data;
	}

	/*
	 * LoggedIn
	 *
	 * Check if the user is logged-in
	 * Check for session and/or cookie is set then reference it
	 * in the database to see if it is valid if so allow the
	 * user to login
	 */
	public function LoggedIn() {
		global $db;
		/* Is a SESSION set? */
		if (isset($_SESSION['member_valid']) && $_SESSION['member_valid']) {
			/* Return true */
			$status = true;
		/* Is a COOKIE set? */
		} elseif (isset($_COOKIE['remember_me_id']) && isset($_COOKIE['remember_me_hash'])) {
			/* If so, find the equivilent in the db */
			$user = $db->query('SELECT id, hash FROM users_logged WHERE id = :id', array(':id' => $_COOKIE['remember_me_id']), 'FETCH_OBJ');
			/* Does the record exist? */
			if ($db->sth->rowCount() >= '1') {
				/* Do the hashes match? */
				if ($user->hash == $_COOKIE['remember_me_hash']) {
					/* If so Create a new cookie and mysql record */
					$this->createNewCookie($user->id);
					/* Return true */
					$status = true;
					/* If correct recreate session */
					session_regenerate_id();
					$_SESSION['member_id'] = $user->id;
					$_SESSION['member_valid'] = 1;
				} else {
					/* Return false */
					$status = false;
				}
			}
		} else {
			/* Return false */
			$status = false;
		}
		/* Does the user need to login? */
		if ($status != true) {
			/* Redirect Cookie */
			setcookie("redirect", $this->currentPage(), time() + 31536000);  /* expire in 1 year */
			/* Go to Login */
			header("Location: index.php?p=login");
		}
	}

	/*
	 * sessionIsSet
	 *
	 * Checks and sees if the session is set
	 * Similar to LoggedIn however it does not
	 * rediect the user
	 */
	public function sessionIsSet() {
		global $db;
		/* Is a SESSION set? */
		if (isset($_SESSION['member_valid']) && $_SESSION['member_valid']) {
			/* Return true */
			$status = true;
		/* Is a COOKIE set? */
		} elseif (isset($_COOKIE['remember_me_id']) && isset($_COOKIE['remember_me_hash'])) {
			/* If so, find the equivilent in the db */
			$user = $db->query('SELECT id, hash FROM users_logged WHERE id = :id', array(':id' => $_COOKIE['remember_me_id']), 'FETCH_OBJ');
			/* Does the record exist? */
			if ($db->sth->rowCount() >= '1') {
				/* Do the hashes match? */
				if ($user->hash == $_COOKIE['remember_me_hash']) {
					/* If so Create a new cookie and mysql record */
					$this->createNewCookie($user->id);
					/* Return true */
					$status = true;
					/* If correct recreate session */
					session_regenerate_id();
					$_SESSION['member_id'] = $user->id;
					$_SESSION['member_valid'] = 1;
				} else {
					/* Return false */
					$status = false;
				}
			}
		} else {
			/* Return false */
			$status = false;
		}
		/* Is Session Set */
		return $status;
	}

	/*
	 * Logout
	 *
	 * Resets Session and destroyes it,
	 * deletes any cookies and redirects to index
	 */
	public function logout() {
		/* Log */
		if (isset($_SESSION['member_id'])) {
			$user_id = $_SESSION['member_id'];
		} else {
			$user_id = $_COOKIE['remember_me_id'];
		}
		/* Clear the SESSION */
		$_SESSION = array();
		/* Destroy the SESSION */
		session_unset();
		session_destroy();
		/* Delete all old cookies and user_logged */
		if (isset($_COOKIE['remember_me_id'])) {
			$this->deleteCookie($_COOKIE['remember_me_id']);
		}
		/* Redirect */
		header('Refresh: 2; url=index.php');
	}

	/*
	 * createNewCookie
	 *
	 * If the remember me feature is enabled and the
	 * user has selected it create a cookie for them.
	 * Log it in the database
	 *
	 * @param string $id The users id
	 */
	public function createNewCookie($id) {
		global $db;
		/* User Rember me feature? */
		if (Config::read('remember') == true) {
			/* Gen new Hash */
			$hash = $this->genHash($this->genSalt(), $_SERVER['REMOTE_ADDR']);
			/* Set Cookies */
			setcookie("remember_me_id", $id, time() + 31536000);  /* expire in 1 year */
			setcookie("remember_me_hash", $hash, time() + 31536000);  /* expire in 1 year */
			/* Delete old record, if any */
			$db->query('DELETE FROM users_logged WHERE id = :id', array(':id' => $id));
			/* Insert new cookie */
			$db->query('INSERT INTO users_logged(id, hash) VALUES(:id, :hash)', array(':id' => $id, ':hash' => $hash));
		}
	}

	/*
	 * deleteCookie
	 *
	 * Delete the users cookie
	 *
	 * @param string $id The users id
	 */
	public function deleteCookie($id) {
		global $db;
		/* User Rember me feature? */
		if (Config::read('remember') == true) {
			/* Destroy Cookies */
			setcookie("remember_me_id", "", time() - 31536000);  /* expire in 1 year */
			setcookie("remember_me_hash", "", time() - 31536000);  /* expire in 1 year */
			/* Clear DB */
			$db->query('DELETE FROM users_logged WHERE id = :id', array(':id' => $id));
		}
	}

	/*
	 * Member Data
	 *
	 * Loads all the member data
	 */
	public function data() {
		global $db;
		if (isset($_SESSION['member_id'])) {
			$user_id = $_SESSION['member_id'];
		} elseif (isset($_COOKIE['remember_me_id'])) {
			$user_id = $_COOKIE['remember_me_id'];
		} else {
			$user_id = null;
		}
		if (!isset($user_id)) {
			$notice = new Notice;
			$notice->add('error', 'Could not retrive user data becuase no user is logged in!');
			return $notice->report();
		} else {
			$user = $db->query('SELECT id, username, email, date FROM users WHERE id = :id', array(':id' => $user_id), 'FETCH_OBJ');
			return $user;
		}
	}

}
