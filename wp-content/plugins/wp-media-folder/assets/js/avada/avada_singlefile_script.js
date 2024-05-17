(function ($) {
    $(document).ready(function () {
        $(document).on("click", '.wpmf_avada_select_file', function (e) {
            if (typeof frame !== "undefined") {
                frame.open();
                return;
            }

            // Create the media frame.
            var frame = wp.media({
                // Tell the modal to show only images.
                library: {
                    type: ['application/*', 'video', 'audio']
                }
            });

            // When an image is selected, run a callback.
            frame.on('select', function () {
                // Grab the selected attachment.
                var attachment = frame.state().get('selection').first().toJSON();
                $('.wpmf_avada_single_file input[name="url"]').val(attachment.url).change();
            });

            frame.open();
        });
    });
}(jQuery));