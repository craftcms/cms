jQuery.parseFragmentString = function (value) {
    var
        // Object that holds names => values.
        params = {},
        // Get query string pieces (separated by &)
        pieces = value.split('&'),
        // Temporary variables used in loop.
        pair, i, l;

    // Loop through query string pieces and assign params.
    for (i = 0, l = pieces.length; i < l; i++) {
        pair = pieces[i].split('=', 2);
        // Repeated parameters with the same name are overwritten. Parameters
        // with no value get set to boolean true.
        params[decodeURIComponent(pair[0])] = (pair.length == 2 ?
            decodeURIComponent(pair[1].replace(/\+/g, ' ')) : true);
    }

    return params;
};