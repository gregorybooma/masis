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
    <script src="resources/masis/masis.config.js.php" type="text/javascript"></script>
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
                            <label for="selectToggle">Draw Polygon</label>
                            <input type="radio" name="control" value="regular_polygon" id="regularSelectToggle" onclick="toggleControl(this);" />
                            <label for="regularSelectToggle">Draw Regular Polygon</label>
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

                <!-- image info -->
                <div>
                    <h1><a href="#">Image Information</a></h1>
                    <div>
                        <div id="image-info"></div>

                        <h3>Annotation status:</h3>
                        <div id="image-annotation-status">
                            <input type="radio" name="annotation-status" value="incomplete" id="annotation-status-incomplete" checked="checked" />
                            <label for="annotation-status-incomplete">Incomplete</label><br/>
                            <input type="radio" name="annotation-status" value="review" id="annotation-status-review" />
                            <label for="annotation-status-review">Needs Review</label><br/>
                            <input type="radio" name="annotation-status" value="complete" id="annotation-status-complete" />
                            <label for="annotation-status-complete">Complete</label><br/>
                        </div>
                    </div>
                </div>
                <!-- end image info -->

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
                        <li><a href="#" onclick="onCommit(); return false;" class="button">Save Selections</a></li>
                        <li><a href="#" onclick="onAnnotate(); return false;" class="button">Annotate Image</a></li>
                    </ul>
                </div>
                <!-- end buttons -->

                <!-- map -->
                <div id="map"></div>
                <!-- end map -->

                <div id="context-controls">
                    <div id="regular-polygon-controls">
                        <input type="radio" name="sides" value="4" id="polygonSquare" onchange="setRegularPolygonOptions({sides: parseInt(this.value)})" />
                        <label for="polygonSquare">Square</label>
                        <input type="radio" name="sides" value="5" id="polygonPentagon" checked="checked" onchange="setRegularPolygonOptions({sides: parseInt(this.value)})" />
                        <label for="polygonPentagon">Pentagon</label>
                        <input type="radio" name="sides" value="6" id="polygonHexagon" onchange="setRegularPolygonOptions({sides: parseInt(this.value)})" />
                        <label for="polygonHexagon">Hexagon</label>
                        <input type="radio" name="sides" value="40" id="polygonCircle" onchange="setRegularPolygonOptions({sides: parseInt(this.value)})" />
                        <label for="polygonCircle">Circle</label>
                    </div>
                </div>

            </div> <!-- end content -->

        </div> <!-- end tab workspace -->

        <!-- tab statistics -->
        <div id="tab-statistics">
            <!-- Species statistics -->
            <h1>Species coverage</h1>

            <h2>Overall coverage</h2>
            <p>Coverage based on all annotated images. Only images for which
            the annotation status is set to "Complete" are included in the
            calculation.</p>
            <div id="species-coverage-overall">Loading...</div>

            <h2>Coverage where present</h2>
            <p>Coverage based on images where the species was found.</p>
            <div id="species-coverage-where-present">Loading...</div>
        </div> <!-- end tab statistics -->

    </div> <!-- end tabs -->
    </div> <!-- end content-wrapper -->

    <!-- dialogs -->
    <div class="hidden">
        <div id="dialog-on-commit" title="Commit changes?">
            <p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>You are about to save all changes. Are you sure?</p>
        </div>
        <div id="dialog-remove-selection" title="Remove selection?">
            <p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>The selection will be permanently deleted. Are you sure?</p>
        </div>
        <div id="dialog-selections-save-success" title="Selections saved">
            <p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 20px 0;"></span>All selections have been saved.</p>
        </div>
        <div id="dialog-unknown-error" title="Error">
            <p><span class="ui-icon ui-icon-circle-close" style="float:left; margin:0 7px 20px 0;"></span>An unknown error has occured. Please contact the website administrator about this problem.</p>
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
        <div id="dialog-annotate" title="Annotate">
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
        </div>
    </div>
    <!-- end dialogs -->

  </body>
</html>
