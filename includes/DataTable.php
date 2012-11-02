<?php

/**
 * Generate HTML tables.
 *
 * Generated HTML tables can be used for the DataTables jQuery extension.
 */
class DataTable {

    /**
     * The number of decimals to round to.
     */
    public $round_precision = 5;

    /**
     * Names of columns for which the values should be rounded.
     */
    public $round_columns = array('species_cover_percent','species_area',
        'avg_species_area','surface_area');

    /**
     * Set table heads from the query output.
     *
     * @uses array $this->tableHeads Column names for the query output.
     * @uses array $this->result Query output.
     */
    private function set_table_heads($sth) {
        $this->tableHeads = array();
        $column_names = array(
            'vector_id' => "Vector ID",
            'aphia_id' => "Aphia ID",
            'scientific_name' => "Species Name",
            'area_m2' => "Area (m<sup>2</sup>)",
            'species_area' => "Species Coverage (m<sup>2</sup>)",
            'avg_species_area' => "Average Species Coverage (m<sup>2</sup>)",
            'vector_count' => "Selection Count",
            'image_count' => "Image Count",
            'surface_area' => "Surface Area (m<sup>2</sup>)",
            'species_cover' => "Species Coverage Fraction",
            'species_cover_percent' => "Species Coverage %",
            'image_info_id' => "Image ID",
            'created_by' => "Created By",
            'updated_on' => "Last Updated",
            'img_dir' => "Image Directory",
            'file_name' => "Filename",
            'event_id' => "Event ID",
            'date_taken' => "Date Taken",
            '' => "",
            );
        for ($i = 0; $i < $sth->columnCount(); $i++) {
            $meta = $sth->getColumnMeta($i);
            $field = $meta['name'];
            if ( array_key_exists($field, $column_names) ) {
                $field = $column_names[$field];
            }
            $this->tableHeads[] = $field;
        }
    }

    /**
     * Print HTML table.
     *
     * @param string $body The HTML tbody element for the table.
     * @param string $properties Optional additional properties for the table tag.
     * @param array $components Specifies the elements for the table. Possible
     *      items for the array are the strings 'header' and 'footer'. By
     *      default, both the header and the table are printed.
     */
    public function build($body, $properties="", $components = array('header','footer')) {
        print "<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" {$properties}>";
        if ( in_array('header', $components) ) {
            print $this->build_thead();
        }
        print $body;
        if ( in_array('footer', $components) ) {
            print $this->build_tfoot();
        }
        print "</table>";
    }

    /**
     * Prints the HTML thead element for a table.
     *
     * The thead element contains the column names set in $this->tableHeads.
     *
     * @uses $this->tableHeads
     */
    private function build_thead() {
        print "<thead>\n<tr>\n";
        foreach ($this->tableHeads as $head) {
            print "<th>{$head}</th>\n";
        }
        print "</tr>\n</thead>\n";
    }

    /**
     * Prints the HTML tfoot element for a table.
     *
     * The tfoot element contains the column names set in $this->tableHeads.
     *
     * @uses $this->tableHeads
     */
    private function build_tfoot() {
        print "<tfoot>\n<tr>\n";
        foreach ($this->tableHeads as $head) {
            print "<th>{$head}</th>\n";
        }
        print "</tr>\n</tfoot>\n";
    }

    /**
     * Return a simple HTML table body.
     *
     * @param $sth PDO Statement handler.
     * @param $class Optional CSS class for rows.
     * @return string A HTML tbody element.
     */
    public function build_tbody($sth, $class="") {
        $tbody = "<tbody>";
        while ( $row = $sth->fetch(PDO::FETCH_ASSOC) ) {
            $tbody .= "<tr class='{$class}'>\n";
            foreach ($row as $key => $col_value) {
                $col_class = "";
                // Show scientific species names in italics.
                if ($key == 'scientific_name') $col_class .= "text-italic ";
                // Link Aphia ID's to the WoRMS website.
                if ($key == 'aphia_id') $col_value = "<a href=\"http://www.marinespecies.org/aphia.php?p=taxdetails&id={$col_value}\" target=\"_blank\">{$col_value}</a>";
                // Round some values.
                if ( in_array($key, $this->round_columns) ) {
                    $col_value = round($col_value, $this->round_precision);
                }

                $tbody .= "<td align='center' class='{$col_class}'>{$col_value}</td>\n";
            }
            $tbody .= "</tr>\n";
        }
        $tbody .= "</tbody>";
        return $tbody;
    }

