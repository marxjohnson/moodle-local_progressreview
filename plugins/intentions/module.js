
M.progressreview_intentions = {
  Y: null,
  istops: null,
  disabled: null,
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
    this.istops = Y.all('.intentions.istop');
    this.istops.on('click', function(e) {
      var haserror, ischecked;
      this.autosave(e.target);
      haserror = this.istops.getStyle('color').indexOf('red') > -1;
      this.check_istops();
      ischecked = e.target.get('checked');
      if (!ischecked && haserror) {
        return Y.all('.intentions.istop').each(function(el) {
          return this.autosave(el);
        }, this);
      }
    }, this);
    return Y.all('.intentions.cont').on('click', function(e) {
      return this.check_istops();
    }, this);
  },
  autosave: function(target) {
    var field, value;
    field = target.get('id');
    value = target.get('checked');
    return M.local_progressreview.autosave('intentions', field, value, target);
  },
  check_istops: function() {
    var checked, unchecked;
    checked = this.istops.get('checked').filter(function(x) {
      return x;
    });
    if (checked.length === 3) {
      if (unchecked = this.istops.filter(':not(:checked):enabled')) {
        unchecked.set('disabled', true);
        return this.disabled = unchecked;
      }
    } else {
      if (this.disabled) return this.disabled.set('disabled', false);
    }
  }
};
