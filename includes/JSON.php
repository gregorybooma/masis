<?php

/**
 * The JSON class generates data in JSON format.
 */
class JSON {

    /**
     * Prints info for an image file.
     *
     * @param string $path Path to the image file.
     * @uses array $config Configuration array from config.php.
     * @uses $db Database object.
     */
    public function get_image_info($path) {
        global $config, $db;

        if ( !is_file($path) ) {
            throw new Exception( "Not a file: {$path}" );
        }

        // Get file info.
        list($width, $height, $type, $dim_attr) = getimagesize($path);
        $stack = explode('/', $path);

        // Set info array.
        $info = array();
        $info['name'] = array_pop($stack);
        $info['dir'] = array_pop($stack);
        $info['width'] = $width;
        $info['height'] = $height;
        $info['url'] = $config['image_base_url'] . $info['dir'] . '/' . $info['name'];
        $info['path'] = $path;

        $arr = $db->get_image_attributes($info['dir'], $info['name']);
        $info = array_merge($info, $arr);

        if ( empty($info['area']) && !empty($info['altitude']) ) {
            $info['area'] = MaSIS::get_area_from_altitude($info['altitude']);
        }
        if ( !empty($info['area']) && !empty($width) && !empty($height) ) {
            $info['area_per_pixel'] = $info['area'] / ($width * $height);
        }
        print json_encode($info);
    }

    public function html_select_species() {
        global $db;

        $filter = isset($_GET['term']) ? $_GET['term'] : null;
        $sth = $db->get_species($filter);
        $species = array();
        $species[] = array('label' => "Unassigned", 'value' => null);
        while ( $s = $sth->fetch(PDO::FETCH_ASSOC) ) {
            if ( $s['name_venacular'] ) {
                $label = sprintf("%s (%s)", $s['name_latin'], $s['name_venacular']);
            } else {
                $label = $s['name_latin'];
            }
            $species[] = array(
                'label' => $label,
                'value' => $s['id']
                );
        }
        print json_encode($species);
    }

    public function get_vectors($image_id) {
        global $db;

        $sth = $db->get_vectors($image_id);
        $vectors = array();
        while ( $row = $sth->fetch(PDO::FETCH_ASSOC) ) {
            if ( $row['name_venacular'] ) {
                $species_name = sprintf("%s (%s)", $row['name_latin'], $row['name_venacular']);
            } else {
                $species_name = $row['name_latin'];
            }
            $row['species_name'] = $species_name;
            $vectors[] = $row;
        }
        print json_encode($vectors);
    }
}
