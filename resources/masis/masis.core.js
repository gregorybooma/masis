var zoom = 3; // Default number of zoom levels
var map;
var vectorLayer;
var imageLayer;
var imageObject;
var selectedFeature;
var controls;

function ImageInfo() {
    // Properties
    this.name;
    this.dir;
    this.path;
    this.url;
    this.width;
    this.height;
    this.altitude;
    this.depth;
    this.area;
    this.area_per_pixel;

    // Methods
    this.get_width = function () {
        return this.area_per_pixel * this.width;
    };
    this.get_height = function () {
        return this.area_per_pixel * this.height;
    };
}

$(document).ready(function() {
    // Initialize the interface.
    initInterface();

    // Initialize the workspace.
    initWorkspace();

    // Load the directory tree.
    onLoadDirTree();
});

function initInterface() {
    // Set the tabs widget.
    $( "#tabs" ).tabs({
        select: function(event, ui) {
            // Update the contents of the Statistics tab whenever it's selected
            if ( ui.panel.getAttribute('id') == 'tab-statistics' ) {
                onLoadTableSpeciesCoverageOverall();
            }
        }
    });

    // Style buttons with jQuery UI.
    $( "input:submit, button").button();

    // Make sidebar elements toggleable.
    /*
	$('#sidebar-left h3').click(function() {
		$(this).next().toggle('blind');
		return false;
	}).next().hide();
    */
    $("#sidebar-left").accordion({ header: "h1", active: 3, fillSpace: false });

    // Make the map element resizable.
    $( "#map" ).resizable({
        stop: function(event, ui) {
                // Render the map in the resized map element to maintain
                // restriction of movement outside image borders.
                map.render('map');
            }
        });

    // Transform feature controls into a jQuery UI button set.
    $("#feature-controls").buttonset();
    $("#regular-polygon-controls").buttonset();
    //$("#image-annotation-status").buttonset();

    // Disable feature controls.
    $("#feature-controls input:radio").button("disable");

    // Set button actions.
    $("#action-commit").click(function() {
        onCommit();
    });
    $("#action-list-vectors").click(function() {
        onLoadVectorsTable();
    });
    $("#action-set-db-areas").click(function() {
        onSetDatabaseAreas();
    });
    $("input:radio[name=annotation-status]").change(function() {
        onSetImageAnnotationStatus(this);
    });

    // Initialize dialogs.
    $( "#dialog-remove-selection" ).dialog({
        autoOpen: false,
        resizable: false,
        modal: true
    });
    $( "#dialog-selections-save-success" ).dialog({
        autoOpen: false,
        resizable: false,
        modal: true,
        buttons: {
            Ok: function() {
                $(this).dialog("close");
            }
        }
    });
    $( "#dialog-unknown-error" ).dialog({
        autoOpen: false,
        resizable: false,
        modal: true,
        buttons: {
            Ok: function() {
                $(this).dialog("close");
            }
        }
    });
}

function initWorkspace() {
    // Create a map.
    map = new OpenLayers.Map('map', {
        units: 'dd', // Set units to decimal degrees (but we treat them as pixels)
        theme: null,
        displayProjection: 'EPSG:90091'
        }
    );

    // Create a vector layer.
    vectorLayer = new OpenLayers.Layer.Vector( "Selections" );

    // Add layers to the map.
    map.addLayers([vectorLayer]);

    // Add a layer switcher to the map.
    var container = document.getElementById("olControlLayerSwitcher");
    map.addControl(new OpenLayers.Control.LayerSwitcher({div: container}));

    // Set stroke width.
    OpenLayers.Feature.Vector.style['default']['strokeWidth'] = '2';

    // Set controls.
    controls = {
        polygon: new OpenLayers.Control.DrawFeature(vectorLayer, OpenLayers.Handler.Polygon),
        regular_polygon: new OpenLayers.Control.DrawFeature(vectorLayer, OpenLayers.Handler.RegularPolygon, {irregular: true}),
        modify: new OpenLayers.Control.ModifyFeature(vectorLayer),
        drag: new OpenLayers.Control.DragFeature(vectorLayer),
        annotate: new OpenLayers.Control.SelectFeature(vectorLayer,
            {onSelect: onFeatureSelect, onUnselect: onFeatureUnselect}),
        remove: new OpenLayers.Control.SelectFeature(vectorLayer,
            {onSelect: onFeatureRemove})
    };
    for(var key in controls) {
        map.addControl(controls[key]);
    }
}

