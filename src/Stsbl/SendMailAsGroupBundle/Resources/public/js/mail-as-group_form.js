/* 
 * The MIT License
 *
 * Copyright 2018 Felix Jacobi.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
IServ.SendMailAsGroup = {};

IServ.SendMailAsGroup.Form = IServ.register(function(IServ) {
    "use strict";
    
    var returnFalseHandler = function() {
        return false;
    };
    
    function resetForm()
    {
        $('#compose_group_mail_subject').val('');
        $('#compose_group_mail_group').val('');
        $('#compose_group_mail_recipients').val('');
        $('#compose_group_mail_body').val('');
        var attachments = $('#compose_group_mail_attachments > ul.bc-collection > li');
                
        attachments.each(function () {
            $(this).remove();
        });
    }
    
    function lockForm()
    {
        $('#compose_group_mail_subject').prop('disabled', true);
        $('#compose_group_mail_group').prop('disabled', true);
        $('#compose_group_mail_recipients').prop('disabled', true);
        $('#compose_group_mail_body').prop('disabled', true);
        
        var addFileButton = $('[data-collection="compose_group_mail_attachments"');
        addFileButton.attr('disabled', 'disabled');
        addFileButton.bind('click', returnFalseHandler);
        
        var attachments = $('[id^="compose_group_mail_attachments_"]');
                
        attachments.each(function () {
            $(this).prop('disabled', true)
        });
    }
    
    function unlockForm()
    {
        $('#compose_group_mail_subject').prop('disabled', false);
        $('#compose_group_mail_group').prop('disabled', false);
        $('#compose_group_mail_recipients').prop('disabled', false);
        $('#compose_group_mail_body').prop('disabled', false);
        
        var addFileButton = $('[data-collection="compose_group_mail_attachments"');
        addFileButton.removeAttr('disabled');
        addFileButton.unbind('click', returnFalseHandler);
        
        var attachments = $('[id^="compose_group_mail_attachments_"]');
                
        attachments.each(function () {
            $(this).prop('disabled', false)
        });
    }
    
    function registerSubmitHandler()
    {
        var form = $('[name="compose_group_mail"]');
        var target = IServ.Routing.generate('group_mail_send');
        var spinner = IServ.Spinner.add('#compose_group_mail_submit');
            
        form.submit(function(e) {      
            $.ajax({
                beforeSend: function() {
                    IServ.Loading.on('stsbl.mail-as-group.form');
                    spinner.data('spinner').start();
                    lockForm();
                },
                success: function(data) {    
                    IServ.Loading.off('stsbl.mail-as-group.form');
                    spinner.data('spinner').stop();
                    unlockForm();
                    
                    if (data.result === 'success') {
                        resetForm();
                    }
                },
                error: function() {
                    IServ.Loading.off('stsbl.mail-as-group.form');
                    spinner.data('spinner').stop();
                    unlockForm();
                    
                    IServ.Message.error(_('Unexcpected error during sending e-mail. Please try again.'), false, '#groupmail-compose-hook');
                },
                url: target,
                type: 'POST',
                data: new FormData(this),
                processData: false,
                contentType: false
            });
            
            e.preventDefault();
            return false;
        });    
    }
    
    function updateMailPrivNotifier(e)
    {
        var currentGroup = e.val();
        var hook = $('#groupmail-compose-hook');
        
        if (currentGroup.length > 0) {
            var hasPrivilege = lookupMailPriv(currentGroup, 'priv');
            removeMailExtPrivWarning();
            removeMailExtFlagWarning();
            removeMailIntFlagWarning();
            
            if (!hasPrivilege) {
                addMailExtPrivWarning(hook);
            } else {
                var hasFlag = lookupMailPriv(currentGroup, 'flag');
                
                if (!hasFlag) {
                    addMailExtFlagWarning(hook);
                }
            }
            
            var hasInternalFlag = lookupMailPriv(currentGroup, 'flag_internal');
            
            if(!hasInternalFlag) {
                addMailIntFlagWarning(hook);
            }
        }
    }
    
    function registerMailPrivNotifier()
    {
        var e = $('#compose_group_mail_group');
        
        // insert warning if there is already a group selected and it has not the privilege(s)
        updateMailPrivNotifier(e);
        
        // register dynamic update for future changes
        e.change(function() {
            updateMailPrivNotifier($(this));
        });
    }
    
    function addMailExtPrivWarning(e)
    {
        e.after(function() {
           return '<div class="alert alert-warning" id="groupmail-ext-priv-alert">' + _('The selected sender is not allowed to send e-mails to other servers.') + '</div>'; 
        });
    }
    
    function addMailExtFlagWarning(e)
    {
        e.after(function() {
           return '<div class="alert alert-warning" id="groupmail-ext-flag-alert">' + _('The selected sender can not receive replies from external e-mail addresses.') + '</div>'; 
        });
    }
    
    function addMailIntFlagWarning(e)
    {
        e.after(function() {
           return '<div class="alert alert-warning" id="groupmail-int-flag-alert">' + _('The selected sender can not receive replies from internal e-mail addresses.') + '</div>'; 
        });
    }
    
    function removeMailExtPrivWarning()
    {
        $('#groupmail-ext-priv-alert').remove();
    }
    
    function removeMailExtFlagWarning()
    {
        $('#groupmail-ext-flag-alert').remove();
    }
    
    function removeMailIntFlagWarning()
    {
        $('#groupmail-int-flag-alert').remove();
    }
    
    function lookupMailPriv(group, type)
    {
        var ret;
        var target =IServ.Routing.generate('group_mail_lookup_priv') + '?type=' + type + '&group=' + group;
        
        // don't use $.getJSON here, because it is asynchronously
        $.ajax({
            async: false,
            dataType: 'json',
            url: target, 
            success: function(data) {
                ret = data.result;
            }
        });
        
        return ret;
    }
    
    function initialize()
    {
        registerSubmitHandler();
        registerMailPrivNotifier();
    }
    
    // Public API
    return {
        init: initialize
    };
    
}(IServ));
