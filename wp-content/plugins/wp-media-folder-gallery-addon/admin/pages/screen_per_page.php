<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');
?>
<label class="screen-options" style="width: auto">
    <span for="img_per_page" class="img_per_page_label"><?php esc_html_e('Images per page', 'wp-media-folder-gallery-addon') ?></span>
    <input type="number" step="1" min="1" max="999" class="img_per_page" name="img_per_page" id="img_per_page"
           maxlength="3" value="<?php echo esc_html($limit) ?>">
</label>