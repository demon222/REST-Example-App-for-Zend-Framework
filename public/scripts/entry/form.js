if (typeof(application) == 'undefined') var application = {};
application['guestbook.entry.form'] =
    '<form enctype="application/x-www-form-urlencoded" method="post" action="/entry-api/">' +
    '    <dl class="zend_form">' +
    '        <dt><label for="email" class="required">Your email address:</label></dt>' +
    '        <dd><input type="text" name="email" value="" /></dd>' +
    
    '        <dt><label for="comment" class="required">Please Comment:</label></dt>' +
    '        <dd><textarea name="comment" rows="12" cols="40"></textarea></dd>' +
    
    '        <dt>&nbsp;</dt>' +
    '        <dd><input type="submit" name="submit" value="Sign Guestbook" /></dd>' +
    '    </dl>' +
    '</form>';