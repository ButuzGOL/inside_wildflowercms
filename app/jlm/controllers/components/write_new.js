$.jlm.component('WriteNew', 'wild_posts.wf_index, wild_posts.wf_edit, wild_pages.wf_index, wild_pages.wf_edit', function() {
    
    $('#sidebar .add').click(function() {
        // if ($('.new-dialog').size() > 0) {
        //     return false;
        // }
        // Hide sidebar contet
        var sidebarContent = $('#sidebar ul');
        sidebarContent.hide();
        
        var buttonEl = $(this);
        var formAction = buttonEl.attr('href');
        
        var templatePath = 'posts/new_post';
        var parentPageOptions = null;
        if ($.jlm.params.controller == 'wild_pages') {
            templatePath = 'pages/new_page';
            parentPageOptions = $('.all-page-parents').html();
            parentPageOptions = parentPageOptions.replace('[Page]', '[WildPage]');
            parentPageOptions = parentPageOptions.replace('[parent_id_options]', '[parent_id]');
        }
        
        var dialogEl = $($.jlm.template(templatePath, { action: formAction, parentPageOptions: parentPageOptions }));
        
        var contentEl = $('#content-pad');
        
        contentEl.append(dialogEl);
        
        var toHeight = 230;
        
        var hiddenContentEls = contentEl.animate({
            height: toHeight
        }, 600, function() {
            // After the animation, focus the title input box
            $('.input:first input', dialogEl).focus();
        }).children().not(dialogEl).hide();
        
        // Bind cancel link
        $('.cancel-edit a', dialogEl).click(function() {
            dialogEl.remove();
            hiddenContentEls.show();
            contentEl.height('auto');
            sidebarContent.show();
            return false;
        });
        
        // Create link
        // TODO - first submit data by AJAX, on success redirect
        $('.submit input', dialogEl).click(function() {
            //$(this).attr('disabled', 'disabled').attr('value', '<l18n>Saving...</l18n>');
            return true;
        });
        
        return false;
    });
    
});