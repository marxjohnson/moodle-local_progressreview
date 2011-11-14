M.local_progressreview = {

    Y: '',

    progress: '',

    savebutton: '',

    init_autosave: function (Y, savestring) {
        this.Y = Y;
        this.progress = Y.one('#progressindicator');
        strautosave = M.util.get_string('autosaving', 'local_progressreview');
        this.progress.one('#autosavelabel').setContent(strautosave);
        this.progress.setStyle('display', 'none');
        this.savebutton = Y.one('#id_save');
        strsaveactive = M.util.get_string('autosaveactive', 'local_progressreview');
        this.savebutton.set('disabled', true);
        this.savebutton.set('value', strsaveactive);
        this.savestring = savestring;
    },

    autosave: function(plugin, field, value) {

        Y = this.Y;
        this.progress.setStyle('display', 'block');
        var studentid = Y.one('#id_editid').get('value');
        var sessionid = Y.one('#id_sessionid').get('value');
        var courseid = Y.one('#id_courseid').get('value');
        var teacherid = Y.one('#id_teacherid').get('value');
        var reviewtype = Y.one('#id_reviewtype').get('value');

        var url = M.cfg.wwwroot+'/local/progressreview/autosave.php';
        Y.io(url, {
            data: 'studentid='+studentid+'&sessionid='+sessionid
                +'&courseid='+courseid+'&teacherid='+teacherid
                +'&reviewtype='+reviewtype+'&plugin='+plugin
                +'&field='+field+'&value='+value,
            on: {
                success: function(id, o) {
                    M.local_progressreview.progress.setStyle('display', 'none');
                },

                failure: function(id, o) {
                    var message = o.responseText;
                    module = M.local_progressreview;
                    alert(M.util.get_string('autosavefailed', 'local_progressreview', message));
                    module.savebutton.set('disabled', false);
                    module.savebutton.set('value', module.savestring);
                    M.local_progressreview.progress.setStyle('display', 'none');
                }
            }
        });
    }
}
