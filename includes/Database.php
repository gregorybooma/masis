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
        if ( !$result = pg_query($this->dbconn, $query) ) {
            throw new Exception( pg_last_error() );
        }
        return pg_fetch_assoc($result);
    }

    public function get_files_for_dir($dir) {
        $query = "SELECT file_name FROM image_info
            WHERE img_dir = '{$dir}'";
        if ( !$result = pg_query($this->dbconn, $query) ) {
            throw new Exception( pg_last_error() );
        }
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
        if ( !$result = pg_query($this->dbconn, $query) ) {
            throw new Exception( pg_last_error() );
        }
        return $result;
    }

    public function get_vectors($image_id) {
        $query = "SELECT v.vector_id,
                v.vector_wkt,
                s.id AS species_id,
                s.name_latin,
                s.name_venacular
            FROM vectors v
                -- OUTER JOIN because unassigned vectors should be returned
                -- as well
                LEFT OUTER JOIN species s ON v.species_id = s.id
            WHERE v.image_info_id = {$image_id};";

        if ( !$result = pg_query($this->dbconn, $query) ) {
            throw new Exception( pg_last_error() );
        }
        return $result;
    }

    public function save_vectors($vectors) {
        foreach ($vectors as $i => $vector) {
            // Check if this particular vector already exists in the database.
            $query = "SELECT id FROM vectors
                WHERE image_info_id = {$vector['image_id']}
                AND vector_id = '{$vector['id']}';";
            if ( !$result = pg_query($this->dbconn, $query) ) {
                throw new Exception( pg_last_error() );
            }
            $row = pg_fetch_row($result);
            $vector_id = $row ? $row[0] : NULL;

            // Handle vectors not assigned to a species.
            if ( !is_int($vector['species_id']) && !ctype_digit($vector['species_id']) ) {
                $vector['species_id'] = 'NULL';
                $vector['species_name'] = NULL;
            }
            $vector['species_name'] = is_null($vector['species_name']) ? 'NULL' : "'{$vector['species_name']}'";

            // Save or update vector.
            if ( is_null($vector_id) ) {
                $query = "INSERT INTO vectors (
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
                        {$vector['species_name']});";
            }
            else {
                $query = "UPDATE vectors SET (
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
                        {$vector['species_name']})
                    WHERE image_info_id = {$vector['image_id']}
                        AND vector_id = '{$vector['id']}';";
            }
            if ( !$result = pg_query($this->dbconn, $query) ) {
                throw new Exception( pg_last_error() );
            }
        }
    }

    public function delete_vector($image_id, $vector_id) {
        $query = "DELETE FROM vectors
            WHERE image_info_id = {$image_id}
                AND vector_id = '{$vector_id}';";
        if ( !$result = pg_query($this->dbconn, $query) ) {
            throw new Exception( pg_last_error() );
        }
    }
}

