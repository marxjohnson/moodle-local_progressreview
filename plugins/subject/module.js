M.progressreview_subject = {

    Y: '',

    init_autosave: function(Y) {
        this.Y = Y;
        Y.all('.subject').on('change', function(e) {
            fieldname = e.target.get('name');
            fieldname = fieldname.substring(6);
            reviewid = fieldname.match(/\d+/)[0];
            field = fieldname.match(/[a-z]+/)[0];
            value = e.target.get('value');
            studentid = Y.one('#id_student_'+reviewid).get('value');
            Y.one('#id_editid').set('value', studentid);

            M.local_progressreview.autosave('subject', field, value, e.target);

            console.log(field);
            if (field == 'homeworkdone') {
                var totalfield = Y.one('#review_'+reviewid+'_homeworktotal');
                var total = totalfield.get('value');
                M.local_progressreview.autosave('subject', 'homeworktotal', total, totalfield);
            } else if (field == 'homeworktotal') {
                var donefield = Y.one('#review_'+reviewid+'_homeworkdone');
                var done = donefield.get('value');
                M.local_progressreview.autosave('subject', 'homeworkdone', done, donefield);
            }
        });
    }
}
