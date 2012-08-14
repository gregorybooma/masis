<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>MaSIS</title>
    <!-- stylesheets: -->
    <link rel="stylesheet" href="resources/openlayers/theme/default/style.css" type="text/css" />
    <link rel="stylesheet" href="resources/jquery.filetree/jquery.filetree.css" type="text/css" />
    <link rel="stylesheet" href="resources/jquery.ui/themes/benthic/jquery-ui.css" type="text/css" />
    <link rel="stylesheet" href="styles/main.css" type="text/css" />
    <!-- javascripts: -->
    <script src="resources/jquery/jquery.min.js" type="text/javascript"></script>
    <script src="resources/jquery.ui/jquery-ui.min.js" type="text/javascript"></script>
    <script src="resources/jquery.filetree/jquery.filetree.js" type="text/javascript"></script>
    <script src="resources/openlayers/lib/OpenLayers.js" type="text/javascript"></script>
    <script src="resources/masis/masis.config.js.php" type="text/javascript"></script>
    <script src="resources/masis/masis.std.js" type="text/javascript"></script>
    <script src="resources/masis/masis.core.js" type="text/javascript"></script>
  </head>
  <body>
    <div id="content-wrapper" class="clearfix">
        <!-- sidebar-left -->
        <div id="sidebar-left">
            <!-- controls -->
            <div>
                <h3><a href="#">Controls</a></h3>
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
                    <input type="radio" name="control" value="modify" id="dragToggle" onclick="toggleControl(this);" />
                    <label for="dragToggle">Drag</label>
                    <input type="radio" name="control" value="remove" id="removePolygon" onclick="toggleControl(this);" />
                    <label for="removePolygon">Delete</label>
                </div>
            </div>
            <!-- end controls -->

            <!-- layer switcher -->
            <div>
                <h3><a href="#">Layers</a></h3>
                <div id="olControlLayerSwitcher"></div>
            </div>
            <!-- end layer switcher -->

            <!-- directory tree -->
            <div>
                <h3><a href="#">Photo Library</a></h3>
                <div id="dir-tree"></div>
            </div>
            <!-- end directory tree -->
        </div>
        <!-- end sidebar-left -->

        <div id="content">
            <!-- buttons -->
            <div id="action-buttons">
                <ul>
                    <li><button id="action-commit">Commit</button></li>
                </ul>
            </div>
            <!-- end buttons -->

            <!-- map -->
            <div id="map"></div>
            <!-- end map -->

            <!-- image info -->
            <div id="image-info">
                <ul>
                    <li id="image-altitude"></li>
                    <li id="image-area"></li>
                </ul>
            </div>
            <!-- end image info -->

            <!-- temporary -->
            <pre id="polygons"></pre>
            <!-- end temporary -->
        </div> <!-- end content -->

    </div> <!-- end content-wrapper -->

    <!-- dialogs -->
    <div id="dialog-remove-selection" title="Remove selection?">
        <p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>The selection will be permanently deleted. Are you sure?</p>
    </div>
    <div id="dialog-selections-save-success" title="Selections saved">
        <p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 20px 0;"></span>The selections have been saved successfully.</p>
    </div>
    <div id="dialog-unknown-error" title="Error">
        <p><span class="ui-icon ui-icon-circle-close" style="float:left; margin:0 7px 20px 0;"></span>An unknown error has occured. Please contact the website administrator about this problem.</p>
    </div>
    <!-- end dialogs -->

  </body>
</html>
