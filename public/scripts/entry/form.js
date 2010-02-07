function encodeForHTML(str) {
    return $('<div/>').text(str + '').html().replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

if (typeof(application) == 'undefined') var application = {};

application['guestbook.entry.form'] = function(comment, method) {
    comment = typeof(comment) != 'undefined' ? comment : '';

    method = typeof(method) != 'undefined' ? method : 'PUT';

    return '' +
        '<dl>' +

        '    <dt><label for="comment">Comment:</label></dt>' +
        '    <dd><textarea class="comment" name="comment" rows="8" cols="40">' + encodeForHTML(comment) + '</textarea></dd>' +

        '    <dt>&nbsp;</dt>' +
        '    <dd>' +
        '       <input type="button" class="cancel" value="Cancel" />' +
        '       <input type="button" class="submit" value="' + (method == 'POST' ? 'Sign Guestbook' : 'Edit Entry') + '" />' +
        '    </dd>' +
        '</dl>' +
        '';
};

