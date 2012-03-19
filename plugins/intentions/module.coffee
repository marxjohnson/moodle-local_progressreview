M.progressreview_intentions =
    Y: null

    istops: null

    disabled: null

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

        @istops = Y.all('.intentions.istop')

        @istops.on 'click', (e) ->
            @autosave e.target

            haserror = @istops.getStyle('color').indexOf('red') > -1

            @check_istops()

            ischecked = e.target.get 'checked'
            if !ischecked and haserror
                Y.all('.intentions.istop').each (el) ->
                    @autosave el
                , @

        , @

        Y.all('.intentions.cont').on 'click', (e) ->
            @check_istops()
        , @

    autosave: (target) ->
        field = target.get 'id'
        value = target.get 'checked'
        M.local_progressreview.autosave 'intentions', field, value, target

    check_istops: ->
        checked = @istops.get('checked').filter (x) ->
            x
        if checked.length == 3
            if unchecked = @istops.filter ':not(:checked):enabled'
                unchecked.set 'disabled', true
                @disabled = unchecked
        else
            if @disabled
                @disabled.set 'disabled', false
