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
            $this->dbh = new PDO("pgsql:dbname=" . Config::read('database') . ";host=" . Config::read('hostname'),
                Config::read('username'),
                Config::read('password'),
                Config::read('drivers'));
        }
        catch (PDOException $e) {
            exit( "Unable to connect: " . $e->getMessage() );
        }

        // Throw exceptions so errors can be handled gracefully.
        $this->dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    }

	/*
	 * Create a new database prepared query
	 *
	 * @param string $query The prepared statement query to the database
	 * @param array|string $bind All the variables to bind to the prepared statement
	 * @return return the executed string
	 */
	public function query($query, $bind = null, $fetch = 'FETCH_ASSOC') {
		/* Prepare the query statement */
		$this->sth = $this->dbh->prepare($query);
		/* Bind each value supplied from $bind */
		if ($bind != null) {
			foreach($bind as $select => $value) {
				/* For each type of value give the appropriate param */
				if (is_int($value)) {
					$param = PDO::PARAM_INT;
				} elseif (is_bool($value)) {
					$param = PDO::PARAM_BOOL;
				} elseif (is_null($value)) {
					$param = PDO::PARAM_NULL;
				} elseif (is_string($value)) {
					$param = PDO::PARAM_STR;
				} else {
					$param = FALSE;
				}
				/* Bid value */
				if ($param) {
					$this->sth->bindValue($select, $value, $param);
				}
			}
		}
		/* Execute Query & check for any errors */
		if (!$this->sth->execute()){
			$result = array(
				1 => 'false',
				2 => '<b>[DATABASE] Error - Query:</b> There was an error in sql syntax',
			);
			return $result;
		}
		/* Return all content */
		if ($fetch == 'FETCH_ASSOC') {
			$result = $this->sth->fetch(PDO::FETCH_ASSOC);
		} elseif ($fetch == 'FETCH_BOTH') {
			$result = $this->sth->fetch(PDO::FETCH_BOTH);
		} elseif ($fetch == 'FETCH_LAZY') {
			$result = $this->sth->fetch(PDO::FETCH_LAZY);
		} elseif ($fetch == 'FETCH_OBJ') {
			$result = $this->sth->fetch(PDO::FETCH_OBJ);
		} elseif ($fetch == 'fetchAll') {
			$result = $this->sth->fetchAll();
		}
		return $result;
	}

    /**
     * Get the attributes for an image file.
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
            $sth = $this->dbh->prepare("SELECT i.file_name,
                    i.annotation_status,
                    COUNT(v.id) AS n_vectors
                FROM image_info i
                    LEFT OUTER JOIN vectors v ON v.image_info_id = i.id
                WHERE i.img_dir = :dir
                GROUP BY i.file_name, i.annotation_status
                ORDER BY i.file_name;");
            $sth->bindParam(":dir", $dir, PDO::PARAM_STR);
            $sth->execute();
        }
        catch (Exception $e) {
            throw new Exception( $e->getMessage() );
        }
        return $sth;
    }

    /**
     * Caches the records retrieved from the online WoRMS database.
     *
     * @param $records The records object returned by the SOAP function getAphiaRecords*()
     * @param boolean $update Whether to update existing records in the database.
     */
    public function cache_aphia_records($records, $update=true) {
        // Begin a database transaction.
        $this->dbh->beginTransaction();

        foreach ($records as $sp) {
            // Check if this record already exists in the database.
            try {
                $sth = $this->dbh->prepare("SELECT aphia_id FROM species
                    WHERE aphia_id = :aphia_id;");
                $sth->bindParam(":aphia_id", $sp->AphiaID, PDO::PARAM_INT);
                $sth->execute();
            }
            catch (Exception $e) {
                throw new Exception( $e->getMessage() );
            }
            $row = $sth->fetch();
            $aphia_id = $row ? $row[0] : NULL;

            // Cache or update the record.
            $query = NULL;
            if ( is_null($aphia_id) ) {
                $query = "INSERT INTO species (
                        aphia_id,
                        lsid,
                        scientific_name,
                        status,
                        valid_aphia_id,
                        valid_name,
                        kingdom,
                        phylum,
                        class,
                        \"order\",
                        family,
                        genus
                        )
                    VALUES (
                        :aphia_id,
                        :lsid,
                        :scientific_name,
                        :status,
                        :valid_aphia_id,
                        :valid_name,
                        :kingdom,
                        :phylum,
                        :class,
                        :order,
                        :family,
                        :genus);";
            }
            else if ($update) {
                $query = "UPDATE species SET (
                        lsid,
                        scientific_name,
                        status,
                        valid_aphia_id,
                        valid_name,
                        kingdom,
                        phylum,
                        class,
                        \"order\",
                        family,
                        genus
                    ) = (
                        :lsid,
                        :scientific_name,
                        :status,
                        :valid_aphia_id,
                        :valid_name,
                        :kingdom,
                        :phylum,
                        :class,
                        :order,
                        :family,
                        :genus)
                    WHERE aphia_id = :aphia_id;";
            }

            if (!$query) continue;

            try {
                $sth = $this->dbh->prepare($query);
                $sth->bindParam(":aphia_id", $sp->AphiaID, PDO::PARAM_INT);
                $sth->bindParam(":lsid", $sp->lsid, PDO::PARAM_STR);
                $sth->bindParam(":scientific_name", $sp->scientificname, PDO::PARAM_STR);
                $sth->bindParam(":status", $sp->status, PDO::PARAM_STR);
                $sth->bindParam(":valid_aphia_id", $sp->valid_AphiaID, PDO::PARAM_INT);
                $sth->bindParam(":valid_name", $sp->valid_name, PDO::PARAM_STR);
                $sth->bindParam(":kingdom", $sp->kingdom, PDO::PARAM_STR);
                $sth->bindParam(":phylum", $sp->phylum, PDO::PARAM_STR);
                $sth->bindParam(":class", $sp->class, PDO::PARAM_STR);
                $sth->bindParam(":order", $sp->order, PDO::PARAM_STR);
                $sth->bindParam(":family", $sp->family, PDO::PARAM_STR);
                $sth->bindParam(":genus", $sp->genus, PDO::PARAM_STR);
                $sth->execute();
            }
            catch (Exception $e) {
                throw new Exception( $e->getMessage() );
            }
        }

        // Commit the transaction.
        $this->dbh->commit();
    }

    /**
     * Return a list of species names matching the search term.
     *
     * This method can be used for the Autocomplete feature of jQuery UI.
     *
     * @param $term The keyword to match against species names in the database.
     * @return A PDO statement handler which returns the results.
     */
    public function get_species($term) {
        try {
            $sth = $this->dbh->prepare("SELECT * FROM species WHERE scientific_name ~* :term;");
            $sth->bindParam(":term", $term, PDO::PARAM_STR);
            $sth->execute();
        }
        catch (Exception $e) {
            throw new Exception( $e->getMessage() );
        }
        return $sth;
    }

    /**
     * Return a list of substrate types matching the search term.
     *
     * This method can be used for the Autocomplete feature of jQuery UI.
     *
     * @param $term The keyword to match against substrate types in the database.
     * @return A PDO statement handler which returns the results.
     */
    public function get_substrate_types($term=null) {
        if ($term) {
            $term = "^".$term;
            $query = "SELECT * FROM substrate_types WHERE name ~* :term;";
        }
        else {
            $query = "SELECT * FROM substrate_types ORDER BY name;";
        }

        try {
            $sth = $this->dbh->prepare($query);
            if ($term) $sth->bindParam(":term", $term, PDO::PARAM_STR);
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
                v.aphia_id,
                s.scientific_name
            FROM vectors v
                -- OUTER JOIN because unassigned vectors should be returned
                -- as well
                LEFT OUTER JOIN species s ON v.aphia_id = s.aphia_id
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
        global $member;

        // Get user info.
        $user = $member->data();

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
                        aphia_id,
                        vector_id,
                        vector_wkt,
                        area_pixels,
                        area_m2,
                        created_by,
                        remarks)
                    VALUES (
                        :image_id,
                        :aphia_id,
                        :id,
                        :vector_wkt,
                        :area_pixels,
                        :area_m2,
                        :user_id,
                        :species_name);";
            }
            else {
                $query = "UPDATE vectors SET (
                        aphia_id,
                        vector_wkt,
                        area_pixels,
                        area_m2,
                        updated_by,
                        updated_on,
                        remarks
                    ) = (
                        :aphia_id,
                        :vector_wkt,
                        :area_pixels,
                        :area_m2,
                        :user_id,
                        NOW(),
                        :species_name)
                    WHERE image_info_id = :image_id
                        AND vector_id = :id;";
            }
            try {
                $sth = $this->dbh->prepare($query);
                $sth->bindParam(":id", $vector['id'], PDO::PARAM_STR);
                $sth->bindParam(":image_id", $vector['image_id'], PDO::PARAM_INT);
                $sth->bindParam(":aphia_id", $vector['species_id'], PDO::PARAM_INT);
                $sth->bindParam(":vector_wkt", $vector['vector_wkt'], PDO::PARAM_STR);
                $sth->bindParam(":area_pixels", $vector['area_pixels'], PDO::PARAM_INT);
                $sth->bindParam(":area_m2", $vector['area_m2'], PDO::PARAM_STR);
                $sth->bindParam(":user_id", $user->id, PDO::PARAM_STR);
                $sth->bindParam(":species_name", $vector['species_name'], PDO::PARAM_STR);
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
            $sth = $this->dbh->exec("TRUNCATE areas_image_grouped;");
        }
        catch (Exception $e) {
            throw new Exception( $e->getMessage() );
        }

        try {
            $sth = $this->dbh->exec("INSERT INTO areas_image_grouped (image_info_id,aphia_id,species_area,image_area)
                SELECT i.id,
                    s.aphia_id,
                    sum(v.area_m2),
                    i.area
                FROM vectors v
                    INNER JOIN species s ON s.aphia_id = v.aphia_id
                    INNER JOIN image_info i ON i.id = v.image_info_id
                GROUP BY i.id, s.aphia_id;");
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
