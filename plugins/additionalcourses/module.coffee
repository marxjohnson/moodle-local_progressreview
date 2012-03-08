M.progressreview_additionalcourses =

    init_autosave: (Y) ->
        Y.all('#additionalcourses_header .additionalcourse').on 'change', (e) ->
            @autosave e.target
        , @

    autosave: (target) ->
        field = target.get 'id'
        value = target.get 'value'
        M.local_progressreview.autosave('additionalcourses', field, value, target)