    /**
     * Print a list of images with vectors that are not assigned to a species.
     */
    public function images_unassigned_vectors() {
        global $db;

        try {
            $sth = $db->dbh->prepare("SELECT  i.img_dir,
                    i.file_name,
                    i.event_id,
                    to_char(i.timestamp, 'DD Mon YYYY, HH24:MI:SS') AS date_taken
                FROM vectors v
                    INNER JOIN image_info i ON i.id = v.image_info_id
                WHERE aphia_id IS NULL
                GROUP BY i.id;");
            $sth->execute();
        }
        catch (Exception $e) {
            throw new Exception( $e->getMessage() );
        }

        $this->set_table_heads($sth);
        $body = $this->build_tbody($sth);
        $this->build($body);
    }

    /**
     * Print a list of images that are flagged for review.
     */
    public function images_need_review() {
        global $db;

        try {
            $sth = $db->dbh->prepare("SELECT i.img_dir,
                    i.file_name,
                    i.event_id,
                    to_char(i.timestamp, 'DD Mon YYYY, HH24:MI:SS') AS date_taken
                FROM image_info i
                    INNER JOIN image_tags t ON t.image_info_id = i.id
                WHERE t.image_tag = 'flag for review';");
            $sth->execute();
        }
        catch (Exception $e) {
            throw new Exception( $e->getMessage() );
        }

        $this->set_table_heads($sth);
        $body = $this->build_tbody($sth);
        $this->build($body);
    }

    /**
     * Print a list of all highlighted images.
     */
    public function images_highlighted() {
        global $db;

        try {
            $sth = $db->dbh->prepare("SELECT i.img_dir,
                    i.file_name,
                    i.event_id,
                    to_char(i.timestamp, 'DD Mon YYYY, HH24:MI:SS') AS date_taken
                FROM image_info i
                    INNER JOIN image_tags t ON t.image_info_id = i.id
                WHERE t.image_tag = 'highlight';");
            $sth->execute();
        }
        catch (Exception $e) {
            throw new Exception( $e->getMessage() );
        }

        $this->set_table_heads($sth);
        $body = $this->build_tbody($sth);
        $this->build($body);
    }

    /**
     * Print a list of images with annotations of unaccepted Aphia records.
     */
    public function images_species_unaccepted() {
        global $db;

        try {
            $sth = $db->dbh->prepare("SELECT i.img_dir,
                    i.file_name,
                    i.event_id,
                    to_char(i.timestamp, 'DD Mon YYYY, HH24:MI:SS') AS date_taken
                FROM vectors v
                    INNER JOIN image_info i ON i.id = v.image_info_id
                    INNER JOIN species s ON s.aphia_id = v.aphia_id
                WHERE s.status = 'unaccepted';");
            $sth->execute();
        }
        catch (Exception $e) {
            throw new Exception( $e->getMessage() );
        }

        $this->set_table_heads($sth);
        $body = $this->build_tbody($sth);
        $this->build($body);
    }

    /**
     * Print the overall coverage per species.
     *
     * This is the coverage based on all annotated images. Only images for
     * which the annotation status is set to "complete" are included in the
     * calculation.
     */
    public function species_coverage_overall() {
        global $db;

        $tags_image_unusable = "'".implode("','", $db->tags_image_unusable)."'";

        try {
            $sth = $db->dbh->prepare("SELECT sum(i.img_area)
                FROM image_info i
                    INNER JOIN image_annotation_status a ON a.image_info_id = i.id
                -- Images marked as 'complete' are fully reviewed and
                -- annotated. Only for these images can be said that a species
                -- is not present on the image if no vectors are set.
                WHERE a.annotation_status = 'complete'
                    AND NOT EXISTS (SELECT 1 FROM image_tags WHERE image_info_id = a.image_info_id AND image_tag IN ({$tags_image_unusable}))");
            $sth->execute();
        }
        catch (Exception $e) {
            throw new Exception( $e->getMessage() );
        }
        $row = $sth->fetch();
        $total_surface = $row ? $row[0] : 0;

        try {
            $sth = $db->dbh->prepare("SELECT s.aphia_id,
                    s.scientific_name,
                    COUNT(a.image_info_id) AS image_count,
                    SUM(a.vector_count) AS vector_count,
                    SUM(a.species_area) AS species_area,
                    (1.0 * :total_surface) AS surface_area,
                    SUM(a.species_area) / :total_surface * 100 AS species_cover_percent
                FROM areas_image_grouped a
                    INNER JOIN species s ON s.aphia_id = a.aphia_id
                    INNER JOIN image_info i ON i.id = a.image_info_id
                    INNER JOIN image_annotation_status y ON y.image_info_id = i.id
                -- Images marked as 'complete' are fully reviewed and
                -- annotated. Only for these images can be said that a species
                -- is not present on the image if no vectors are set.
                WHERE y.annotation_status = 'complete'
                    AND NOT EXISTS (SELECT 1 FROM image_tags WHERE image_info_id = a.image_info_id AND image_tag IN ({$tags_image_unusable}))
                GROUP BY s.aphia_id;");
            $sth->bindParam(":total_surface", $total_surface, PDO::PARAM_STR);
            $sth->execute();
        }
        catch (Exception $e) {
            throw new Exception( $e->getMessage() );
        }

        $this->set_table_heads($sth);
        $body = $this->build_tbody($sth);
        $this->build($body);
    }

    /**
     * Print the coverage for each species based on images where the species
     * was found.
     */
    public function species_coverage_where_present() {
        global $db;

        $tags_image_unusable = "'".implode("','", $db->tags_image_unusable)."'";

        try {
            $sth = $db->dbh->prepare("SELECT s.aphia_id,
                    s.scientific_name,
                    COUNT(a.image_info_id) AS image_count,
                    SUM(a.vector_count) AS vector_count,
                    SUM(a.species_area) AS species_area,
                    AVG(a.species_area) AS avg_species_area,
                    SUM(a.image_area) AS surface_area,
                    SUM(a.species_area) / SUM(a.image_area) * 100 AS species_cover_percent
                FROM areas_image_grouped a
                    INNER JOIN species s ON s.aphia_id = a.aphia_id
                    --LEFT JOIN image_tags t ON t.image_info_id = a.image_info_id
                --WHERE t.image_tag NOT IN ({$tags_image_unusable})
                -- Unlike the commented INNER JOIN method, this method
                -- does not cause duplicate rows.
                WHERE NOT EXISTS (SELECT 1 FROM image_tags WHERE image_info_id = a.image_info_id AND image_tag IN ({$tags_image_unusable}))
                GROUP BY s.aphia_id;");
            $sth->execute();
        }
        catch (Exception $e) {
            throw new Exception( $e->getMessage() );
        }

        $this->set_table_heads($sth);
        $body = $this->build_tbody($sth);
        $this->build($body);
    }
}
