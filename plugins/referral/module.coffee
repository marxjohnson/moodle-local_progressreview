M.progressreview_referral =

    Y: null

    init_autosave: (Y) ->
        @Y = Y
        Y.all('.referral').on 'change', (e) ->
            field = e.target.get 'name'
            if field == 'refer'
                if e.target.get 'checked'
                    @check_message(Y.one('#id_refer_message'))
                else
                    @clear_error()
            else if field == 'refer_message'
                @check_message(e.target)
        , @

    check_message: (node) ->
        if node.get('value') == ''
            strerror = M.util.get_string 'musthavemessage', 'progressreview_referral'
            M.local_progressreview.errorcontainer.setContent strerror
            M.local_progressreview.errorindicator.setStyle 'display', 'block'
        else
            @clear_error()

    clear_error: ->
        M.local_progressreview.errorindicator.setStyle 'display', 'none'
