<?php

/**
 * The XML class generates data in XML format.
 *
 * Requires that config.php is imported.
 */
class XML {
    // Current working directory used by list_files().
    private $pwd = null;

    public function get_file_list($dir, $exclude=null) {
        header("Content-type: text/xml");
        print "<root>\n";
        $this->list_files($dir, $exclude);
        print "</root>";
    }

    /**
     * Recursively list all files in the specified directory.
     *
     * @param string $dir Path to the directory.
     * @param array $exclude Directory names to exclude.
     */
    public function list_files($dir, $exclude=null) {
        global $config, $pwd;

        $ls = scandir($dir);
        foreach ($ls as $name) {
            if ($name != '.' && $name != '..') {
                # Skip excluded paths.
                if (is_array($exclude) and in_array($name, $exclude)) continue;
                # Set absolute path.
                $abs_path = rtrim($dir, '/').'/'.$name;

                if (is_dir($abs_path)) {
                    # Set working directory.
                    $pwd = $name;
                    # Set directory element.
                    print "<dir name=\"{$name}\">\n";
                    # Set file elements for this directory.
                    $this->list_files($abs_path, $exclude);
                    print "</dir>\n";
                } else {
                    # Set file element.
                    $url = $config['image_base_url'] . $pwd . '/' . $name;
                    print "<file name=\"{$name}\">";
                    print "<url>{$url}</url>";
                    print "<path>{$abs_path}</path>";
                    print "</file>";
                }
            }
        }
        $pwd = null;
    }

    /**
     * Prints info for an image file.
     *
     * @param string $path Path to the image file.
     * @uses array $config Configuration array from config.php.
     * @uses $db Database object.
     */
    public function get_image_info($path) {
        global $config, $db;

        if (!is_file($path)) die("Error: Not a file: {$path}");

        // Create a new DOM document.
        $doc = new DOMDocument();
        $doc->formatOutput = true;
        $info = $doc->createElement("info");
        $image = $doc->createElement("image");
        $doc->appendChild($info);
        $info->appendChild($image);

        // Get file attributes.
        list($width, $height, $type, $dim_attr) = getimagesize($path);
        $stack = explode('/', $path);

        // Set attributes and child elements.
        $attr = array();
        $attr['name'] = array_pop($stack);
        $attr['dir'] = array_pop($stack);
        $attr['width'] = $width;
        $attr['height'] = $height;
        $arr = $db->get_image_attributes($attr['dir'], $attr['name']);
        $attr = array_merge($attr, $arr);

        $childs = array();
        $childs['url'] = $config['image_base_url'] . $attr['dir'] . '/' . $attr['name'];
        $childs['path'] = $path;

        // Calculate additional attributes.
        if ( empty($attr['area']) && !empty($attr['altitude']) ) {
            $attr['area'] = $this->get_area($attr['altitude']);
        }
        if ( !empty($attr['area']) && !empty($width) && !empty($height) ) {
            $attr['area_per_pixel'] = $attr['area'] / ($width * $height);
        }

        // Set DOM attributes and child nodes.
        foreach ($attr as $key => $val) {
            $image->setAttribute($key, $val);
        }
        foreach ($childs as $key => $val) {
            $e = $doc->createElement($key);
            $e->appendChild( $doc->createTextNode($val) );
            $image->appendChild($e);
        }

        header("Content-type: text/xml");
        print $doc->saveXML();
    }

    public static function get_area($altitude, $angle_x = 0.510472157, $angle_y = 0.386512004) {
        $ratio_x = 2 * tan($angle_x / 2);
        $ratio_y = 2 * tan($angle_y / 2);

        $size_y = $altitude * $ratio_y;
        $size_x = $altitude * $ratio_x;

        return $size_y * $size_x;
    }
}

