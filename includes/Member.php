<?php
/*
 * Originally part of Tutis Login <http://www.firedartstudios.com/labs/tutis-login>
 * Author: FireDart
 * License: CC-BY-SA 3.0 <http://creativecommons.org/licenses/by-sa/3.0/>
 *
 * Modified by Serrano Pereira for MaSIS
 */


class Member {

    /**
     * Constructor.
     *
     * - Set some basic settings for extra security.
     * - Start the session.
     * - Check last know IP to prevent hijacking.
     */
    public function __construct() {
        // Prevent JavaScript from reading session cookies.
        ini_set('session.cookie_httponly', true);

        // Start session.
        session_start();

        // Check if last session was from the same computer.
        if (!isset($_SESSION['last_ip'])) {
            $_SESSION['last_ip'] = $_SERVER['REMOTE_ADDR'];
        }
        if ($_SESSION['last_ip'] !== $_SERVER['REMOTE_ADDR']) {
            // Clear the session.
            $_SESSION = array();
            // Destroy the session.
            session_unset();
            session_destroy();
        }

        // Include Notice class
        require_once("Notice.php");
    }

    /**
     * Return the current URL directory.
     */
    public function currentPath() {
        $currentPath  = 'http';
        if (isset($_SERVER["HTTPS"]) == "on") {$currentPage .= "s";}
        $currentPath .= "://";
        $currentPath .= dirname($_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]) . '/';
        return $currentPath;
    }

    /**
     * Return the current URL.
     */
    public function currentPage() {
        /* Current Page */
        $currentPage  = 'http';
        if (isset($_SERVER["HTTPS"]) == "on") {$currentPage .= "s";}
        $currentPage .= "://";
        $currentPage .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
        return $currentPage;
    }

    /**
     * Generate a random salt to be used in a password hasing.
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

    /**
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

    /**
     * Verify the password.
     *
     * @param string $password The provided password
     * @param string $existingHash The current stored hashed password
     */
    public function verify($password, $existingHash) {
        // Hash new password with old hash.
        $hash = crypt($password, $existingHash);
        // Check if the hashes match.
        return $hash == $existingHash;
    }

    /**
     * Handle user login.
     */
    public function login() {
        global $db;

        $notice = new Notice;
        $username = !empty($_POST['username']) ? $_POST['username'] : null;
        $password = !empty($_POST['password']) ? $_POST['password'] : null;
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
    <div class="clearer"> </div><p class="remember_me"><input type="checkbox" name="remember_me" id="remember_me" value="1" /><label for="remember_me">Remember me</label></p>
    <input name="login" type="submit" value="Login" class="button" />
</form>
END;

        if ( isset($_POST['login']) ) {
            if ( $username && $password ) {
                $user = $db->query('SELECT id, password FROM users WHERE username = :username', array(':username' => $username), 'FETCH_OBJ');

                if ( $db->sth->rowCount() >= '1' ) {
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
                        $redirect = isset($_COOKIE['redirect']) ? $_COOKIE['redirect'] : '/';
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

    /**
     * Check if the user is logged-in.
     *
     * Check if session and/or cookie is set, then reference it in the database
     * to see if it is valid. If so, allow the user to login.
     */
    public function LoggedIn() {
        global $db;

        $status = FALSE;

        // Check if a session is set.
        if (isset($_SESSION['member_valid']) && $_SESSION['member_valid']) {
            $status = TRUE;
        }

        // Check if a cookie is set.
        elseif (isset($_COOKIE['remember_me_id']) && isset($_COOKIE['remember_me_hash'])) {
            // If so, find the equivilent in the db
            $user = $db->query('SELECT id, hash FROM users_logged WHERE id = :id', array(':id' => $_COOKIE['remember_me_id']), 'FETCH_OBJ');

            // Does the record exist?
            if ($db->sth->rowCount() >= '1') {
                // Check if the hashes match
                if ($user->hash == $_COOKIE['remember_me_hash']) {
                    // If so, create a new cookie and database record
                    $this->createNewCookie($user->id);

                    // And recreate session
                    session_regenerate_id();
                    $_SESSION['member_id'] = $user->id;
                    $_SESSION['member_valid'] = 1;

                    $status = TRUE;
                }
            }
        }

        // Decide whether the user needs to login.
        if ( !$status ) {
            // Set the redirect cookie (expire in 1 year)
            setcookie("redirect", $this->currentPage(), time() + 31536000);
            // Redirect to the login page
            header("Location: /?p=login");
        }
    }

    /**
     * Checks if the session is set.
     *
     * Similar to LoggedIn, but does not redirect the user.
     */
    public function sessionIsSet() {
        global $db;

        // Check if a session is set.
        if (isset($_SESSION['member_valid']) && $_SESSION['member_valid']) {
            return TRUE;
        }
        // Check if a cookie is set.
        if (isset($_COOKIE['remember_me_id']) && isset($_COOKIE['remember_me_hash'])) {
            // If so, find the equivilent in the db
            $user = $db->query('SELECT id, hash FROM users_logged WHERE id = :id', array(':id' => $_COOKIE['remember_me_id']), 'FETCH_OBJ');
            // Does the record exist?
            if ($db->sth->rowCount() >= '1') {
                // Check if the hashes match
                if ($user->hash == $_COOKIE['remember_me_hash']) {
                    // If so, create a new cookie and database record
                    $this->createNewCookie($user->id);

                    // And recreate session
                    session_regenerate_id();
                    $_SESSION['member_id'] = $user->id;
                    $_SESSION['member_valid'] = 1;

                    return TRUE;
                }
            }
        }
        return FALSE;
    }

    /**
     * Reset and destroy session, delete cookies, and redirects to main page.
     */
    public function logout() {
        // Clear the session
        $_SESSION = array();

        // Destroy the session
        session_unset();
        session_destroy();

        // Delete all old cookies and user_logged
        if (isset($_COOKIE['remember_me_id'])) {
            $this->deleteCookie($_COOKIE['remember_me_id']);
        }

        // Redirect to the main page
        header('Refresh: 2; url=/');
    }

    /**
     * If the remember me feature is enabled and the
     * user has selected it create a cookie for them.
     * Log it in the database
     *
     * @param string $id The users id
     */
    public function createNewCookie($id) {
        global $db;

        // Generate random hash
        $hash = $this->genHash($this->genSalt(), $_SERVER['REMOTE_ADDR']);

        // Set cookies (expire in 1 year)
        setcookie("remember_me_id", $id, time() + 31536000);
        setcookie("remember_me_hash", $hash, time() + 31536000);

        // Remove old cookie records from the database.
        $db->query("DELETE FROM users_logged WHERE id = :id;", array(':id' => $id));

        // Set new cookie record in the database.
        $db->query("INSERT INTO users_logged (id, hash) VALUES (:id, :hash);", array(':id' => $id, ':hash' => $hash));
    }

    /**
     * Delete the users cookie
     *
     * @param string $id The users id
     */
    public function deleteCookie($id) {
        global $db;

        if (Config::read('remember') == true) {
            // Expire the cookies (the browser will delete the expired cookies)
            setcookie("remember_me_id", "", time() - 31536000);
            setcookie("remember_me_hash", "", time() - 31536000);
            setcookie("redirect", "", time() - 31536000);

            // Clear cookie records in the database
            $db->query("DELETE FROM users_logged WHERE id = :id;", array(':id' => $id));
        }
    }

    /**
     * Return user data.
     */
    public function data() {
        global $db;

        if (isset($_SESSION['member_id'])) {
            $user_id = $_SESSION['member_id'];
        }
        elseif (isset($_COOKIE['remember_me_id'])) {
            $user_id = $_COOKIE['remember_me_id'];
        }
        else {
            $user_id = NULL;
        }

        if ( isset($user_id) ) {
            $user = $db->query("SELECT id, username, email, date FROM users WHERE id = :id;", array(':id' => $user_id), 'FETCH_OBJ');
            return $user;
        }
        return NULL;
    }
}
