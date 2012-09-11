<?php

// Mark valid web server entry point.
define('MASIS', true);

class MaSIS {
    public $lens_angle_x = 0.510472157;
    public $lens_angle_y = 0.386512004;

    public function start() {
        global $member;

        // Load settings. If the settings file doesn't exist, give directions
        // to create one.
        if ( is_file(ROOT."/settings.php") ) {
            require(ROOT."/settings.php");
        } else {
            require(ROOT."/includes/Setup.php");
            return;
        }

        // Check if the user is logged in.
        if ( $member->sessionIsSet() != true ) {
            $title   = 'Login to MaSIS';
            $content =  $member->login();
            require(ROOT."/pages/login.php");
            return;
        }

        $p = isset($_GET['p']) ? $_GET['p'] : null;
        if ($p == 'logout') {
            echo $member->logout();
            $title = 'Logging user out';
            $content = '<div class="notice info">You are being logged out...</div>';
            require(ROOT."/pages/login.php");
        }
        elseif ($p == 'settings') {
            $user = $member->data();
            $title   = 'Settings';
            $content = '<a href="/" class="button full">Main page</a>';
            require(ROOT."/pages/login.php");
        }
        else {
            // Load the main page.
            require(ROOT."/pages/main.php");
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
