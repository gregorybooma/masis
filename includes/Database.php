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

        $query = "SELECT altitude,depth,area FROM image_info
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
}

