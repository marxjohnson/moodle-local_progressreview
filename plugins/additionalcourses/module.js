
M.progressreview_additionalcourses = {
  init_autosave: function(Y) {
    return Y.all('#additionalcourses_header .additionalcourse').on('change', function(e) {
      return this.autosave(e.target);
    }, this);
  },
  autosave: function(target) {
    var field, value;
    field = target.get('id');
    value = target.get('value');
    return M.local_progressreview.autosave('additionalcourses', field, value, target);
  }
};
