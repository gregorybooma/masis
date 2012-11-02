/**
 * Round a decimal number to a set number of decimals.
 *
 * @param {Number} num The number to be rounded
 * @param {Number} dec The number of decimals to round to
 * @return {Number} The rounded number
 */
function roundNumber(num, dec) {
	var result = Math.round(num*Math.pow(10,dec))/Math.pow(10,dec);
	return result;
}

/**
 * Populate a HTML select element with option elements.
 *
 * @param {Object} selobj The HTML select element object
 * @param {String} url The URL which will return JSON formatted options
 * @param {Object} callback The callback function to be called after the select
 *      element is populated
 */
function loadSelectList(selobj, url, callback)
{
    selobj.empty();
    $.getJSON(url, {}, function(data) {
        // Build the select list.
        $.each(data, function(i,obj) {
            selobj.append($('<option></option>')
                .html(obj.label)
                .attr('label', obj.value));
        });
        // Run callback method when done.
        if (callback) {
            selobj.promise().done(callback);
        }
    });
}
