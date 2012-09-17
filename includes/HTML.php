<?php

/**
 * The HTML class for generating HTML.
 */
class HTML {

    /**
     * Print the file list for a directory.
     *
     * @param string $base_path The base path. This is the directory path to
     *      the website root path and will be put in front of the images
     *      directory path $dir
     * @param string $dir The path to the image directory relative to the
     *      base path $base_path
     */
    public function get_file_list($base_path, $dir) {
        global $db;

        $html = "";
        if ( file_exists($base_path . $dir) ) {
            $files = scandir($base_path . $dir);
            natcasesort($files);
            if ( count($files) > 2 ) { /* The 2 accounts for . and .. */
                $html .= "<ul class=\"jqueryFileTree\" style=\"display: none;\">";
                // List dirs
                foreach( $files as $file ) {
                    if ( file_exists($base_path . $dir . $file) && $file != '.' && $file != '..' && is_dir($base_path . $dir . $file) ) {
                        $html .= "<li class=\"directory collapsed\"><a href=\"#\" rel=\"" . htmlentities($dir . $file) . "/\">{$file}</a></li>";
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
                        if ( file_exists($base_path . $dir . $filename) ) {
                            // Set the file extension.
                            $ext = preg_replace('/^.*\./', '', $filename);
                            // Set the image tags.
                            $tags = explode(',', $row['tags']);
                            // Set the indicator icons.
                            $indicators = "";
                            $indicators .= $row['n_vectors'] > 0 ? "<span class='vector-count' title='{$row['n_vectors']} selection(s)'>{$row['n_vectors']}</span>" : "";
                            $indicators .= $row['substrate_annotated'] ? "<span class='icon substrate-annotated' title='Substrate is annotated'></span>" : "";
                            $indicators .= $row['annotation_status'] == 'complete' ? "<span class='icon annotation-complete' title='Annotation complete'></span>" : "";
                            $indicators .= in_array('flag for review', $tags) ? "<span class='icon needs-review' title='Flagged for review'></span>" : "";
                            $indicators .= in_array('unusable', $tags) ? "<span class='icon unusable' title='Marked unusable'></span>" : "";
                            $indicators .= in_array('highlight', $tags) ? "<span class='icon highlight' title='Highlighted image'></span>" : "";

                            $html .= "<li class=\"file ext_{$ext}\"><a href=\"#\" rel=\"" . htmlentities($dir . $filename) . "\">{$filename}</a><span class='indicators'>{$indicators}</span></li>";
                            break;
                        }
                    }
                }
                $html .= "</ul>";
            }
        }
        return $html;
    }
}

