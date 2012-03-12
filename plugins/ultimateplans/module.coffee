M.progressreview_ultimateplans =

    Y: null

    init_autosave: (Y) ->
        @Y = Y

        Y.all('.ultimateplan').on 'change', (e) ->
            @autosave e.target
        , @

    autosave: (target) ->
        field1 = target.get 'name'
        if field1 == 'ultimateplan'
            value =
                ultimateplan: target.get 'value'
                ultimateplan_comments: @Y.one('#id_ultimateplan_comments').get 'value'
        else
            value =
                ultimateplan: @Y.one('#id_ultimateplan').get 'value'
                ultimateplan_comments: target.get 'value'

        field = 'ultimateplan'
        target = @Y.one '#id_ultimateplan'
        value = @Y.JSON.stringify value
        M.local_progressreview.autosave 'ultimateplans', field, value, target
