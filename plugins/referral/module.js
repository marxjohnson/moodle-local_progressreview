
M.progressreview_referral = {
  Y: null,
  init_autosave: function(Y) {
    this.Y = Y;
    return Y.all('.referral').on('change', function(e) {
      var field;
      field = e.target.get('name');
      if (field === 'refer') {
        if (e.target.get('checked')) {
          return this.check_message(Y.one('#id_refer_message'));
        } else {
          return this.clear_error();
        }
      } else if (field === 'refer_message') {
        return this.check_message(e.target);
      }
    }, this);
  },
  check_message: function(node) {
    var strerror;
    if (node.get('value') === '') {
      strerror = M.util.get_string('musthavemessage', 'progressreview_referral');
      M.local_progressreview.errorcontainer.setContent(strerror);
      return M.local_progressreview.errorindicator.setStyle('display', 'block');
    } else {
      return this.clear_error();
    }
  },
  clear_error: function() {
    return M.local_progressreview.errorindicator.setStyle('display', 'none');
  }
};
