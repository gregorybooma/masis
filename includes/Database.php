<?php

/**
 * The Database class provides methods for retrieving data from the database.
 *
 * Requires that config.php is imported.
 */
class Database {
    private $dbconn = null;

    /**
     * Connect with the PostgreSQL database.
     */
    public function connect() {
        global $config;
        $this->dbconn = pg_connect("host={$config['pg']['host']} dbname={$config['pg']['dbname']} user={$config['pg']['username']} password={$config['pg']['password']}");
    }

    /**
     * Get the altitude for an image file.
     *
     * @param string $dir Directory name for the image file (e.g. iCamera_2010-08-18_1924_session0010).
     * @param array $filename File name of the image file (e.g. 004624.jpeg).
     * @return float $altitude Altitude at which the photo was taken.
     */
    public function get_image_attributes($dir, $filename) {
        $filename = explode('.', $filename);
        $filename = $filename[0];

        $query = "SELECT id,altitude,depth,area FROM image_info
            WHERE img_dir = '{$dir}'
                AND file_name SIMILAR TO '{$filename}.%';";
        $result = pg_query($this->dbconn, $query) or die('Query failed: ' . pg_last_error());
        return pg_fetch_assoc($result);
    }

    public function get_files_for_dir($dir) {
        $query = "SELECT file_name FROM image_info
            WHERE img_dir = '{$dir}'";
        $result = pg_query($this->dbconn, $query) or die('Query failed: ' . pg_last_error());
        return $result;
    }

    public function get_species($filter = null) {
        if ($filter) {
            $query = "SELECT * FROM species
                WHERE name_latin ~* '{$filter}'
                OR name_venacular ~* '{$filter}';";
        } else {
            $query = "SELECT * FROM species;";
        }
        $result = pg_query($this->dbconn, $query) or die('Query failed: ' . pg_last_error());
        return $result;
    }

    public function get_vectors($image_id) {
        $query = "SELECT v.vector_id,
                v.vector_wkt,
                s.id AS species_id,
                s.name_latin,
                s.name_venacular
            FROM selections v
                INNER JOIN species s ON v.species_id = s.id
            WHERE v.image_info_id = {$image_id};";

        $result = pg_query($this->dbconn, $query) or die('Query failed: ' . pg_last_error());
        return $result;
    }

    public function save_vectors($vectors) {
        $return_value = 0;
        foreach ($vectors as $i => $vector) {
            // Check if this particular vector already exists in the database.
            $query = "SELECT id FROM selections
                WHERE image_info_id = {$vector['image_id']}
                AND vector_id = '{$vector['id']}';";
            $result = pg_query($this->dbconn, $query) or die('Query failed: ' . pg_last_error());
            $row = pg_fetch_row($result);
            $vector_id = $row ? $row[0] : NULL;

            // Save or update vector.
            if ( is_null($vector_id) ) {
                $query = "INSERT INTO selections (
                        image_info_id,
                        species_id,
                        vector_id,
                        vector_wkt,
                        area_pixels,
                        area_m2,
                        remarks)
                    VALUES (
                        {$vector['image_id']},
                        {$vector['species_id']},
                        '{$vector['id']}',
                        '{$vector['vector_wkt']}',
                        {$vector['area_pixels']},
                        {$vector['area_m2']},
                        '{$vector['species_name']}');";
            }
            else {
                $query = "UPDATE selections SET (
                        species_id,
                        vector_wkt,
                        area_pixels,
                        area_m2,
                        remarks
                    ) = (
                        {$vector['species_id']},
                        '{$vector['vector_wkt']}',
                        {$vector['area_pixels']},
                        {$vector['area_m2']},
                        '{$vector['species_name']}')
                    WHERE image_info_id = {$vector['image_id']}
                        AND vector_id = '{$vector['id']}';";
            }
            $result = pg_query($this->dbconn, $query) or die('Query failed: ' . pg_last_error());
        }
    }
}

