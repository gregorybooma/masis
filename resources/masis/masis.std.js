function roundNumber(num, dec) {
	var result = Math.round(num*Math.pow(10,dec))/Math.pow(10,dec);
	return result;
}

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
