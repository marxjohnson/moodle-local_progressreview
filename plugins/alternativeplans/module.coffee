M.progressreview_alternativeplans =

    Y: null

    init_autosave: (Y) ->
        @Y = Y
        @check_intentions()

        Y.all('.intentions.cont').on 'click', (e) ->
            @check_intentions()
        , @

        Y.all('.alternativeplan').on 'change', (e) ->
            @autosave e.target
        , @

    autosave: (target) ->
        field1 = target.get 'name'
        if field1 == 'alternativeplan'
            value =
                alternativeplan: target.get 'value'
                alternativeplan_comments: @Y.one('#id_alternativeplan_comments').get 'value'
        else
            value =
                alternativeplan: @Y.one('#id_alternativeplan').get 'value'
                alternativeplan_comments: target.get 'value'

        field = 'alternativeplan'
        target = @Y.one '#id_alternativeplan'
        value = @Y.JSON.stringify value
        M.local_progressreview.autosave 'alternativeplans', field, value, target

    check_intentions: ->
        alternativeplan = @Y.one('#id_alternativeplan')
        checked = @Y.all('.intentions.cont').get('checked').indexOf(true) >= 0
        if checked
            alternativeplan.one('option').set 'selected', true
            comments = @Y.one('#id_alternativeplan_comments')
            comments.setContent ''
            alternativeplan.set 'disabled', true
            @autosave comments
        else
            alternativeplan.set 'disabled', false

