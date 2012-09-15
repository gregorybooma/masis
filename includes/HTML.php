<?php

/**
 * The HTML class for generating HTML.
 *
 * Requires that config.php is imported.
 */
class HTML {

    public function get_file_list($dir) {
        global $config, $db;

        if ( file_exists($dir) ) {
            $files = scandir($dir);
            natcasesort($files);
            if ( count($files) > 2 ) { /* The 2 accounts for . and .. */
                echo "<ul class=\"jqueryFileTree\" style=\"display: none;\">";
                // List dirs
                foreach( $files as $file ) {
                    if ( file_exists($dir . $file) && $file != '.' && $file != '..' && is_dir($dir . $file) ) {
                        echo "<li class=\"directory collapsed\"><a href=\"#\" rel=\"" . htmlentities($dir . $file) . "/\">{$file}</a></li>";
                    }
                }
                // List files
                // File names for current folder are obtained from the database
                // because only files also represented in the database should
                // be listed.
                $stack = explode('/', trim($dir, '/'));
                $img_dir = array_pop($stack);
                $ext_pattern = '/\.[a-z]+$/';
                $sth = $db->get_files_for_dir($img_dir);
                while ( $row = $sth->fetch(PDO::FETCH_ASSOC) ) {
                    // Many entries in the database are .ppm files which are
                    // not supported. So replace the extension .ppm by common
                    // and supported file type extensions and look for these
                    // files instead.
                    $file = array();
                    $file[] = preg_replace($ext_pattern, '.jpeg', $row['file_name']);
                    $file[] = preg_replace($ext_pattern, '.jpg', $row['file_name']);
                    $file[] = preg_replace($ext_pattern, '.png', $row['file_name']);
                    foreach ($file as $filename) {
                        if ( file_exists($dir . $filename) ) {
                            // Set the file extension.
                            $ext = preg_replace('/^.*\./', '', $filename);
                            // Set the indicator icons.
                            $indicators = "";
                            $indicators .= $row['n_vectors'] > 0 ? "<span class='vector-count' title='{$row['n_vectors']} selection(s)'>{$row['n_vectors']}</span>" : "";
                            $indicators .= $row['substrate_annotated'] ? "<span class='icon substrate-annotated' title='Substrate is annotated'></span>" : "";
                            $indicators .= in_array($row['annotation_status'], array('complete','review')) ? "<span class='icon annotation-{$row['annotation_status']}' title='Annotation status: {$row['annotation_status']}'></span>" : "";

                            echo "<li class=\"file ext_{$ext}\"><a href=\"#\" rel=\"" . htmlentities($dir . $filename) . "\">{$filename}</a><span class='indicators'>{$indicators}</span></li>";
                            break;
                        }
                    }
                }
                echo "</ul>";
            }
        }
    }
}

