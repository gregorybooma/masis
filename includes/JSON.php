<?php

/**
 * The JSON class generates data in JSON format.
 */
class JSON {
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
