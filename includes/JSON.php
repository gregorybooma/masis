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
        $info['url'] = Config::read('image_base_url') . $info['dir'] . '/' . $info['name'];
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
        global $config, $db;

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

        $species = array();
        $species[] = array('label' => "Unassigned", 'value' => null);
        if ($records) {
            // Cache the records to the local database.
            $db->cache_aphia_records($records, Config::read('update_species_records'));

            foreach ( $records as $sp ) {
                // Skip species with a specific status
                if ($sp->status == "nomen nudum") continue;
                if ($sp->status == "nomen dubium") continue;

                $species[] = array(
                    'label' => $sp->scientificname,
                    'value' => $sp->AphiaID
                    );
            }
        }
        print json_encode($species);
    }

    public function get_substrate_types($term=null) {
        global $db;

        $sth = $db->get_substrate_types($term);
        $types = array();
        while ( $row = $sth->fetch(PDO::FETCH_ASSOC) ) {
            $types[] = array(
                'value' => $row['id'],
                'label' => $row['name']
                );
        }
        print json_encode($types);
    }

    public function get_vectors($image_id) {
        global $db;

        $sth = $db->get_vectors($image_id);
        $vectors = array();
        while ( $row = $sth->fetch(PDO::FETCH_ASSOC) ) {
            $vectors[] = $row;
        }
        print json_encode($vectors);
    }
}
