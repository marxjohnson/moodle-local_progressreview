M.progressreview_intentions =
    Y: null

    init_autosave: (Y) ->
        @Y = Y
        Y.all('.intentions.cont').on 'click', (e) ->
            @autosave e.target
            if !e.target.get 'checked'
                idparts = e.target.get('id').split('_')
                idparts.pop()
                idparts.push 'istop'
                istop = Y.one('#'+idparts.join('_'))
                istop.set('checked', false)
                @autosave istop
        , @
        Y.all('.intentions.istop').on 'click', (e) ->
            @autosave e.target

            haserror = Y.all('.intentions.istop').getStyle('color').indexOf('red') > -1

            if !e.target.get('checked') and haserror
                Y.all('.intentions.istop').each (el) ->
                    @autosave el
                , @
        , @

    autosave: (target) ->
        field = target.get 'id'
        value = target.get 'checked'
        M.local_progressreview.autosave 'intentions', field, value, target
