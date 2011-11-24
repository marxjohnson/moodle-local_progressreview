M.progressreview_tutor = {

    init: function(Y) {
        if (!Y.one('#didntattend')) {
            var strdidntattend = M.util.get_string('didntattend', 'progressreview_tutor');
            var button = Y.Node.create('<button id="didntattend">'+strdidntattend+'</button>');
            button.on('click', function(e) {
                e.preventDefault();
                comments = e.target.siblings('#id_comments');
                commentsvalue = comments.get('value');
                strfiller = M.util.get_string('didntattendfiller', 'progressreview_tutor');
                comments.set('value', strfiller+' '+comments.get('value'));
                Y.one('#id_comments').simulate('change');
            });
            Y.one('#id_comments').insert(button, 'after');
        }
        });
    }
}
