<?php

/**
 * Export data to files in CSV format.
 */
class Exporter {

    /**
     * PDO statement handler set by a set_* method.
     */
    public $sth = null;

    /**
     * Character to be used as the CSV field delimiter.
     */
    public $delimiter = ";";

    /**
     * Whether to print a header for the CSV output.
     */
    public $header = TRUE;

    /**
     * Associative array with Aphia ID as key, species name as value.
     */
    public $aphia2name = array();

    /**
     * Images with a dominant substrate type that matches a value in this
     * list are excluded.
     */
    public $exclude_dominant_substrates = array();

    /**
     * Images with a dominant substrate type that matches a value in this
     * list are exclusively included.
     */
    public $include_dominant_substrates = array();

    /**
     * Set the values for the object attributes.
     *
     * @param string $delimiter Character to be used as the CSV field delimiter.
     * @param bool $header Whether to print a header for the CSV output.
     */
    public function __construct($delimiter=";", $header=TRUE) {
        $this->delimiter = $delimiter;
        $this->header = $header;
    }

    /**
     * Print results from a PDO statement hander in CSV format.
     *
     * @param string $filename The name of the CSV file to be exported.
     */
    public function export_csv($filename) {
        if (!$this->sth) return;
        header("Content-Type: text/csv; charset=utf-8");
        header("Content-Disposition: attachment; filename={$filename}");
        if ($this->header) print implode($this->delimiter, $this->get_header($this->sth))."\n";
        while ( $row = $this->sth->fetch(PDO::FETCH_NUM) ) {
            print implode($this->delimiter, $row)."\n";
        }
        exit();
    }

    /**
     * Return a header array from the query output.
     *
     * @param $sth A PDO statement handler.
     * @return array Column names.
     */
    private function get_header($sth) {
        $header = array();
        for ($i = 0; $i < $sth->columnCount(); $i++) {
            $meta = $sth->getColumnMeta($i);
            $field = $meta['name'];
            $header[] = $field;
        }
        return $header;
    }

