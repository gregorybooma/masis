<?php

/**
 * The Exporter class exports data to files in CSV format.
 */
class Exporter {

    /**
     * PDO statement handler set by a set_* method.
     */
    public $sth = null;

    /**
     * Character to be used as the CSV field delimiter.
     * @var String
     */
    public $delimiter = ";";

    /**
     * Whether to print a header for the CSV output.
     * @var Bool
     */
    public $header = TRUE;

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
     * images where both species are present.
     *
     * Only images for which the annotation status is "complete" are used in
     * the calculations.
     *
     * @param string $species1 Scientific name of the first species.
     * @param string $species2 Scientific name of the second species.
     * @throws Exception
     */
    public function set_coverage_two_species_present($species1, $species2) {
        global $db;

        try {
            $sth = $db->dbh->prepare("SELECT a1.image_info_id AS image_id,
                    a1.vector_count AS \"{$species1} count\",
                    a1.species_area/a1.image_area AS \"{$species1} coverage\",
                    a2.vector_count AS \"{$species2} count\",
                    a2.species_area/a1.image_area AS \"{$species2} coverage\"
                FROM areas_image_grouped a1
                    INNER JOIN image_annotation_status ann ON ann.image_info_id = a1.image_info_id
                    LEFT OUTER JOIN image_tags t ON t.image_info_id = a1.image_info_id
                    INNER JOIN areas_image_grouped a2 ON a2.image_info_id = a1.image_info_id
                    INNER JOIN species sp1 ON sp1.aphia_id = a1.aphia_id
                    INNER JOIN species sp2 ON sp2.aphia_id = a2.aphia_id
                WHERE ann.annotation_status = 'complete'
                    AND t.image_tag NOT IN ('unusable','cannot see seafloor')
                    AND sp1.scientific_name = :species1
                    AND sp2.scientific_name = :species2;");
            $sth->bindParam(":species1", $species1, PDO::PARAM_STR);
            $sth->bindParam(":species2", $species2, PDO::PARAM_STR);
            $sth->execute();
        }
        catch (Exception $e) {
            throw new Exception( $e->getMessage() );
        }
        $this->sth = $sth;
    }
}