function onLoadDirTree() {
    $('#dir-tree').fileTree(
        {
        root: config.image_path,
        script: 'load.php?do=get_file_list_html',
        folderEvent: 'click',
        expandSpeed: 750,
        collapseSpeed: 750,
        multiFolder: false
        },
        function(file) {
            setImage(file);
        }
    );
}

function onSetDatabaseAreas() {
    $.ajax({
        type: "GET",
        url: "fetch.php?do=set_areas",
        dataType: "json",
        success: function(data) {
            if (data.result == 'success') {
                alert("All areas have been set in the database (" + data.count + " records were updated).");
            }
            else {
                $("#dialog-unknown-error").dialog('open');
            }
        }
    });
}

function onSetImageAnnotationStatus(element) {
    $.ajax({
        type: "GET",
        url: "fetch.php?do=set_annotation_status",
        dataType: "json",
        data: {image_id: imageObject.id, status: element.value},
        success: function(data) {
            if (data.result != 'success') {
                $("#dialog-unknown-error").dialog('open');
            }
        }
    });
}

function onLoadTableSpeciesCoverageOverall() {
    $.ajax({
        type: "GET",
        url: "load.php?do=table_species_coverage_overall",
        dataType: "html",
        success: function(table) {
            $('#species-coverage-overall').html(table);
            $('#species-coverage-overall table').dataTable({
                "bJQueryUI" : true, // Enable jQuery UI ThemeRoller support
                "bSort" : true, // Enable sorting
                "bFilter" : true, // Enable search box
                "bLengthChange" : false
            });

            // Load the next table.
            onLoadTableSpeciesCoverageWherePresent();
        }
    });
}

function onLoadTableSpeciesCoverageWherePresent() {
    $.ajax({
        type: "GET",
        url: "load.php?do=table_species_coverage_where_present",
        dataType: "html",
        success: function(table) {
            $('#species-coverage-where-present').html(table);
            $('#species-coverage-where-present table').dataTable({
                "bJQueryUI" : true, // Enable jQuery UI ThemeRoller support
                "bSort" : true, // Enable sorting
                "bFilter" : true, // Enable search box
                "bLengthChange" : false
            });
        }
    });
}

function onLoadVectorsTable() {
    if (!imageObject) return;
    $.ajax({
        type: "GET",
        url: "load.php?do=table_image_vectors",
        dataType: "html",
        data: {image_id: imageObject.id},
        success: function(table) {
            $('#vectors-list').html(table);
            $('#vectors-list table').dataTable({
                "bJQueryUI" : true, // Enable jQuery UI ThemeRoller support
                "bSort" : false, // Disable sorting
                "bFilter" : false, // Disable search box
                "bLengthChange" : false
            });
        }
    });
}

function onCommit() {
    if (!imageObject) return;
    var vectors = {};
    for (f in vectorLayer.features) {
        var feature = vectorLayer.features[f];
        var area_pixels = parseInt(feature.geometry.getArea());
        vectors[f] = {
            id: feature.id,
            image_id: imageObject.id,
            image_name: imageObject.name,
            image_dir: imageObject.dir,
            area_pixels: area_pixels,
            area_m2: area_pixels * imageObject.area_per_pixel,
            vector_wkt: feature.geometry.toString(),
            species_id: feature.species_id,
            species_name: feature.species_name,
            };
    }
    // Save features to the database.
    $.ajax({
        type: "POST",
        url: "fetch.php?do=save_vectors",
        dataType: "json",
        data: vectors,
        success: function(data) {
            if (data.result == 'success') {
                $("#dialog-selections-save-success").dialog('open');
            }
            else {
                $("#dialog-unknown-error").dialog('open');
            }
        }
    });
}

function onFeatureRemove(feature) {
    var checked = document.getElementById("removePolygon").checked;
    if (checked) {
        // Set button options for the dialog.
        $("#dialog-remove-selection").dialog("option", 'buttons', {
                "Delete selection": function() {
                    $( this ).dialog( "close" );

                    // Delete the vector from the database.
                    $.ajax({
                        type: "GET",
                        url: "fetch.php?do=delete_vector",
                        dataType: "json",
                        data: {image_id: imageObject.id, vector_id: feature.id},
                        success: function(data) {
                            if (data.result == 'success') {
                                // Destroy the vector object.
                                feature.destroy();
                            }
                            else {
                                $("#dialog-unknown-error").dialog('open');
                            }
                        }
                    });
                },
                Cancel: function() {
                    $( this ).dialog( "close" );
                }
            });
        // Open the dialog.
        $("#dialog-remove-selection").dialog('open');
    }
}

