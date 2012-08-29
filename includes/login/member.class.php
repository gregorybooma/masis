<?php
/*
 * Member Class
 *
 * The main hub of the Tutis Login script
 * Stores almost all the functions
 */
class member {
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
		require_once("includes/login/notice.class.php");
		require_once("mailer.class.php");
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
		/* Create new instance of notice class */
		$notice = new notice;
		/* User Rember me feature? */
		if (Config::read('remember') == true) {
			$remember = '<div class="clearer"> </div><p class="remember_me"><input type="checkbox" name="remember_me" id="remember_me" value="1" /><label for="remember_me">Remember me</label></p>';
		} else {
			$remember = "";
		}
		/* Login Form */
		$form = '
<form name="login" action="' . $this->currentPage() . '" method="post" class="group">
	<label>
		<span>Username</span>
		<input type="text" name="username" />
	</label>
	<label>
		<span>Password</span>
		<input type="password" name="password" />
	</label>
	' . $remember . '
	<input name="login" type="submit" value="Login" />
</form>';
		/* Check if Login is set */
		if (isset($_POST['login'])) {
			/* Set username and password */
			if (empty($_POST['username'])) {
				$username = null;
			} else {
				$username = $_POST['username'];
			}
			if (empty($_POST['password'])) {
				$password = null;
			} else {
				$password = $_POST['password'];
			}
			/* Is both Username and Password set? */
			if ($username && $password) {
				/* Get User data */
				$user = $db->query('SELECT id, password FROM users WHERE username = :username', array(':username' => $username), 'FETCH_OBJ');
				/* Check if user exist */
				if ($db->sth->rowCount() >= '1') {
					/* Check hash */
					if ($this->verify($password, $user->password) == true) {
						/* If correct create session */
						session_regenerate_id();
						$_SESSION['member_id'] = $user->id;
						$_SESSION['member_valid'] = 1;
						/* User Rember me feature? */
						$this->createNewCookie($user->id);
						/* Log */
						$this->userLogger($user->id, 0);
						/* Report Status */
						$notice->add('success', 'Authentication Success');
						$return_form = 0;
						/* Redirect */
						if (isset($_COOKIE['redirect'])) {
							$redirect = $_COOKIE['redirect'];
						} else {
							$redirect = '';
						}
						echo '<meta http-equiv="refresh" content="2;url=' . $redirect . '" />';
					} else {
						/* Report Status */
						$notice->add('error', 'Authentication Failed');
						$return_form = 1;
					}
				} else {
					/* Report Status */
					$notice->add('error', 'Authentication Failed');
					$return_form = 1;
				}
			} else {
				/* Report Status */
				$notice->add('error', 'Authentication Failed');
				$return_form = 1;
			}
		} else {
			/* Report Status */
			$notice->add('info', 'Please authenticate yourself');
			$return_form = 1;
		}
		$data = "";
		/* We need the login form? */
		if ($return_form == 1) {
			$data .= $form;
		}
		/* Return data */
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
			header("Location: member.php?action=login");
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
		$this->userLogger($user_id, 1);
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
	 * userLogger
	 *
	 * Logs users activity,
	 * Nothing personal.... just login times, logout times,
	 * when recover passwords have been activated
	 * All for security
	 *
	 * @param string $userid The users id
	 * @param string $action What action is happening and needs logging
	 */
	public function userLogger($userid, $action) {
		global $db;
		/* What type of action? */
		switch($action) {
			case 0:
				$action = "Logged In";
				break;
			case 1:
				$action = "Logged Out";
				break;
			case 2:
				$action = "Recover Password";
				break;
			case 3:
				$action = "Reset Password";
				break;
			case 4:
				$action = "Reset E-Mail";
				break;
			case 5:
				$action = "Account Delete";
				break;
		}
		/* Get User's IP */
		$ip = $_SERVER['REMOTE_ADDR'];
		/* Date */
		$timestamp = date("Y-m-d H:i:s", time());
		$db->query('INSERT INTO users_logs(userid, action, time, ip) VALUES(:userid, :action, :time, :ip)', array(':userid' => $userid, ':action' => $action, ':time' => $timestamp, ':ip' => $ip));
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
			$notice = new notice;
			$notice->add('error', 'Could not retrive user data becuase no user is logged in!');
			return $notice->report();
		} else {
			$user = $db->query('SELECT id, username, email, date FROM users WHERE id = :id', array(':id' => $user_id), 'FETCH_OBJ');
			return $user;
		}
	}

	/*
	 * Register
	 *
	 * This function allows user to register
	 */
	public function register() {
		global $db;
		/* Create new instance of notice class */
		$notice = new notice;
		/* Set Message Array */
		$message = array();
		/* Check if Login is set */
		if (isset($_POST['register'])) {
			/* Check Username */
			if (!empty($_POST['username'])) {
				$check_username = strtolower($_POST['username']);
				/* Check the username length */
				$length = strlen($check_username);
				if ($length >= 5 && $length <= 25) {
					/* Is the username Alphanumeric? */
					if (preg_match('/[^a-zA-Z0-9_]/', $check_username)) {
						$notice->add('error', 'Please enter a valid alphanumeric username');
						$username = null;
					} else {
						$db->query('SELECT id FROM users WHERE username = :username', array(':username' => $check_username));
						/* Check if user exist in database */
						if ($db->sth->rowCount() == 0) {
							/* Require use to validate account */
							if (Config::read('email_verification') === true) {
								/* Check if user exist in inactive database */
								$user = $db->query('SELECT date FROM users_inactive WHERE username = :username', array(':username' => $check_username), 'FETCH_OBJ');
								/* If user incative is older than 24 hours */
								if ($db->sth->rowCount() == 0 or time() >= strtotime($user->date) + 86400) {
									/* If user incative is older than 24 hours */
									$username = $_POST['username'];
								} else {
									$notice->add('error', 'Username already in use');
									$username = $check_username;
								}
							} else {
								$username = $_POST['username'];
							}
						} else {
							$notice->add('error', 'Username already in use');
							$username = $check_username;
						}
					}
				} else {
					$notice->add('error', 'Please enter a username between 5 to 25 characters');
					$username = $check_username;
				}
			} else {
				$notice->add('error', 'Please enter a username');
				$username = null;
			}
			/* Check Password */
			if (!empty($_POST['password'])) {
				/* Do passwords match? */
				if (isset($_POST['password_again']) && $_POST['password_again'] == $_POST['password']) {
					/* Is the password long enough? */
					$length = strlen($_POST['password']);
					if ($length >= 8) {
						$password = $_POST['password'];
					} else {
						$notice->add('error', 'Passwords must be at least 8 characters');
					}
				} else {
					$notice->add('error', 'Passwords must match');
				}
			} else {
				$notice->add('error', 'Please enter a password');
			}
			/* Check E-Mail */
			if (!empty($_POST['email'])) {
				$check_email = strtolower($_POST['email']);
				$check_email_again = strtolower($_POST['email_again']);
				/* Do E-Mails match? */
				if (isset($check_email_again) && $check_email_again == $check_email) {
					$length = strlen($check_email);
					/* Is the E-Mail really an E-Mail? */
					if (filter_var($check_email, FILTER_VALIDATE_EMAIL) == true) {
						$db->query('SELECT id FROM users WHERE email = :email', array(':email' => $check_email));
						/* Check if user exist with email */
						if ($db->sth->rowCount() == 0) {
							/* Require use to validate account */
							if (Config::read('email_verification') === true) {
								/* Check if user exist with email in inactive */
								$user = $db->query('SELECT date FROM users_inactive WHERE email = :email', array(':email' => $check_email), 'FETCH_OBJ');
								/* If user incative is older than 24 hours */
								if ($db->sth->rowCount() == 0 or time() >= strtotime($user->date) + 86400) {
									$email = $check_email;
									$email_again = $check_email_again;
								} else {
									$notice->add('error', 'E-Mail already in use');
									$email = null;
									$email_again = null;
								}
							} else {
								$email = $check_email;
								$email_again = $check_email_again;
							}
						} else {
							$notice->add('error', 'E-Mail already in use');
							$email = null;
							$email_again = null;
						}
					} else {
						$notice->add('error', 'Invalid E-Mail');
						$email = $check_email;
						$email_again = $check_email_again;
					}
				} else {
					$notice->add('error', '"E-Mails must match');
					$email = $check_email;
					$email_again = $check_email_again;
				}
			} else {
				$notice->add('error', 'Please enter an E-Mail');
				$email = null;
				$email_again = null;
			}

			/* Captcha? */
			if (Config::read('captcha') === true) {
				/* Check E-Mail */
				if (!empty($_POST['captcha'])) {
					if ($_POST['captcha'] != $_SESSION['captcha']) {
						$notice->add('error', 'Invalid Captcha');
					}
				} else {
					$notice->add('error', 'Please fill in the Captcha');
				}
			}
			/* Is both Username and Password set? */
			if ($notice->errorsExist() == false) {
				/* Start Mailer Class */
				$mailer = new mailer(Config::read('email_master'));
				$return_form = 0;
				/* Final Format */
				$password = $this->genHash($this->genSalt(), $password);
				/* Set Template Path */
				$template_path = 'includes/login/email_templates/' . Config::read('email_template') . '.html';
				/* Send the user a welcome E-Mail */
				if (Config::read('email_welcome') === true) {
					/* Send the user an E-Mail */
					/* Can we send a user an E-Mail? */
					if (Config::read('email_master') != null) {
						/* Email Info */
						$subject = 'Welcome ' . $username . '!';
						$content = 'Hi ' . $username . ',<br />Thanks for signing-up!<br /><br /><i>-Admin</i>';
						/* Mail it! */
						$mailer->mail($email, $subject, $content);

					}
				}
				/* Require use to validate account */
				if (Config::read('email_verification') === true) {
					/* Send the user an E-Mail */
					/* Can we send a user an E-Mail? */
					if (function_exists('mail')) {
						if (Config::read('email_master') != null) {
							$verCode = md5(uniqid(rand(), true) . md5(uniqid(rand(), true)));
							/* Email Info */
							$subject = 'Thank you for creating an account, ' . $username;
							$content = 'Hi ' . $username . ',<br />Thanks for signing-up!<br />To activate your account please click the link below, or copy past it into the address bar of your web browser<hr /><a href="' . $this->currentPath() . 'member.php?action=verification&vercode=' . $verCode . '">' . $this->currentPath() . 'member.php?action=verification&vercode=' . $verCode . '</a><br /><br /><i>-Admin</i>';
							/* Mail it! */
							if ($mailer->mail($email, $subject, $content) == true) {
								/* Insert Data */
								$date = date("Y-m-d H:i:s", time());
								$db->query('INSERT INTO users_inactive(verCode, username, password, email, date) VALUES (:vercode, :username, :password, :email, :date)', array(':vercode' => $verCode, ':username' => $username, 'password' => $password, 'email' => $email, 'date' => $date));
								$notice->add('info', 'Please check your e-mail to activate your account');
								/* Redirect */
								echo '<meta http-equiv="refresh" content="2;url=index.php" />';
							} else {
								$notice->add('error', 'Could not send e-mail!<br />Please contact the site admin.');
							}
						} else {
							$notice->add('error', 'The admin has not set a master email!<br />Please contact the site admin and tell him to set one.');
						}
					} else {
						$notice->add('error', 'It seems this server cannot send e-mails!<br />Please contact the site admin.');
					}
				} else {
					/* Insert Data */
					$date = date("Y-m-d", time());
					$db->query('INSERT INTO users(username, password, email, date) VALUES (:username, :password, :email, :date)', array(':username' => $username, 'password' => $password, 'email' => $email, 'date' => $date));
					$notice->add('success', 'You account has been created!');
					/* Redirect */
					echo '<meta http-equiv="refresh" content="2;url=index.php" />';
				}
			} else {
				if (Config::read('captcha') === true) {
					/* If an error recreate captcha */
					$this->randomString();
				}
				$return_form = 1;
			}
		} else {
			/* Report Status */
			$notice->add('info', 'Please fill in all the information');
			$return_form = 1;
			$username = null;
			$email = null;
			$email_again = null;
			if (Config::read('captcha') === true) {
					/* If an error recreate captcha */
					$this->randomString();
				}
		}
		/* Register Form */
		/* Captcha? */
		if (Config::read('captcha') === true) {
			$captcha_input = '
<label>
		<span>Captcha</span>
		<span id="captcha">
			<input type="text" name="captcha" value="" />
			<img alt="Captcha" src="' . $this->currentPath() . 'includes/login/captcha.php" />
		</span>
	</label>
			';
		} else {
			$captcha_input = null;
		}

		$form = '
<form name="register" action="' . $this->currentPage() . '" method="post">
	<label>
		<span>Username</span>
		<input type="text" name="username" value="' . $username . '" />
	</label>
	<label>
		<span>Password</span>
		<input type="password" name="password" />
	</label>
	<label>
		<span>Password Again</span>
		<input type="password" name="password_again" />
	</label>
	<label>
		<span>E-Mail</span>
		<input type="text" name="email" value="' . $email . '" />
	</label>
	<label>
		<span>E-Mail Again</span>
		<input type="text" name="email_again" value="' . $email_again . '" />
	</label>
	' . $captcha_input . '
	<input name="register" type="submit" value="Register" />
</form>
		';
		/* Combine Data */
		$data = "";
		/* Do we need the login form? */
		if ($return_form == 1) {
			$data .= $form;
		}
		/* Return data */
		return $notice->report() . $data;
	}

	/*
	 * Random String
	 *
	 * Creates a random string of A-Z0-9
	 */
	public function randomString() {
		$chars = '1234567890AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz';
		$string = "";
		for($i = 0; $i < 6; $i++) {
			$string .= ($i%2) ? $chars[mt_rand(10, 23)] : $chars[mt_rand(0, 18)];
		}
		$_SESSION['captcha'] = $string;
		return $_SESSION['captcha'];
	}

	/*
	 * Recover Password
	 *
	 * Checks and sees if a verCode is present and that a user is try to reover a password, otherwise
	 * Lets the user Recover their password using e-mail
	 */
	 public function recoverPassword() {
		global $db;
		/* Create new instance of notice class */
		$notice = new notice;
		/* Is a user trying to recover a password via e-mail? */
		if (isset($_GET['vercode'])) {
			$data = "";
			/* Get info on verCode */
			$recover = $db->query('SELECT user, verCode, requestTime FROM users_recover WHERE verCode = :verCode', array(':verCode' => $_GET['vercode']), 'FETCH_OBJ');
			/* Does an active record exist? */
			if ($db->sth->rowCount() >= '1') {
				/* Is the request older than 24 hours? */
				if (time() - strtotime($recover->requestTime) > 60*60*24) {
					$notice->add('error', 'This recover request is older than 24 hours, please request a new password for recovery');
				} else {
					$data = $this->changePassword($recover->user);
				}
			} else {
				$notice->add('error', 'An active record does not exist for the recover of this email');
			}
		/* If no; present form */
		} else {
			/* Recover Password Form */
			$form = '
<form name="recover" action="' . $this->currentPage() . '" method="post" class="group">
	<input type="text" name="email" />
	<input name="recover" type="submit" value="Recover" />
</form>
			';
			if (isset($_POST['recover'])) {
				/* Get the users info */
				$user = $db->query('SELECT id, username, email FROM users WHERE email = :email', array(':email' => $_POST['email']), 'FETCH_OBJ');
				/* Check if user exist */
				if ($db->sth->rowCount() >= '1') {
					/* Set Template Path */
					$template_path = 'includes/login/email_templates/' . Config::read('email_template') . '.html';
					/* Can we send a user an E-Mail? */
					if (function_exists('mail') && Config::read('email_master') != null) {
						$verCode = md5(uniqid(rand(), true) . md5(uniqid(rand(), true)));
						/* Start Mailer Class */
						$mailer = new mailer(Config::read('email_master'));
						/* Email Info */
						$subject = 'Password change Request';
						$content = 'Hi ' . $user->username . ',<br />We have a revived a request for a password change. Please click the link below to change your password:<br /><br /><a href="' . $this->currentPath() . 'member.php?action=recover-password&vercode=' . $verCode . '">' . $this->currentPath() . 'member.php?action=recover-password&vercode=' . $verCode . '</a><br /><br />If the link does not work when you click it, copy and paste the link directly into your browser. If you did not request a password change ignore this message.<br /><br />For security reason this link is only active for 24 hours upon request.<br /><br /><i>-Admin</i>';
						/* Mail it! */
						if ($mailer->mail($user->email, $subject, $content) == true) {
							/* Upadte password only if you can mail them it! */
							$requestTime = date('Y-m-d H:i:s');
							$db->query('INSERT INTO users_recover(user, verCode, requestTime) VALUES (:user, :vercode, :requestTime)', array(':user' => $user->id, ':vercode' => $verCode, ':requestTime' => $requestTime));
							$this->userLogger($user->id, 2);
							$notice->add('info', 'Please check your e-mail for further instructions.');
							$return_form = 0;
							/* Redirect */
							echo '<meta http-equiv="refresh" content="2;url=index.php" />';
						} else {
							$notice->add('error', 'Could not send e-mail!<br />Contact the site admin.');
							$return_form = 0;
						}
					} else {
						$notice->add('error', 'Could not send e-mail!<br />Contact the site admin.');
						$return_form = 0;
					}
				} else {
					$notice->add('error', 'Sorry that e-mail does not exist in our database');
					$return_form = 1;
				}
			} else {
				$notice->add('info', 'Please enter your e-mail');
				$return_form = 1;
			}
			$data = "";
			/* We need the login form? */
			if ($return_form == 1) {
				$data .= $form;
			}
		}
		return $notice->report() . $data;
	}
	/*
	 * Change Password
	 *
	 * Chnages the selected users password
	 *
	 * @param string $user The users ID
	 */
	public function changePassword($user = null) {
		global $db;
		/* Create new instance of notice class */
		$notice = new notice;
		if (isset($user)) {
			if (isset($_POST['change-password'])) {
				/* Check Password */
				if (!empty($_POST['password'])) {
					/* Do passwords match? */
					if (isset($_POST['password_again']) && $_POST['password_again'] == $_POST['password']) {
						/* Is the password long enough? */
						$length = strlen($_POST['password']);
						if ($length >= 8) {
							$password = $_POST['password'];
						} else {
							$notice->add('error', 'Passwords must be at least 8 characters');
						}
					} else {
						$notice->add('error', 'Passwords must match');
					}
				} else {
					$notice->add('error', 'Please enter a password');
				}
				if ($notice->errorsExist() == false) {
					$password = $this->genHash($this->genSalt(), $password);
					$db->query('UPDATE users SET password = :password WHERE id = :id', array(':password' => $password, ':id' => $user));
					$this->userLogger($user, 3);
					/* Report Status */
					$notice->add('success', 'Password has been updated!');
					$return_form = 0;
					/* Redirect */
					echo '<meta http-equiv="refresh" content="2;url=index.php" />';
				} else {
					$return_form = 1;
				}
			} else {
				/* Report Status */
				$notice->add('info', 'Please choose a new password');
				$return_form = 1;
			}

			/* Reset Password Form */
			$form = '
<form name="change-password" action="' . $this->currentPage() . '" method="post">
	<label>
		<span>New Password</span>
		<input type="password" name="password" />
	</label>
	<label>
		<span>New Password Again</span>
		<input type="password" name="password_again" />
	</label>
	<input name="change-password" type="submit" value="Change Password" />
</form>
			';
			/* Combine Data */
			$data = "";
			/* Do we need the login form? */
			if ($return_form == 1) {
				$data .= $form;
			}
		} else {
			/* No User selected! */
			$notice->add('error', 'No user selected!');
			$data = "";
		}
		/* Return data */
		return $notice->report() . $data;
	}

	/*
	 * changeEmail
	 *
	 * Chnages the selected users email
	 *
	 * @param string $user The users ID
	 */
	public function changeEmail($user = null) {
		global $db;
		/* Create new instance of notice class */
		$notice = new notice;
		if (isset($user)) {
			$userInfo = $db->query('SELECT email FROM users WHERE id = :id', array(':id' => $user), 'FETCH_OBJ');
			$notice->add('info', 'Current E-Mail for this account is:<br />' . $userInfo->email);
			if (isset($_POST['email'])) {
				/* Set $email */
				$email = strtolower($_POST['email']);
				$email_again = strtolower($_POST['email_again']);
				/* Check E-Mail */
				if (!empty($email)) {
					/* Do E-Mails match? */
					if (isset($email_again) && $email_again == $email) {
						/* Is the E-Mail really an E-Mail? */
						if (filter_var($email, FILTER_VALIDATE_EMAIL) == true) {
							$db->query('SELECT id FROM users WHERE email = :email', array(':email' => $email));
							/* Check if user exist with email */
							if ($db->sth->rowCount() != 0) {
								$notice->add('error', 'E-Mail already in use');
							}
						} else {
							$notice->add('error', 'Invalid E-Mail');
						}
					} else {
						$notice->add('error', 'E-Mails must match');
					}
				} else {
					$notice->add('error', 'Please enter a E-Mail');
				}
				if ($notice->errorsExist() == false) {
					$db->query('UPDATE users SET email = :email WHERE id = :id', array(':email' => $email, ':id' => $user));
					$this->userLogger($user, 4);
					/* Report Status */
					$notice->add('success', 'E-Mail has been updated!');
					$return_form = 0;
					/* Redirect */
					echo '<meta http-equiv="refresh" content="2;url=index.php" />';
				} else {
					$return_form = 1;
				}
			} else {
				/* Report Status */
				$notice->add('info', 'Please choose a new E-Mail');
				$return_form = 1;
			}

			/* Reset Password Form */
			$form = '
<form name="change-email" action="' . $this->currentPage() . '" method="post">
	<label>
		<span>E-Mail</span>
		<input type="text" name="email" />
	</label>
	<label>
		<span>E-Mail Again</span>
		<input type="text" name="email_again" />
	</label>
	<input name="change-email" type="submit" value="Change E-Mail" />
</form>
			';
			/* Combine Data */
			$data = "";
			/* Do we need the login form? */
			if ($return_form == 1) {
				$data .= $form;
			}
		} else {
			/* No User selected! */
			$notice->add('error', 'No user selected!');
			$data = "";
		}
		/* Return data */
		return $notice->report() . $data;
	}

	/*
	 * deleteAccount
	 *
	 * Delete user account
	 *
	 * @param string $user The users ID
	 */
	public function deleteAccount($user = null) {
	global $db;
		/* Create new instance of notice class */
		$notice = new notice;
		if (isset($user)) {
			if (isset($_POST['delete_account'])) {
				$db->query('DELETE FROM users WHERE id = :id', array(':id' => $user));
				$this->userLogger($user, 5);
				/* Report Status */
				$notice->add('success', 'This accout has been delete!');
				$return_form = 0;
				/* Loguser out */
				$this->logout();
			} else {
				/* Report Status */
				$notice->add('info', 'Are you sure you wish to delete this account?');
				$return_form = 1;
			}

			/* Reset Password Form */
			$form = '<form name="change-email" action="' . $this->currentPage() . '" method="post">
	<input name="delete_account" type="submit" value="Delete Account" />
</form>';
			/* Combine Data */
			$data = "";
			/* Do we need the login form? */
			if ($return_form == 1) {
				$data .= $form;
			}
		} else {
			/* No User selected! */
			$notice->add('error', 'No user selected!');
			$data = "";
		}
		/* Return data */
		return $notice->report() . $data;
	}

	/*
	 * Verification
	 *
	 * If email verification is set this handels those verifications
	 */
	public function verification() {
		global $db;
		/* Create new instance of notice class */
		$notice = new notice;
		if (isset($_GET['vercode'])) {
			$verCode = $_GET['vercode'];
			$user = $db->query('SELECT username, password, email FROM users_inactive WHERE verCode = :verCode', array(':verCode' => $verCode), 'FETCH_OBJ');
			/* Insert Data */
			$db->query('INSERT INTO users(username, password, email, date) VALUES (:username, :password, :email, :date)', array(':username' => $user->username, ':password' => $user->password, ':email' => $user->email, ':date' => date('Y-m-d')));
			/* Clear Inactive */
			$db->query('DELETE FROM users_inactive WHERE verCode = :verCode', array(':verCode' => $verCode));
			/* Message */
			$notice->add('success', 'You account has been verified!');
			/* Redirect */
			echo '<meta http-equiv="refresh" content="2;url=index.php" />';
		} else {
			$notice->add('info', 'No verCode (Verification Code)!');
		}
		/* Combine Data */
		$data = "";
		/* Return data */
		return $notice->report() . $data;
	}
}
?>
