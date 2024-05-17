<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');
?>
<div data-gallery_id="<?php echo (isset($gallery_id)) ? esc_attr($gallery_id) : 0 ?>"
     class="ju-main-wrapper wpmf-google-photo-wrap <?php echo (isset($header) && $header === 'no-header') ? 'google-photo-wrap-no-header' : '' ?>">
    <div class="photo-albums-list">
        <div class="tree_view">
            <ul>
                <li class="photo-album-item photo-image-item" data-id="photos">
                    <div class="photo-album-item-el">
                        <a class="album-title">
                            <div class="google_photo_icon"></div>
                            <span><?php esc_html_e('All Google photos', 'wpmfAddon') ?></span>
                        </a>
                    </div>
                </li>
                <?php
                if (!empty($albums)) {
                    foreach ($albums as $album) {
                        echo '<li class="photo-album-item" data-id="' . esc_attr($album->id) . '">
                            <div class="photo-album-item-el">
                                <a class="album-title">
                                    <i class="material-icons-outlined album-icon">photo_album</i>
                                    <span>' . esc_html($album->title) . '</span>
                                </a>
                            </div>
                        </li>';
                    }
                }
                ?>
            </ul>
        </div>
    </div>

    <div class="google-photo-wrap-right">
        <div class="google-photo-toolbar-right">
            <?php if (isset($header) && $header === 'no-header') : ?>
                <button class="ju-button ju-rect-button google-photo-import-photo-to-gallery google-photo-import-selection"><?php esc_html_e('Import selection', 'wpmfAddon') ?></button>
                <button class="ju-button ju-rect-button google-photo-import-album-to-gallery import-album-btn"><?php esc_html_e('Import album', 'wpmfAddon') ?></button>
            <?php else : ?>
                <button class="ju-button ju-rect-button google-photo-import-photo google-photo-import-selection"><?php esc_html_e('Import selection', 'wpmfAddon') ?></button>
                <button class="ju-button ju-rect-button google-photo-import-album import-album-btn"><?php esc_html_e('Import album', 'wpmfAddon') ?></button>
            <?php endif; ?>
        </div>
        <div class="photo-items-list" id="photo-items-list"></div>
        <button type="button" class="ju-button orange-button ju-rect-button load-more-photos"
                data-token=""><?php esc_html_e('Load more', 'wpmfAddon') ?></button>
    </div>
</div>