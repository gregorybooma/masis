var zoom = 3; // Default number of zoom levels
var map;
var vectorLayer;
var imageLayer;
var imageObject;
var selectedFeature;
var controls;

/**
 * @class Class for collecting image information in a single object.
 */
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

    /**
    * Return the width of the image in meters.
    *
    * @return {Numeric} Image width in meters.
    */
    this.get_width = function () {
        return Math.sqrt(this.area_per_pixel) * this.width;
    };

    /**
    * Return the height of the image in meters.
    *
    * @return {Numeric} Image height in meters.
    */
    this.get_height = function () {
        return Math.sqrt(this.area_per_pixel) * this.height;
    };
}

/**
 * When the HTML page has finished loading, do the following:
 * - Initialize the interface
 * - Initialize the workspace
 * - Load the photo library
 *
 * @method ready
 * @constructor
 */
$(document).ready(function() {
    // Initialize the interface.
    initInterface();

    // Initialize the workspace.
    initWorkspace();

    // Load the directory tree.
    onLoadPhotoLibrary();
});

/**
 * Initialize the user interface.
 */
function initInterface() {
    // Set the tabs widget.
    $( "#tabs" ).tabs({
        select: function(event, ui) {
            // Update the contents of the Statistics tab whenever it's selected
            if ( ui.panel.getAttribute('id') == 'tab-manager' ) {
                onLoadTable('load.php?do=table_images_unassigned_vectors', '#images-unassigned-vectors');
                onLoadTable('load.php?do=table_images_need_review', '#images-need-review');
                onLoadTable('load.php?do=table_images_highlighted', '#images-highlighted');
                onLoadTable('load.php?do=table_images_species_unaccepted', '#images-species-unaccepted');
            }
            else if ( ui.panel.getAttribute('id') == 'tab-statistics' ) {
                onLoadTable('load.php?do=table_species_coverage_overall&reset_areas=1','#species-coverage-overall');
                onLoadTable('load.php?do=table_species_coverage_where_present','#species-coverage-where-present');
            }
        }
    });

    // Style buttons with jQuery UI.
    $("button").button();

    // Make sidebar elements toggleable.
    $("#sidebar-left").accordion({ header: "h1", active: 2, fillSpace: false });

    // Make the map element resizable.
    $( "#map" ).resizable({
        stop: function(event, ui) {
                // Re-render the map in the resized map element to maintain
                // restriction of movement outside image borders.
                if (map) {
                    map.render('map');
                }
            }
        });

    // Transform feature controls into a jQuery UI button set.
    $("#feature-controls").buttonset();
    $("#polygon-controls").buttonset();
    $("#image-annotation-status").buttonset();

    // Disable feature controls.
    $("#feature-controls input:radio").button("disable");

    // Set button actions.
    $("#select-species-worms-searchpar").change(function() {
        var val = $("#select-species-worms-searchpar option:selected").val();

        // Check the source option to "marinespecies.org" if "Common Name" is selected.
        if (val == 1) {
            $("input:radio[name=select-species-source]").filter('[value=worms]').attr('checked', true);
        }
        // Set the source to marinespecies.org with the searchpar option set.
        $('#select-species').autocomplete("option", {
            delay: 1000,
            source: "load.php?do=get_worms_species&searchpar=" + val
        });
    });
    $("input:radio[name=select-species-source]").change(function() {
        var val = $("input:radio[name=select-species-source]:checked").val();

        if (val == 'local') {
            // Set the searchpar option to "Scientific Name" if source is set to "Local".
            $("#select-species-worms-searchpar option").filter('[value=0]').attr('selected', true);
            // Set the autocomplete source to local search.
            $('#select-species').autocomplete("option", {
                delay: 500,
                source: "load.php?do=get_species_matching"
            });
        }
        else {
            // Set the autocomplete source to WoRMS search.
            $('#select-species').autocomplete("option", {
                delay: 1000,
                source: "load.php?do=get_worms_species"
            });
        }
    });

    // Set autocomplete for inputs.
    $('#select-species').autocomplete({
        delay: 1000,
        source: "load.php?do=get_worms_species",
        select: function(event, ui) {
            var id = ui.item.value;
            var name = ui.item.label;

            // Set the species ID and name for the selected feature.
            selectedFeature.attributes.aphia_id = id;
            selectedFeature.attributes.species_name = name;

            // The default action of select is to replace the text field's
            // value with the value of the selected item. This is not desired.
            event.preventDefault();

            // Replace the text field value with the label instead.
            $("#select-species").val(name);

            // Update dialog label.
            if ( id ) {
                $('#assign-species-label a').attr( {'href': "http://www.marinespecies.org/aphia.php?p=taxdetails&id=" + id, 'target': '_blank'} );
                $('#assign-species-label a').text(name);
            }
            else {
                $('#assign-species-label a').attr( {'href': "#", 'target': '_self'} );
                $('#assign-species-label a').text("Unassigned");
            }
        }
    });
    $('#export-coverage-two-species input:text[name^=aphia_id]').autocomplete({
        minLength: 3,
        delay: 500,
        source: "load.php?do=get_species_matching"
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
    initDialogs();
}


/**
 * Initialize all dialogs.
 *
 * Dialogs are initialized, but not displayed.
 */
function initDialogs() {
    $( "#dialog-on-save-selections" ).dialog({
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
            Close: function() {
                $(this).dialog("close");
            }
        }
    });
    $( "#dialog-annotate-image" ).dialog({
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

/**
 * Initialize the workspace.
 */
function initWorkspace() {
    // Create a map.
    map = new OpenLayers.Map('map', {
        units: 'dd', // Set units to decimal degrees (but we treat them as pixels)
        theme: null,
        displayProjection: 'EPSG:4326'
        }
    );

    // Here we create a new style object with rules that determine which
    // symbolizer will be used to render each feature.
    var style = new OpenLayers.Style(
        // The first argument is a base symbolizer; all other symbolizers in
        // rules will extend this one.
        {
            strokeColor: "#EE9900",
            strokeOpacity: 1,
            strokeWidth: 2,
            fillColor: "#EE9900",
            fillOpacity: 0.1,
            pointRadius: 6,
            pointerEvents: "visiblePainted",
            fontColor: "white",
            fontSize: "12x",
            fontFamily: "Arial, sans-serif",
            fontWeight: "normal",
            fontStyle: "italic",
            labelXOffset: 0,
            labelYOffset: 0,
            labelOutlineColor: "black",
            labelOutlineWidth: 2
        },
        // The second argument will include all rules.
        {
            rules: [
                // Show a label with the species name for each vector with the
                // attribute `aphia_id` set.
                new OpenLayers.Rule({
                    filter: new OpenLayers.Filter.Comparison({
                        type: OpenLayers.Filter.Comparison.GREATER_THAN,
                        property: "aphia_id",
                        value: 0,
                    }),
                    symbolizer: {label: "${species_name}"}
                }),
                // Apply this rule if no others apply.
                new OpenLayers.Rule({
                    elseFilter: true,
                    symbolizer: {label: null}
                })
            ]
        }
    );

    // Create a vector layer.
    vectorLayer = new OpenLayers.Layer.Vector( "Selections" , {
        styleMap: new OpenLayers.StyleMap({'default': style})
    });

    // Add layers to the map.
    map.addLayers([vectorLayer]);

    // Add a layer switcher to the map.
    var container = document.getElementById("olControlLayerSwitcher");
    map.addControl(new OpenLayers.Control.LayerSwitcher({div: container}));

    // Set controls.
    controls = {
        polygon: new OpenLayers.Control.DrawFeature(vectorLayer, OpenLayers.Handler.Polygon),
        regular_polygon: new OpenLayers.Control.DrawFeature(vectorLayer, OpenLayers.Handler.RegularPolygon, {irregular: true}),
        modify: new OpenLayers.Control.ModifyFeature(vectorLayer),
        drag: new OpenLayers.Control.DragFeature(vectorLayer),
        annotate: new OpenLayers.Control.SelectFeature(vectorLayer,
            {onSelect: onFeatureAnnotateSelect, onUnselect: onFeatureAnnotateUnselect}),
        remove: new OpenLayers.Control.SelectFeature(vectorLayer,
            {onSelect: onFeatureRemove})
    };
    for (var key in controls) {
        map.addControl(controls[key]);
    }
}

/**
 * Load the photo library.
 */
function onLoadPhotoLibrary() {
    // Load the directory tree in the photo library.
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
    // Make the photo library horizontally resizable.
    $( "#photo-library" ).resizable({handles: 's'});
}

/**
 * Set the empty image area fields in the database.
 */
function onSetDatabaseAreas() {
    $.ajax({
        type: "GET",
        url: "load.php?do=set_areas",
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

/**
 * Set the annotation status for the current image in the database.
 */
function onSetImageAnnotationStatus() {
    if (!imageObject) return;
    var element = $("input:radio[name=annotation-status]:checked");
    $.ajax({
        type: "GET",
        url: "load.php?do=set_annotation_status",
        dataType: "json",
        data: {image_id: imageObject.id, status: element.val()},
        success: function(data) {
            if (data.result == 'success') {
                // Update the imageObject annotation status.
                imageObject.annotation_status = element.val();
            }
            else {
                $("#dialog-unknown-error").dialog('open');
            }
        }
    });
}

/**
 * Load a data table in a div element.
 *
 * @param {String} url The URL which returns the HTML table
 * @param {String} div_id The jQuery selector string for the div element which will hold the table
 * @param {Object} options Optional options object for the dataTable function
 */
function onLoadTable(url, div_id, options) {
    if (!options) {
        var options = {
                "bJQueryUI" : true,
                "bSort" : true,
                "bFilter" : true,
                "bLengthChange" : true,
                "sPaginationType" : "full_numbers",
                "iDisplayLength" : 10};
    }

    $.ajax({
        type: "GET",
        url: url,
        dataType: "html",
        success: function(table) {
            $(div_id).html(table);
            $(div_id+' table').dataTable(options);
        }
    });
}

/**
 * Show a confirmation dialog before saving the vectors to the database.
 */
function onSaveSelections() {
    if (!imageObject) return;
    $("#dialog-on-save-selections").dialog('open');
}

/**
 * Save all workspace vectors to the database.
 */
function saveVectors() {
    // Don't save vectors if the area for the current image is unknown.
    if (!imageObject.area) {
        $("#dialog-error-image-area-unknown").dialog('open');
        return;
    }
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
            aphia_id: feature.attributes.aphia_id,
            species_name: feature.attributes.species_name,
            };
    }
    // Save features to the database.
    $.ajax({
        type: "POST",
        url: "load.php?do=save_vectors",
        dataType: "json",
        data: vectors,
        success: function(data) {
            if (data.result != 'success') {
                $("#dialog-unknown-error").dialog('open');
            }
        }
    });
}

/**
 * Delete a vector from the workspace and the database.
 *
 * @param {Object} feature The vector to be removed
 */
function onFeatureRemove(feature) {
    var checked = document.getElementById("removePolygon").checked;
    if (checked) {
        // Contruct the confirmation dialog.
        $("#dialog-remove-selection").dialog("option", 'buttons', {
                "Delete selection": function() {
                    $( this ).dialog( "close" );

                    // Delete the vector from the database.
                    $.ajax({
                        type: "GET",
                        url: "load.php?do=delete_vector",
                        dataType: "json",
                        data: {image_id: imageObject.id, vector_id: feature.id},
                        success: function(data) {
                            if (data.result == 'success') {
                                // Remove the vector from the workspace.
                                feature.destroy();
                            }
                            else {
                                // Display error message on failure.
                                $("#dialog-unknown-error").dialog('open');
                            }
                        }
                    });
                },
                Cancel: function() {
                    $( this ).dialog( "close" );
                }
            });
        // Open the confirmation dialog.
        $("#dialog-remove-selection").dialog('open');
    }
}

/**
 * Add a category item to a catefory-editor.
 *
 * @param {Object} feature The vector to be annotated
 */
function onFeatureAnnotateSelect(feature) {
    selectedFeature = feature;

    // Remove the value of the species input field.
    $('#select-species').attr('value', "");

    // Replace the text field value if the selected feature is already
    // assigned to a species.
    if (selectedFeature.attributes.aphia_id && selectedFeature.attributes.species_name) {
        $("#select-species").val(selectedFeature.attributes.species_name);
    }

    // Update dialog label.
    if ( feature.attributes.aphia_id ) {
        $('#assign-species-label a').attr( {'href': "http://www.marinespecies.org/aphia.php?p=taxdetails&id=" + feature.attributes.aphia_id, 'target': '_blank'} );
        $('#assign-species-label a').text(feature.attributes.species_name);
    }
    else {
        $('#assign-species-label a').attr( {'href': "#", 'target': '_self'} );
        $('#assign-species-label a').text("Unassigned");
    }

    // Open dialog.
    $("#dialog-assign-species").dialog('open');
}

/**
 * Remove the assign-species input field.
 */
function onFeatureAnnotateUnselect(feature) {
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
function onAnnotateImage() {
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
    $("#dialog-annotate-image").dialog('open');
}

/**
 * Add a category item to a category-editor.
 *
 * @param {String} select_id The ID for the category selector element
 * @param {String} list_id The ID for the category list element to add the selected category to
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
 * @param {String} list_id The ID for the category list element
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
        url: "load.php?do=set_substrate_annotations",
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
        url: "load.php?do=set_image_tags",
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
    var features = [];
    for (i in vectors) {
        var vector = vectors[i];
        features[i] = new OpenLayers.Feature.Vector(
            OpenLayers.Geometry.fromWKT(vector.vector_wkt)
            );
        features[i].id = vector.vector_id;

        features[i].attributes = {
            show_label: vector.aphia_id ? 1 : 0,
            aphia_id: vector.aphia_id,
            species_name: vector.scientific_name ? vector.scientific_name : ""
        };
    }
    vectorLayer.addFeatures(features);
}

/**
 * Activate the selected option from the controls menu.
 *
 * @param {Object} element The HTML radio input element that was selected
 */
function toggleControl(element) {
    // Hide/show context control buttons.
    toggleContextControl(element);

    for (key in controls) {
        var control = controls[key];
        if (element.value == key && element.checked) {
            // Activate a control.
            control.activate();
            // If the activated control is a polygon modify control,
            // set the modify mode.
            if (key == 'modify')  setModifyMode();
        }
        else {
            // Deactivate a control.
            control.deactivate();
        }
    }
}

/**
 * Hide or show context control buttons.
 *
 * @param {Object} element The HTML radio input element that was selected
 */
function toggleContextControl(element) {
    // Show the context controls for drawing polygons.
    if (element.getAttribute('id') == 'selectToggle' && element.checked) {
        $('#polygon-controls').show();
        setPolygonControl('custom');
        $('#polygonCustom').attr('checked', true);
        $("#polygon-controls input:radio").button("refresh");
    }
    else {
        // Hide the controls for drawing regular polygons.
        $('#polygon-controls').hide();
    }
}

/**
 * Set and activate the polygon drawing control for the selected option.
 *
 * For the regular_polygon control, the option `irregular` is always set to
 * true to enable drawing of irregular polygons.
 *
 * @param {Object} element The HTML radio input element that was selected
 */
function setPolygonControl(element) {
    if (element.value == 'square') {
        var options = {sides: 4, irregular: true};
        controls.regular_polygon.handler.setOptions(options);
    }
    else if (element.value == 'pentagon') {
        var options = {sides: 5, irregular: true};
        controls.regular_polygon.handler.setOptions(options);
    }
    else if (element.value == 'hexagon') {
        var options = {sides: 6, irregular: true};
        controls.regular_polygon.handler.setOptions(options);
    }
    else if (element.value == 'circle') {
        var options = {sides: 40, irregular: true};
        controls.regular_polygon.handler.setOptions(options);
    }

    if (element.value == 'custom') {
        controls.regular_polygon.deactivate();
        controls.polygon.activate();
    }
    else {
        controls.polygon.deactivate();
        controls.regular_polygon.activate();
    }
}

/**
 * Set the modify mode for the modify polygon feature.
 */
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

/**
 * Go to the workspace and display an image.
 *
 * @param {String} path The relative path to the image file. This is the path
 *      from the root of the web folder.
 */
function goToImage(path) {
    // Select the workspace tab.
    $("#tabs").tabs("select", "#tab-workspace");
    // Load the image.
    setImage(path);
}

/**
 * Load a new image in the workspace.
 *
 * @param {String} path The relative path to the image file. This is the path
 *      from the root of the web folder.
 */
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
            updateImageInfoDialog(imageObject);
        }
    });
}

/**
 * Set the image information object `imageObject`.
 *
 * @param {Object} info Object with image information.
 */
function setImageObject(info) {
    // Attributes for which the type whould be changed.
    var to_float = ['altitude','area','area_per_pixel','depth'];

    // Set new image object.
    imageObject = new ImageInfo();

    // Copy all info attributes to the image object.
    for (key in info) {
        // Skip if the attribute value is not set.
        if (!info[key]) continue;
        var val = info[key];
        // Convert types for attribute values.
        if ( $.inArray(key, to_float) != -1 ) {
            val = parseFloat(val);
        }
        // Copy the attribute to the image object.
        imageObject[key] = val;
    }
}

/**
 * Load a new image into the workspace.
 *
 * @param {Object} img An ImageInfo object
 */
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

    // Activate the default control.
    toggleControl(e);

    // Reset other page elements.
    $('#assign-species').remove();

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

/**
 * Update the image information in the Image Information dialog.
 *
 * @param {String} img An ImageInfo object
 */
function updateImageInfoDialog(img) {
    $('#dialog-image-info table').empty();

    $('#image-info-file').append("<tr><th>Location:</th><td>" + img.url + "</td></tr>");
    $('#image-info-file').append("<tr><th>Image Type:</th><td>" + img.mime + "</td></tr>");
    $('#image-info-file').append("<tr><th>Date Taken:</th><td>" + img.timestamp + "</td></tr>");
    $('#image-info-file').append("<tr><th>Width:</th><td>" + img.width + " pixels</td></tr>");
    $('#image-info-file').append("<tr><th>Height:</th><td>" + img.height + " pixels</td></tr>");
    if (img.exif.Make) $('#image-info-file').append("<tr><th>Camera Brand:</th><td>" + img.exif.Make + "</td></tr>");
    if (img.exif.Model) $('#image-info-file').append("<tr><th>Camera Model:</th><td>" + img.exif.Model + "</td></tr>");
    if (img.exif.ImageDescription) $('#image-info-file').append("<tr><th>Description:</th><td>" + img.exif.ImageDescription + "</td></tr>");
    if (img.area) $('#image-info-file').append("<tr><th>Image Area:</th><td>" + roundNumber(img.area, 2) + " m<sup>2</sup></td></tr>");

    if (img.event_id) $('#image-info-event').append("<tr><th>Event ID:</th><td>" + img.event_id + "</td></tr>");
    if (img.mission_id) $('#image-info-event').append("<tr><th>Mission ID:</th><td>" + img.mission_id + "</td></tr>");
    if (img.location_map_url) $('#image-info-event').append("<tr><th>Location:</th><td>Lat: " + roundNumber(img.latitude, 2) + " Lon: " + roundNumber(img.longitude, 2) + " <a href=\"" + img.location_map_url + "\" target=\"_blank\"><span class='inline-icon map' title='View map'>&nbsp;</span></a></td></tr>");
    if (img.depth) $('#image-info-event').append("<tr><th>Depth:</th><td>" + img.depth + " m</td></tr>");
    if (img.altitude) $('#image-info-event').append("<tr><th>Altitude:</th><td>" + roundNumber(img.altitude, 2) + " m</td></tr>");
    if (img.salinity) $('#image-info-event').append("<tr><th>Salinity:</th><td>" + img.salinity + " PSU</td></tr>");
    if (img.temperature) $('#image-info-event').append("<tr><th>Temperature:</th><td>" + img.temperature + " &deg;C</td></tr>");
}

/**
 * Draw a 100x100px box in the workspace.
 */
function test1() {
    bounds = new OpenLayers.Bounds(0, imageObject.height-100, 100, imageObject.height);
    box = new OpenLayers.Feature.Vector(bounds.toGeometry());
    vectorLayer.addFeatures(box);
}

/**
 * Draw a 1279x959px box in the workspace.
 */
function test2() {
    bounds = new OpenLayers.Bounds(0, imageObject.height-959, 1279, imageObject.height);
    box = new OpenLayers.Feature.Vector(bounds.toGeometry());
    vectorLayer.addFeatures(box);
}
