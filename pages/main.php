<?php
$user = $member->data();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>MaSIS &mdash; Marine Species Identification System</title>
    <!-- stylesheets: -->
    <link rel="stylesheet" href="resources/openlayers/theme/default/style.css" type="text/css" />
    <link rel="stylesheet" href="resources/jquery.filetree/jquery.filetree.css" type="text/css" />
    <link rel="stylesheet" href="resources/jquery.ui/themes/benthic/jquery-ui.css" type="text/css" />
    <link rel="stylesheet" href="resources/jquery.datatables/css/jquery.dataTables_themeroller.css" type="text/css" />
    <link rel="stylesheet" href="styles/main.css" type="text/css" />
    <!-- javascripts: -->
    <script src="resources/underscore/underscore-min.js" type="text/javascript"></script>
    <script src="resources/jquery/jquery.min.js" type="text/javascript"></script>
    <script src="resources/jquery.ui/jquery-ui.min.js" type="text/javascript"></script>
    <script src="resources/jquery.filetree/jquery.filetree.js" type="text/javascript"></script>
    <script src="resources/jquery.datatables/jquery.dataTables.min.js" type="text/javascript"></script>
    <script src="resources/openlayers/lib/OpenLayers.js" type="text/javascript"></script>
    <script src="resources/masis/masis.std.js" type="text/javascript"></script>
    <script src="resources/masis/masis.core.js" type="text/javascript"></script>
  </head>
  <body>
    <div id="content-wrapper">

    <div id="header" class="clearfix">
        <h1>MaSIS</h1>
        <div id="user">
            <div id="user-options">
                <ul>
                    <li>Hello, <?php print $user->first_name; ?></li>
                    <li><a href="?p=logout" class="button">Logout</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- tabs -->
    <div id="tabs">
        <ul>
            <li><a href="#tab-workspace">Workspace</a></li>
            <li><a href="#tab-manager">Manager</a></li>
            <li><a href="#tab-statistics">Statistics</a></li>
        </ul>

        <!-- tab workspace -->
        <div id="tab-workspace" class="clearfix">

            <!-- sidebar-left -->
            <div id="sidebar-left">
                <!-- controls -->
                <div>
                    <h1><a href="#">Controls</a></h1>
                    <div>
                        <div id="feature-controls" class="control-buttons">
                            <input type="radio" name="control" value="none" id="navigateToggle" onclick="toggleControl(this);" checked="checked" />
                            <label for="navigateToggle">Navigate</label>
                            <input type="radio" name="control" value="polygon" id="selectToggle" onclick="toggleControl(this);" />
                            <label for="selectToggle">Select</label>
                            <input type="radio" name="control" value="annotate" id="annotateToggle" onclick="toggleControl(this);" />
                            <label for="annotateToggle">Annotate</label>
                            <span class="section">Modify selections:</span>
                            <input type="radio" name="control" value="modify" id="transformToggle" onclick="toggleControl(this);" />
                            <label for="transformToggle">Transform</label>
                            <input type="radio" name="control" value="modify" id="rotateToggle" onclick="toggleControl(this);" />
                            <label for="rotateToggle">Rotate</label>
                            <input type="radio" name="control" value="modify" id="resizeToggle" onclick="toggleControl(this);" />
                            <label for="resizeToggle">Resize</label>
                            <input type="radio" name="control" value="drag" id="dragToggle" onclick="toggleControl(this);" />
                            <label for="dragToggle">Drag</label>
                            <input type="radio" name="control" value="remove" id="removePolygon" onclick="toggleControl(this);" />
                            <label for="removePolygon">Delete</label>
                        </div>
                    </div>
                </div>
                <!-- end controls -->

                <!-- layer switcher -->
                <div>
                    <h1><a href="#">Layers</a></h1>
                    <div id="olControlLayerSwitcher"></div>
                </div>
                <!-- end layer switcher -->

                <!-- photo library -->
                <div>
                    <h1><a href="#">Photo Library</a></h1>
                    <div id="photo-library"></div>
                </div>
                <!-- end photo library -->
            </div>
            <!-- end sidebar-left -->

            <div id="content">
                <!-- buttons -->
                <div id="workspace-buttons" class="action-buttons">
                    <ul>
                        <li><a href="#" onclick="onSaveSelections(); return false;" class="button">Save Selections</a></li>
                        <li><a href="#" onclick="onAnnotateImage(); return false;" class="button">Annotate Image</a></li>
                        <li><a href="#" onclick="onShowImageInformation(); return false;" class="button">Image Information</a></li>
                    </ul>
                </div>
                <!-- end buttons -->

                <!-- map -->
                <div id="map"></div>
                <!-- end map -->

                <div id="context-controls">
                    <div id="polygon-controls">
                        <input type="radio" name="polygon" value="custom" id="polygonCustom" checked="checked" onchange="setPolygonControl(this)" />
                        <label for="polygonCustom">Custom</label>
                        <input type="radio" name="polygon" value="square" id="polygonSquare" onchange="setPolygonControl(this)" />
                        <label for="polygonSquare">Square</label>
                        <input type="radio" name="polygon" value="pentagon" id="polygonPentagon" onchange="setPolygonControl(this)" />
                        <label for="polygonPentagon">Pentagon</label>
                        <input type="radio" name="polygon" value="hexagon" id="polygonHexagon" onchange="setPolygonControl(this)" />
                        <label for="polygonHexagon">Hexagon</label>
                        <input type="radio" name="polygon" value="circle" id="polygonCircle" onchange="setPolygonControl(this)" />
                        <label for="polygonCircle">Circle</label>
                    </div>
                </div>

            </div> <!-- end content -->

        </div>
        <!-- end tab workspace -->

        <!-- tab manager -->
        <div id="tab-manager">
            <h1>Manager</h1>

            <h2>Images with unassigned selections</h2>
            <p>Images with selections that are not assigned to a species.</p>
            <div id="images-unassigned-vectors">Loading...</div>

            <h2>Images flagged for review</h2>
            <p>Images that need to be reviewed.</p>
            <p>To mark an image as "reviewed", remove the
            <span class="tag">flag for review</span> tag and add the <span class="tag">reviewed</span>
            tag in the Annotate Image dialog.</p>
            <div id="images-need-review">Loading...</div>

            <h2>Highlighted Images</h2>
            <p>Images of special interest.</p>
            <div id="images-highlighted">Loading...</div>

            <h2>Images with unaccepted species</h2>
            <p>Images with annotations of unaccepted Aphia species records.</p>
            <div id="images-species-unaccepted">Loading...</div>
        </div>
        <!-- end tab manager -->

        <!-- tab statistics -->
        <div id="tab-statistics">
            <h1>Species coverage</h1>

            <h2>Export</h2>
            <p>The forms below allow you to export species coverage data to
            CSV data files. Only images for which the annotation status is set
            to "Complete" are included. Images tagged as
            <span class="tag">unusable</span> or similar are excluded.</p>

            <form action="load.php" method="get" target="_blank" id="export-coverage-two-species">
            <input type="hidden" name="do" value="export_coverage_two_species" />
            <fieldset>
                <legend>Species coverage per image</legend>
                <p>Export the species selections count and species
                coverage/m<sup>2</sup> for two species per image.</p>
                <p><label>Aphia ID Species A: <input type="text" name="aphia_id1" size="30" value="" placeholder="Enter species name..."></label></p>
                <p><label>Aphia ID Species B: <input type="text" name="aphia_id2" size="30" value="" placeholder="Enter species name..."></label></p>
                <p><label><input type="checkbox" name="and" value="1" /> Only include images where both species are present</label></p>
                <p><input type="submit" value="Export" class="button" /></p>
            </fieldset>
            </form>

            <h2>Overall coverage</h2>
            <p>Coverage based on all annotated images. Only images for which
            the annotation status is set to "Complete" are included in the
            calculation.</p>
            <div id="species-coverage-overall">Loading...</div>

            <h2>Coverage where present</h2>
            <p>Coverage based on images where the species was found.</p>
            <div id="species-coverage-where-present">Loading...</div>
        </div>
        <!-- end tab statistics -->

    </div> <!-- end tabs -->
    </div> <!-- end content-wrapper -->

    <!-- dialogs -->
    <div class="hidden">
        <div id="dialog-on-save-selections" title="Commit changes?">
            <p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>You are about to save all changes. Are you sure?</p>
        </div>

        <div id="dialog-remove-selection" title="Remove selection?">
            <p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>The selection will be permanently deleted. Are you sure?</p>
        </div>

        <div id="dialog-selections-save-success" title="Selections saved">
            <p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 20px 0;"></span>All selections have been saved.</p>
        </div>

        <div id="error-dialogs">
            <div id="dialog-unknown-error" title="Error">
                <p><span class="ui-icon ui-icon-circle-close" style="float:left; margin:0 7px 20px 0;"></span>An unknown error has occured. Please contact the website administrator about this problem.</p>
            </div>

            <div id="dialog-error-image-area-unknown" title="Error">
                <p><span class="ui-icon ui-icon-circle-close" style="float:left; margin:0 7px 20px 0;"></span>The area for this image is unknown. Cannot work with this image.</p>
            </div>
        </div>

        <div id="dialog-assign-species" title="Assign species">
            <p>To assign a species to the selection, enter a species name in the search
            field and wait for a list of matches to appear. Select a species from
            the list to assign the species.</p>
            <form>
            <fieldset>
                <legend>Select species</legend>
                <select name="searchpar" id="select-species-searchpar">
                    <option value="0" selected>Scientific Name</option>
                    <option value="1">Common Name</option>
                </select>
                <input type="text" name="assign-species" id="select-species" size="50" value="" placeholder="Enter species name...">
            </fieldset>
            <p>Assigned to: <span id="assign-species-label" class="text-italic"><a href="#">Unassigned</a></span></p>
            </form>
        </div>

        <div id="dialog-annotate-image" title="Annotate Image">
            <fieldset>
                <legend>Dominant Substrate</legend>
                <select id="select-dominant-substrate">
                    <option value="">Select substrate type...</option>
                </select>
                <a href="#" onclick="onAddCategory('select-dominant-substrate', 'dominant-substrates-list'); return false;" class="button">Add</a>
                <div class="category-editor">
                    <ul id="dominant-substrates-list"></ul>
                </div>
            </fieldset>

            <fieldset>
                <legend>Subdominant Substrate</legend>
                <select id="select-subdominant-substrate">
                    <option value="">Select substrate type...</option>
                </select>
                <a href="#" onclick="onAddCategory('select-subdominant-substrate', 'subdominant-substrates-list'); return false;" class="button">Add</a>
                <div class="category-editor">
                    <ul id="subdominant-substrates-list"></ul>
                </div>
            </fieldset>

            <fieldset>
                <legend>Image Tags</legend>
                <select id="select-image-tag">
                    <option value="">Select tag...</option>
                </select>
                <a href="#" onclick="onAddCategory('select-image-tag', 'image-tags-list'); return false;" class="button">Add</a>
                <div class="category-editor">
                    <ul id="image-tags-list"></ul>
                </div>
            </fieldset>

            <fieldset>
                <legend>Annotation status</legend>
                <div id="image-annotation-status">
                    <input type="radio" name="annotation-status" value="incomplete" id="annotation-status-incomplete" checked="checked" />
                    <label for="annotation-status-incomplete">Incomplete</label>
                    <input type="radio" name="annotation-status" value="complete" id="annotation-status-complete" />
                    <label for="annotation-status-complete">Complete</label>
                </div>
            </fieldset>
        </div>

        <div id="dialog-image-info" title="Image Information">
            <fieldset>
                <legend>Image Information</legend>
                <table id="image-info-file"></table>
            </fieldset>
            <fieldset>
                <legend>Event Information</legend>
                <table id="image-info-event"></table>
            </fieldset>
        </div>
    </div>
    <!-- end dialogs -->

  </body>
</html>
