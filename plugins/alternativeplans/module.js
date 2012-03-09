
M.progressreview_alternativeplans = {
  Y: null,
  init_autosave: function(Y) {
    this.Y = Y;
    this.check_intentions();
    Y.all('.intentions.cont').on('click', function(e) {
      return this.check_intentions();
    }, this);
    return Y.all('.alternativeplan').on('change', function(e) {
      return this.autosave(e.target);
    }, this);
  },
  autosave: function(target) {
    var field, field1, value;
    field1 = target.get('name');
    if (field1 === 'alternativeplan') {
      value = {
        alternativeplan: target.get('value'),
        alternativeplan_comments: this.Y.one('#id_alternativeplan_comments').get('value')
      };
    } else {
      value = {
        alternativeplan: this.Y.one('#id_alternativeplan').get('value'),
        alternativeplan_comments: target.get('value')
      };
    }
    field = 'alternativeplan';
    target = this.Y.one('#id_alternativeplan');
    value = this.Y.JSON.stringify(value);
    return M.local_progressreview.autosave('alternativeplans', field, value, target);
  },
  check_intentions: function() {
    var alternativeplan, checked, comments;
    alternativeplan = this.Y.one('#id_alternativeplan');
    checked = this.Y.all('.intentions.cont').get('checked').indexOf(true) >= 0;
    if (checked) {
      alternativeplan.one('option').set('selected', true);
      comments = this.Y.one('#id_alternativeplan_comments');
      comments.setContent('');
      alternativeplan.set('disabled', true);
      return this.autosave(comments);
    } else {
      return alternativeplan.set('disabled', false);
    }
  }
};
