/* 
 * The MIT License
 *
 * Copyright 2017 Felix Jacobi.
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
    
    function resetForm()
    {
        $('#compose_group_mail_subject').val('');
        $('#compose_group_mail_group').val('');
        $('#compose_group_mail_recipients').val('');
        $('#compose_group_mail_body').val('');
        var attachments = $('[id^="compose_group_mail_attachments"');
                
        attachments.each(function () {
            $(this).val('');
        });
    }
    
    function initialize()
    {
        var form = $('[name="compose_group_mail"]');
        var target = IServ.Routing.generate('group_mail_send');
            
        form.submit(function(e) {      
            $.ajax({
                beforeSend: function() {
                    IServ.Loading.on('stsbl.mail-as-group.form');
                },
                success: function(data) {    
                    IServ.Loading.off('stsbl.mail-as-group.form');
                    
                    /*
                    NOT longer required, now handled by Message.JS automatically. \o/
                    
                    var i = 0;
                    while (i < data.messages.length) {
                        /*if (data.messages[i].type === 'error') {
                            IServ.Message.error(data.messages[i].message, 10000, false);
                        } else if (data.messages[i].type === 'success') {
                            IServ.Message.success(data.messages[i].message, 10000, false);
                        }
                        
                        i++;
                    }*/
                    
                    if (data.result === 'success') {
                        resetForm();
                    }
                },
                error: function() {
                    IServ.Loading.off('stsbl.mail-as-group.form');
                    IServ.Message.error(_('Unexcpected error during sending e-mail. Please try again.'), false, '.output');
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
    
    // Public API
    return {
        init: initialize
    };
    
}(IServ));
