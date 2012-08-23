<?php

/**
 * The Database class provides methods for retrieving data from the database.
 */
class Database {
    public $dbh = null;

    /**
     * Connect with the PostgreSQL database.
     */
    public function connect() {
        global $config;

        try {
            $this->dbh = new PDO("pgsql:dbname={$config['pg']['dbname']};host={$config['pg']['host']}",
                $config['pg']['username'],
                $config['pg']['password'],
                array(
                    PDO::ATTR_PERSISTENT => true // Use persistent connections.
                ));
        }
        catch (PDOException $e) {
            exit( "Unable to connect: " . $e->getMessage() );
        }

        // Throw exceptions so errors can be handled gracefully.
        $this->dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
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
        $filename = $filename[0] . ".%";

        try {
            $sth = $this->dbh->prepare("SELECT * FROM image_info
                WHERE img_dir = :dir AND file_name SIMILAR TO :filename;");
            $sth->bindParam(":dir", $dir, PDO::PARAM_STR);
            $sth->bindParam(":filename", $filename, PDO::PARAM_STR);
            $sth->execute();
        }
        catch (Exception $e) {
            throw new Exception( $e->getMessage() );
        }
        return $sth->fetch(PDO::FETCH_ASSOC);
    }

    public function get_files_for_dir($dir) {
        try {
            $sth = $this->dbh->prepare("SELECT file_name FROM image_info
                WHERE img_dir = :dir
                ORDER BY file_name;");
            $sth->bindParam(":dir", $dir, PDO::PARAM_STR);
            $sth->execute();
        }
        catch (Exception $e) {
            throw new Exception( $e->getMessage() );
        }
        return $sth;
    }

    public function get_species($filter = null) {
        if ($filter) {
            $query = "SELECT * FROM species
                WHERE name_latin ~* :filter
                    OR name_venacular ~* :filter;";
        } else {
            $query = "SELECT * FROM species;";
        }

        try {
            $sth = $this->dbh->prepare($query);
            if ($filter) $sth->bindParam(":filter", $filter, PDO::PARAM_STR);
            $sth->execute();
        }
        catch (Exception $e) {
            throw new Exception( $e->getMessage() );
        }
        return $sth;
    }

    public function get_vectors($image_id) {
        try {
            $sth = $this->dbh->prepare("SELECT v.vector_id,
                v.vector_wkt,
                s.id AS species_id,
                s.name_latin,
                s.name_venacular
            FROM vectors v
                -- OUTER JOIN because unassigned vectors should be returned
                -- as well
                LEFT OUTER JOIN species s ON v.species_id = s.id
            WHERE v.image_info_id = :image_id;");
            $sth->bindParam(":image_id", $image_id, PDO::PARAM_INT);
            $sth->execute();
        }
        catch (Exception $e) {
            throw new Exception( $e->getMessage() );
        }
        return $sth;
    }

    public function save_vectors($vectors) {
        // Start a database transaction.
        $this->dbh->beginTransaction();

        foreach ($vectors as $i => $vector) {
            // Check if this particular vector already exists in the database.
            try {
                $sth = $this->dbh->prepare("SELECT id FROM vectors
                    WHERE image_info_id = :image_id
                        AND vector_id = :vector_id;");
                $sth->bindParam(":image_id", $vector['image_id'], PDO::PARAM_INT);
                $sth->bindParam(":vector_id", $vector['id'], PDO::PARAM_STR);
                $sth->execute();
            }
            catch (Exception $e) {
                throw new Exception( $e->getMessage() );
            }
            $row = $sth->fetch();
            $vector_id = $row ? $row[0] : NULL;

            // Handle vectors not assigned to a species.
            // The ( !is_int() && !ctype_digit() ) part is for checking numeric
            // strings.
            if ( !isset($vector['species_id']) || ( !is_int($vector['species_id']) && !ctype_digit($vector['species_id']) ) ) {
                $vector['species_id'] = NULL;
                $vector['species_name'] = NULL;
            }

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
                        :image_id,
                        :species_id,
                        :id,
                        :vector_wkt,
                        :area_pixels,
                        :area_m2,
                        :species_name);";
            }
            else {
                $query = "UPDATE vectors SET (
                        species_id,
                        vector_wkt,
                        area_pixels,
                        area_m2,
                        remarks
                    ) = (
                        :species_id,
                        :vector_wkt,
                        :area_pixels,
                        :area_m2,
                        :species_name)
                    WHERE image_info_id = :image_id
                        AND vector_id = :id;";
            }
            try {
                $sth = $this->dbh->prepare($query);
                $sth->bindParam(":species_id", $vector['species_id'], PDO::PARAM_INT);
                $sth->bindParam(":vector_wkt", $vector['vector_wkt'], PDO::PARAM_STR);
                $sth->bindParam(":area_pixels", $vector['area_pixels'], PDO::PARAM_INT);
                $sth->bindParam(":area_m2", $vector['area_m2'], PDO::PARAM_STR);
                $sth->bindParam(":species_name", $vector['species_name'], PDO::PARAM_STR);
                $sth->bindParam(":image_id", $vector['image_id'], PDO::PARAM_INT);
                $sth->bindParam(":id", $vector['id'], PDO::PARAM_STR);
                $sth->execute();
            }
            catch (Exception $e) {
                throw new Exception( $e->getMessage() );
            }
        }

        // Commit the transaction.
        $this->dbh->commit();
    }

    public function delete_vector($image_id, $vector_id) {
        try {
            $sth = $this->dbh->prepare("DELETE FROM vectors
            WHERE image_info_id = :image_id
                AND vector_id = :vector_id;");
            $sth->bindParam(":image_id", $image_id, PDO::PARAM_INT);
            $sth->bindParam(":vector_id", $vector_id, PDO::PARAM_STR);
            $sth->execute();
        }
        catch (Exception $e) {
            throw new Exception( $e->getMessage() );
        }
    }

    /**
     * Create database table `areas_image_grouped`.
     *
     * This table contains the species coverage per image. The total coverage
     * for each species can be calculated using this table.
     */
    public function set_areas_image_grouped() {
        // Start a database transaction.
        $this->dbh->beginTransaction();

        try {
            $sth = $this->dbh->prepare("DROP TABLE IF EXISTS areas_image_grouped;");
            $sth->execute();
        }
        catch (Exception $e) {
            throw new Exception( $e->getMessage() );
        }

        try {
            $sth = $this->dbh->prepare("SELECT i.id as image_id,
                    s.id as species_id,
                    sum(v.area_m2) as species_area,
                    i.area as image_area
                INTO areas_image_grouped
                FROM vectors v
                    INNER JOIN species s ON s.id = v.species_id
                    INNER JOIN image_info i ON i.id = v.image_info_id
                GROUP BY i.id, s.id;");
            $sth->execute();
        }
        catch (Exception $e) {
            throw new Exception( $e->getMessage() );
        }

        // Commit the transaction.
        $this->dbh->commit();
    }

    /**
     * Calculate the area for all records in table `image_info`. Records that
     * already have the area set are skipped.
     *
     * @return int The number of updated records.
     */
    public function set_areas() {
        try {
            $sth = $this->dbh->prepare("SELECT id, altitude FROM image_info
                WHERE area IS NULL;");
            $sth->execute();
        }
        catch (Exception $e) {
            throw new Exception( $e->getMessage() );
        }

        // Start a database transaction.
        $this->dbh->beginTransaction();

        $count = 0;
        while ( $row = $sth->fetch(PDO::FETCH_ASSOC) ) {
            $area = MaSIS::get_area_from_altitude($row['altitude']);

            try {
                $sth2 = $this->dbh->prepare("UPDATE image_info SET area = :area
                    WHERE id = :id;");
                $sth2->bindParam(":area", $area, PDO::PARAM_STR);
                $sth2->bindParam(":id", $row['id'], PDO::PARAM_INT);
                $sth2->execute();
            }
            catch (Exception $e) {
                throw new Exception( $e->getMessage() );
            }
            $count++;
        }

        // Commit the transaction.
        $this->dbh->commit();
        return $count;
    }

    /**
     * Set the annotation status for an image.
     *
     * @param int $image_id The id for the image (image_info.id).
     * @param string $status The status ('incomplete','complete','moderate','review').
     */
    public function set_annotation_status($image_id, $status) {
        try {
            $sth = $this->dbh->prepare("UPDATE image_info SET annotation_status = :status
                WHERE id = :id;");
            $sth->bindParam(":status", $status, PDO::PARAM_STR);
            $sth->bindParam(":id", $image_id, PDO::PARAM_INT);
            $sth->execute();
        }
        catch (Exception $e) {
            throw new Exception( $e->getMessage() );
        }
    }

}
