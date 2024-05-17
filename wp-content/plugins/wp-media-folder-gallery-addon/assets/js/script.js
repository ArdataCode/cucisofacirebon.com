/**
 * Main WP Media Gallery addon script
 */
var wpmfGalleryModule;
(function ($) {
    if (typeof ajaxurl === "undefined") {
        ajaxurl = wpmf.vars.ajaxurl;
    }

    wpmfGalleryModule = {
        upload_from_pc: false,
        wpmf_current_gallery: 0, // current gallery selected
        target_gallery: 0,
        is_gallery_loading: false,
        is_perpage_change: false,
        current_page_nav: 1, // current page for images gallery selection
        gallery_details: {},
        shouldconfirm: false,
        custom_gird_gutter_change: false,
        is_resizing: true,
        current_tab: 'main-gallery',
        events: [], // event handling
        init: function () {
            var folder_options_html = '';
            var space = '&nbsp;&nbsp;';
            var list_cloud = [];
            var list_local = [];
            $('.form_edit_gallery input, .form_edit_gallery select').on('change', function () {
                if ($(this).data('param') === 'gutterwidth') {
                    wpmfGalleryModule.custom_gird_gutter_change = true;
                }
                wpmfGalleryModule.shouldconfirm = true;
                window.onbeforeunload = function() {
                    if (wpmfGalleryModule.shouldconfirm) {
                        return true;
                    }
                };
            });

            $('.form_edit_gallery .edit-gallery-name').on('keyup', function () {
                wpmfGalleryModule.shouldconfirm = true;
                window.onbeforeunload = function() {
                    if (wpmfGalleryModule.shouldconfirm) {
                        return true;
                    }
                };
            });

            $.each(wpmf.vars.wpmf_categories, function (i, v) {
                if (parseInt(v.id) !== 0) {
                    if (typeof v.drive_type !== 'undefined' && v.drive_type !== '') {
                        list_cloud.push({id: v.id, label: v.label, depth: v.depth});
                    } else {
                        list_local.push({id: v.id, label: v.label, depth: v.depth});
                    }
                } else {
                    list_local.push({id: 0, label: v.label, depth: 0});
                }
            });

            $.each(list_local, function (i, v) {
                if (typeof v.depth !== "undefined" && parseInt(v.depth) > 0) {
                    folder_options_html += '<option value="' + v.id + '">' + space.repeat(v.depth) + v.label + '</option>';
                } else {
                    folder_options_html += '<option value="' + v.id + '">' + v.label + '</option>';
                }
            });

            $.each(list_cloud, function (i, v) {
                if (typeof v.depth !== "undefined" && parseInt(v.depth) > 0) {
                    folder_options_html += '<option value="' + v.id + '">' + space.repeat(v.depth) + v.label + '</option>';
                } else {
                    folder_options_html += '<option value="' + v.id + '">' + v.label + '</option>';
                }
            });

            $('.wpmf-gallery-folder').html(folder_options_html);


            tippy('.wpmf-theme-item', {
                theme: 'wpmftheme',
                animation: 'scale',
                animateFill: false,
                maxWidth: 320,
                duration: 0,
                arrow: false,
                allowHTML: true,
                onShow(instance) {
                    var theme = $(instance.reference).data('theme');
                    instance.popper.hidden = false;
                    instance.setContent($('#theme_' + theme).html());
                }
            });

            // tabs
            $('.gallery-ju-top-tabs li').click(function () {
                var tab_id = $(this).attr('data-tab');
                wpmfGalleryModule.setCookie('wpmf_gallery_tab_selected_' + wpmf_glraddon.vars.site_url, tab_id, 365);
                $('.gallery-ju-top-tabs li').removeClass('current');
                $('.gallery-tab-content').removeClass('current');
                $(this).addClass('current');
                $("#" + tab_id).addClass('current');
                if (tab_id === 'preview') {
                    setTimeout(function () {
                        wpmfGalleryModule.loadGalleryPreview();
                    }, 300);
                } else if (tab_id === 'main-gallery') {
                    var theme = $('#main-gallery-settings .wpmf-theme-item.selected').data('theme');
                    if (wpmfGalleryModule.current_tab !== 'main-gallery') {
                        if ((!$('.gallery-attachment.ui-draggable').length || wpmfGalleryModule.custom_gird_gutter_change) && theme === 'custom_grid') {
                            setTimeout(function () {
                                $('.gallery-attachment').each(function () {
                                    if ($(this).hasClass('ui-resizable')) {
                                        $(this).resizable("destroy");
                                    }
                                });
                                wpmfGalleryModule.initPackery();
                            }, 200);
                        } else {
                            var $container = $('.wpmf_gallery_selection');
                            if (theme !== 'custom_grid') {
                                if ($container.hasClass('wpmfInitPackery')) {
                                    $container.packery('destroy');
                                    $container.removeClass('wpmfInitPackery custom_grid').attr('style', '');
                                }
                                $('.gallery-attachment').each(function () {
                                    if ($(this).hasClass('ui-resizable')) {
                                        $(this).draggable('destroy');
                                        $(this).resizable("destroy");
                                        $(this).attr('style', '');
                                    }
                                });
                                wpmfGalleryModule.sortAbleImages('.wpmf_gallery_selection');
                            }
                        }
                    }
                }
                wpmfGalleryModule.custom_gird_gutter_change = false;
                wpmfGalleryModule.current_tab = tab_id;
            });

            // show popup inline
            if ($().magnificPopup) {
                $('.new-gallery-popup').magnificPopup({
                    type: 'inline',
                    closeBtnInside: true,
                    midClick: true
                });

                $('.wpmf-hover-item').magnificPopup({
                    type: 'inline',
                    mainClass: 'hover_color_popup',
                    closeBtnInside: true,
                    midClick: true,
                    closeOnBgClick: false
                });
            }

            wpmfGalleryModule.uploadImages();
            /* Show tooltip for some icon */
            wpmfGalleryModule.showToolTip();

            wpmfGalleryModule.renderContextMenu();
            wpmfGalleryModule.bindEvent();
            wpmfGalleryModule.eventImages();

            var eventMethod = window.addEventListener ? "addEventListener" : "attachEvent";
            var eventer = window[eventMethod];
            var messageEvent = eventMethod === "attachEvent" ? "onmessage" : "message";

            // Listen to message from child window
            eventer(messageEvent, function (e) {
                var res = e.data;
                if (typeof res !== "undefined" && typeof res.type !== "undefined" && res.type === "wpmf_google_photo_gallery_import") {
                    tb_remove();
                    wpmfGalleryModule.changeGallery(wpmfGalleryModule.wpmf_current_gallery);
                }
            }, false);
        },

        showToolTip: function () {
            /* Show tooltip for some icon */
            tippy('.wpmftippy', {
                theme: 'wpmf',
                animation: 'scale',
                animateFill: false,
                maxWidth: 300,
                duration: 0,
                arrow: true,
                onShow(instance) {
                    instance.popper.hidden = false;
                    instance.setContent($(instance.reference).data('wpmftippy'));
                }
            });
        },

        rowHeightStatus: function() {
            var theme = $('#main-gallery-settings .wpmf-theme-item.selected').data('theme');
            if (theme !== 'masonry' || $('.edit-gallery-layout').val() === 'vertical') {
                $('.wpmf_row_height').hide();
            } else {
                $('.wpmf_row_height').show();
            }
        },

        updateThemeSelection: function (theme, type = 'edit') {
            if (type === 'edit') {
                $('.edit-gallery-theme').val(theme).change();
                $('.form_edit_gallery .wpmf-theme-item').removeClass('selected');
                $('.form_edit_gallery .wpmf-theme-item[data-theme="' + theme + '"]').addClass('selected');
                $('#main-gallery-settings, .gallery-options-wrap').attr('data-theme', theme);
                wpmfGalleryModule.renderShortcode(theme);
                wpmfGalleryModule.rowHeightStatus();
            } else {
                $('.new-gallery-theme').val(theme).change();
                $('.form_add_gallery .wpmf-theme-item').removeClass('selected');
                $('.form_add_gallery .wpmf-theme-item[data-theme="' + theme + '"]').addClass('selected');
            }
        },

        resetNewGalleryFrom: function() {
            $('.new-gallery-name').val('').removeClass('wpmf-field-require').change();
            $('.new-gallery-parent').val(0).change();
            $('.new-gallery-theme').val('masonry').change();
            $('.form_add_gallery .wpmf-theme-item').removeClass('selected');
            $('.form_add_gallery .wpmf-theme-item[data-theme="masonry"]').addClass('selected');
        },

        fileUpload: function () {
            $('.WpmfGalleryList').fileupload({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                autoUpload: true,
                maxFileSize: 104857600,
                acceptFileTypes: new RegExp($(this).find('input[name="acceptfiletypes"]').val(), "i"),
                messages: {
                    maxNumberOfFiles: wpmf_glraddon.vars.maxNumberOfFiles,
                    acceptFileTypes: wpmf_glraddon.vars.acceptFileTypes,
                    maxFileSize: wpmf_glraddon.vars.maxFileSize,
                    minFileSize: wpmf_glraddon.vars.minFileSize
                },
                limitConcurrentUploads: 3,
                disableImageLoad: true,
                disableImageResize: true,
                disableImagePreview: true,
                disableAudioPreview: true,
                disableVideoPreview: true,
                uploadTemplateId: null,
                downloadTemplateId: null,
                add: function (e, data) {
                    if (wpmfGalleryModule.upload_from_pc) {
                        return;
                    }

                    $('.wpmf-drop-overlay').removeClass('in');
                    if (!$('.fileupload-container').length) {
                        return;
                    }
                    $.each(data.files, function (index, file) {
                        file.hash = file.name.hashCode() + '_' + Math.floor(Math.random() * 1000000);
                        file = wpmfGalleryModule.validateFile(file);
                        var row = wpmfGalleryModule.renderFileUploadRow(file);
                        if (file.error !== false) {
                            data.files.splice(index, 1);
                        }
                    });

                    if (data.files.length > 0) {
                        data.process().done(function () {
                            data.submit();
                        });
                    }

                },
                done: function (e, data) {
                    if (data.result !== false) {
                        if (!$('.template-upload').length) {
                            wpmfGalleryModule.changeGallery(wpmfGalleryModule.wpmf_current_gallery, 'upload');
                        }
                    }
                }
            }).on('fileuploadsubmit', function (e, data) {
                $.each(data.files, function (index, file) {
                    wpmfGalleryModule.uploadStart(file);
                });

                data.formData = {
                    action: 'wpmfgallery',
                    task: 'gallery_uploadfile',
                    up_gallery_id: wpmfGalleryModule.wpmf_current_gallery,
                    wpmf_gallery_nonce: wpmf_glraddon.vars.wpmf_gallery_nonce
                };

            }).on('fileuploadprogress', function (e, data) {
                var progress = parseInt(data.loaded / data.total * 100, 10);
                $.each(data.files, function (index, file) {
                    wpmfGalleryModule.uploadProgress(file, {percentage: 100});
                });

            }).on('fileuploadstopped', function () {
            }).on('fileuploaddone', function (e, data) {
                wpmfGalleryModule.uploadFinished(data.files[0]);
            }).on('fileuploaddragenter', function (e) {
                $('.wpmf-drop-overlay').addClass('in');
            }).on('fileuploaddragleave', function (e) {
                if (!$(e.target).hasClass('WpmfGalleryList')) {
                    $('.wpmf-drop-overlay').removeClass('in');
                }
            });
        },

        /**
         * Start upload file
         * @param file
         */
        uploadStart: function (file) {
            var row = $(".WpmfGalleryList .fileupload-list [data-id='" + file.hash + "']");
            row.find('.upload-progress').slideDown();
        },

        /**
         * Helper functions
         * @param bytes
         * @param si
         * @returns {string}
         */
        humanFileSize: function (bytes, si) {
            var thresh = si ? 1000 : 1024;
            if (Math.abs(bytes) < thresh) {
                return bytes + ' B';
            }
            var units = si
                ? ['kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB']
                : ['KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'];
            var u = -1;
            do {
                bytes /= thresh;
                ++u;
            } while (Math.abs(bytes) >= thresh && u < units.length - 1);
            return bytes.toFixed(1) + ' ' + units[u];
        },

        /**
         * Validate File for Upload
         * @param file
         * @returns {*}
         */
        validateFile: function (file) {
            var acceptFileType = new RegExp($(".WpmfGalleryList").find('input[name="acceptfiletypes"]').val(), "i");
            file.error = false;
            if (file.name.length && !acceptFileType.test(file.name)) {
                file.error = wpmf_glraddon.vars.acceptFileTypes;
            }

            if (wpmf_glraddon.vars.maxsize !== '' && file.size > 0 && file.size > wpmf_glraddon.vars.maxsize) {
                file.error = wpmf_glraddon.vars.maxFileSize;
            }
            return file;
        },

        /**
         * Get thumbnail for local and cloud files
         * @param file
         * @returns {*}
         */
        getThumbnail: function (file) {
            if (typeof file.thumbnail === 'undefined' || file.thumbnail === null || file.thumbnail === '') {
                var icon = 'file_default';
                if (file.type.indexOf("image") >= 0) {
                    return URL.createObjectURL(file);
                }

                return wpmf_glraddon.vars.plugin_url_image + icon + '.png';
            } else {
                return file.thumbnail;
            }
        },

        /**
         * Render file in upload list
         * @param file
         */
        renderFileUploadRow: function (file) {
            var row = ($(".WpmfGalleryList").find('.template-row').clone().removeClass('template-row'));
            row.attr('data-file', file.name).attr('data-id', file.hash);
            row.find('.file-name').text(file.name);
            if (file.size !== 'undefined' && file.size > 0) {
                row.find('.file-size').text(wpmfGalleryModule.humanFileSize(file.size, true));
            }
            row.find('.upload-thumbnail img').attr('src', wpmfGalleryModule.getThumbnail(file));

            row.addClass('template-upload');
            $(".WpmfGalleryList .fileupload-list .files").append(row[1]);
            return row;
        },

        /**
         * Render the progress of uploading cloud files
         * @param file
         * @param status
         */
        uploadProgress: function (file, status) {
            var row = $(".WpmfGalleryList .fileupload-list [data-id='" + file.hash + "']");
            row.find('.ui-progressbar-value')
                .attr('aria-valuenow', status.percentage)
                .animate({
                    width: status.percentage + '%'
                }, 'fast', function () {
                });
        },

        /**
         * when upload file finish
         * @param file
         */
        uploadFinished: function (file) {
            var row = $(".WpmfGalleryList .fileupload-list [data-id='" + file.hash + "']");

            row.addClass('template-download').removeClass('template-upload');
            row.find('.file-name').text(file.name);
            row.find('.upload-thumbnail img').attr('src', wpmfGalleryModule.getThumbnail(file));
            row.find('.upload-progress').slideUp();
            row.animate({"opacity": "0"}, "slow", function () {
                if ($(this).parent().find('.template-upload').length <= 1) {
                    $(this).closest('.fileuploadform').find('div.fileupload-drag-drop').fadeIn();

                    /* Update Filelist */
                    var formData = {
                        listtoken: file.listtoken
                    };
                }

                $(this).remove();
            });
        },

        /**
         * Change gallery function
         * @param id id of gallery
         */
        changeGallery: function (id, action = '') {
            if (typeof id === 'undefined' || parseInt(id) === 0) {
                return;
            }

            if (wpmfGalleryModule.is_gallery_loading) {
                return;
            }

            if ($('.btn_import_from_google_photos').length) {
                var url = wpmf_glraddon.vars.admin_url + 'upload.php?page=wpmf-google-photos&noheader=1';
                var body_width = $('body').width();
                var body_height = $('body').height();
                var google_photo_page_width = body_width * 80 / 100;
                var google_photo_page_height = body_height * 80 / 100;
                url += '&width=' + google_photo_page_width;
                url += '&height=' + google_photo_page_height;
                url += '&gallery_id=' + id;
                $('.btn_import_from_google_photos').attr('href', url);
            }

            var data_params = $('#gallerylist').data('edited');
            var sesion_wrap = $('.gallery-options-wrap');
            var btn_import_image_fromwp = $('.btn_modal_import_image_fromwp');
            var edit_selection_wrap = $('.form_edit_gallery .wpmf_gallery_selection');
            $.ajax({
                type: "POST",
                url: ajaxurl,
                data: {
                    action: "wpmfgallery",
                    task: "change_gallery",
                    id: id,
                    wpmf_gallery_nonce: wpmf_glraddon.vars.wpmf_gallery_nonce
                },
                beforeSend: function () {
                    wpmfGalleryModule.is_gallery_loading = true;
                    sesion_wrap.addClass('loading');
                },
                success: function (res) {
                    if (!res.status) {
                        $('.gallery-toolbar-bottom').hide();
                    } else {
                        $('.gallery-toolbar-bottom').show();
                    }
                    wpmfGalleryModule.setCookie('wpmf_gallery_selected_' + wpmf_glraddon.vars.site_url, id, 365);
                    sesion_wrap.removeClass('loading');
                    wpmfGalleryModule.current_page_nav = 1;
                    $('.wpmf-desc-msg').addClass('wpmf-hidden');
                    var prev_tab = wpmfGalleryModule.getCookie('wpmf_gallery_tab_selected_' + wpmf_glraddon.vars.site_url);
                    if (typeof prev_tab !== "undefined" && prev_tab !== '' && prev_tab !== null) {
                        $('.gallery-ju-top-tabs li[data-tab="'+ prev_tab +'"]').trigger('click');
                    } else {
                        $('.gallery-ju-top-tabs li:first-child').trigger('click');
                    }

                    // open gallery tree
                    $('.tree_view li[data-id="'+ id +'"]').parents('li').removeClass('closed');
                    // set default parent when create gallery
                    var parent_id = $('.wpmf-gtree-item[data-id="'+ id +'"]').data('parent_id');
                    $('.new-gallery-parent').val(parent_id).change();

                    if (btn_import_image_fromwp.length > 0) {
                        var url_modal = btn_import_image_fromwp.attr('href');
                        var new_url = url_modal + '&gallery_id=' + id;
                        btn_import_image_fromwp.attr('href', new_url);
                    }

                    $('.up_gallery_id').val(id);
                    $('.fileupload-container').remove();
                    sesion_wrap.append(res.upload_form_html);
                    edit_selection_wrap.find('.gallery-attachment').remove();
                    if (res.status) {
                        edit_selection_wrap.append(res.images_html);
                        // load thumbnail of current gallery on tree
                        var thumb_url = $('.gallery-attachment[data-id="'+ res.glr.feature_image_id +'"]').data('thumbnail');
                        $('.wpmf-gallery-list li[data-id="' + wpmfGalleryModule.wpmf_current_gallery + '"] > .wpmf-gtree-item').find('.wpmf-gallery-thumbnail-icon').attr('src', thumb_url);

                        wpmfGalleryModule.sortAbleImages('.wpmf_gallery_selection');
                        wpmfGalleryModule.eventImages();
                        if (action !== '') {
                            wpmfGalleryModule.saveCustomGridStyles();
                        }
                    }

                    $('.wpmf-overlay-inner').bind('dragover', function (e) {
                        if (!$('.fileupload-container').length) {
                            return;
                        }

                        $('.wpmf-drop-overlay').addClass('in');
                    });

                    $('.wpmf-overlay-inner').bind('dragleave', function (e) {
                        $('.wpmf-drop-overlay').removeClass('in');
                    });

                    wpmfGalleryModule.fileUpload();

                    /* Load image template */
                    wpmfGalleryModule.gallery_details[id] = res.glr;
                    $('.form_edit_gallery .gallery_name').val(wpmfGalleryModule.gallery_details[id].name);
                    $('.edit-gallery-parent option[value="' + wpmfGalleryModule.gallery_details[id].parent + '"]').prop('selected', true).change();

                    if (parseInt(data_params.gallery_id) !== 0 && parseInt(data_params.gallery_id) === parseInt(id)) {
                        $('.edit-gallery-layout').val(data_params.layout);
                        $('.edit-gallery-row_height').val(data_params.row_height);
                        $('.edit-gallery-aspect_ratio').val(data_params.aspect_ratio);
                        $('.edit-gallery-columns').val(data_params.columns);
                        $('.edit-gallery-size').val(data_params.size);
                        $('.edit-gallery-targetsize').val(data_params.targetsize);
                        $('.edit-gallery-link').val(data_params.link);
                        $('.edit-gallery-orderby').val(data_params.wpmf_orderby);
                        $('.edit-gallery-order').val(data_params.wpmf_order);
                        $('.edit-gallery-animation').val(data_params.animation);
                        $('.edit-gallery-duration').val(data_params.duration);
                        $('.edit-gallery-auto_animation').val(data_params.auto_animation);
                        $('.edit-gallery-number_lines').val(data_params.number_lines);
                        $('.edit-gallery-gutterwidth').val(data_params.gutterwidth);


                        $('.hover_color_input').val(data_params.hover_color).change();
                        $('.hover_opacity_input').val(data_params.hover_opacity);
                        $('.hover_title_position').val(data_params.hover_title_position);
                        $('.hover_title_size').val(data_params.hover_title_size);
                        $('.hover_title_color_input').val(data_params.hover_title_color).change();
                        $('.hover_desc_position').val(data_params.hover_desc_position);
                        $('.hover_desc_size').val(data_params.hover_desc_size);
                        $('.hover_desc_color_input').val(data_params.hover_desc_color).change();

                        if (typeof data_params.tree_width === "undefined" || parseInt(data_params.tree_width) < 250) {
                            $('.gallery_tree_width').val(250);
                        } else {
                            $('.gallery_tree_width').val(data_params.tree_width);
                        }

                        if (parseInt(data_params.display_tree) === 1) {
                            $('.gallery_display_tree').prop('checked', true);
                        } else {
                            $('.gallery_display_tree').prop('checked', false);
                        }

                        $('.wpmf-gallery-folder').val(data_params.folder);
                        if (parseInt(data_params.auto_from_folder) === 1) {
                            $('.auto_from_folder').prop('checked', true);
                        } else {
                            $('.auto_from_folder').prop('checked', false);
                        }

                        if (parseInt(data_params.display_tag) === 1) {
                            $('.gallery_display_tag').prop('checked', true);
                        } else {
                            $('.gallery_display_tag').prop('checked', false);
                        }

                        if (parseInt(data_params.show_buttons) === 1) {
                            $('.gallery_flow_show-buttons').prop('checked', true);
                        } else {
                            $('.gallery_flow_show-buttons').prop('checked', false);
                        }
                        wpmfGalleryModule.updateThemeSelection(data_params.display, 'edit');
                    } else {
                        $('.edit-gallery-layout').val(res.glr.params.layout);
                        $('.edit-gallery-row_height').val(res.glr.params.row_height);
                        $('.edit-gallery-aspect_ratio').val(res.glr.params.aspect_ratio);
                        $('.edit-gallery-columns').val(res.glr.params.columns);
                        $('.edit-gallery-size').val(res.glr.params.size);
                        $('.edit-gallery-targetsize').val(res.glr.params.targetsize);
                        $('.edit-gallery-link').val(res.glr.params.link);
                        $('.edit-gallery-orderby').val(res.glr.params.wpmf_orderby);
                        $('.edit-gallery-order').val(res.glr.params.wpmf_order);
                        $('.edit-gallery-animation').val(res.glr.params.animation);
                        $('.edit-gallery-duration').val(res.glr.params.duration);
                        $('.edit-gallery-auto_animation').val(res.glr.params.auto_animation);
                        $('.edit-gallery-number_lines').val(res.glr.params.number_lines);
                        $('.edit-gallery-gutterwidth').val(res.glr.params.gutterwidth);

                        $('.hover_color_input').val(res.glr.params.hover_color).change();
                        $('.hover_opacity_input').val(res.glr.params.hover_opacity);
                        $('.hover_title_position').val(res.glr.params.hover_title_position);
                        $('.hover_title_size').val(res.glr.params.hover_title_size);
                        $('.hover_title_color_input').val(res.glr.params.hover_title_color).change();
                        $('.hover_desc_position').val(res.glr.params.hover_desc_position);
                        $('.hover_desc_size').val(res.glr.params.hover_desc_size);
                        $('.hover_desc_color_input').val(res.glr.params.hover_desc_color).change();

                        if (typeof res.glr.params.tree_width === "undefined" || parseInt(res.glr.params.tree_width) < 250) {
                            $('.gallery_tree_width').val(250);
                        } else {
                            $('.gallery_tree_width').val(res.glr.params.tree_width);
                        }
                        $('.wpmf-gallery-folder').val(res.glr.params.folder);
                        if (parseInt(res.glr.params.auto_from_folder) === 1) {
                            $('.auto_from_folder').prop('checked', true);
                        } else {
                            $('.auto_from_folder').prop('checked', false);
                        }

                        if (parseInt(res.glr.params.display_tree) === 1) {
                            $('.gallery_display_tree').prop('checked', true);
                        } else {
                            $('.gallery_display_tree').prop('checked', false);
                        }

                        if (parseInt(res.glr.params.display_tag) === 1) {
                            $('.gallery_display_tag').prop('checked', true);
                        } else {
                            $('.gallery_display_tag').prop('checked', false);
                        }

                        if (parseInt(res.glr.params.show_buttons) === 1) {
                            $('.gallery_flow_show-buttons').prop('checked', true);
                        } else {
                            $('.gallery_flow_show-buttons').prop('checked', false);
                        }
                        wpmfGalleryModule.updateThemeSelection(wpmfGalleryModule.gallery_details[id].theme, 'edit');
                    }

                    if ($('.wpmf_gallery_selection').hasClass('wpmfInitPackery')) {
                        if (wpmfGalleryModule.current_tab !== 'main-gallery' && $('.gallery-attachment.ui-draggable').length) {
                            $('.wpmf_gallery_selection').packery('destroy');
                        }
                    }

                    wpmfGalleryModule.updateNav(res);
                    wpmfGalleryModule.bindEvent();
                    wpmfGalleryModule.initPackery();
                    wpmfGalleryModule.is_gallery_loading = false;
                    wpmfGalleryModule.shouldconfirm = false;

                    var theme = $('#main-gallery-settings .wpmf-theme-item.selected').data('theme');
                    if (theme === 'custom_grid') {
                        wpmfGalleryModule.is_resizing = false;
                    }  else {
                        wpmfGalleryModule.is_resizing = true;
                    }

                }
            });
        },

        initPackery: function (action = '') {
            if (wpmfGalleryModule.current_tab !== 'main-gallery') {
                return;
            }

            var theme = $('#main-gallery-settings .wpmf-theme-item.selected').data('theme');
            var $container = $('.wpmf_gallery_selection');
            if ($container.hasClass('ui-sortable')) {
                $container.sortable('destroy');
            }

            if (theme === 'custom_grid') {
                $container.addClass('custom_grid');

                if ($container.hasClass('wpmfInitPackery')) {
                    if (action === 'resize') {
                        $container.packery('layout');
                        //return;
                    } else {
                        $container.packery('destroy');
                    }
                }

                var wrap_width = $container.width();
                if (wrap_width == 0) {
                    return;
                }

                var gutter = parseInt($('.edit-gallery-gutterwidth').val());
                var one_col_width = parseInt((wrap_width - gutter*12)/12);
                if (action !== 'resize') {
                    $('.wpmf_gallery_selection.custom_grid .gallery-attachment').each(function() {
                        var dimensions = $(this).data('styles');
                        var w = (typeof dimensions.width !== "undefined") ? parseInt(dimensions.width) : 2;
                        var h = (typeof dimensions.height !== "undefined") ? parseInt(dimensions.height) : 2;
                        var g = (parseInt(w) - 1)*gutter;
                        var display_width = one_col_width;
                        var display_height = one_col_width;

                        if (w > 1) {
                            display_width = one_col_width*w + g;
                        }

                        if (w == h) {
                            display_height = display_width;
                        } else {
                            if (h > 1) {
                                display_height = (one_col_width*h) + (h - 1)*gutter;
                            }
                        }

                        $(this).width(display_width);
                        $(this).height(display_height);
                    });
                }

                imagesLoaded($container, function () {
                    var $grid = $container.packery({
                        itemSelector: '.gallery-attachment',
                        columnWidth: one_col_width,
                        resizable: true,
                        gutter: parseInt(gutter),
                    });

                    if (action !== 'resize') {
                        var $items = $grid.find('.gallery-attachment').draggable();
                        $grid.packery( 'bindUIDraggableEvents', $items );
                        $items.on('dragstop', function (event, ui) {
                            var $itemElems = $($grid.packery('getItemElements'));
                            wpmfGalleryModule.doReorderImage($itemElems);
                           // $grid.packery('layout');
                            wpmfGalleryModule.generateGrid(wrap_width);
                        });
                    }

                    if (!$container.hasClass('wpmfInitPackery')) {
                        $container.addClass('wpmfInitPackery');
                    }

                    wpmfGalleryModule.initResizable(one_col_width);
                    wpmfGalleryModule.generateGrid(wrap_width);
                });
            } else {
                $('.wpmf_gallery_selection').removeClass('custom_grid');
                if ($container.hasClass('wpmfInitPackery')) {
                    $container.packery('destroy');
                    $container.removeClass('wpmfInitPackery');
                }

                $('.gallery-attachment').each(function () {
                    if ($(this).hasClass('ui-resizable')) {
                        $(this).resizable("destroy");
                        $(this).attr('style', '');
                    }
                });

                $container.find('.gallery-attachment').each(function (i, item) {
                    if ($(item).hasClass('ui-draggable')) {
                        $(item).draggable('destroy');
                    }
                });

                wpmfGalleryModule.sortAbleImages('.wpmf_gallery_selection');
            }
        },

        generateGrid: function(wrap_width) {
            var $el = $('.wpmf-grid');
            var gutter = parseInt($('.edit-gallery-gutterwidth').val());
            var columnWidth = parseInt((wrap_width - gutter*12)/12);
            var neededRows = 0,
                neededItems = 0,
                neededContainerHeight = 0,
                containerHeight = 0,
                minContainerHeight = 0,
                parentHeight = $el.parent().height();
            containerHeight = $('.wpmf-gallery-selection-wrap').height();
            minContainerHeight = (columnWidth + gutter) * 3 - gutter;

            if (containerHeight < minContainerHeight) {
                containerHeight = minContainerHeight;
            }

            neededRows = Math.round((containerHeight + gutter) / (columnWidth + gutter)) + 1;
            neededContainerHeight = (neededRows) * (columnWidth + gutter) - gutter;

            while (containerHeight < neededContainerHeight) {
                neededContainerHeight = neededContainerHeight - (columnWidth + gutter);
            }

            $el.height(neededContainerHeight);
            if (neededContainerHeight > parentHeight) {
                $el.parent().height(neededContainerHeight);
            }

            var currentRows = 0;
            if (neededRows > currentRows) {

                neededItems = (neededRows - currentRows) * 12;
                currentRows = neededRows;

                $el.html('');
                for (var i = 1; i <= neededItems; i++) {
                    $el.append('<div class="wpmf-grid-item"></div>');
                }

                $el.find('.wpmf-grid-item').css({
                    'width': columnWidth,
                    'height': columnWidth,
                    'margin-right': gutter,
                    'margin-bottom': gutter
                });
                $el.css({
                    'width': 'calc(100% + '+ gutter +'px)',
                });

            }
        },

        initResizable: function (one_col_width) {
            $('.gallery-attachment').each(function () {
                var $this = $(this);
                if (!$this.hasClass('ui-resizable')) {
                    $this.resizable({
                        handles: {
                            'se': $this.find('.wpmfsegrip'),
                        },
                        minHeight: one_col_width,
                        minWidth: one_col_width,
                        maxWidth: $('.wpmf-gallery-selection-wrap').width(),
                        helper: "ui-resizable-helper",
                        resize: function (event, ui) {
                            $(event.target).css('z-index', '999');

                            var snap_width = wpmfGalleryModule.calculateSize(ui.size.width, one_col_width);
                            var snap_height = wpmfGalleryModule.calculateSize(ui.size.height, one_col_width);

                            // We need to snap the helper to a grid
                            ui.helper.width(snap_width - 3);
                            ui.helper.height(snap_height - 3);

                            // The element will increase normally
                            ui.element.width(ui.size.width);
                            ui.element.height(ui.size.height);
                        },

                        stop: function (event, ui) {
                            $(event.target).css('z-index', 'auto');
                            var width = ui.size.width;
                            var height = ui.size.height;
                            var newWidth = wpmfGalleryModule.calculateSize(width, one_col_width);
                            var newHeight = wpmfGalleryModule.calculateSize(height, one_col_width);

                            $(event.target).width(newWidth);
                            $(event.target).height(newHeight);

                            // Save Image
                            //wpmfGalleryModule.saveImage( this.model.get( 'id' ) );
                            setTimeout(function () {
                                wpmfGalleryModule.saveCustomGridStyles('resize');
                            }, 200);
                        },
                    });
                }
            });
        },

        saveCustomGridStyles: function(action = '') {
            var theme = $('.edit-gallery-theme').val();
            var gutterwidth = $('.edit-gallery-gutterwidth').val();
            var grid_styles = {};
            if (theme === 'custom_grid') {
                var wrap_width = $('.wpmf_gallery_selection.custom_grid').width();
                var one_col_width = (wrap_width - gutterwidth*11)/12;
                $('.wpmf_gallery_selection.custom_grid .gallery-attachment').each(function(){
                    var img_id = $(this).data('id');
                    var img_width = $(this).width();
                    var img_height = $(this).height();
                    var w = Math.round(img_width/one_col_width);
                    if (parseInt(w) > 12) {
                        w = 12;
                    }
                    var h = Math.round(img_height/one_col_width);
                    grid_styles['attachment-' + img_id] = {width: w, height: h};
                    $('.gallery-attachment[data-id="'+ img_id +'"]').data('styles', {width: w, height: h}).attr('data-styles', {width: w, height: h});
                });
                wpmfGalleryModule.initPackery(action);
            }

            /* Ajax edit gallery */
            $.ajax({
                url: ajaxurl,
                method: "POST",
                dataType: 'json',
                data: {
                    action: "wpmfgallery",
                    task: "save_custom_grid_styles",
                    id: wpmfGalleryModule.wpmf_current_gallery,
                    theme: theme,
                    grid_styles: JSON.stringify(grid_styles),
                    wpmf_gallery_nonce: wpmf_glraddon.vars.wpmf_gallery_nonce
                },
                beforeSend: function () {

                },
                success: function (res) {

                }
            });

        },

        // Get columns from width/height
        getSizeColumns: function (currentSize) {
            var size = 100;
            return Math.round(currentSize / size);
        },

        calculateSize: function (currentSize, one_col_width) {
            var columns = Math.round(currentSize / one_col_width),
                gutter = $('.edit-gallery-gutterwidth').val(),
                containerColumns = 12,
                correctSize;

            if (columns > containerColumns) {
                columns = containerColumns;
            }
            correctSize = one_col_width * columns + (parseInt(gutter) * (columns - 1));
            return correctSize;
        },

        /**
         * sortable image in gallery
         */
        sortAbleImages: function (selector) {
            $(selector).sortable({
                revert: true,
                helper: function (e, item) {
                    return $(item).clone();
                },
                /** Prevent firefox bug positionnement **/
                start: function (event, ui) {
                },
                stop: function (event, ui) {
                },
                beforeStop: function (event, ui) {
                    var userAgent = navigator.userAgent.toLowerCase();
                    if (ui.offset !== "undefined" && userAgent.match(/firefox/)) {
                        ui.helper.css('margin-top', 0);
                    }
                },
                update: function () {
                    var theme = $('#main-gallery-settings .wpmf-theme-item.selected').data('theme');
                    if (theme !== 'custom_grid') {
                        wpmfGalleryModule.doReorderImage();
                    }
                }
            });

            $(selector).disableSelection();
        },

        doReorderImage: function($itemElems = '') {
            var order = '';
            if ($itemElems !== '') {
                $.each($itemElems, function (i, val) {
                    if (order !== '') {
                        order += ',';
                    }
                    order += '"' + i + '":' + $(val).data('id');
                });
            } else {
                $.each($('.gallery-attachment'), function (i, val) {
                    if (order !== '') {
                        order += ',';
                    }
                    order += '"' + i + '":' + $(val).data('id');
                });
            }

            order = '{' + order + '}';

            // do re-order file
            $.ajax({
                type: "POST",
                url: ajaxurl,
                data: {
                    action: "wpmfgallery",
                    task: "reorder_image_gallery",
                    order: order,
                    gallery_id: wpmfGalleryModule.wpmf_current_gallery,
                    wpmf_gallery_nonce: wpmf_glraddon.vars.wpmf_gallery_nonce
                },
                success: function () {
                    /* display notification */
                    wpmfSnackbarModule.show({
                        id: 'save_gallery',
                        content: wpmf_glraddon.l18n.save_glr,
                        auto_close_delay: 2000
                    });
                    //wpmfGalleryModule.initPackery('resize');
                }
            });
        },

        /**
         * Escape string
         * @param s string
         */
        wpmfescapeScripts: function (s) {
            return s
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        },

        /**
         * action edit and remove image
         */
        eventImages: function () {
            $('.set_feature_image').unbind('click').bind('click', function () {
                var image_id = $(this).closest('.gallery-attachment').data('id');
                var thumb_url = $(this).closest('.gallery-attachment').data('thumbnail');
                $('.gallery-attachment').removeClass('is_feature_gallery');
                $(this).closest('.gallery-attachment').addClass('is_feature_gallery');
                $('.wpmf-gallery-list li[data-id="' + wpmfGalleryModule.wpmf_current_gallery + '"] > .wpmf-gtree-item').find('.wpmf-gallery-thumbnail-icon').attr('src', thumb_url);
                $.ajax({
                    url: ajaxurl,
                    method: "POST",
                    dataType: 'json',
                    data: {
                        action: "wpmfgallery",
                        task: "wpmf_gallery_set_feature_image",
                        image_id: image_id,
                        gallery_id: wpmfGalleryModule.wpmf_current_gallery,
                        wpmf_gallery_nonce: wpmf_glraddon.vars.wpmf_gallery_nonce
                    },
                    success: function (res) {

                    }
                });
            });

            $('.edit_gallery_item').unbind('click').bind('click', function () {
                var id = $(this).closest('.gallery-attachment').data('id');
                $.ajax({
                    url: ajaxurl,
                    method: "POST",
                    dataType: 'json',
                    data: {
                        action: "wpmfgallery",
                        task: "item_details",
                        id: id,
                        wpmf_gallery_nonce: wpmf_glraddon.vars.wpmf_gallery_nonce
                    },
                    success: function (res) {
                        if (res.status) {
                            showDialog({
                                text: res.html,
                                cancelable: false,
                                closeicon: true,
                                id: 'wpmf-gallery-edit-item',
                                negative: {
                                    title: wpmf_glraddon.l18n.cancel,
                                    id: 'wpmf-dl-cancel-edit-image'
                                },
                                positive: {
                                    title: wpmf_glraddon.l18n.save,
                                    id: 'wpmf-dl-save-image',
                                    onClick: function () {
                                        var title = wpmfGalleryModule.wpmfescapeScripts($('.form_item_details_popup .img_title').val());
                                        var excerpt = wpmfGalleryModule.wpmfescapeScripts($('.form_item_details_popup .img_excerpt').val());
                                        var alt = wpmfGalleryModule.wpmfescapeScripts($('.form_item_details_popup .img_alt').val());
                                        var link_to = wpmfGalleryModule.wpmfescapeScripts($('.form_item_details_popup .custom_image_link').val());
                                        var link_target = $('.form_item_details_popup .image_link_target').val();
                                        var img_tags = wpmfGalleryModule.wpmfescapeScripts($('.form_item_details_popup .img_tags').val());
                                        var video_url = $('.form_item_details_popup .edit_video_url').val();
                                        var video_thumb_id = $('.form_item_details_popup .edit-video-thumbnail-id').val();

                                        /* Run ajax update image */
                                        $.ajax({
                                            url: ajaxurl,
                                            method: "POST",
                                            dataType: 'json',
                                            data: {
                                                action: "wpmfgallery",
                                                task: "update_gallery_item",
                                                id: id,
                                                title: title,
                                                excerpt: excerpt,
                                                alt: alt,
                                                link_to: link_to,
                                                link_target: link_target,
                                                img_tags: img_tags,
                                                video_url: video_url,
                                                video_thumb_id: video_thumb_id,
                                                wpmf_gallery_nonce: wpmf_glraddon.vars.wpmf_gallery_nonce
                                            },
                                            success: function (res) {
                                                if (res.status) {
                                                    /* display notification */
                                                    wpmfSnackbarModule.show({
                                                        id: 'save_image',
                                                        content: wpmf_glraddon.l18n.save_img,
                                                        auto_close_delay: 2000
                                                    });
                                                    wpmfGalleryModule.changeGallery(wpmfGalleryModule.wpmf_current_gallery);
                                                }
                                            }
                                        });
                                    }
                                }
                            });

                            $('.edit-video-thumbnail').off('click').on('click', function () {
                                if (typeof frame !== "undefined") {
                                    frame.open();
                                    return;
                                }
                                // Create the media frame.
                                var frame = wp.media({
                                    // Tell the modal to show only images.
                                    library: {
                                        type: 'image'
                                    }
                                });

                                // When an image is selected, run a callback.
                                frame.on('select', function () {
                                    // Grab the selected attachment.
                                    var attachment = frame.state().get('selection').first().toJSON();
                                    $('.thumbnail-image img').attr('src', attachment.url);
                                    $('.thumbnail-image .edit-video-thumbnail-id').val(attachment.id);
                                });

                                // let's open up the frame.
                                frame.open();
                            });

                            wpmfGalleryModule.linkAction('form_item_details_popup');
                        }
                    }
                });
            });

            /* Delete image gallery selection */
            $('.delete_gallery_item').unbind('click').bind('click', function () {
                if ($(this).closest('.is_item_folder').length) {
                    showDialog({
                        text: wpmf_glraddon.l18n.item_folder_msg
                    });
                    return;
                }
                var id = $(this).closest('.gallery-attachment').data('id');
                showDialog({
                    title: wpmf_glraddon.l18n.delete_image_gallery,
                    negative: {
                        title: wpmf_glraddon.l18n.cancel
                    },
                    positive: {
                        title: wpmf_glraddon.l18n.delete,
                        onClick: function () {
                            $.ajax({
                                url: ajaxurl,
                                method: "POST",
                                dataType: 'json',
                                data: {
                                    action: "wpmfgallery",
                                    task: "image_selection_delete",
                                    id: id,
                                    id_gallery: wpmfGalleryModule.wpmf_current_gallery,
                                    wpmf_gallery_nonce: wpmf_glraddon.vars.wpmf_gallery_nonce
                                },
                                success: function (res) {
                                    if (res.status) {
                                        $('.gallery-attachment[data-id="' + id + '"]').remove();
                                        /* display notification */
                                        wpmfSnackbarModule.show({
                                            id: 'delete_image',
                                            content: wpmf_glraddon.l18n.delete_img,
                                            auto_close_delay: 2000
                                        });
                                        wpmfGalleryModule.changeGallery(wpmfGalleryModule.wpmf_current_gallery);
                                    }
                                }
                            });
                        }
                    }
                });
            });
        },

        /**
         * render lists galleries tree
         * @param res
         * @param open_id
         * @param type
         */
        renderListstree: function (res, open_id, type) {
            wpmfGalleryTreeModule.categories = res.categories;
            wpmfGalleryTreeModule.categories_order = res.categories_order;
            wpmfGalleryTreeModule.importCategories();
            wpmfGalleryTreeModule.loadTreeView();
            wpmfGalleryModule.galleryEvent();
            if (type) {
                open_id = $('#gallerylist').find('.tree_view ul li:nth-child(2)').data('id');
            }
            wpmfGalleryTreeModule.glrTitleopengallery(open_id, true);
        },
        /**
         * Open link dialog
         * @param selector
         */
        linkAction: function (selector) {
            $('.link-btn').on('click', function () {
                if (typeof wpLink !== "undefined") {
                    wpLink.open('link-btn');
                    /* Bind to open link editor! */
                    $('#wp-link-backdrop').show();
                    $('#wp-link-wrap').show();
                    $('#url-field,#wp-link-url').closest('div').find('span').html('Link To');
                    $('#link-title-field').closest('div').hide();
                    $('.wp-link-text-field').hide();

                    $('#url-field,#wp-link-url').val($('.compat-field-wpmf_gallery_custom_image_link input.text').val());
                    if ($('.compat-field-gallery_link_target select').val() === '_blank') {
                        $('#link-target-checkbox,#wp-link-target').prop('checked', true);
                    } else {
                        $('#link-target-checkbox,#wp-link-target').prop('checked', false);
                    }
                }
            });

            /* Update link  */
            $('#wp-link-submit').on('click', function () {
                var link = $('#url-field').val();
                if (typeof link === "undefined") {
                    link = $('#wp-link-url').val();
                } // version 4.2+

                var link_target = $('#link-target-checkbox:checked').val();
                if (typeof link_target === "undefined") {
                    link_target = $('#wp-link-target:checked').val();
                } // version 4.2+

                if (link_target === 'on') {
                    link_target = '_blank';
                } else {
                    link_target = '';
                }

                $('.' + selector + ' .custom_image_link').val(link);
                $('.' + selector + ' .image_link_target option[value="' + link_target + '"]').prop('selected', true).change();
            });
        },

        /* update nav */
        updateNav: function (res) {
            $('.wpmf-gallery-image-pagging').html(res.nav);
            var count_page = $('.wpmf-gallery-image-pagging .total-pages').html();
            $('.wpmf-number-page').removeClass('wpmf-page-disable');
            if (parseInt(wpmfGalleryModule.current_page_nav) === 1) {
                $('.glr-first-page, .glr-prev-page').addClass('wpmf-page-disable');
            }

            if (parseInt(wpmfGalleryModule.current_page_nav) >= parseInt(count_page)) {
                $('.wpmf-number-page').removeClass('wpmf-page-disable');
                $('.glr-last-page, .glr-next-page').addClass('wpmf-page-disable');
            }

            wpmfGalleryModule.bindEvent();
        },

        /**
         * render context menu box
         */
        renderContextMenu: function () {
            var context_wrap = '<ul class="wpmf-contextmenu wpmf-gallery-contextmenu contextmenu z-depth-1 grey-text text-darken-2">\n' +
                '                <li><div class="wpmficon-rename-gallery items_menu">' + wpmf_glraddon.l18n.rename + '<span class="material-icons-outlined wpmf_icon"> edit </span></div></li><li><div class="wpmficon-delete-gallery items_menu">' + wpmf_glraddon.l18n.delete + '<span class="material-icons-outlined wpmf_icon"> delete_outline </span></div></li></ul>';

            // Add the context menu box for folder to body
            if (!$('.wpmf-gallery-contextmenu').length) {
                $('body').append(context_wrap);
            }
        },

        /**
         * click outside
         */
        houtside: function () {
            $('.wpmf-gallery-contextmenu').hide();
        },

        /* action for gallery */
        galleryEvent: function () {
            /* import image from wordpress */
            $('.btn_import_image_fromwp').off('click').on('click', function () {
                if (typeof frame !== "undefined") {
                    frame.open();
                    return;
                }
                // Create the media frame.
                var frame = wp.media({
                    // Tell the modal to show only images.
                    library: {
                        type: 'image'
                    },
                    title: wpmf_glraddon.l18n.iframe_import_label,
                    button: {
                        text: wpmf_glraddon.l18n.import
                    },
                    multiple: true
                });

                // When an image is selected, run a callback.
                frame.on('select', function () {
                    // Grab the selected attachment.
                    var attachments = frame.state().get('selection').toJSON();
                    var percent = Math.ceil(100 / (attachments.length));
                    $('.wpmf-process-bar').data('w', 0).css('width', '0%');
                    $('.wpmf-process-bar-full').show();
                    var ids = [];
                    $.each(attachments, function (i, v) {
                        ids.push(v.id);
                    });
                    ids = ids.join();
                    $.ajax({
                        url: ajaxurl,
                        method: "POST",
                        dataType: 'json',
                        data: {
                            action: "wpmfgallery",
                            task: "import_images_from_wp",
                            ids: ids,
                            gallery_id: wpmfGalleryModule.wpmf_current_gallery,
                            wpmf_gallery_nonce: wpmf_glraddon.vars.wpmf_gallery_nonce
                        },
                        success: function (res) {
                            $('.wpmf-process-bar').data('w', 100).css('width', '100%');
                            $('.wpmf-process-bar-full').fadeOut(3000);
                            wpmfGalleryModule.changeGallery(wpmfGalleryModule.wpmf_current_gallery, 'upload');
                        }
                    });
                });

                // let's open up the frame.
                frame.open();
            });

            $('.wpmf_btn_video').off('click').on('click', function () {
                var html = '<div><input type="text" name="wpmf_gallery_video_url" class="wpmf_gallery_video_url" placeholder="' + wpmf_glraddon.l18n.video_url + '" onfocus="this.placeholder = \'\'" onblur="this.placeholder = wpmf_glraddon.l18n.video_url"><span style="font-weight: bold; margin:  0 10px; text-transform: uppercase; color: #8c8c8e">'+ wpmf_glraddon.l18n.or +'</span><button type="button" class="add_video_btn">'+ wpmf_glraddon.l18n.select_from_library +'</button></div>';
                html += '<div class="add-video-wrap">';
                html += '<div class="add-video-img-wrap">';
                html += '<img class="thumb-video" src="'+ wpmf_glraddon.vars.plugin_url_image +'images-default.png">';
                html += '<img class="thumb-loading" src="'+ wpmf_glraddon.vars.plugin_url_image +'spinner.gif">';
                html += '<input type="hidden" class="video-thumbnail-id">';
                html += '<i class="material-icons wpmf_gallery_video_icon">play_circle_filled</i>';
                html += '<div class="video-thumbnail-action">';
                html += '<span class="material-icons-outlined remove-video-thumbnail"> close </span>';
                html += '</div>';
                html += '</div>';
                html += '<div class="video-thumbnail-btn-wrap"><button class="add-video-thumbnail-btn">'+ wpmf_glraddon.l18n.add_image +'</button></div>';
                html += '<p class="add_video_msg"></p>';
                html += '</div>';
                showDialog({
                    id: 'wpmf-add-video-dialog',
                    title: wpmf_glraddon.l18n.add_video,
                    help_icon: '<span class="material-icons-outlined wpmf-video-help"> help_outline </span>',
                    text: html,
                    question: true,
                    question_text: wpmf_glraddon.l18n.question_quit_video_edit,
                    negative: {
                        title: wpmf_glraddon.l18n.cancel
                    },
                    positive: {
                        title: wpmf_glraddon.l18n.create,
                        onClick: function () {
                            // Call php script to create the folder
                            var video_url = $('.wpmf_gallery_video_url').val();
                            var thumbnail_id = $('.video-thumbnail-id').val();
                            if (video_url === '') {
                                $('.add_video_msg').html(wpmf_glraddon.l18n.empty_url).fadeIn(1000).delay(4000).fadeOut(200);
                                return true;
                            }

                            if (video_url.indexOf("facebook") !== -1 || video_url.indexOf("wistia") !== -1 || video_url.indexOf("twitch") !== -1 || video_url.indexOf(wpmf_glraddon.vars.site_url) !== -1) {
                                if (thumbnail_id === '') {
                                    $('.add_video_msg').html(wpmf_glraddon.l18n.empty_thumbnail).fadeIn(1000).delay(4000).fadeOut(200);
                                    return true;
                                }
                            }

                            wpmfGalleryModule.addVideoToGallery(video_url, thumbnail_id);
                        }
                    }
                });

                tippy('.wpmf-video-help', {
                    theme: 'wpmf',
                    animation: 'scale',
                    animateFill: false,
                    maxWidth: 400,
                    duration: 0,
                    arrow: true,
                    allowHTML: true,
                    onShow(instance) {
                        var tippy_html = '';
                        tippy_html += '<p class="video-tippy-help" style="color: yellow; font-size:14px">Support: youtube, vimeo, facebook watch, wistia, twitch, dailymotion, self-hosted</p>';
                        tippy_html += '<p class="video-tippy-help">https://www.youtube.com/watch?v=5ncy4gn6S0k</p>';
                        tippy_html += '<p class="video-tippy-help">https://vimeo.com/496843494</p>';
                        tippy_html += '<p class="video-tippy-help">https://www.facebook.com/svmteam/videos/972235670261003</p>';
                        tippy_html += '<p class="video-tippy-help">https://www.twitch.tv/videos/999290199</p>';
                        tippy_html += '<p class="video-tippy-help">https://www.dailymotion.com/video/x80wibi</p>';
                        tippy_html += '<p class="video-tippy-help">https://xx.wistia.com/medias/uqlavso61q</p>';
                        tippy_html += '<p class="video-tippy-help">https://your-domain.com/video.mp4</p>';
                        instance.popper.hidden = false;
                        instance.setContent(tippy_html);
                    }
                });

                $('.wpmf_gallery_video_url').off('change').on('change', function () {
                    var video_url = $(this).val();
                    if (video_url !== '') {
                        $('.add-video-wrap').addClass('show');
                        $.ajax({
                            url: ajaxurl,
                            method: "POST",
                            dataType: 'json',
                            data: {
                                action: "wpmfgallery",
                                task: "auto_load_video_thumbnail",
                                video_url: video_url,
                                wpmf_gallery_nonce: wpmf_glraddon.vars.wpmf_gallery_nonce
                            },
                            beforeSend: function () {
                                $('.add-video-img-wrap').addClass('loading');
                            },
                            success: function (res) {
                                $('.add-video-img-wrap').removeClass('loading');
                                if (res.status) {
                                    $('.add-video-img-wrap .thumb-video').attr('src', res.thumb_url);
                                    $('.add-video-thumbnail-btn').html(wpmf_glraddon.l18n.edit);
                                } else {
                                    $('.add_video_msg').html(wpmf_glraddon.l18n.empty_video_thumbnail).fadeIn(1000).delay(4000).fadeOut(200);
                                }
                            }
                        });
                    } else {
                        $('.add-video-wrap').removeClass('show');
                    }
                });

                $('.add_video_btn').off('click').on('click', function () {
                    if (typeof frame !== "undefined") {
                        frame.open();
                        return;
                    }
                    // Create the media frame.
                    var frame = wp.media({
                        // Tell the modal to show only images.
                        library: {
                            type: 'video'
                        }
                    });

                    // When an image is selected, run a callback.
                    frame.on('select', function () {
                        // Grab the selected attachment.
                        var attachment = frame.state().get('selection').first().toJSON();
                        $('.wpmf_gallery_video_url').val(attachment.url).change();
                    });

                    // let's open up the frame.
                    frame.open();
                });

                $('.add-video-thumbnail-btn').off('click').on('click', function () {
                    if (typeof frame !== "undefined") {
                        frame.open();
                        return;
                    }
                    // Create the media frame.
                    var frame = wp.media({
                        // Tell the modal to show only images.
                        library: {
                            type: 'image'
                        }
                    });

                    // When an image is selected, run a callback.
                    frame.on('select', function () {
                        // Grab the selected attachment.
                        var attachment = frame.state().get('selection').first().toJSON();
                        $('.add-video-img-wrap .thumb-video').attr('src', attachment.url);
                        $('.add-video-img-wrap .video-thumbnail-id').val(attachment.id);
                        $('.add-video-thumbnail-btn').html(wpmf_glraddon.l18n.edit);
                    });

                    // let's open up the frame.
                    frame.open();
                });

                $('.remove-video-thumbnail').off('click').on('click', function () {
                    $('.add-video-img-wrap .thumb-video').attr('src', wpmf_glraddon.vars.plugin_url_image +'images-default.png');
                    $('.add-video-img-wrap .video-thumbnail-id').val('');
                    $('.add-video-thumbnail-btn').html(wpmf_glraddon.l18n.add_image);
                });
            });
        },

        addVideoToGallery: function(video_url = '', thumbnail_id = '') {
            if (video_url === '') {
                return;
            }
            $.ajax({
                url: ajaxurl,
                method: "POST",
                dataType: 'json',
                data: {
                    action: "wpmfgallery",
                    task: "add_video",
                    video_url: video_url,
                    thumbnail_id: thumbnail_id,
                    id_gallery: wpmfGalleryModule.wpmf_current_gallery,
                    wpmf_gallery_nonce: wpmf_glraddon.vars.wpmf_gallery_nonce
                },
                beforeSend: function () {
                    $('.wpmf-gallery-selection-wrap').addClass('loading');
                },
                success: function (res) {
                    $('.wpmf-gallery-selection-wrap').removeClass('loading');
                    if (res.status) {
                        wpmfGalleryModule.changeGallery(wpmfGalleryModule.wpmf_current_gallery);
                    }
                }
            });
        },

        /**
         * Get images selection
         */
        getImgSelection: function () {
            $('.WpmfGalleryList #current-page-selector').val(wpmfGalleryModule.current_page_nav);
            $.ajax({
                url: ajaxurl,
                method: "POST",
                dataType: 'json',
                data: {
                    action: "wpmfgallery",
                    task: "get_imgselection",
                    id_gallery: wpmfGalleryModule.wpmf_current_gallery,
                    current_page_nav: wpmfGalleryModule.current_page_nav,
                    wpmf_gallery_nonce: wpmf_glraddon.vars.wpmf_gallery_nonce
                },
                beforeSend: function () {
                    $('.wpmf-gallery-selection-wrap').addClass('loading');
                },
                success: function (res) {
                    $('.wpmf-gallery-selection-wrap').removeClass('loading');
                    if (res.status) {
                        $('.wpmf_gallery_selection').html(res.html);
                        $('.wpmf-remove-imgs-btn').hide();
                        wpmfGalleryModule.updateNav(res);
                        wpmfGalleryModule.bindEvent();
                        wpmfGalleryModule.eventImages();
                    }
                }
            });
        },

        /**
         * search key by value
         * @param arr
         * @param val
         * @returns {*}
         */
        arraySearch: function (arr, val) {
            for (var i = 0; i < arr.length; i++)
                if (arr[i] === val)
                    return i;
            return false;
        },

        /**
         * Upload function
         */
        uploadImages: function () {
            /* Upload image */
            $('#wpmf_gallery_file').unbind('change').bind('change', function () {
                wpmfGalleryModule.upload_from_pc = true;
                jQuery('#wpmf_progress_upload').hide();
                $('#wpmf_bar').width(0);
                $('#wpmfglr_form_upload').submit();
            });

            $('.btn_upload_from_pc').unbind('click').bind('click', function () {
                $('#wpmf_gallery_file').click();
            });

            var wpmf_bar = jQuery('.wpmf-process-bar');
            var wpmf_percentValue = '0%';
            jQuery('#wpmfglr_form_upload').ajaxForm({
                beforeSend: function () {
                    wpmf_percentValue = '0%';
                    wpmf_bar.width(wpmf_percentValue);
                },
                uploadProgress: function (event, position, total, percentComplete) {
                    jQuery('.wpmf-process-bar-full').show();
                    var percentValue = percentComplete + '%';
                    wpmf_bar.animate({
                        width: '' + percentValue + ''
                    }, {
                        duration: 5000,
                        easing: "linear",
                        step: function (x) {
                            var percentText = Math.round(x * 100 / percentComplete);
                            wpmf_bar.width(percentText + "%");
                        }
                    });
                },
                success: function () {
                    var wpmf_percentValue = '100%';
                    wpmf_bar.width(wpmf_percentValue);
                },
                complete: function (xhr) {
                    setTimeout(function () {
                        jQuery('.wpmf-process-bar-full').hide();
                        try {
                            var ob = JSON.parse(xhr.responseText);
                            if (typeof xhr.responseText !== "undefined") {
                                wpmfGalleryModule.upload_from_pc = false;
                                if (ob.status) {
                                    /* display notification */
                                    wpmfSnackbarModule.show({
                                        id: 'gallery_image_uploaded',
                                        content: wpmf_glraddon.l18n.upload_img,
                                        auto_close_delay: 2000
                                    });
                                    wpmfGalleryModule.changeGallery(wpmfGalleryModule.wpmf_current_gallery);
                                } else {
                                    alert(ob.msg);
                                }
                            }
                        } catch (err) {
                            wpmfSnackbarModule.show({
                                id: 'gallery_image_upload_error',
                                content: wpmf_glraddon.l18n.upload_error,
                                error: true,
                                auto_close_delay: 5000
                            });
                        }
                    },2000);
                }
            });
        },

        updateGalleryShortcode: function () {
            var display = $('.form_edit_gallery .wpmf-theme-item.selected').data('theme');
            var layout = $('.edit-gallery-layout').val();
            var row_height = $('.edit-gallery-row_height').val();
            var aspect_ratio = $('.edit-gallery-aspect_ratio').val();
            var display_tag = 0;
            var display_tree = 0;
            var auto_from_folder = 0;
            var columns = $('.edit-gallery-columns').val();
            var size = $('.edit-gallery-size').val();
            var targetsize = $('.edit-gallery-targetsize').val();
            var link = $('.edit-gallery-link').val();
            var wpmf_orderby = $('.edit-gallery-orderby').val();
            var wpmf_order = $('.edit-gallery-order').val();
            var animation = $('.edit-gallery-animation').val();
            var duration = $('.edit-gallery-duration').val();
            var auto_animation = $('.edit-gallery-auto_animation').val();
            var number_lines = $('.edit-gallery-number_lines').val();
            var gutterwidth = $('.edit-gallery-gutterwidth').val();
            var hover_color = $('.hover_color_input').val();
            var hover_opacity = $('.hover_opacity_input').val();
            var hover_title_position = $('.hover_title_position').val();
            var hover_title_size = $('.hover_title_size').val();
            var hover_title_color = $('.hover_title_color_input').val();
            var hover_desc_position = $('.hover_desc_position').val();
            var hover_desc_size = $('.hover_desc_size').val();
            var hover_desc_color = $('.hover_desc_color_input').val();

            var show_buttons = 0;
            var folder = $('.wpmf-gallery-folder').val();
            var tree_width = $('.gallery_tree_width').val();
            var gallery_shortcode = '[wpmfgallery';
            gallery_shortcode += ' gallery_id="' + wpmfGalleryModule.wpmf_current_gallery + '"';
            gallery_shortcode += ' display="' + display + '"';
            gallery_shortcode += ' layout="' + layout + '"';
            gallery_shortcode += ' row_height="' + row_height + '"';
            gallery_shortcode += ' aspect_ratio="' + aspect_ratio + '"';
            gallery_shortcode += ' customlink="0"';
            gallery_shortcode += ' columns="' + columns + '"';
            gallery_shortcode += ' size="' + size + '"';
            gallery_shortcode += ' targetsize="' + targetsize + '"';
            gallery_shortcode += ' link="' + link + '"';
            gallery_shortcode += ' wpmf_orderby="' + wpmf_orderby + '"';
            gallery_shortcode += ' wpmf_order="' + wpmf_order + '"';
            gallery_shortcode += ' animation="' + animation + '"';
            gallery_shortcode += ' duration="' + duration + '"';
            gallery_shortcode += ' auto_animation="' + auto_animation + '"';
            gallery_shortcode += ' number_lines="' + number_lines + '"';
            gallery_shortcode += ' gutterwidth="' + gutterwidth + '"';
            gallery_shortcode += ' hover_color="' + hover_color + '"';
            gallery_shortcode += ' hover_opacity="' + hover_opacity + '"';
            gallery_shortcode += ' hover_title_position="' + hover_title_position + '"';
            gallery_shortcode += ' hover_title_size="' + hover_title_size + '"';
            gallery_shortcode += ' hover_title_color="' + hover_title_color + '"';
            gallery_shortcode += ' hover_desc_position="' + hover_desc_position + '"';
            gallery_shortcode += ' hover_desc_size="' + hover_desc_size + '"';
            gallery_shortcode += ' hover_desc_color="' + hover_desc_color + '"';

            if ($('.gallery_display_tree').is(':checked')) {
                gallery_shortcode += ' display_tree="1"';
                display_tree = 1;
            } else {
                gallery_shortcode += ' display_tree="0"';
            }

            if ($('.gallery_display_tag').is(':checked')) {
                gallery_shortcode += ' display_tag="1"';
                display_tag = 1;
            } else {
                gallery_shortcode += ' display_tag="0"';
            }

            if ($('.gallery_flow_show-buttons').is(':checked')) {
                gallery_shortcode += ' show_buttons="1"';
                show_buttons = 1;
            } else {
                gallery_shortcode += ' show_buttons="0"';
            }

            if ($('.auto_from_folder').is(':checked')) {
                gallery_shortcode += ' auto_from_folder="1"';
                auto_from_folder = 1;
            } else {
                gallery_shortcode += ' auto_from_folder="0"';
            }
            gallery_shortcode += ' folder="' + folder + '"';
            gallery_shortcode += ' tree_width="' + tree_width + '"';
            gallery_shortcode += ']';
            if ($('#WpmfGalleryList').hasClass('wpmfgutenberg')) {
                var datas = $('#gallerylist').data('edited');
                parent.postMessage({
                    'galleryId': wpmfGalleryModule.wpmf_current_gallery,
                    'display': display,
                    'layout': layout,
                    'row_height': parseInt(row_height),
                    'aspect_ratio': aspect_ratio,
                    'idblock': datas.idblock,
                    'type': 'wpmfgalleryinsert',
                    'display_tree': display_tree,
                    'display_tag': display_tag,
                    'columns': columns,
                    'size': size,
                    'targetsize': targetsize,
                    'link': link,
                    'wpmf_orderby': wpmf_orderby,
                    'wpmf_order': wpmf_order,
                    'animation': animation,
                    'duration': duration,
                    'auto_animation': auto_animation,
                    'number_lines': number_lines,
                    'show_buttons': show_buttons,
                    'auto_from_folder': auto_from_folder,
                    'folder': folder,
                    'tree_width': tree_width,
                    'gutterwidth': gutterwidth,
                    'hover_color': hover_color,
                    'hover_opacity': hover_opacity,
                    'hover_title_position': hover_title_position,
                    'hover_title_size': hover_title_size,
                    'hover_title_color': hover_title_color,
                    'hover_desc_position': hover_desc_position,
                    'hover_desc_size': hover_desc_size,
                    'hover_desc_color': hover_desc_color,
                }, wpmf_glraddon.vars.admin_url);
            } else {
                var win = window.dialogArguments || opener || parent || top;
                win.send_to_editor(gallery_shortcode);
                // Refocus in window
                var ed = parent.tinymce.editors[0];
                ed.windowManager.windows[0].close();
            }
        },

        /**
         * all event
         */
        bindEvent: function () {
            $('.wpmf_color_field').wpColorPicker();
            $('.hover_save').unbind('click').bind('click', function () {
                $('.gallery-toolbar .btn_edit_gallery').trigger('click');
                $.magnificPopup.close();
            });
            $('.wpmf-gallery-folder').unbind('change').bind('change', function () {
                $(this).closest('.wpmf-gallery-field').find('.wpmf-desc-msg').removeClass('wpmf-hidden');
            });

            $('.wpmficon-delete-gallery').unbind('click').bind('click', function () {
                var gallery_id = wpmfGalleryModule.target_gallery;
                if (parseInt(gallery_id) === 0) {
                    wpmfGalleryModule.houtside();
                    return;
                }
                wpmfGalleryTreeModule.deleteGallery(gallery_id);
            });

            $('.wpmficon-rename-gallery').unbind('click').bind('click', function () {
                var gallery_id = wpmfGalleryModule.target_gallery;
                if (parseInt(gallery_id) === 0) {
                    wpmfGalleryModule.houtside();
                    return;
                }
                wpmfGalleryTreeModule.glrTitleopengallery(gallery_id);
            });

            $('.form_add_gallery .wpmf-theme-item').unbind('click').bind('click', function () {
                var theme = $(this).data('theme');
                wpmfGalleryModule.updateThemeSelection(theme, 'new');
            });


            $('.form_edit_gallery .edit-gallery-layout').unbind('change').bind('change', function () {
                wpmfGalleryModule.rowHeightStatus();
            });

            $('.form_edit_gallery .wpmf-theme-item').unbind('click').bind('click', function () {
                var theme = $(this).data('theme');
                if (theme === 'custom_grid') {
                    wpmfGalleryModule.is_resizing = false;
                }  else {
                    wpmfGalleryModule.is_resizing = true;
                }

                wpmfGalleryModule.updateThemeSelection(theme, 'edit');
                wpmfGalleryModule.initPackery();
            });

            $('.glr-next-page').unbind('click').bind('click', function () {
                if (!$(this).hasClass('wpmf-page-disable')) {
                    wpmfGalleryModule.current_page_nav++;
                    var page_count = $(this).data('page_count');
                    if (wpmfGalleryModule.current_page_nav > parseInt(page_count)) wpmfGalleryModule.current_page_nav = page_count;
                    wpmfGalleryModule.getImgSelection();
                }
            });

            $('.glr-prev-page').unbind('click').bind('click', function () {
                if (!$(this).hasClass('wpmf-page-disable')) {
                    wpmfGalleryModule.current_page_nav--;
                    if (wpmfGalleryModule.current_page_nav < 1) wpmfGalleryModule.current_page_nav = 1;
                    wpmfGalleryModule.getImgSelection();
                }
            });

            $('.glr-first-page').unbind('click').bind('click', function () {
                if (!$(this).hasClass('wpmf-page-disable')) {
                    wpmfGalleryModule.current_page_nav = 1;
                    wpmfGalleryModule.getImgSelection();
                }
            });

            $('.glr-last-page').unbind('click').bind('click', function () {
                if (!$(this).hasClass('wpmf-page-disable')) {
                    wpmfGalleryModule.current_page_nav = $(this).data('page_count');
                    wpmfGalleryModule.getImgSelection();
                }
            });

            $('.glr-current-page').unbind('change').bind('change', function () {
                var page_count = $('.glr-next-page').data('page_count');
                if ($(this).val() > parseInt(page_count)) {
                    wpmfGalleryModule.current_page_nav = page_count;
                    $(this).val(wpmfGalleryModule.current_page_nav);
                } else if ($(this).val() < 1) {
                    wpmfGalleryModule.current_page_nav = 1;
                    $(this).val(wpmfGalleryModule.current_page_nav);
                } else {
                    wpmfGalleryModule.current_page_nav = $(this).val();
                }

                wpmfGalleryModule.getImgSelection();
            });

            $('.img_per_page').unbind('change').bind('change', function () {
                if (wpmfGalleryModule.is_perpage_change) {
                    return;
                }

                var img_per_page = $(this).val();
                $.ajax({
                    url: ajaxurl,
                    method: "POST",
                    dataType: 'json',
                    data: {
                        action: "wpmfgallery",
                        task: "update_img_per_page",
                        img_per_page: img_per_page,
                        wpmf_gallery_nonce: wpmf_glraddon.vars.wpmf_gallery_nonce
                    },
                    beforeSend: function() {
                        wpmfGalleryModule.is_perpage_change = true;
                        $('.img_per_page').prop('disabled', true);
                    },
                    success: function (res) {
                        if (res.status) {
                            wpmfGalleryModule.getImgSelection();
                        }
                        wpmfGalleryModule.is_perpage_change = false;
                        $('.img_per_page').prop('disabled', false);
                    }
                });

            });

            /* insert shortcode gallery */
            $('.btn_insert_gallery').unbind('click').bind('click', function () {
                wpmfGalleryModule.updateGalleryShortcode();
            });

            /* Select images */
            var singleIndex;
            $('.wpmf_gallery_selection .gallery-attachment').unbind('click').bind('click', function (e) {
                var $this = $(this);
                if ($this.hasClass('gallery-attachment-folder')) {
                    return;
                }
                if (!$(e.target).hasClass('material-icons') && !$(e.target).hasClass('wpmfsegrip')) {
                    var nodes = Array.prototype.slice.call(document.getElementById('wpmf_gallery_selection').children);
                    if (!$('.gallery-attachment.selected').length) {
                        singleIndex = nodes.indexOf(this);
                    }

                    // select multiple image use ctrl key or shift key
                    if (e.ctrlKey || e.shiftKey) {
                        if (!$('.gallery-attachment.selected').length) {
                            $this.addClass('selected');
                        } else {
                            var modelIndex = nodes.indexOf(this), i;
                            if (singleIndex < modelIndex) {
                                for (i = singleIndex; i <= (modelIndex + 1); i++) {
                                    $('.gallery-attachment:nth-child(' + i + ')').addClass('selected');
                                }
                            } else {
                                for (i = modelIndex; i <= (singleIndex + 1); i++) {
                                    $('.gallery-attachment:nth-child(' + (i + 1) + ')').addClass('selected');
                                }
                            }
                        }
                    } else {
                        if ($this.hasClass('selected')) {
                            $this.removeClass('selected');
                        } else {
                            $this.addClass('selected');
                        }
                    }

                    if ($('.gallery-attachment.selected').length) {
                        $('.wpmf-remove-imgs-btn').show();
                    } else {
                        $('.wpmf-remove-imgs-btn').hide();
                    }
                }
            });

            /* Create gallery */
            $('.btn_create_gallery').unbind('click').bind('click', function () {
                var $this = $(this);
                var title = $('.new-gallery-name').val();
                var theme = $('.new-gallery-theme').val();
                var parent = $('.new-gallery-parent').val();
                if (title === '') {
                    $('.new-gallery-name').focus().addClass('wpmf-field-require');
                    return;
                }
                $.ajax({
                    url: ajaxurl,
                    method: "POST",
                    dataType: 'json',
                    data: {
                        action: "wpmfgallery",
                        task: "create_gallery",
                        title: title,
                        theme: theme,
                        parent: parent,
                        wpmf_gallery_nonce: wpmf_glraddon.vars.wpmf_gallery_nonce
                    },
                    beforeSend: function () {
                        $this.closest('.wpmf-gallery-fields').find('.spinner').css('visibility', 'visible').show();
                    },
                    success: function (res) {
                        if (res.status) {
                            wpmfGalleryModule.gallery_details[res.items.term_id] = res.items;
                            $this.closest('.wpmf-gallery-fields').find('.spinner').hide();
                            $.magnificPopup.close();
                            wpmfGalleryModule.resetNewGalleryFrom();
                            // Update the categories variables
                            wpmfGalleryModule.updateDropdownParent(res.dropdown_gallery);
                            wpmfGalleryModule.renderListstree(res, res.items.term_id, false);

                            /* display notification */
                            wpmfSnackbarModule.show({
                                id: 'gallery_added',
                                content: wpmf_glraddon.l18n.add_gallery,
                                auto_close_delay: 2000
                            });
                        }
                    }
                });
            });

            /* Delete selected images gallery */
            $('.wpmf-remove-imgs-btn').unbind('click').bind('click', function () {
                var ids = [];
                $('.wpmf_gallery_selection .gallery-attachment.selected').each(function (i, v) {
                    var id = $(v).data('id');
                    ids.push(id);
                });

                showDialog({
                    title: wpmf_glraddon.l18n.delete_selected_image,
                    negative: {
                        title: wpmf_glraddon.l18n.cancel
                    },
                    positive: {
                        title: wpmf_glraddon.l18n.delete,
                        onClick: function () {
                            $.ajax({
                                url: ajaxurl,
                                method: "POST",
                                dataType: 'json',
                                data: {
                                    action: "wpmfgallery",
                                    task: "delete_imgs_selected",
                                    ids: ids.join(),
                                    id_gallery: wpmfGalleryModule.wpmf_current_gallery,
                                    wpmf_gallery_nonce: wpmf_glraddon.vars.wpmf_gallery_nonce
                                },
                                success: function (res) {
                                    if (res.status) {
                                        $.each(ids, function (i, id) {
                                            $('.gallery-attachment[data-id="' + id + '"]').remove();
                                        });

                                        /* display notification */
                                        wpmfSnackbarModule.show({
                                            id: 'image_deleted',
                                            content: wpmf_glraddon.l18n.delete_img,
                                            auto_close_delay: 2000
                                        });

                                        wpmfGalleryModule.changeGallery(wpmfGalleryModule.wpmf_current_gallery);
                                    }
                                }
                            });
                        }
                    }
                });
            });

            /* Create gallery */
            $('.btn_edit_gallery').unbind('click').bind('click', function () {
                var $this = $(this);
                var title = $('.edit-gallery-name').val();
                var theme = $('.edit-gallery-theme').val();
                var layout = $('.edit-gallery-layout').val();
                var row_height = $('.edit-gallery-row_height').val();
                var aspect_ratio = $('.edit-gallery-aspect_ratio').val();
                var parent = $('.edit-gallery-parent').val();
                var columns = $('.edit-gallery-columns').val();
                var size = $('.edit-gallery-size').val();
                var targetsize = $('.edit-gallery-targetsize').val();
                var link = $('.edit-gallery-link').val();
                var orderby = $('.edit-gallery-orderby').val();
                var order = $('.edit-gallery-order').val();
                var animation = $('.edit-gallery-animation').val();
                var duration = $('.edit-gallery-duration').val();
                var auto_animation = $('.edit-gallery-auto_animation').val();
                var number_lines = $('.edit-gallery-number_lines').val();
                var gutterwidth = $('.edit-gallery-gutterwidth').val();
                var tree_width = $('.gallery_tree_width').val();
                var display_tree = 0;
                var display_tag = 0;
                var show_buttons = 0;
                var auto_from_folder = 0;
                if ($('.auto_from_folder').is(':checked')) {
                    auto_from_folder = 1;
                }

                var gallery_editor = $('#gallerylist').data('edited');
                if ($('.gallery_display_tree').is(':checked')) {
                    display_tree = 1;
                }

                if ($('.gallery_display_tag').is(':checked')) {
                    display_tag = 1;
                }

                if ($('.gallery_flow_show-buttons').is(':checked')) {
                    show_buttons = 1;
                }

                var folder = $('.wpmf-gallery-folder').val();

                var hover_color = $('.hover_color_input').val();
                var hover_opacity = $('.hover_opacity_input').val();
                var hover_title_position = $('.hover_title_position').val();
                var hover_title_size = $('.hover_title_size').val();
                var hover_title_color = $('.hover_title_color_input').val();
                var hover_desc_position = $('.hover_desc_position').val();
                var hover_desc_size = $('.hover_desc_size').val();
                var hover_desc_color = $('.hover_desc_color_input').val();

                /* Ajax edit gallery */
                $.ajax({
                    url: ajaxurl,
                    method: "POST",
                    dataType: 'json',
                    data: {
                        action: "wpmfgallery",
                        task: "edit_gallery",
                        id: wpmfGalleryModule.wpmf_current_gallery,
                        title: title,
                        theme: theme,
                        layout: layout,
                        row_height: row_height,
                        aspect_ratio: aspect_ratio,
                        parent: parent,
                        columns: columns,
                        size: size,
                        targetsize: targetsize,
                        link: link,
                        wpmf_orderby: orderby,
                        wpmf_order: order,
                        display_tree: display_tree,
                        display_tag: display_tag,
                        tree_width: tree_width,
                        animation: animation,
                        duration: duration,
                        auto_animation: auto_animation,
                        number_lines: number_lines,
                        gutterwidth: gutterwidth,
                        show_buttons: show_buttons,
                        auto_from_folder: auto_from_folder,
                        folder: folder,
                        hover_color: hover_color,
                        hover_opacity: hover_opacity,
                        hover_title_position: hover_title_position,
                        hover_title_size: hover_title_size,
                        hover_title_color: hover_title_color,
                        hover_desc_position: hover_desc_position,
                        hover_desc_size: hover_desc_size,
                        hover_desc_color: hover_desc_color,
                        wpmf_gallery_nonce: wpmf_glraddon.vars.wpmf_gallery_nonce
                    },
                    beforeSend: function () {
                        wpmfSnackbarModule.show({
                            id: 'wpmf-gallery-saving',
                            content: wpmf_glraddon.l18n.gallery_saving,
                            is_progress: true,
                            auto_close: false
                        });
                    },
                    success: function (res) {
                        if (res.status) {
                            wpmfSnackbarModule.close('wpmf-gallery-saving');
                            if ($this.hasClass('wpmf-modal-save') && parseInt(gallery_editor.gallery_id) === parseInt(wpmfGalleryModule.wpmf_current_gallery)) {
                                // set data params on element
                                $('#gallerylist').data('edited', {
                                    'gallery_id': gallery_editor.gallery_id,
                                    'idblock': gallery_editor.idblock,
                                    'display': theme,
                                    'layout': layout,
                                    'auto_from_folder': auto_from_folder,
                                    'folder': folder,
                                    'row_height': row_height,
                                    'aspect_ratio': aspect_ratio,
                                    'display_tree': display_tree,
                                    'display_tag': display_tag,
                                    'columns': columns,
                                    'size': size,
                                    'targetsize': targetsize,
                                    'link': link,
                                    'wpmf_orderby': orderby,
                                    'wpmf_order': order,
                                    'animation': animation,
                                    'duration': duration,
                                    'auto_animation': auto_animation,
                                    'number_lines': number_lines,
                                    'show_buttons': show_buttons,
                                    'gutterwidth': gutterwidth,
                                    'hover_color': hover_color,
                                    'hover_opacity': hover_opacity,
                                    'hover_title_position': hover_title_position,
                                    'hover_title_size': hover_title_size,
                                    'hover_title_color': hover_title_color,
                                    'hover_desc_position': hover_desc_position,
                                    'hover_desc_size': hover_desc_size,
                                    'hover_desc_color': hover_desc_color
                                });

                                wpmfSnackbarModule.show({
                                    id: 'save_gallery_modal',
                                    content: wpmf_glraddon.l18n.save_glr_modal,
                                    auto_close_delay: 5000
                                });
                            } else {
                                wpmfSnackbarModule.show({
                                    id: 'save_gallery',
                                    content: wpmf_glraddon.l18n.save_glr,
                                    auto_close_delay: 1000
                                });
                            }

                            // Update the categories variables
                            wpmfGalleryModule.shouldconfirm = false;
                            wpmfGalleryModule.gallery_details[res.items.term_id] = res.items;
                            wpmfGalleryModule.updateDropdownParent(res.dropdown_gallery);
                            wpmfGalleryModule.renderListstree(res, wpmfGalleryModule.wpmf_current_gallery, false);
                        }
                    }
                });

            });
        },

        updateDropdownParent: function (dropdown_gallery) {
            $('.sl-gallery-parent-wrap').html(dropdown_gallery);
            $('.form_edit_gallery .wpmf-gallery-categories').addClass('edit-gallery-parent');
            $('.form_add_gallery .wpmf-gallery-categories').addClass('new-gallery-parent');
        },

        getShortcode: function(theme, preview = false) {
            var renderShortCode = '[wpmfgallery';
            renderShortCode += ' gallery_id="' + wpmfGalleryModule.wpmf_current_gallery + '"';
            renderShortCode += ' display="' + theme + '"';
            renderShortCode += ' customlink="0"';
            $('.shortcode_param').each(function(){
                var param = $(this).data('param');
                if (param === 'show_buttons' || param === 'display_tree' || param === 'display_tag') {
                    if ($(this).is(':checked')) {
                        renderShortCode += ' ' + param + '="1"';
                    } else {
                        renderShortCode += ' ' + param + '="0"';
                    }
                } else {
                    var value = $(this).val();
                    if (preview) {
                        if (param === 'link') {
                            renderShortCode += ' link="none"';
                        } else {
                            renderShortCode += ' ' + param + '="' + value + '"';
                        }
                    } else {
                        renderShortCode += ' ' + param + '="' + value + '"';
                    }
                }
            });
            renderShortCode += ']';
            return renderShortCode;
        },

        renderShortcode: function (theme = 'default') {
            var renderShortCode = wpmfGalleryModule.getShortcode(theme);
            $('.gallery_shortcode_input').val(renderShortCode);
        },

        loadGalleryPreview: function () {
            var theme = $('.edit-gallery-theme').val();
            var shortcode = wpmfGalleryModule.getShortcode(theme, true);
            $.ajax({
                type: "POST",
                url: ajaxurl,
                data: {
                    action: "wpmfgallery",
                    task: "load_gallery_preview",
                    shortcode: shortcode,
                    wpmf_gallery_nonce: wpmf_glraddon.vars.wpmf_gallery_nonce
                },
                beforeSend: function () {
                    $('.preview-wrap').html('<img src="'+ wpmf_glraddon.vars.plugin_url_image +'Loading_icon.gif">');
                },
                success: function (res) {
                    if (res.status) {
                        var $container;
                        $('.preview-wrap').html(res.html);
                        switch (theme) {
                            case 'slider':
                                wpmfGalleryModule.initSlider($('.wpmfslick'));
                                break;
                            case 'masonry':
                                wpmfGalleryModule.initMasonry($('.gallery-masonry'), theme);
                                break;
                            case 'default':
                            case 'material':
                            case 'portfolio':
                            case 'square_grid':
                                $container = $('.glrdefault');
                                imagesLoaded($container, function () {
                                    $container.closest('.wpmf_gallery_wrap').find('.loading_gallery').hide();
                                    $container.find('figure').each(function (j, v) {
                                        if ((j + 1) % columns === 0) {
                                            $('.glrdefault').find('figure:nth(' + (j) + ')').after('<hr class="wpmfglr-line-break" />');
                                        }
                                    });
                                });
                                break;
                            case 'custom_grid':
                                $container = $('.wpmf-custom-grid');
                                if ($container.hasClass('wpmfInitPackery')) {
                                    $container.isotope('destroy');
                                }
                                imagesLoaded($container, function () {
                                    var gutter = parseInt($container.data('gutter'));
                                    $container.closest('.wpmf_gallery_wrap').find('.loading_gallery').hide();
                                    var wrap_width = $container.width();
                                    var one_col_width = (wrap_width - gutter*11)/12;
                                    $container.find('.grid-item').each(function() {
                                        var dimensions = jQuery(this).data('styles');
                                        var w = (typeof dimensions.width !== "undefined") ? parseInt(dimensions.width) : 2;
                                        var h = (typeof dimensions.height !== "undefined") ? parseInt(dimensions.height) : 2;
                                        var g = (parseInt(w) - 1)*gutter;
                                        var display_width = one_col_width;
                                        var display_height = one_col_width;

                                        if (w > 1) {
                                            display_width = one_col_width*w + g;
                                        }

                                        if (w == h) {
                                            display_height = display_width;
                                        } else {
                                            if (h > 1) {
                                                display_height = (one_col_width*h) + (h - 1)*gutter;
                                            }
                                        }

                                        jQuery(this).width(display_width);
                                        jQuery(this).height(display_height);
                                    });

                                    $container.isotope({
                                        itemSelector: '.grid-item',
                                        layoutMode: 'packery',
                                        resizable: true,
                                        initLayout: true,
                                        packery: {
                                            gutter: gutter
                                        }
                                    });
                                    $container.addClass('wpmfInitPackery');
                                });

                                break;
                            case 'flowslide':
                                $container = $('.flipster');
                                imagesLoaded($container, function () {
                                    $container.closest('.wpmf_gallery_wrap').find('.loading_gallery').hide();
                                    var enableNavButtons = $container.data('button');
                                    if (typeof enableNavButtons !== "undefined" && parseInt(enableNavButtons) === 1) {
                                        $container.flipster({
                                            style: 'coverflow',
                                            buttons: 'custom',
                                            spacing: 0,
                                            loop: true,
                                            autoplay: 5000,
                                            buttonNext: '<i class="flipto-next material-icons"> keyboard_arrow_right </i>',
                                            buttonPrev: '<i class="flipto-prev material-icons"> keyboard_arrow_left </i>',
                                            onItemSwitch: function (currentItem, previousItem) {
                                                $container.find('.flipster__container').height($(currentItem).height());
                                            },
                                            onItemStart: function (currentItem) {
                                                $container.find('.flipster__container').height($(currentItem).height());
                                            }
                                        });
                                    } else {
                                        $container.flipster({
                                            style: 'coverflow',
                                            spacing: 0,
                                            loop: true,
                                            autoplay: 5000,
                                            onItemSwitch: function (currentItem, previousItem) {
                                                $container.find('.flipster__container').height($(currentItem).height());
                                            },
                                            onItemStart: function (currentItem) {
                                                $container.find('.flipster__container').height($(currentItem).height());
                                            }
                                        });
                                    }
                                    wpmfGallery.callPopup();
                                });
                                break;
                        }
                    }
                }
            });
        },

        calculateGrid: function ($container) {
            var columns = parseInt($container.data('wpmfcolumns'));
            var gutterWidth = $container.data('gutter-width');
            var containerWidth = $container.width();

            if (isNaN(gutterWidth)) {
                gutterWidth = 5;
            } else if (gutterWidth > 500 || gutterWidth < 0) {
                gutterWidth = 5;
            }

            if (parseInt(columns) < 2 || containerWidth <= 450) {
                columns = 2;
            }

            gutterWidth = parseInt(gutterWidth);

            var allGutters = gutterWidth * (columns - 1);
            var contentWidth = containerWidth - allGutters;

            var columnWidth = Math.floor(contentWidth / columns);
            return {columnWidth: columnWidth, gutterWidth: gutterWidth, columns: columns};
        },

        initMasonry($container, theme = 'masonry') {
            imagesLoaded($container, function () {
                $container.closest('.wpmf_gallery_wrap').find('.loading_gallery').hide();
                var layout = $container.closest('.wpmf-gallerys').data('layout');
                if (layout === 'horizontal' && (theme === 'masonry' || theme === 'square_grid')) {
                    if ($container.hasClass('justified-gallery')) {
                        return;
                    }

                    var padding = $('.edit-gallery-gutterwidth').val();
                    var row_height = $('.edit-gallery-row_height').val();
                    if (typeof row_height === "undefined" || row_height === '') {
                        row_height = 200;
                    }
                    if (typeof padding === "undefined") {
                        padding = 5;
                    }
                    setTimeout(function () {
                        $container.justifiedGallery({
                            rowHeight: row_height,
                            margins: padding
                        });
                    },200);
                } else {
                    var $postBox = $container.children('.wpmf-gallery-item');
                    var o = wpmfGalleryModule.calculateGrid($container);
                    var padding = o.gutterWidth;
                    var duration = $('.edit-gallery-duration').val();
                    $postBox.css({'width': o.columnWidth + 'px', 'margin-bottom': padding + 'px'});

                    $container.masonry({
                        itemSelector: '.wpmf-gallery-item',
                        columnWidth: o.columnWidth,
                        gutter: padding,
                        isAnimated: true,
                        animationOptions: {
                            duration: duration,
                            easing: 'linear',
                            queue: false
                        },
                        isFitWidth: true
                    });

                    if ($container.hasClass('gallery-portfolio')) {
                        var w = $container.find('.attachment-thumbnail').width();
                        $container.find('.wpmf-caption-text.wpmf-gallery-caption , .gallery-icon').css('max-width', w + 'px');
                    }
                    $container.find('.wpmf-gallery-item').addClass('wpmf-gallery-item-show');
                }
            });
        },

        initSlider($container) {
            if ($container.hasClass('slick-initialized')) {
                return;
            }

            var animation = $container.data('animation');
            var duration = parseInt($container.data('duration'));
            var columns = parseInt($container.data('wpmfcolumns'));
            var number_lines = parseInt($container.data('number_lines'));
            var containerWidth = $container.width();
            if (parseInt(columns) >= 4 && containerWidth <= 450) {
                columns = 2;
            }
            var auto_animation = parseInt($container.data('auto_animation'));
            imagesLoaded($container, function () {
                $container.closest('.wpmf_gallery_wrap').find('.loading_gallery').hide();
                var slick_args = {
                    infinite: true,
                    slidesToShow: parseInt(columns),
                    slidesToScroll: parseInt(columns),
                    pauseOnHover: true,
                    autoplay: (auto_animation === 1),
                    adaptiveHeight: (parseInt(columns) === 1),
                    autoplaySpeed: duration,
                    rows: number_lines,
                    fade: (animation === 'fade'),
                    responsive: [
                        {
                            breakpoint: 1024,
                            settings: {
                                slidesToShow: 3,
                                slidesToScroll: 3,
                                infinite: true,
                                dots: true
                            }
                        },
                        {
                            breakpoint: 600,
                            settings: {
                                slidesToShow: 2,
                                slidesToScroll: 2
                            }
                        },
                        {
                            breakpoint: 480,
                            settings: {
                                slidesToShow: 1,
                                slidesToScroll: 1
                            }
                        }
                    ]
                };

                if (!$container.hasClass('slick-initialized')) {
                    $container.slick(slick_args);
                }
            });
        },

        /**
         * set a cookie
         * @param cname cookie name
         * @param cvalue cookie value
         * @param exdays
         */
        setCookie: function (cname, cvalue, exdays) {
            let d = new Date();
            d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
            let expires = "expires=" + d.toUTCString();
            document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
        },

        /**
         * get a cookie
         * @param cname cookie name
         * @returns {*}
         */
        getCookie: function (cname) {
            let name = cname + "=";
            let ca = document.cookie.split(';');
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) === ' ') {
                    c = c.substring(1);
                }
                if (c.indexOf(name) === 0) {
                    return c.substring(name.length, c.length);
                }
            }
            return "";
        }
    };

    $(document).on( 'wp-collapse-menu', function () {
        wpmfGalleryModule.initPackery();
    });

    // initialize WPMF gallery features
    $(document).ready(function () {
        $('.WpmfGalleryList').show();
        $('#wpmf-drop-overlay').appendTo($('body'));
        var body_height = $('body').height();
        //$('#main-gallery, #main-gallery-settings').css('min-height', body_height + 'px');
        wpmfGalleryModule.init();
        $('.shortcode_param').on('change',function(){
            var theme = $('#main-gallery-settings .wpmf-theme-item.selected').data('theme');
            wpmfGalleryModule.renderShortcode(theme);
        });

        $('.copy_shortcode_gallery').on('click',function () {
            var shortcode_value = $('.gallery_shortcode_input').val();
            wpmfFoldersModule.setClipboardText(shortcode_value, wpmf_glraddon.l18n.success_copy_shortcode);
        });
    });
})(jQuery);
String.prototype.hashCode = function () {
    var hash = 0, i, char;
    if (this.length === 0)
        return hash;
    for (i = 0, l = this.length; i < l; i++) {
        char = this.charCodeAt(i);
        hash = ((hash << 5) - hash) + char;
        hash |= 0; // Convert to 32bit integer
    }
    return Math.abs(hash);
};