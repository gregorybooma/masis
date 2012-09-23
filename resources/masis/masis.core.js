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
                onLoadTableSpeciesCoverageWherePresent();
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
    $("#sidebar-left").accordion({ header: "h1", active: 2, fillSpace: false });

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
    $("#image-annotation-status").buttonset();

    // Disable feature controls.
    $("#feature-controls input:radio").button("disable");

    // Set button actions.
    $("#select-species-searchpar").change(function() {
        $('#select-species').autocomplete(
            "option", "source", "load.php?do=get_species&searchpar=" + $("#select-species-searchpar option:selected").val()
        );
    });

    // Populate select menu's
    $.getJSON('load.php?do=get_substrate_types', function(data) {
        var html = '';
        var len = data.length;
        for (var i = 0; i< len; i++) {
            html += '<option value="' + data[i].value + '">' + data[i].label + '</option>\n';
        }
        $('select#select-dominant-substrate').append(html);
        $('select#select-subdominant-substrate').append(html);
    });
    $.getJSON('load.php?do=get_image_tag_types', function(data) {
        var html = '';
        var len = data.length;
        for (var i = 0; i< len; i++) {
            html += '<option value="' + data[i].value + '">' + data[i].label + '</option>\n';
        }
        $('select#select-image-tag').append(html);
    });

    // Initialize dialogs.
    $( "#dialog-on-commit" ).dialog({
        autoOpen: false,
        resizable: false,
        modal: true,
        buttons: {
            Yes: function() {
                $( this ).dialog( "close" );
                saveVectors();
            },
            Cancel: function() {
                $( this ).dialog( "close" );
            }
        }
    });
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
    $( "#error-dialogs div" ).dialog({
        autoOpen: false,
        resizable: false,
        modal: true,
        buttons: {
            Ok: function() {
                $(this).dialog("close");
            }
        }
    });
    $( "#dialog-assign-species" ).dialog({
        autoOpen: false,
        width: 400,
        modal: true,
        buttons: {
            Ok: function() {
                $(this).dialog("close");
            }
        },
        close: function() {
            $('#select-species').autocomplete("destroy");
        }
    });
    $( "#dialog-annotate" ).dialog({
        autoOpen: false,
        width: 400,
        modal: true,
        buttons: {
            Cancel: function() {
                $(this).dialog("close");
            },
            Save: function() {
                $(this).dialog("close");
                onSaveSubstrateAnnotations();
                onSaveImageTags();
                onSetImageAnnotationStatus();
            }
        },
        close: function() {
            // Clear the substrate input field.
            $('#select-substrate-type').val("");
            // Clear the substrate types list.
            $('#substrate-types-list').empty();
        }
    });
    $( "#dialog-image-info" ).dialog({
        autoOpen: false,
        width: 400,
        resizable: true,
        modal: false,
        buttons: {
            Close: function() {
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

    // Set vector styles.
    OpenLayers.Feature.Vector.style['default']['strokeWidth'] = 2;
    OpenLayers.Feature.Vector.style['default']['strokeOpacity'] = 1;
    OpenLayers.Feature.Vector.style['default']['strokeColor'] = "#EE9900";
    OpenLayers.Feature.Vector.style['default']['fillColor'] = "#EE9900";
    OpenLayers.Feature.Vector.style['default']['fillOpacity'] = 0.3;

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
    $('#photo-library').fileTree({
            root: '/data/',
            script: 'load.php?do=get_file_list',
            folderEvent: 'click',
            expandSpeed: 750,
            collapseSpeed: 750,
            multiFolder: false
        },
        function(file, e) {
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

function onSetImageAnnotationStatus() {
    if (!imageObject) return;
    var element = $("input:radio[name=annotation-status]:checked");
    $.ajax({
        type: "GET",
        url: "fetch.php?do=set_annotation_status",
        dataType: "json",
        data: {image_id: imageObject.id, status: element.val()},
        success: function(data) {
            if (data.result != 'success') {
                $("#dialog-unknown-error").dialog('open');
            }
            else {
                // Update the imageObject annotation status.
                imageObject.annotation_status = element.val();
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
    $("#dialog-on-commit").dialog('open');
}

function saveVectors() {
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

    $('#select-species').attr('value', "");
    $('#select-species').autocomplete({
        delay: 2000,
        source: "load.php?do=get_species",
        create: function(event, ui) {
            // Replace the text field value if the selected feature is already
            // assigned to a species.
            if (selectedFeature.species_id && selectedFeature.species_name) {
                $("#select-species").val(selectedFeature.species_name);
            }
        },
        select: function(event, ui) {
            var id = ui.item.value;
            var name = ui.item.label;
            // The default action of select is to replace the text field's
            // value with the value of the selected item. This is not desired.
            event.preventDefault();
            // Set the species ID and name for the selected feature.
            selectedFeature.species_id = id;
            selectedFeature.species_name = name;
            // Replace the text field value with the label.
            $("#select-species").val(name);
            // Update dialog label.
            if ( id ) {
                $('#assign-species-label a').attr('href', "http://www.marinespecies.org/aphia.php?p=taxdetails&id=" + id);
                $('#assign-species-label a').text(name);
            }
            else {
                $('#assign-species-label a').attr('href', "#");
                $('#assign-species-label a').text("Unassigned");
            }
        }
    });
    // Update dialog label.
    if ( feature.species_id ) {
        $('#assign-species-label a').attr('href', "http://www.marinespecies.org/aphia.php?p=taxdetails&id=" + feature.species_id);
        $('#assign-species-label a').text(feature.species_name);
    }
    else {
        $('#assign-species-label a').attr('href', "#");
        $('#assign-species-label a').text("Unassigned");
    }
    // Reset the search parameter (sets it to "Scientific Name").
     $("#select-species-searchpar").val(0);
    // Open dialog.
    $("#dialog-assign-species").dialog('open');

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

/**
 * Remove the assign-species input field.
 */
function onFeatureUnselect(feature) {
    $('#assign-species').remove();
}

/**
 * Display the Image Information dialog.
 */
function onShowImageInformation() {
    if (!imageObject) return;
    $("#dialog-image-info").dialog('open');
}

/**
 * Prepare and open the annotate dialog.
 *
 * Preparations:
 *  - Load the substrate annotations from the database and populate the
 *    substrate lists.
 */
function onAnnotate() {
    if (!imageObject) return;

    // Load and set the substrate annotations.
    $('#dominant-substrates-list').empty();
    $('#subdominant-substrates-list').empty();
    $.ajax({
        type: "GET",
        url: "load.php?do=get_substrate_annotations",
        dataType: "json",
        data: {image_id: imageObject.id},
        success: function(data) {
            for (i in data) {
                var o = data[i];
                $('#'+o.dominance+'-substrates-list').append('<li class="category-container-item"><span class="jellybean"><span class="value">' + o.substrate_type + '</span><span class="remove">×</span></span></li>');
            }

            // Set the callback function for the remove buttons.
            $(".jellybean span.remove").click(function() {
                $(this).parents('li.category-container-item').remove();
            });
        }
    });

    // Load and set the image tags.
    $('#image-tags-list').empty();
    $.ajax({
        type: "GET",
        url: "load.php?do=get_image_tags",
        dataType: "json",
        data: {image_id: imageObject.id},
        success: function(data) {
            for (i in data) {
                var o = data[i];
                $('#image-tags-list').append('<li class="category-container-item"><span class="jellybean"><span class="value">' + o.image_tag + '</span><span class="remove">×</span></span></li>');
            }

            // Set the callback function for the remove buttons.
            $(".jellybean span.remove").click(function() {
                $(this).parents('li.category-container-item').remove();
            });
        }
    });

    // Set the image annotation status input to the right value.
    var radios = $('input:radio[name=annotation-status]');
    if ( imageObject.annotation_status ) {
        radios.filter('[value=' + imageObject.annotation_status + ']').attr('checked', true);
    }
    else {
        radios.filter('[value=incomplete]').attr('checked', true);
    }
    $('#image-annotation-status input:radio').button("refresh");

    // Open dialog.
    $("#dialog-annotate").dialog('open');
}

/**
 * Add a category item to a catefory-editor.
 *
 * @param {String} select_id The ID for the category selector
 * @param {String} list_id The ID for the category list to add the selected category to
 */
function onAddCategory(select_id, list_id) {
    // Get the selected category name.
    var e = document.getElementById(select_id);
    var value = e.options[e.selectedIndex].value;
    var label = e.options[e.selectedIndex].text;

    // Add the category to the category list.
    if (value) {
        $('#'+list_id).append('<li class="category-container-item"><span class="jellybean"><span class="value">' + label + '</span><span class="remove">×</span></span></li>');

        // Set the callback function for the remove button.
        $(".jellybean span.remove").click(function() {
            $(this).parents('li.category-container-item').remove();
        });
    }
}

/**
 * Return a unique list of category names from a category list.
 *
 * @param {String} list_id The ID for the category list
 * @return {Array} Category names
 */
function getCategories(list_id) {
    var list = [];
    $('#' + list_id +' li .jellybean .value').each(function (i) {
        list.push( $(this).text() );
    });
    return _.uniq(list);
}

/**
 * Set the substrate annotations for the current image in the database.
 */
function onSaveSubstrateAnnotations() {
    if (!imageObject) return;
    $.ajax({
        type: "POST",
        url: "fetch.php?do=set_substrate_annotations",
        dataType: "json",
        data: { image_id: imageObject.id,
                annotations: {
                    dominant: getCategories('dominant-substrates-list'),
                    subdominant: getCategories('subdominant-substrates-list')
                    }
        },
        success: function(data) {
            if (data.result != 'success') {
                $("#dialog-unknown-error").dialog('open');
            }
        }
    });
}

/**
 * Set the image tags for the current image in the database.
 */
function onSaveImageTags() {
    if (!imageObject) return;
    $.ajax({
        type: "POST",
        url: "fetch.php?do=set_image_tags",
        dataType: "json",
        data: {image_id: imageObject.id,
            tags: getCategories('image-tags-list')},
        success: function(data) {
            if (data.result != 'success') {
                $("#dialog-unknown-error").dialog('open');
            }
        }
    });
}

/**
 * Load vectors from a vectors object to the vector layer.
 *
 * @param {Object} vectors An object with vector objects from the database
 */
function onLoadVectors(vectors) {
    var Feature = OpenLayers.Feature.Vector;
    var Geometry = OpenLayers.Geometry;
    var features = [];
    for (i in vectors) {
        var vector = vectors[i];
        features[i] = new Feature(Geometry.fromWKT(vector.vector_wkt));
        features[i].id = vector.vector_id;
        features[i].species_id = vector.aphia_id;
        features[i].species_name = vector.scientific_name;
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
    else if (resize) {
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
        $("#dialog-unknown-error").dialog('open');
        return;
    }
    if (img.width == undefined || img.height == undefined) {
        $("#dialog-unknown-error").dialog('open');
        return;
    }
    if (img.area_per_pixel == undefined) {
        $("#dialog-error-image-area-unknown").dialog('open');
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
}

function updatePageImageInfo(img) {
    $('#dialog-image-info table').empty();

    $('#image-info-file').append("<tr><th>Location:</th><td>" + img.url + "</td></tr>");
    $('#image-info-file').append("<tr><th>Image Type:</th><td>" + img.mime + "</td></tr>");
    $('#image-info-file').append("<tr><th>Date Taken:</th><td>" + img.timestamp + "</td></tr>");
    $('#image-info-file').append("<tr><th>Width:</th><td>" + img.width + " pixels</td></tr>");
    $('#image-info-file').append("<tr><th>Height:</th><td>" + img.height + " pixels</td></tr>");
    $('#image-info-file').append("<tr><th>Image Area:</th><td>" + roundNumber(img.area, 2) + " m<sup>2</sup></td></tr>");

    if (img.event_id) $('#image-info-event').append("<tr><th>Event ID:</th><td>" + img.event_id + "</td></tr>");
    if (img.mission_id) $('#image-info-event').append("<tr><th>Mission ID:</th><td>" + img.mission_id + "</td></tr>");
    if (img.latitude && img.longitude) $('#image-info-event').append("<tr><th>Location:</th><td><a href=\"https://maps.google.com/maps?q=" + img.latitude + "," + img.longitude + " (" + img.event_id + ")&iwloc=A&hl=en\" target=\"_blank\">" + img.latitude + ":" + img.longitude + "</a></td></tr>");
    if (img.depth) $('#image-info-event').append("<tr><th>Depth:</th><td>" + img.depth + " m</td></tr>");
    if (img.altitude) $('#image-info-event').append("<tr><th>Altitude:</th><td>" + roundNumber(img.altitude, 2) + " m</td></tr>");
    if (img.salinity) $('#image-info-event').append("<tr><th>Salinity:</th><td>" + img.salinity + " PSU</td></tr>");
    if (img.temperature) $('#image-info-event').append("<tr><th>Temperature:</th><td>" + img.temperature + " &deg;C</td></tr>");
}

// Functions for testing purposes.
function test1() {
    bounds = new OpenLayers.Bounds(0, imageObject.height-100, 100, imageObject.height);
    box = new OpenLayers.Feature.Vector(bounds.toGeometry());
    vectorLayer.addFeatures(box);
}
