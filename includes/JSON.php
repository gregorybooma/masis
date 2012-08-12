<?php

/**
 * The JSON class generates data in JSON format.
 */
class JSON {
    public function html_select_species() {
        global $db;

        $filter = isset($_GET['term']) ? $_GET['term'] : null;
        $species = $db->get_species($filter);
        $species_arr = array();
        $species_arr[] = array('label' => "Unassigned", 'value' => null);
        while ( $s = pg_fetch_array($species, null, PGSQL_ASSOC) ) {
            if ( $s['name_venacular'] ) {
                $label = sprintf("%s (%s)", $s['name_latin'], $s['name_venacular']);
            } else {
                $label = $s['name_latin'];
            }
            $species_arr[] = array(
                'label' => $label,
                'value' => $s['id']
                );
        }
        print json_encode($species_arr);
    }
}
