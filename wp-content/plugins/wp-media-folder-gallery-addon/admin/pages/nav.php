<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');
?>
<div class="tablenav-pages">
    <span class="displaying-num">
        <span class="count"><?php echo esc_html($post_count) ?></span>
        <?php esc_html_e(' items', 'wp-media-folder-gallery-addon') ?>
    </span>
    <span class="pagination-links">
        <a class="wpmf-number-page glr-first-page first-page"
           data-page_count="<?php echo esc_html($page_count) ?>"><span
                    class="screen-reader-text"><?php esc_html_e('First page', 'wp-media-folder-gallery-addon') ?></span><span
                    aria-hidden="true">«</span></a>
        <a class="wpmf-number-page glr-prev-page prev-page" data-page_count="<?php echo esc_html($page_count) ?>"><span
                    class="screen-reader-text"><?php esc_html_e('Prev page', 'wp-media-folder-gallery-addon') ?></span><span
                    aria-hidden="true">‹</span></a>
        <span class="paging-input"><label for="current-page-selector"
                                          class="screen-reader-text">
                <?php esc_html_e('Current Page', 'wp-media-folder-gallery-addon') ?></label><input
                    class="current-page glr-current-page ju-input" id="current-page-selector" type="text" name="paged"
                    value="<?php echo esc_attr($current_page_nav) ?>" size="10"
                    aria-describedby="table-paging"><span class="tablenav-paging-text"> of <span
                        class="total-pages"><?php echo esc_html($page_count) ?></span></span></span>
        <a class="wpmf-number-page glr-next-page next-page" data-page_count="<?php echo esc_html($page_count) ?>"><span
                    class="screen-reader-text"><?php esc_html_e('Next page', 'wp-media-folder-gallery-addon') ?></span><span
                    aria-hidden="true">›</span></a>
        <a class="wpmf-number-page glr-last-page last-page" data-page_count="<?php echo esc_html($page_count) ?>"><span
                    class="screen-reader-text"><?php esc_html_e('Last page', 'wp-media-folder-gallery-addon') ?></span><span
                    aria-hidden="true">»</span></a>
    </span>
</div>