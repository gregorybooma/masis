<?php

/**
 * The Exporter class exports data to files in CSV format.
 */
class Exporter {

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
     * Exports the percent cover for two species per image.
     *
     * Only images for which the annotation status is "complete" are used in
     * the calculations.
     *
     * @param string $file Absolute path to the file to be exported.
     * @param string $species1 Scientific name of the first species.
     * @param string $species2 Scientific name of the second species.
     * @param string $delimiter Character for the CSV field delimiter (defaults to ";").
     * @param bool $header Whether to export a CSV header (defaults to TRUE).
     * @throws Exception
     */
    public function percent_coverage_two_species($file, $species1, $species2, $delimiter=';', $header=TRUE) {
        global $db;

        try {
            $sth = $db->dbh->prepare("SELECT a1.image_info_id AS image_id,
                    a1.species_area/a1.image_area AS \"{$species1}\",
                    a2.species_area/a1.image_area AS \"{$species2}\"
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

        $fp = @fopen($file, 'w');
        if (!$fp) {
            $e = error_get_last();
            throw new Exception( $e['message'] );
        }

        if ($header) fputcsv( $fp, $this->get_header($sth), $delimiter );
        while ( $row = $sth->fetch(PDO::FETCH_NUM) ) {
            fputcsv($fp, $row, $delimiter);
        }
        fclose($fp);
    }
}
