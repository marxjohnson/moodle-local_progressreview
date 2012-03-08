
M.progressreview_intentions = {
  Y: null,
  init_autosave: function(Y) {
    this.Y = Y;
    Y.all('.intentions.cont').on('click', function(e) {
      var idparts, istop;
      this.autosave(e.target);
      if (!e.target.get('checked')) {
        idparts = e.target.get('id').split('_');
        idparts.pop();
        idparts.push('istop');
        istop = Y.one('#' + idparts.join('_'));
        istop.set('checked', false);
        return this.autosave(istop);
      }
    }, this);
    return Y.all('.intentions.istop').on('click', function(e) {
      var haserror;
      this.autosave(e.target);
      haserror = Y.all('.intentions.istop').getStyle('color').indexOf('red') > -1;
      if (!e.target.get('checked') && haserror) {
        return Y.all('.intentions.istop').each(function(el) {
          return this.autosave(el);
        }, this);
      }
    }, this);
  },
  autosave: function(target) {
    var field, value;
    field = target.get('id');
    value = target.get('checked');
    return M.local_progressreview.autosave('intentions', field, value, target);
  }
};
