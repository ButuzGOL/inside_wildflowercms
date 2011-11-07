/**
 * Select Actions Component
 *
 * Used on lists with checkboxes. On checking some, action menus pop up.
 */
$.jlm.component('SelectActions', 'wild_posts.wf_index, wild_pages.wf_index, wild_assets.wf_index, wild_users.wf_index', function() {
    
     var selectActionsEl = $('.select-actions');
     var handledFormEl = $('form:first');
     
     // Mark all selectedEls items
     var selectedEls = $('input:checked', handledFormEl);
     if (selectedEls.size() > 0) {
         selectedEls.parent().parent('li').addClass('selected');
         selectActionsEl.show();
     }

     function selectionChanged() {
         selectedEls = $('input:checked', handledFormEl);
         
         if (selectedEls.size() > 0) {
             selectActionsEl.slideDown(100);
         } else {
             selectActionsEl.slideUp(100);
         }
         
         // Add selectedEls class
         $(this).parent().parent('li').toggleClass('selected');
         
         return true;
     }

     $('input[type=checkbox]', handledFormEl).click(selectionChanged);
     
     // Bind main actions
     $('.select-actions > a', handledFormEl).click(function() {
         // @TODO add AJAX submit
         handledFormEl.
            append('<input type="hidden" name="data[__action]" value="' + $(this).attr('rel') + '" />').
            submit();
         return false;
     });
     
     // Bind select All/None
     $('a[href=#SelectAll]', handledFormEl).click(function() {
         $('input:checkbox', handledFormEl).attr('checked', 'true').parent().parent('li').addClass('selected');
         return false;
     });
     
     $('a[href=#SelectNone]', handledFormEl).click(function() {
         selectActionsEl.slideUp(100);
         $('input:checkbox', handledFormEl).removeAttr('checked').parent().parent('li').removeClass('selected');
         return false;
     });
     
});
