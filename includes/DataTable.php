<?php

/**
 * The DataTable class for generating HTML tables.
 */
class DataTable {
    /**
     * Set table heads from the query output.
     *
     * @uses array $this->tableHeads Column names for the query output.
     * @uses array $this->result Query output.
     */
    private function set_table_heads($sth) {
        $this->tableHeads = array();
        $column_names = array(
            'vector_id' => "Vector",
            'species_id' => "Species ID",
            'name_latin' => "Species (Latin)",
            'name_venacular' => "Species (venacular)",
            'area_m2' => "Area (m<sup>2</sup>)",
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
     * @param string $body The HTML <tbody> element for the table.
     * @param string $properties Optional additional properties for the <table> tag.
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
     * Prints the HTML <thead> element for a table.
     *
     * The <thead> element contains the column names set in $this->tableHeads.
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
     * Prints the HTML <tfoot> element for a table.
     *
     * The <tfoot> element contains the column names set in $this->tableHeads.
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
     * @return string A HTML <tbody> element.
     */
    public function build_tbody_simple($sth, $class="") {
        $tbody = "<tbody>";
         while ( $row = $sth->fetch(PDO::FETCH_ASSOC) ) {
            $tbody .= "<tr class='{$class}'>\n";
            foreach ($row as $col_value) {
                $tbody .= "<td align='center'>{$col_value}</td>\n";
            }
            $tbody .= "</tr>\n";
        }
        $tbody .= "</tbody>";
        return $tbody;
    }

    public function list_image_vectors($image_id) {
        global $db;

        try {
            $sth = $db->dbh->prepare("SELECT v.vector_id,
                v.area_m2,
                s.name_latin,
                s.name_venacular
            FROM vectors v
                LEFT OUTER JOIN species s ON v.species_id = s.id
            WHERE v.image_info_id = :image_id;");
            $sth->bindParam(":image_id", $image_id, PDO::PARAM_INT);
            $sth->execute();
        }
        catch (Exception $e) {
            throw new Exception( $e->getMessage() );
        }

        $this->set_table_heads($sth);
        $body = $this->build_tbody_simple($sth);
        $this->build($body, "", array('header'));
    }
}

