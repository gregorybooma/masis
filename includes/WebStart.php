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
            exit();
        }

        // Check if the user is logged in.
        if ( $member->sessionIsSet() != true ) {
            $title   = 'Login to MaSIS';
            $content =  $member->login();
            $this->member_screen($title, $content);
            return;
        }

        $p = isset($_GET['p']) ? $_GET['p'] : null;
        if ($p == 'logout') {
            echo $member->logout();
            $title = 'Logging user out';
            $content = '<div class="notice info">You are being logged out...</div>';
            $this->member_screen($title, $content);
        }
        elseif ($p == 'settings') {
            $user = $member->data();
            $title   = 'Settings';
            $content = '<a href="index.php" class="button full">Main page</a>';
            $this->member_screen($title, $content);
        }
        else {
            // Load the main page.
            require(ROOT."/pages/main.php");
        }
    }

    private function member_screen($title, $content) {
        print <<<END
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>$title</title>
    <link rel="stylesheet" href="styles/main.css" type="text/css" />
    <link rel="stylesheet" href="styles/login.css" type="text/css" />
</head>
<body>
<div id="members" class="group">
    <h1>$title</h1>
    $content
</div>
</body>
</html>
END;
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
