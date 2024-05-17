<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');
wp_enqueue_script('thickbox');
wp_enqueue_style('thickbox');
$google_photo_config = get_option('_wpmfAddon_google_photo_config', true);
global $pagenow;
$themes = array(
    'default' => array('icon' => 'view_week', 'title' => __('Default', 'wp-media-folder-gallery-addon')),
    'masonry' => array('icon' => 'view_quilt', 'title' => __('Masonry', 'wp-media-folder-gallery-addon')),
    'portfolio' => array('icon' => 'view_stream', 'title' => __('Portfolio', 'wp-media-folder-gallery-addon')),
    'slider' => array('icon' => 'view_carousel', 'title' => __('Slider', 'wp-media-folder-gallery-addon')),
    'flowslide' => array('icon' => 'vertical_split', 'title' => __('Flow slide', 'wp-media-folder-gallery-addon')),
    'square_grid' => array('icon' => 'view_module', 'title' => __('Square grid', 'wp-media-folder-gallery-addon')),
    'material' => array('icon' => 'view_headline', 'title' => __('Material', 'wp-media-folder-gallery-addon')),
    'custom_grid' => array('icon' => 'view_quilt', 'title' => __('Custom grid', 'wp-media-folder-gallery-addon')),
);
?>

<?php foreach ($themes as $key => $theme) : ?>
    <div id="theme_<?php echo esc_attr($key) ?>" style="display: none">
        <img src="<?php echo esc_url(WPMF_GALLERY_ADDON_PLUGIN_URL . 'assets/images/themes/' . $key . '.png') ?>">
    </div>
<?php endforeach; ?>

<div id="new-gallery-popup" class="form_add_gallery white-popup mfp-hide">
    <div class="gallery-options-wrap">
        <div class="wpmf-gallery-fields">
            <div class="wpmf-gallery-field">
                <label class="wpmf-gallery-label"><?php esc_html_e('Gallery name', 'wp-media-folder-gallery-addon') ?></label>
                <input type="text" size="35" class="new-gallery-name gallery_name ju-input"
                       placeholder="<?php esc_html_e('Title', 'wp-media-folder-gallery-addon') ?>">
            </div>

            <div class="wpmf-gallery-field">
                <label class="wpmf-gallery-label"><?php esc_html_e('Gallery level', 'wp-media-folder-gallery-addon') ?></label>
                <div class="sl-gallery-parent-wrap">
                    <?php
                    $dropdown_options = array(
                        'show_option_none' => __('Parent gallery', 'wp-media-folder-gallery-addon'),
                        'option_none_value' => 0,
                        'hide_empty' => false,
                        'hierarchical' => true,
                        'orderby' => 'name',
                        'taxonomy' => WPMF_GALLERY_ADDON_TAXO,
                        'id' => 'new-gallery-parent',
                        'class' => 'wpmf-gallery-categories new-gallery-parent ju-select',
                        'name' => 'new-gallery-parent',
                        'selected' => 0
                    );
                    wp_dropdown_categories($dropdown_options);
                    ?>
                </div>
            </div>
        </div>

        <label class="wpmf-gallery-label"><?php esc_html_e('Gallery theme', 'wp-media-folder-gallery-addon') ?></label>
        <div class="wpmf-gallery-fields">
            <?php foreach ($themes as $key => $theme) : ?>
                <div class="wpmf-gallery-field wpmf-theme-item <?php echo ($key === 'masonry' ? 'selected' : '') ?>"
                     data-theme="<?php echo esc_html($key) ?>">
                    <span class="wpmf-theme-item__start-detail white-bg" role="presentation">
                        <i class="material-icons"><?php echo esc_attr($theme['icon']) ?></i>
                    </span>
                    <span class="wpmf-theme-item__text" title="<?php echo esc_html($theme['title']) ?>"><?php echo esc_html($theme['title']) ?></span>
                    <i class="material-icons ckecked-theme"> check_circle_outline </i>
                </div>
            <?php endforeach; ?>
            <input type="hidden" class="new-gallery-theme" value="masonry">
        </div>

        <div class="wpmf-gallery-fields">
            <button type="button" class="ju-button blue-button wpmf-save-gallery btn_create_gallery">
                <?php esc_html_e('Create', 'wp-media-folder-gallery-addon') ?>
            </button>

            <span class="spinner"></span>
        </div>
    </div>
