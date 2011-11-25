M.progressreview_targets = {

    Y: null,
    calendar_watch: null,

    init_autosave: function(Y) {
        this.Y = Y;
        Y.all('.targets').on('change', function(e) {
            this.autosave(e.target);
        }, this);

        Y.one('#targets').delegate('focus', function(e) {
            if (this.calendar_watch == null) {
                this.calendar_watch = Y.one('#dateselector-calendar-panel').delegate('click', function(e) {
                    this.autosave(M.form.dateselector.currentowner.dayselect);
                }, '.calcell', this);
            }
        }, 'select', this);

        Y.one('#targets').delegate('focus', function(e) {
            if (this.calendar_watch != null) {
                Y.one('#dateselector-calendar-panel').addClass('yui3-overlay-hidden').detach('click');
                this.calendar_watch = null;
            }
        }, 'textarea', this);

    },

    autosave: function(target) {
        Y = this.Y;
        fieldname = target.get('name');
        targetnumber = fieldname.match(/\d+/)[0];
        field = fieldname.match(/[a-z]+/)[0];

        if (field == 'deadlines') {
            Y.all('select.deadline'+targetnumber).each(function(select) {
                switch(select.get('name')) {
                    case 'deadlines['+targetnumber+'][day]':
                        day = select.get('value');
                        break;

                    case 'deadlines['+targetnumber+'][month]':
                        month = select.get('value');
                        break;

                    case 'deadlines['+targetnumber+'][year]':
                        year = select.get('value');
                        break;
                }
            });

            value = Date.parse(year+'/'+month+'/'+day)/1000;

        } else {
            value = target.get('value');
        }

        field = field+targetnumber;

        M.local_progressreview.autosave('targets', field, value);
    }
}