function onFeatureSelect(feature) {
    selectedFeature = feature;
    $('#workspace-buttons ul').append( $('<li></li>')
        .attr('id', "assign-species")
        .text("Assign species: ") );

    $('#assign-species').append( $('<input />')
        .attr('name', "assign-species")
        .attr('id', "select-species")
        .attr('placeholder', "Enter species name...") );

    $('#select-species').autocomplete({
        source: "load.php?do=get_species",
        create: function(event, ui) {
            // Replace the text field value if the selected feature is already
            // assigned to a species.
            if (selectedFeature.species_id && selectedFeature.species_name) {
                $("#select-species").val(selectedFeature.species_name);
            }
        },
        select: function(event, ui) {
            // The default action of select is to replace the text field's
            // value with the value of the selected item. This is not desired.
            event.preventDefault();
            // Set the species ID and name for the selected feature.
            selectedFeature.species_id = parseInt(ui.item.value);
            selectedFeature.species_name = ui.item.label;
            // Replace the text field value with the label.
            $("#select-species").val(ui.item.label);
        }
    });

    /*
    loadSelectList($('#select-species'),
        'load.php?do=get_species',
        function() {
            // Preselect the correct option if the selected feature is already
            // assigned to a species.
            if (selectedFeature.species_id) {
                $("#select-species").val(selectedFeature.species_id);
            }
        }
    );

    // Set the species ID and name for the selected feature.
    $("#select-species").change(function() {
        selectedFeature.species_id = $(this).val();
        selectedFeature.species_name = $("#select-species option:selected").text();
    });
    */
}

// Remove the assign species input field when a vector is unselected.
function onFeatureUnselect(feature) {
    $('#assign-species').remove();
}

// Load vectors from a vectors object to the vector layer.
function onLoadVectors(vectors) {
    var Feature = OpenLayers.Feature.Vector;
    var Geometry = OpenLayers.Geometry;
    var features = [];
    for (i in vectors) {
        var vector = vectors[i];
        features[i] = new Feature(Geometry.fromWKT(vector.vector_wkt));
        features[i].id = vector.vector_id;
        features[i].species_id = vector.species_id;
        features[i].species_name = vector.species_name;
    }
    vectorLayer.addFeatures(features);
}

/*** Other functions ***/

// Activate selected control.
function toggleControl(element) {
    toggleContextControl(element);
    for (key in controls) {
        var control = controls[key];
        if (element.value == key && element.checked) {
            control.activate();
            if (key == 'modify')  setModifyMode();
        }
        else {
            control.deactivate();
        }
    }
}

// Hide or show context controls.
function toggleContextControl(element) {
    if (element.getAttribute('id') == 'regularSelectToggle' && element.checked) {
        // Show the controls for drawing regular polygons.
        $('#regular-polygon-controls').show();
        setRegularPolygonOptions({sides: 4});
        $('#polygonSquare').attr('checked', true);
        $("#regular-polygon-controls input:radio").button("refresh");
    }
    else {
        // Hide the controls for drawing regular polygons.
        $('#regular-polygon-controls').hide();
    }
}

// Set the options for drawing regular polygons.
function setRegularPolygonOptions(options) {
    options.irregular = true; // Draw irregular polygons by default.
    controls.regular_polygon.handler.setOptions(options);
}

// Set the modify feature for modifying polygons.
function setModifyMode() {
    var transform = document.getElementById("transformToggle").checked;
    var rotate = document.getElementById("rotateToggle").checked;
    var resize = document.getElementById("resizeToggle").checked;

    if (transform) {
        controls.modify.mode = OpenLayers.Control.ModifyFeature.RESHAPE;
        controls.modify.createVertices = true;
    }
    else if (rotate) {
        controls.modify.mode = OpenLayers.Control.ModifyFeature.ROTATE;
    }
    else if(resize) {
        controls.modify.mode = OpenLayers.Control.ModifyFeature.RESIZE;
    }
}