</div>

<!-- Edit form -->
<div class="form_edit_gallery">
    <div class="gallery-toolbar">
        <div class="gallery-top-tabs-wrapper">
            <ul class="tabs gallery-ju-top-tabs">
                <li class="tab-link <?php echo (isset($_COOKIE['wpmf_gallery_tab_selected_' . site_url()]) && $_COOKIE['wpmf_gallery_tab_selected_' . site_url()] === 'main-gallery') ? 'current' : '' ?>" data-tab="main-gallery">
                    <?php esc_html_e('General', 'wp-media-folder-gallery-addon') ?>
                </li>

                <li class="tab-link <?php echo (isset($_COOKIE['wpmf_gallery_tab_selected_' . site_url()]) && $_COOKIE['wpmf_gallery_tab_selected_' . site_url()] === 'main-gallery-settings') ? 'current' : '' ?>" data-tab="main-gallery-settings">
                    <?php esc_html_e('Display settings & Shortcode', 'wp-media-folder-gallery-addon') ?>
                </li>

                <li class="tab-link <?php echo (isset($_COOKIE['wpmf_gallery_tab_selected_' . site_url()]) && $_COOKIE['wpmf_gallery_tab_selected_' . site_url()] === 'preview') ? 'current' : '' ?>" data-tab="preview">
                    <?php esc_html_e('Preview', 'wp-media-folder-gallery-addon') ?>
                </li>
            </ul>
        </div>
        <div>
            <button type="button" class="ju-button blue-button wpmf-save-gallery btn_edit_gallery <?php echo ($type === 'iframe') ? 'wpmf-modal-save' : '' ?>">
                <?php esc_html_e('Save', 'wp-media-folder-gallery-addon') ?>
            </button>

            <?php if ($type === 'iframe') : ?>
                <button type="button"
                        class="ju-button btn_insert_gallery"><?php esc_html_e('Insert', 'wp-media-folder-gallery-addon') ?></button>
            <?php endif; ?>

            <button type="button" class="ju-button wpmf-remove-imgs-btn wpmftippy"
                    data-wpmftippy="<?php esc_html_e('Delete selected images', 'wp-media-folder-gallery-addon') ?>"><?php esc_html_e('Delete', 'wp-media-folder-gallery-addon') ?></button>
        </div>
    </div>
    <div class="gallery-options-wrap">
        <div id="main-gallery" class="gallery-tab-content current">
            <div class="wpmf-gallery-fields">
                <div class="wpmf-gallery-field">
                    <label class="wpmf-gallery-label"><?php esc_html_e('Gallery name', 'wp-media-folder-gallery-addon') ?></label>
                    <input type="text" size="35" class="edit-gallery-name gallery_name ju-input"
                           placeholder="<?php esc_html_e('Title', 'wp-media-folder-gallery-addon') ?>">
                </div>

                <div class="wpmf-gallery-field">
                    <label class="wpmf-gallery-label"><?php esc_html_e('Gallery level', 'wp-media-folder-gallery-addon') ?></label>
                    <div class="sl-gallery-parent-wrap">
                        <?php
                        $dropdown_options = array(
                            'show_option_none' => __('Parent gallery', 'wp-media-folder-gallery-addon'),
                            'option_none_value' => 0,
                            'hide_empty' => false,
                            'hierarchical' => true,
                            'orderby' => 'name',
                            'taxonomy' => WPMF_GALLERY_ADDON_TAXO,
                            'id' => 'edit-gallery-parent',
                            'class' => 'wpmf-gallery-categories edit-gallery-parent ju-select',
                            'name' => 'edit-gallery-parent',
                            'selected' => 0
                        );
                        wp_dropdown_categories($dropdown_options);
                        ?>
                    </div>
                </div>

                <div class="wpmf-gallery-field">
                    <label class="wpmf-gallery-label"><?php esc_html_e('Gallery from folder', 'wp-media-folder-gallery-addon') ?></label>
                    <select class="wpmf-gallery-folder shortcode_param" data-param="folder"></select>
                    <p class="description wpmf-hidden wpmf-desc-msg"><?php esc_html_e('Please save to refresh', 'wp-media-folder-gallery-addon') ?></p>
                </div>

                <div class="wpmf-gallery-field" style="width: auto;text-align: center;">
                    <label class="wpmf-gallery-label wpmftippy"
                           data-wpmftippy="<?php esc_html_e('If a gallery is based on a media folder, when adding an image to that folder, it will also display in the gallery', 'wp-media-folder-gallery-addon') ?>">
                        <?php esc_html_e('Auto-add image in folder', 'wp-media-folder-gallery-addon') ?>
                    </label>
                    <br>
                    <div class="ju-switch-button" style="margin: 3px 0; width: 100%;">
                        <label class="switch" style="margin: 0">
                            <input type="checkbox" data-param="auto_from_folder" class="auto_from_folder shortcode_param" value="1">
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="wpmf-gallery-fields">
                <div class="wpmf-gallery-field" style="width: 100%; margin-right: 0; margin-bottom: 0">
                    <label class="wpmf-gallery-label"><?php esc_html_e('Upload images', 'wp-media-folder-gallery-addon') ?></label>
                    <div style="display: flex; align-items: center; justify-content: flex-start;">
                        <?php if ($pagenow === 'upload.php') : ?>
                            <button type="button" class="btn_import_image_fromwp" title="<?php esc_attr_e('From wordpress', 'wp-media-folder-gallery-addon') ?>">
                                <span class="wordpress_blue_icon"></span>
                            </button>
                        <?php else :?>
                            <a href="upload.php?page=media-folder-galleries&view=framemedia&width=5000&height=5000&noheader=1"
                               class="thickbox btn_modal_import_image_fromwp" title="<?php esc_attr_e('From wordpress', 'wp-media-folder-gallery-addon') ?>">
                                <span class="wordpress_blue_icon"></span>
                            </a>
                        <?php endif; ?>
                        <form id="wpmfglr_form_upload" method="post"
                              action="<?php echo esc_html(admin_url('admin-ajax.php')) ?>"
                              enctype="multipart/form-data">
                            <input class="hide" type="file" name="wpmf_gallery_file[]" multiple id="wpmf_gallery_file">
                            <input type="hidden" name="wpmf_gallery_nonce"
                                   value="<?php echo esc_html(wp_create_nonce('wpmf_gallery_nonce')) ?>">
                            <button type="button" class="btn_upload_from_pc"  title="<?php esc_attr_e('From computer', 'wp-media-folder-gallery-addon') ?>">
                                <span class="computer_icon"></span>
                            </button>
                            <input type="hidden" name="action" value="wpmfgallery">
                            <input type="hidden" name="up_gallery_id" class="up_gallery_id" value="0">
                            <input type="hidden" name="task" value="gallery_uploadfile">
                        </form>
                        <button type="button" class="wpmf_btn_video"><span class="btn_video_icon"></span></button>
                        <?php
                        if (!empty($google_photo_config['connected'])) :
                            ?>
                            <a href="#"
                               class="thickbox btn_import_from_google_photos" title="<?php esc_attr_e('From Google Photos', 'wp-media-folder-gallery-addon') ?>">
                                <span class="google_photo_icon"></span>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="wpmf-process-bar-full">
                        <div class="wpmf-process-bar" data-w="0"></div>
                    </div>
                </div>

            </div>

            <div class="wpmf-gallery-fields" style="display: inline-block; margin: 0;">
                <label class="wpmf-gallery-label" style="vertical-align: middle;display: inline-block;line-height: 38px; width: auto">
                    <?php esc_html_e('Gallery images', 'wp-media-folder-gallery-addon') ?>
                </label>

                <?php
                $limit = get_option('wpmf_gallery_img_per_page');
                require_once(WPMF_GALLERY_ADDON_PLUGIN_DIR . '/admin/pages/screen_per_page.php');
                ?>
            </div>

            <div class="wpmf-gallery-selection-wrap">
                <img class="wpmf-gallery-loading" src="<?php echo esc_url(WPMF_GALLERY_ADDON_PLUGIN_URL . '/assets/images/material_design_loading.gif') ?>">
                <div class="wpmf_gallery_selection" id="wpmf_gallery_selection">
                    <div class="wpmf-grid"></div>
                </div>
                <div class="wpmf-gallery-image-pagging"></div>
            </div>
            
            <p class="gallery-toolbar-bottom" style="display: none">
                <button type="button" class="ju-button blue-button wpmf-save-gallery btn_edit_gallery <?php echo ($type === 'iframe') ? 'wpmf-modal-save' : '' ?>">
                    <?php esc_html_e('Save', 'wp-media-folder-gallery-addon') ?>
                </button>
            </p>
        </div>

        <div id="main-gallery-settings" class="gallery-tab-content" data-theme="default">
            <label class="wpmf-gallery-label"><?php esc_html_e('Gallery theme', 'wp-media-folder-gallery-addon') ?></label>
            <div class="wpmf-gallery-fields">
                <?php foreach ($themes as $key => $theme) : ?>
                    <div class="wpmf-gallery-field wpmf-theme-item"
                         data-theme="<?php echo esc_html($key) ?>">
                    <span class="wpmf-theme-item__start-detail white-bg" role="presentation">
                        <i class="material-icons"><?php echo esc_attr($theme['icon']) ?></i>
                    </span>
                        <span class="wpmf-theme-item__text" title="<?php echo esc_attr($theme['title']) ?>"><?php echo esc_html($theme['title']) ?></span>
                        <i class="material-icons ckecked-theme"> check_circle_outline </i>
                    </div>
                <?php endforeach; ?>
                <input type="hidden" class="edit-gallery-theme">
            </div>

            <div class="wpmf-gallery-fields">
                <div class="wpmf-gallery-field wpmf-gallery-maronry-layout">
                    <label class="wpmf-gallery-label"><?php esc_html_e('Masonry layout', 'wp-media-folder-gallery-addon') ?></label>
                    <div>
                        <select class="edit-gallery-layout ju-select shortcode_param" data-param="layout"
                                name="edit-gallery-layout">
                            <option value="vertical"><?php esc_html_e('Vertical', 'wp-media-folder-gallery-addon') ?></option>
                            <option value="horizontal"><?php esc_html_e('Horizontal', 'wp-media-folder-gallery-addon') ?></option>
                        </select>
                    </div>
                </div>

                <div class="wpmf-gallery-field wpmf-gallery-maronry-layout wpmf_row_height">
                    <label class="wpmf-gallery-label"><?php esc_html_e('Row height', 'wp-media-folder-gallery-addon') ?></label>
                    <div>
                        <input type="number" min="100" step="10" max="300" data-param="row_height" class="edit-gallery-row_height shortcode_param">
                    </div>
                </div>

                <div class="wpmf-gallery-field wpmf_aspect_ratio">
                    <label class="wpmf-gallery-label"><?php esc_html_e('Aspect ratio', 'wp-media-folder-gallery-addon') ?></label>
                    <div>
                        <select class="edit-gallery-aspect_ratio ju-select shortcode_param" data-param="aspect_ratio"
                                name="edit-gallery-aspect_ratio">
                            <option value="default"><?php esc_html_e('Default', 'wp-media-folder-gallery-addon') ?></option>
                            <option value="1_1">1:1</option>
                            <option value="3_2">3:2</option>
                            <option value="2_3">2:3</option>
                            <option value="4_3">4:3</option>
                            <option value="3_4">3:4</option>
                            <option value="16_9">16:9</option>
                            <option value="9_16">9:16</option>
                            <option value="21_9">21:9</option>
                            <option value="9_21">9:21</option>
                        </select>
                    </div>
                </div>
            </div>

            <label class="wpmf-gallery-label"><?php esc_html_e('Theme hover selection', 'wp-media-folder-gallery-addon') ?></label>
            <div class="wpmf-gallery-fields">
                <a href="#hover_color" class="wpmf-gallery-field wpmf-hover-item" data-type="hover_color">
                    <span class="wpmf-theme-item__start-detail white-bg" role="presentation">
                        <i class="material-icons-outlined">format_paint</i>
                    </span>
                    <span class="wpmf-theme-item__text"><?php esc_html_e('Hover color', 'wp-media-folder-gallery-addon') ?></span>
                </a>
                <a href="#hover_title" class="wpmf-gallery-field wpmf-hover-item" data-type="title">
                    <span class="wpmf-theme-item__start-detail white-bg" role="presentation">
                        <i class="material-icons-outlined">title</i>
                    </span>
                    <span class="wpmf-theme-item__text"><?php esc_html_e('Title', 'wp-media-folder-gallery-addon') ?></span>
                </a>
                <a href="#hover_desc" class="wpmf-gallery-field wpmf-hover-item" data-type="desc">
                    <span class="wpmf-theme-item__start-detail white-bg" role="presentation">
                        <i class="material-icons-outlined">description</i>
                    </span>
                    <span class="wpmf-theme-item__text"><?php esc_html_e('Description', 'wp-media-folder-gallery-addon') ?></span>
                </a>
            </div>

            <div id="hover_color" class="hover_form white-popup mfp-hide">
                <h3><?php esc_html_e('Hover', 'wp-media-folder-gallery-addon') ?></h3>
                <div class="hover_color_field_wrap">
                    <label><b><?php esc_html_e('Hover color', 'wp-media-folder-gallery-addon') ?></b></label>
                    <input type="text" value="#000" class="wpmf_color_field hover_color_input shortcode_param" data-param="hover_color" data-default-color="#000" />
                </div>
                <p>
                    <label><b><?php esc_html_e('Hover opacity', 'wp-media-folder-gallery-addon') ?></b></label>
                    <input type="number" step="0.1" min="0" max="1" value="0.4" data-param="hover_opacity" class="hover_opacity_input shortcode_param"/>
                </p>
                <p style="text-align: center">
                    <button class="hover_save ju-button blue-button">
                        <?php esc_html_e('Save', 'wp-media-folder-gallery-addon') ?>
                    </button>
                </p>
            </div>

            <div id="hover_title" class="hover_form white-popup mfp-hide">
                <h3><?php esc_html_e('Title', 'wp-media-folder-gallery-addon') ?></h3>
                <p>
                    <label><b><?php esc_html_e('Position', 'wp-media-folder-gallery-addon') ?></b></label>
                    <select class="hover_title_position shortcode_param" data-param="hover_title_position">
                        <option value="none"><?php esc_html_e('None', 'wp-media-folder-gallery-addon') ?></option>
                        <option value="top_left"><?php esc_html_e('Top left', 'wp-media-folder-gallery-addon') ?></option>
                        <option value="top_right"><?php esc_html_e('Top right', 'wp-media-folder-gallery-addon') ?></option>
                        <option value="top_center"><?php esc_html_e('Top center', 'wp-media-folder-gallery-addon') ?></option>
                        <option value="bottom_left"><?php esc_html_e('Bottom left', 'wp-media-folder-gallery-addon') ?></option>
                        <option value="bottom_right"><?php esc_html_e('Bottom right', 'wp-media-folder-gallery-addon') ?></option>
                        <option value="bottom_center"><?php esc_html_e('Bottom center', 'wp-media-folder-gallery-addon') ?></option>
                        <option value="center_center"><?php esc_html_e('Center center', 'wp-media-folder-gallery-addon') ?></option>
                    </select>
                </p>

                <p>
                    <label><b><?php esc_html_e('Size', 'wp-media-folder-gallery-addon') ?></b></label>
                    <input type="number" value="16" class="hover_title_size shortcode_param" data-param="hover_title_size" />
                </p>

                <div class="hover_color_field_wrap">
                    <label><b><?php esc_html_e('Color', 'wp-media-folder-gallery-addon') ?></b></label>
                    <input type="text" value="#fff" class="wpmf_color_field hover_title_color_input shortcode_param" data-param="hover_title_color" data-default-color="#fff" />
                </div>

                <p style="text-align: center">
                    <button class="hover_save ju-button blue-button">
                        <?php esc_html_e('Save', 'wp-media-folder-gallery-addon') ?>
                    </button>
                </p>
            </div>

            <div id="hover_desc" class="hover_form white-popup mfp-hide">
                <h3><?php esc_html_e('Description', 'wp-media-folder-gallery-addon') ?></h3>
                <p>
                    <label><b><?php esc_html_e('Position', 'wp-media-folder-gallery-addon') ?></b></label>
                    <select class="hover_desc_position shortcode_param" data-param="hover_desc_position">
                        <option value="none"><?php esc_html_e('None', 'wp-media-folder-gallery-addon') ?></option>
                        <option value="top_left"><?php esc_html_e('Top left', 'wp-media-folder-gallery-addon') ?></option>
                        <option value="top_right"><?php esc_html_e('Top right', 'wp-media-folder-gallery-addon') ?></option>
                        <option value="top_center"><?php esc_html_e('Top Center', 'wp-media-folder-gallery-addon') ?></option>
                        <option value="bottom_left"><?php esc_html_e('Bottom left', 'wp-media-folder-gallery-addon') ?></option>
                        <option value="bottom_right"><?php esc_html_e('Bottom right', 'wp-media-folder-gallery-addon') ?></option>
                        <option value="bottom_center"><?php esc_html_e('Bottom Center', 'wp-media-folder-gallery-addon') ?></option>
                        <option value="center_center"><?php esc_html_e('Center Center', 'wp-media-folder-gallery-addon') ?></option>
                    </select>
                </p>

                <p>
                    <label><b><?php esc_html_e('Size', 'wp-media-folder-gallery-addon') ?></b></label>
                    <input type="number" value="14" class="hover_desc_size shortcode_param" data-param="hover_desc_size" />
                </p>

                <div class="hover_color_field_wrap">
                    <label><b><?php esc_html_e('Color', 'wp-media-folder-gallery-addon') ?></b></label>
                    <input type="text" value="#fff" class="wpmf_color_field hover_desc_color_input shortcode_param" data-param="hover_desc_color" data-default-color="#fff" />
                </div>

                <p style="text-align: center">
                    <button class="hover_save ju-button blue-button">
                        <?php esc_html_e('Save', 'wp-media-folder-gallery-addon') ?>
                    </button>
                </p>
            </div>

            <div class="wpmf-gallery-fields wpmf-gallery-fields-custom_grid">
                <div class="wpmf-gallery-field">
                    <label class="wpmf-gallery-label"><?php esc_html_e('Columns', 'wp-media-folder-gallery-addon') ?></label>
                    <div>
                        <select class="edit-gallery-columns ju-select shortcode_param" data-param="columns"
                                name="edit-gallery-columns">
                            <?php for ($i = 1; $i <= 8; $i ++) { ?>
                                <option value="<?php echo esc_html($i) ?>">
                                    <?php echo esc_html($i) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="wpmf-gallery-fields">
                <div class="wpmf-gallery-field">
                    <label class="wpmf-gallery-label"><?php esc_html_e('Gallery image size', 'wp-media-folder-gallery-addon') ?></label>
                    <div>
                        <select class="edit-gallery-size ju-select shortcode_param" data-param="size"
                                name="edit-gallery-size">
                            <?php
                            $sizes_value = json_decode(get_option('wpmf_gallery_image_size_value'));
                            $sizes       = apply_filters('image_size_names_choose', array(
                                'thumbnail' => __('Thumbnail', 'wp-media-folder-gallery-addon'),
                                'medium'    => __('Medium', 'wp-media-folder-gallery-addon'),
                                'large'     => __('Large', 'wp-media-folder-gallery-addon'),
                                'full'      => __('Full Size', 'wp-media-folder-gallery-addon'),
                            ));
                            ?>

                            <?php foreach ($sizes_value as $key) : ?>
                                <?php if (!empty($sizes[$key])) : ?>
                                    <option value="<?php echo esc_attr($key); ?>">
                                        <?php echo esc_html($sizes[$key]); ?>
                                    </option>
                                <?php endif; ?>

                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="wpmf-gallery-field">
                    <label class="wpmf-gallery-label"><?php esc_html_e('Lightbox size', 'wp-media-folder-gallery-addon') ?></label>
                    <div>
                        <select class="edit-gallery-targetsize ju-select shortcode_param" data-param="targetsize"
                                name="edit-gallery-targetsize">
                            <?php
                            $sizes_value = json_decode(get_option('wpmf_gallery_image_size_value'));
                            $sizes       = apply_filters('image_size_names_choose', array(
                                'thumbnail' => __('Thumbnail', 'wp-media-folder-gallery-addon'),
                                'medium'    => __('Medium', 'wp-media-folder-gallery-addon'),
                                'large'     => __('Large', 'wp-media-folder-gallery-addon'),
                                'full'      => __('Full Size', 'wp-media-folder-gallery-addon'),
                            ));
                            ?>

                            <?php foreach ($sizes_value as $key) : ?>
                                <?php if (!empty($sizes[$key])) : ?>
                                    <option value="<?php echo esc_attr($key); ?>">
                                        <?php echo esc_html($sizes[$key]); ?>
                                    </option>
                                <?php endif; ?>

                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="wpmf-gallery-fields">
                <div class="wpmf-gallery-field">
                    <label class="wpmf-gallery-label"><?php esc_html_e('Action on click', 'wp-media-folder-gallery-addon') ?></label>
                    <div>
                        <select class="edit-gallery-link ju-select shortcode_param" data-param="link"
                                name="edit-gallery-link">
                            <option value="file">
                                <?php esc_html_e('Lightbox', 'wp-media-folder-gallery-addon'); ?>
                            </option>
                            <option value="post">
                                <?php esc_html_e('Attachment Page', 'wp-media-folder-gallery-addon'); ?>
                            </option>
                            <option value="none">
                                <?php esc_html_e('None', 'wp-media-folder-gallery-addon'); ?>
                            </option>
                        </select>
                    </div>
                </div>

                <div class="wpmf-gallery-field">
                    <label class="wpmf-gallery-label"><?php esc_html_e('Order by', 'wp-media-folder-gallery-addon') ?></label>
                    <div>
                        <select class="edit-gallery-orderby ju-select shortcode_param" data-param="orderby"
                                name="edit-gallery-orderby">
                            <option value="post__in">
                                <?php esc_html_e('Custom', 'wp-media-folder-gallery-addon'); ?>
                            </option>
                            <option value="rand">
                                <?php esc_html_e('Random', 'wp-media-folder-gallery-addon'); ?>
                            </option>
                            <option value="title">
                                <?php esc_html_e('Title', 'wp-media-folder-gallery-addon'); ?>
                            </option>
                            <option value="date">
                                <?php esc_html_e('Date', 'wp-media-folder-gallery-addon'); ?>
                            </option>
                        </select>
                    </div>
                </div>

                <div class="wpmf-gallery-field">
                    <label class="wpmf-gallery-label"><?php esc_html_e('Order', 'wp-media-folder-gallery-addon') ?></label>
                    <div>
                        <select class="edit-gallery-order ju-select shortcode_param" data-param="order"
                                name="edit-gallery-order">
                            <option value="ASC">
                                <?php esc_html_e('Ascending', 'wp-media-folder-gallery-addon'); ?>
                            </option>
                            <option value="DESC">
                                <?php esc_html_e('Descending', 'wp-media-folder-gallery-addon'); ?>
                            </option>
                        </select>
                    </div>
                </div>

                <div class="wpmf-gallery-field">
                    <label class="wpmf-gallery-label"><?php esc_html_e('Gutter', 'wp-media-folder-gallery-addon') ?></label>
                    <div>
                        <select class="edit-gallery-gutterwidth ju-select shortcode_param" data-param="gutterwidth"
                                name="edit-gallery-gutterwidth">
                            <option value="0">0</option>
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="15">15</option>
                            <option value="20">20</option>
                            <option value="25">25</option>
                            <option value="30">30</option>
                            <option value="35">35</option>
                            <option value="40">40</option>
                            <option value="45">45</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="wpmf-gallery-fields wpmf-gallery-fields-slider">
                <div class="wpmf-gallery-field">
                    <label class="wpmf-gallery-label"><?php esc_html_e('Transition type', 'wp-media-folder-gallery-addon') ?></label>
                    <div>
                        <select class="edit-gallery-animation ju-select shortcode_param" data-param="animation"
                                name="edit-gallery-animation">
                            <option value="slide">
                                <?php esc_html_e('Slide', 'wp-media-folder-gallery-addon'); ?>
                            </option>
                            <option value="fade">
                                <?php esc_html_e('Fade', 'wp-media-folder-gallery-addon'); ?>
                            </option>
                        </select>
                    </div>
                </div>

                <div class="wpmf-gallery-field">
                    <label class="wpmf-gallery-label"><?php esc_html_e('Transition duration (ms)', 'wp-media-folder-gallery-addon') ?></label>
                    <div>
                        <input type="number" class="edit-gallery-duration ju-input shortcode_param" data-param="duration"
                                name="edit-gallery-duration" value="4000">
                    </div>
                </div>

                <div class="wpmf-gallery-field">
                    <label class="wpmf-gallery-label"><?php esc_html_e('Automatic animation', 'wp-media-folder-gallery-addon') ?></label>
                    <select class="edit-gallery-auto_animation ju-select shortcode_param" data-param="auto_animation"
                            name="edit-gallery-auto_animation">
                        <option value="1">
                            <?php esc_html_e('On', 'wp-media-folder-gallery-addon'); ?>
                        </option>
                        <option value="0">
                            <?php esc_html_e('Off', 'wp-media-folder-gallery-addon'); ?>
                        </option>
                    </select>
                </div>

                <div class="wpmf-gallery-field">
                    <label class="wpmf-gallery-label"><?php esc_html_e('Number lines', 'wp-media-folder-gallery-addon') ?></label>
                    <select class="edit-gallery-number_lines ju-select shortcode_param" data-param="number_lines"
                            name="edit-gallery-number_lines">
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                    </select>
                </div>
            </div>

            <div class="wpmf-gallery-fields wpmf-gallery-fields-switch">
                <div class="wpmf-gallery-field wpmf-gallery-fields-flowslide">
                    <label class="wpmf-gallery-label" style="width: auto; margin: 0; line-height: 50px;">
                        <?php esc_html_e('Show buttons', 'wp-media-folder-gallery-addon') ?>
                    </label>
                    <div class="ju-switch-button">
                        <label class="switch">
                            <input type="checkbox" data-param="show_buttons" class="gallery_flow_show-buttons shortcode_param" value="1">
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>

                <div class="wpmf-gallery-field">
                    <label class="wpmf-gallery-label wpmftippy" style="width: auto; margin: 0; line-height: 50px;"
                           data-wpmftippy="<?php esc_html_e('Load gallery tree navigation', 'wp-media-folder-gallery-addon') ?>">
                        <?php esc_html_e('Gallery navigation', 'wp-media-folder-gallery-addon') ?>
                    </label>
                    <div class="ju-switch-button">
                        <label class="switch">
                            <input type="checkbox" data-param="display_tree" class="gallery_display_tree shortcode_param" value="1">
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>
                <div class="wpmf-gallery-field">
                    <label class="wpmf-gallery-label wpmftippy" style="width: auto; margin: 0; line-height: 50px;"
                           data-wpmftippy="<?php esc_html_e('Display image
                        tags as display filter', 'wp-media-folder-gallery-addon') ?>">
                        <?php esc_html_e('Images tags', 'wp-media-folder-gallery-addon') ?></label>
                    <div class="ju-switch-button">
                        <label class="switch">
                            <input type="checkbox" data-param="display_tag" class="gallery_display_tag shortcode_param" value="1">
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="wpmf-gallery-fields">
                <div class="wpmf-gallery-field">
                    <label class="wpmf-gallery-label wpmftippy"
                           data-wpmftippy="<?php esc_html_e('Set with for gallery tree navigation', 'wp-media-folder-gallery-addon') ?>" style="text-transform: none">
                        <?php esc_html_e('Gallery navigation width (px)', 'wp-media-folder-gallery-addon') ?>
                    </label>
                    <input type="number" min="250" data-param="tree_width" class="gallery_tree_width shortcode_param">
                </div>
            </div>

            <div class="wpmf-gallery-fields">
                <div class="wpmf-gallery-field" style="width: 100%">
                    <label class="wpmf-gallery-label" style="width: 100%">
                        <?php esc_html_e('Shortcode', 'wp-media-folder-gallery-addon') ?>
                    </label>
                    <input title type="text" class="gallery_shortcode_input" readonly value="" style="width: calc(100% - 50px); vertical-align: middle;">
                    <i data-wpmftippy="<?php esc_html_e('Copy shortcode', 'wp-media-folder-gallery-addon'); ?>"
                       class="material-icons copy_shortcode_gallery wpmftippy">content_copy</i>
                </div>
            </div>

            <p class="gallery-toolbar-bottom" style="display:none;">
                <button type="button" class="ju-button blue-button wpmf-save-gallery btn_edit_gallery <?php echo ($type === 'iframe') ? 'wpmf-modal-save' : '' ?>">
                    <?php esc_html_e('Save', 'wp-media-folder-gallery-addon') ?>
                </button>
            </p>
        </div>

        <div id="preview" class="gallery-tab-content">
            <div class="preview-wrap"></div>
        </div>
    </div>
</div>

<div id="wpmf-drop-overlay" class="wpmf-drop-overlay">
    <div class="wpmf-overlay-inner"><?php esc_html_e('DROP IMAGES HERE TO UPLOAD', 'wp-media-folder-gallery-addon') ?></div>
</div>