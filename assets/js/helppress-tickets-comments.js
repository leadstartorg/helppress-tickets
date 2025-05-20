(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Initialize TinyMCE on load
        if (typeof tinyMCE !== 'undefined' && $('#comment').length) {
            // TinyMCE is already initialized by wp_editor()
            
            // Add special handling for comment form submission
            $('#commentform').on('submit', function() {
                // Make sure TinyMCE content is synced to textarea
                if (tinyMCE.activeEditor && tinyMCE.activeEditor.id === 'comment') {
                    tinyMCE.activeEditor.save();
                }
            });
        }
    });
    
})(jQuery);