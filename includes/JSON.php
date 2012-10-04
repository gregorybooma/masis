<?php

/**
 * The JSON class generates data in JSON format.
 */
class JSON {

    /**
     * Prints info for an image file.
     *
     * @param string $path Relative path to the image file. Relative from the
     *      web page root directory.
     * @uses $db Database object.
     */
    public function get_image_info($rel_path) {
        global $db;

        $abs_path = realpath(Config::read('base_path') . $rel_path);

        if ( !is_file($abs_path) ) {
            throw new Exception( "Not a file: {$abs_path}" );
        }

        // Get file info.
        $size = getimagesize($abs_path);
        $width = $size[0];
        $height = $size[1];
        $type = $size[2];
        $stack = explode('/', $rel_path);

        // Set info array.
        $info = array();
        $info['name'] = array_pop($stack);
        $info['dir'] = array_pop($stack);
        $info['width'] = $width;
        $info['height'] = $height;
        $info['mime'] = $size['mime'];
        $info['url'] = Config::read('base_url') . ltrim($rel_path, '/');
        $info['path'] = $abs_path;
        $info['exif'] = exif_read_data($abs_path);

        $arr = $db->get_image_attributes($info['dir'], $info['name']);
        $info = array_merge($info, $arr);

        if ( empty($info['area']) && !empty($info['altitude']) ) {
            $info['area'] = MaSIS::get_area_from_altitude($info['altitude']);
        }
        if ( !empty($info['area']) && !empty($width) && !empty($height) ) {
            $info['area_per_pixel'] = $info['area'] / ($width * $height);
        }
        if ( !empty($info['latitude']) && !empty($info['longitude']) ) {
            $info['location_map_url'] = htmlentities("https://maps.google.com/maps?q={$info['latitude']},{$info['longitude']}&iwloc=A&hl=en");
        }
        return json_encode($info);
    }

    /**
     * Return a list of species names matching the search term.
     *
     * This method retrieves the species names from the online WoRMS (World
     * Register of Marine Species) database.
     *
     * @param $term The keyword to match against species names in the database.
     * @param $searchpar Search by 0 = scientific name, 1 = common name.
     */
    public function get_species_from_worms($term, $searchpar=0) {
        global $db;

        // Call the WoRMS webservice.
        $client = new SoapClient("http://www.marinespecies.org/aphia.php?p=soap&wsdl=1");

        switch ($searchpar) {
            case 0:
                // Get max. 50 records matching the scientific species name.
                $records = $client->getAphiaRecords($term);
                break;
            case 1:
                // Get max. 50 records matching the common species name.
                $records = $client->getAphiaRecordsByVernacular($term);
                break;
            default:
                throw new Exception( "Value '$searchpar' is invalid for parameter `searchpar`." );
        }

        $cache = array();
        $species = array();
        $species[] = array('label' => "Unassigned", 'value' => null);
        if ($records) {
            // Cache the records to the local database.
            $db->cache_aphia_records($records, Config::read('update_species_records'));

            foreach ( $records as $sp ) {
                // Skip records with a specific status.
                if ( in_array($sp->status, $db->aphia_status_exclude) ) continue;
                // Show in the label if a record is unaccepted.
                $label = $sp->status == 'unaccepted' ? $sp->scientificname . " (unaccepted)" : $sp->scientificname;

                $species[] = array(
                    'label' => $label,
                    'value' => $sp->AphiaID
                    );
            }
        }
        return json_encode($species);
    }

    /**
     * Return all substrate types prepared for HTML select input options.
     *
     * Each object in the array has two attributes: value and label. To be
     * used as value and text for the <option> elements.
     *
     * @return Substrate types in JSON format
     */
    public function get_substrate_types() {
        global $db;

        $sth = $db->get_substrate_types();
        $types = array();
        while ( $row = $sth->fetch(PDO::FETCH_ASSOC) ) {
            $types[] = array(
                'value' => $row['name'],
                'label' => $row['name']
                );
        }
        return json_encode($types);
    }

    /**
     * Return all image tag types prepared for HTML select input options.
     *
     * Each object in the array has two attributes: value and label. To be
     * used as value and text for the <option> elements.
     *
     * @return Substrate types in JSON format
     */
    public function get_image_tag_types() {
        global $db;

        $sth = $db->get_image_tag_types();
        $types = array();
        while ( $row = $sth->fetch(PDO::FETCH_ASSOC) ) {
            $types[] = array(
                'value' => $row['name'],
                'label' => $row['name']
                );
        }
        return json_encode($types);
    }

    /**
     * Return the substrate annotations for an image.
     *
     * @param int $image_id The id for the image
     * @return The substrate annotations in JSON format
     */
    public function get_substrate_annotations($image_id) {
        global $db;

        $sth = $db->get_substrate_annotations($image_id);
        $annotations = $sth->fetchAll(PDO::FETCH_OBJ);
        return json_encode($annotations);
    }

    /**
     * Return the tags for an image.
     *
     * @param int $image_id The id for the image
     * @return The image tags in JSON format
     */
    public function get_image_tags($image_id) {
        global $db;

        $sth = $db->get_image_tags($image_id);
        $tags = $sth->fetchAll(PDO::FETCH_OBJ);
        return json_encode($tags);
    }

    public function get_vectors($image_id) {
        global $db;

        $sth = $db->get_vectors($image_id);
        $vectors = array();
        while ( $row = $sth->fetch(PDO::FETCH_ASSOC) ) {
            $vectors[] = $row;
        }
        return json_encode($vectors);
    }
}
