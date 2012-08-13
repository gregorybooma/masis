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

/*** On page ready ***/

$(document).ready(function() {
    // Style buttons with jQuery UI.
    $( "input:submit, button").button();

    // Make sidebar elements toggleable.
    /*
	$('#sidebar-left h3').click(function() {
		$(this).next().toggle('blind');
		return false;
	}).next().hide();
    */
    $("#sidebar-left").accordion({ header: "h3", active: 2, fillSpace: false });

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

    // Disable feature controls.
    $("#feature-controls input:radio").button("disable");

    // Set commit button action.
    $("#action-commit").click(function() {
        var areas = get_areas(vectorLayer);
        $("#polygons").empty();
        for (a in areas) {
            $("#polygons").append(a + ": " + areas[a] * imageObject.area_per_pixel + " m2 (" + areas[a] + " px)\n");
        }
    });

    // Load the file tree.
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
            set_image(file);
        }
    );

    // Initialize dialogs.
    $( "#dialog-remove-selection" ).dialog({
        autoOpen: false,
        resizable: false,
        modal: true
    });

    // Initialize the page.
    init();
});

/*** Callback functions ***/

function onFeatureRemove(feature) {
    var checked = document.getElementById("removePolygon").checked;
    if (checked) {
        $("#dialog-remove-selection").dialog("option", 'buttons', {
                "Delete selection": function() {
                        $( this ).dialog( "close" );
                        feature.destroy();
                    },
                    Cancel: function() {
                        $( this ).dialog( "close" );
                    }
                });
        $("#dialog-remove-selection").dialog('open');
    }
}

function onFeatureSelect(feature) {
    selectedFeature = feature;
    $('#action-buttons ul').append( $('<li></li>')
        .attr('id', "assign-species")
        .text("Assign species: ") );

    $('#assign-species').append( $('<input />')
        .attr('name', "assign-species")
        .attr('id', "select-species") );

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
            selectedFeature.species_id = ui.item.value;
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

function onFeatureUnselect(feature) {
    $('#assign-species').remove();
}

/*** Other functions ***/

// Page initialization.
function init() {
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

    // Show coordinates of the mouse position.
    //map.addControl(new OpenLayers.Control.MousePosition());

    // Set stroke width.
    OpenLayers.Feature.Vector.style['default']['strokeWidth'] = '2';

    // Set controls.
    controls = {
        polygon: new OpenLayers.Control.DrawFeature(vectorLayer, OpenLayers.Handler.Polygon),
        modify: new OpenLayers.Control.ModifyFeature(vectorLayer),
        annotate: new OpenLayers.Control.SelectFeature(vectorLayer,
            {onSelect: onFeatureSelect, onUnselect: onFeatureUnselect}),
        remove: new OpenLayers.Control.SelectFeature(vectorLayer,
            {onSelect: onFeatureRemove})
    };
    for(var key in controls) {
        map.addControl(controls[key]);
    }
}

function toggleControl(element) {
    for (key in controls) {
        var control = controls[key];
        if (element.value == key && element.checked) {
            control.activate();
            if (key == 'modify') {
                setModifyFeature();
            }
        } else {
            control.deactivate();
        }
    }
}

function setModifyFeature() {
    var transform = document.getElementById("transformToggle").checked;
    var rotate = document.getElementById("rotateToggle").checked;
    var resize = document.getElementById("resizeToggle").checked;
    var drag = document.getElementById("dragToggle").checked;

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
    else if(drag) {
        controls.modify.mode = OpenLayers.Control.ModifyFeature.DRAG;
    }
}

// Set and load image from path.
function set_image(path) {
    $.ajax({
        type: "GET",
        url: "load.php?do=get_image_info&path="+path,
        dataType: "xml",
        success: function(xml) {
            // Set image info.
            var info = $(xml).find('image');
            // Set image object.
            set_image_object(info);
            // Load new image.
            load_image(imageObject);
            // Update image info on page.
            update_page_image_info(imageObject);
        }
    });
}

// Set image object from imago info object.
function set_image_object(info) {
    var to_float = ['altitude','area','area_per_pixel','depth'];
    var to_int = ['width','height'];

    // Set new image object.
    imageObject = new ImageInfo();

    // Copy all info attributes to the image object.
    info.each(function() {
        $(this.attributes).each(function(i, attr) {
            var val = attr.value;
            if ( val ) {
                if ( $.inArray(attr.name, to_float) != -1 ) {
                    val = parseFloat(val);
                }
                else if ( $.inArray(attr.name, to_int) != -1 ) {
                    val = parseInt(val);
                }
                imageObject[attr.name] = val;
            }
        });
    });

    // Copy all child nodes as attributes to the image object.
    info.children().each(function() {
        imageObject[this.tagName] = $(this).text();
    });
}

// Load new image into the map.
function load_image(img) {
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

    // Reset page elements.
    $("#feature-controls input:radio").button("enable");
    var e = document.getElementById("navigateToggle")
    e.checked = true;
    toggleControl(e);
    onFeatureUnselect();
}

function update_page_image_info(img) {
    $('#image-altitude').html("Altitude: " + roundNumber(img.altitude, 2) + " m");
    $('#image-area').html("Area: " + roundNumber(img.area, 2) + " m<sup>2</sup>");
    $('#image-info').show();
}

// Set the next image in the map.
function set_next_image() {
    // Set image position.
    if (imagePosition == imageFiles.find('file').length - 1) {
        // Go to the first file if the end is reached.
        imagePosition = 0;
    } else {
        imagePosition++;
    }
    // Set a new image.
    set_image(imagePosition);
}

// Set the previous image in the map.
function set_previous_image() {
    // Set image position.
    if (imagePosition == 0 ) {
        // Go to the last file if the beginning is reached.
        imagePosition = imageFiles.find('file').length - 1
    } else {
        imagePosition--;
    }
    // Set a new image.
    set_image(imagePosition);
}

// List all file in imageFiles.
function list_files() {
    // Get dir elements.
    imageFiles.find('dir').each(function() {
        var dir_name = $(this).attr('name');
        var dir_text = $(this).text();
        // Get file elements.
        $(this).find('file').each(function() {
            var file_name = $(this).attr('name');
            var file_text = $(this).text();
            $('#image_info').append("file: "+file_name+"\n");
        });
    });
}

// Put all features areas in an array.
function get_areas(vectorLayer) {
    var areas = [];
    for (f in vectorLayer.features) {
        var val = vectorLayer.features[f].geometry.getArea()
        areas[f] = parseInt(val);
    }
    return areas;
}

// Functions for testing purposes.
function test1() {
    bounds = new OpenLayers.Bounds(0, imageObject.height-100, 100, imageObject.height);
    box = new OpenLayers.Feature.Vector(bounds.toGeometry());
    vectorLayer.addFeatures(box);
}