// Set and load image from path.
function setImage(path) {
    $.ajax({
        type: "GET",
        url: "load.php?do=get_image_info",
        dataType: "json",
        data: {path: path},
        success: function(info) {
            // Set image object.
            setImageObject(info);
            // Load new image.
            loadImage(imageObject);
            // Update image info on page.
            updatePageImageInfo(imageObject);
        }
    });
}

// Set image object from imago info object.
function setImageObject(info) {
    var to_float = ['altitude','area','area_per_pixel','depth'];

    // Set new image object.
    imageObject = new ImageInfo();

    // Copy all info attributes to the image object.
    for (key in info) {
        var val = info[key];
        if ( $.inArray(key, to_float) != -1 ) {
            val = parseFloat(val);
        }
        imageObject[key] = val;
    }
}

// Load new image into the workspace.
function loadImage(img) {
    // Check the image object.
    if (! img instanceof ImageInfo) {
        alert("Error: Expected ImageInfo object, got something else.");
        return;
    }
    if (img.width == undefined || img.height == undefined) {
        alert("Error: This file type is unsupported: " + img.name);
        return;
    }
    if (img.area_per_pixel == undefined) {
        alert("Error: The image area could not be determined.");
        return;
    }

    // Dispose of old image layer.
    if ($.inArray(imageLayer, map.layers) != -1) {
        map.removeLayer(imageLayer);
    }

    // Create new image layer.
    imageLayer = new OpenLayers.Layer.Image(
        img.name,
        img.url,
        new OpenLayers.Bounds(0, 0, img.width, img.height),
        new OpenLayers.Size(img.width, img.height),
        {numZoomLevels: zoom}
    );

    // Set the new image layer.
    map.addLayer(imageLayer);
    map.setBaseLayer(imageLayer);

    // Reset zoom.
    map.zoomToMaxExtent();

    // Restrict movement outside image borders.
    map.setOptions({restrictedExtent: new OpenLayers.Bounds(0, 0, img.width, img.height)});

    // Center the view to the top left corner of the image.
    map.setCenter(new OpenLayers.LonLat(0, img.height));

    // Remove all features from the vector layer.
    vectorLayer.removeAllFeatures();

    // Set max width and height of the resizable map element.
    $( "#map" ).resizable('option', 'maxHeight', img.height);
    $( "#map" ).resizable('option', 'maxWidth', img.width);

    // Enable the control buttons.
    $("#feature-controls input:radio").button("enable");

    // Check the default control button.
    var e = document.getElementById("navigateToggle");
    e.checked = true;

    // Refresh the buttons because the element's checked state is changed
    // programatically.
    $("#feature-controls input:radio").button( "refresh" );

    // Activate the right control.
    toggleControl(e);

    // Reset other page elements (e.g. assign species input field).
    onFeatureUnselect();

    // Load existing vectors from the database.
    $.ajax({
        type: "GET",
        url: "load.php?do=get_vectors",
        dataType: "json",
        data: {image_id: img.id},
        success: function(vectors) {
            onLoadVectors(vectors);
        }
    });

    // Set the image annotation status input to the right value.
    var $radios = $('input:radio[name=annotation-status]');
    if( img.annotation_status ) {
        $radios.filter('[value=' + img.annotation_status + ']').attr('checked', true);
    }
    else {
        $radios.filter('[value=incomplete]').attr('checked', true);
    }
}

function updatePageImageInfo(img) {
    $('#image-info').empty();
    $('#image-info').append("<dl></dl>");
    $('#image-info dl').append("<dt>File:</dt><dd>" + img.url + "</dd>");
    $('#image-info dl').append("<dt>Depth:</dt><dd>" + img.depth + " m</dd>");
    $('#image-info dl').append("<dt>Altitude:</dt><dd>" + roundNumber(img.altitude, 2) + " m</dd>");
    $('#image-info dl').append("<dt>Area:</dt><dd>" + roundNumber(img.area, 2) + " m<sup>2</sup></dd>");
}

// Functions for testing purposes.
function test1() {
    bounds = new OpenLayers.Bounds(0, imageObject.height-100, 100, imageObject.height);
    box = new OpenLayers.Feature.Vector(bounds.toGeometry());
    vectorLayer.addFeatures(box);
}
