function encodeForHTML(str) {
    return $('<div/>').text(str).html().replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

if (typeof(application) == 'undefined') var application = {};

application['guestbook.entry.form'] = function(email, comment, method) {
    email = typeof(email) != 'undefined' ? email : '';
    comment = typeof(comment) != 'undefined' ? comment : '';
    
    method = typeof(method) != 'undefined' ? method : 'PUT';

    return '' +
        '    <dl>' +
        '        <dt><label for="email">Email:</label></dt>' +
        '        <dd><input type="text" class="email" name="email" value="' + encodeForHTML(email) + '" /></dd>' +
        
        '        <dt><label for="comment">Comment:</label></dt>' +
        '        <dd><textarea class="comment" name="comment" rows="8" cols="40">' + encodeForHTML(comment) + '</textarea></dd>' +
        
        '        <dt>&nbsp;</dt>' +
        '        <dd>' +
        '           <input type="button" class="cancel" value="Cancel" />' +
        '           <input type="button" class="submit" value="' + (method == 'POST' ? 'Sign Guestbook' : 'Edit Entry') + '" />' +
        '       </dd>' +
        '    </dl>' +
        '';
};