    /**
     * Set vector count and coverage/square meter for two species per image on
     * images where either species is present.
     *
     * Only images for which the annotation status is "complete" are used in
     * the calculations.
     *
     * @param integer $aphia_id1 Aphia ID for species A.
     * @param integer $aphia_id2 Aphia ID for species B.
     * @uses array $this->exclude_dominant_substrates List of dominant substrates to exclude.
     * @uses array $this->include_dominant_substrates List of dominant substrates to include exclusively.
     * @throws Exception
     */
    public function set_coverage_two_species($aphia_id1, $aphia_id2) {
        global $db;

        $this->set_names_from_aphia_ids(array($aphia_id1, $aphia_id2));
        $tags_image_unusable = "'".implode("','", $db->tags_image_unusable)."'";

        $query = "SELECT i.id AS image_id,
                i.img_dir,
                i.file_name,
                COALESCE(a1.vector_count, 0) AS \"{$this->aphia2name[$aphia_id1]} count\",
                COALESCE(a1.species_area/i.img_area, 0) AS \"{$this->aphia2name[$aphia_id1]} coverage\",
                COALESCE(a2.vector_count, 0) AS \"{$this->aphia2name[$aphia_id2]} count\",
                COALESCE(a2.species_area/i.img_area, 0) AS \"{$this->aphia2name[$aphia_id2]} coverage\"
            FROM image_info i
                INNER JOIN image_annotation_status ann ON ann.image_info_id = i.id
                LEFT JOIN areas_image_grouped a1 ON a1.image_info_id = i.id AND a1.aphia_id = :aphia_id1
                LEFT JOIN areas_image_grouped a2 ON a2.image_info_id = i.id AND a2.aphia_id = :aphia_id2
            WHERE i.img_area IS NOT NULL
                AND ann.annotation_status = 'complete'
                AND NOT EXISTS (SELECT 1 FROM image_tags WHERE image_info_id = i.id AND image_tag IN ({$tags_image_unusable}))
                --where
                AND (a1.species_area IS NOT NULL OR a2.species_area IS NOT NULL);";
        if ( count($this->exclude_dominant_substrates) > 0 ) {
            $exclude_dominant_substrates = "'".implode("','", $this->exclude_dominant_substrates)."'";
            $query = str_replace("--where", "AND NOT EXISTS (SELECT 1 FROM image_substrate WHERE image_info_id = i.id AND substrate_type IN ({$exclude_dominant_substrates}) AND dominance = 'dominant')
                --where", $query);
        }
        if ( count($this->include_dominant_substrates) > 0 ) {
            $include_dominant_substrates = "'".implode("','", $this->include_dominant_substrates)."'";
            $query = str_replace("--where", "AND EXISTS (SELECT 1 FROM image_substrate WHERE image_info_id = i.id AND substrate_type IN ({$include_dominant_substrates}) AND dominance = 'dominant')
                --where", $query);
        }

        try {
            $sth = $db->dbh->prepare($query);

            $sth->bindParam(":aphia_id1", $aphia_id1, PDO::PARAM_INT);
            $sth->bindParam(":aphia_id2", $aphia_id2, PDO::PARAM_INT);
            $sth->execute();
        }
        catch (Exception $e) {
            throw new Exception( $e->getMessage() );
        }
        $this->sth = $sth;
    }

    /**
     * Set vector count and coverage/square meter for two species per image on
     * images where both species are present.
     *
     * Only images for which the annotation status is "complete" are used in
     * the calculations.
     *
     * @param integer $aphia_id1 Aphia ID for species A.
     * @param integer $aphia_id2 Aphia ID for species B.
     * @uses array $this->exclude_dominant_substrates List of dominant substrates to exclude.
     * @uses array $this->include_dominant_substrates List of dominant substrates to include exclusively.
     * @throws Exception
     */
    public function set_coverage_two_species_present($aphia_id1, $aphia_id2) {
        global $db;

        $this->set_names_from_aphia_ids(array($aphia_id1, $aphia_id2));
        $tags_image_unusable = "'".implode("','", $db->tags_image_unusable)."'";

        $query = "SELECT i.id AS image_id,
                i.img_dir,
                i.file_name,
                COALESCE(a1.vector_count, 0) AS \"{$this->aphia2name[$aphia_id1]} count\",
                COALESCE(a1.species_area/i.img_area, 0) AS \"{$this->aphia2name[$aphia_id1]} coverage\",
                COALESCE(a2.vector_count, 0) AS \"{$this->aphia2name[$aphia_id2]} count\",
                COALESCE(a2.species_area/i.img_area, 0) AS \"{$this->aphia2name[$aphia_id2]} coverage\"
            FROM image_info i
                INNER JOIN image_annotation_status ann ON ann.image_info_id = i.id
                LEFT JOIN areas_image_grouped a1 ON a1.image_info_id = i.id AND a1.aphia_id = :aphia_id1
                LEFT JOIN areas_image_grouped a2 ON a2.image_info_id = i.id AND a2.aphia_id = :aphia_id2
            WHERE i.img_area IS NOT NULL
                AND ann.annotation_status = 'complete'
                AND NOT EXISTS (SELECT 1 FROM image_tags WHERE image_info_id = i.id AND image_tag IN ({$tags_image_unusable}))
                --where
                AND (a1.species_area IS NOT NULL AND a2.species_area IS NOT NULL);";
        if ( count($this->exclude_dominant_substrates) > 0 ) {
            $exclude_dominant_substrates = "'".implode("','", $this->exclude_dominant_substrates)."'";
            $query = str_replace("--where", "AND NOT EXISTS (SELECT 1 FROM image_substrate WHERE image_info_id = i.id AND substrate_type IN ({$exclude_dominant_substrates}) AND dominance = 'dominant')
                --where", $query);
        }
        if ( count($this->include_dominant_substrates) > 0 ) {
            $include_dominant_substrates = "'".implode("','", $this->include_dominant_substrates)."'";
            $query = str_replace("--where", "AND EXISTS (SELECT 1 FROM image_substrate WHERE image_info_id = i.id AND substrate_type IN ({$include_dominant_substrates}) AND dominance = 'dominant')
                --where", $query);
        }

        try {
            $sth = $db->dbh->prepare($query);
            $sth->bindParam(":aphia_id1", $aphia_id1, PDO::PARAM_INT);
            $sth->bindParam(":aphia_id2", $aphia_id2, PDO::PARAM_INT);
            $sth->execute();
        }
        catch (Exception $e) {
            throw new Exception( $e->getMessage() );
        }
        $this->sth = $sth;
    }

    /**
     * Populate the aphia2name attribute.
     *
     * @param Array $ids Aphia ID's
     * @uses Array $this->aphia2name
     * @throws Exception
     */
    public function set_names_from_aphia_ids($ids) {
        global $db;

        $query = sprintf("SELECT aphia_id,scientific_name
            FROM species
            WHERE aphia_id IN (%s);", implode(',', $ids));
        try {
            $sth = $db->dbh->prepare($query);
            $sth->execute();
        }
        catch (Exception $e) {
            throw new Exception( $e->getMessage() );
        }
        while ( $row = $sth->fetch(PDO::FETCH_ASSOC) ) {
            $this->aphia2name[$row['aphia_id']] = $row['scientific_name'];
        }
    }
}
