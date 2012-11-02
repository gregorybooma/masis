<?php

// Mark valid web server entry point.
define('MASIS', true);

/**
 * Load web content.
 */
class WebStart {
    static $lens_angle_x = 0.510472157;
    static $lens_angle_y = 0.386512004;
    public $page_title;
    public $page_content;

    /**
     * Load the web content.
     */
    public function start() {
        global $db, $member;

        // Load settings. If the settings file doesn't exist, give directions
        // to create one.
        if ( is_file(ROOT."/settings.php") ) {
            require(ROOT."/settings.php");
        } else {
            require(ROOT."/includes/Setup.php");
            $setup = new Setup();
            return;
        }

        // Connect with the database.
        $db->connect();

        // Check if the user is logged in.
        if ( !$member->sessionIsSet() ) {
            // If not, show the login screen.
            $this->page_title   = 'Login to MaSIS';
            $this->page_content =  $member->login();
            $this->load_page('login');
            return;
        }

        $p = isset($_GET['p']) ? $_GET['p'] : 'main';

        if ($p == 'logout') {
            // Show the logout screen.
            echo $member->logout();
            $this->page_title = 'Logging user out';
            $this->page_content = '<div class="notice info">You are being logged out...</div>';
            $this->load_page('login');
            return;
        }

        $this->load_page($p);
    }

    /**
     * Load a page.
     *
     * The file /pages/{$page}.php will be included.
     *
     * @param string $page The page to be loaded.
     */
    public function load_page($page) {
        global $member;

        $path = ROOT.'/pages/'.$page.'.php';
        if ( is_file($path) ) {
            require_once($path);
        } else {
            header('HTTP/1.x 404 Not Found');
            print "<h1>Page Not Found</h1>";
            print "<p>That page doesn't seem to exist.</p>";
            print "<p>Return to the <a href=\"/\">main page</a>.</p>";
        }
    }

    /**
     * Calculate the surface area of the image in square meters from the altitude.
     *
     * @param $altitude The altitude in meters.
     * @param $angle_x Camera lens constant, the horizontal angle.
     * @param $angle_y Camera lens constant, the vertical angle.
     * @return float The area in square meters.
     */
    static function get_area_from_altitude($altitude, $angle_x = null, $angle_y = null) {
        $angle_x = isset($angle_x) ? $angle_x : self::$lens_angle_x;
        $angle_y = isset($angle_y) ? $angle_y : self::$lens_angle_y;

        $ratio_x = 2 * tan($angle_x / 2);
        $ratio_y = 2 * tan($angle_y / 2);

        $size_y = $altitude * $ratio_y;
        $size_x = $altitude * $ratio_x;

        return $size_y * $size_x;
    }
}
