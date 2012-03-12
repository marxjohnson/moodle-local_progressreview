
M.progressreview_ultimateplans = {
  Y: null,
  init_autosave: function(Y) {
    this.Y = Y;
    return Y.all('.ultimateplan').on('change', function(e) {
      return this.autosave(e.target);
    }, this);
  },
  autosave: function(target) {
    var field, field1, value;
    field1 = target.get('name');
    if (field1 === 'ultimateplan') {
      value = {
        ultimateplan: target.get('value'),
        ultimateplan_comments: this.Y.one('#id_ultimateplan_comments').get('value')
      };
    } else {
      value = {
        ultimateplan: this.Y.one('#id_ultimateplan').get('value'),
        ultimateplan_comments: target.get('value')
      };
    }
    field = 'ultimateplan';
    target = this.Y.one('#id_ultimateplan');
    value = this.Y.JSON.stringify(value);
    return M.local_progressreview.autosave('ultimateplans', field, value, target);
  }
};
