var wpmfGalleryImportModule;
(function ($) {
    wpmfGalleryImportModule = {
        categories: [],
        categories_order: [],
        isImportCategories: false,
        init: function () {
            $('.btn_import_fromwp').on('click', function () {
                var themes_select = '<p>';
                themes_select += '<label class="wpmf-gallery-label">' + wpmf_glraddon.l18n.select_theme_label + '</label>';
                themes_select += '<select class="wpmf_gallery_theme ju-select" name="wpmf_gallery_theme">';
                $.each(wpmf_glraddon.vars.themes, function (key, label) {
                    if (key === 'masonry') {
                        themes_select += '<option value="' + key + '" selected>' + label + '</option>';
                    } else {
                        themes_select += '<option value="' + key + '">' + label + '</option>';
                    }
                });
                themes_select += '</select></p>';

                wpmfSnackbarModule.show({
                    id: 'wpmftree-loading',
                    content: wpmf_glraddon.l18n.folder_listing,
                    is_progress: true,
                    auto_close: false
                });

                setTimeout(function () {
                    if (!wpmfGalleryImportModule.isImportCategories) {
                        wpmfGalleryImportModule.categories_order = wpmf.vars.wpmf_categories_order;
                        wpmfGalleryImportModule.categories = wpmf.vars.wpmf_categories;
                        wpmfGalleryImportModule.importCategories();
                        wpmfGalleryImportModule.isImportCategories = true;
                    }

                    showDialog({
                        title: wpmf_glraddon.l18n.create_gallery_desc,
                        id: 'import-wpmf-dialog',
                        text: '<div class="wpmf_categories_tree"></div>' + themes_select,
                        negative: {
                            title: wpmf_glraddon.l18n.cancel,
                        },
                        positive: {
                            title: wpmf_glraddon.l18n.create,
                            onClick: function () {
                                var ids = [];
                                $('.wpmf_checked').each(function (i, checkbox) {
                                    var id = $(checkbox).closest('.wpmf-item').data('id');
                                    if (parseInt(id) !== 0) {
                                        ids.push(id);
                                    }
                                });

                                if (ids.length) {
                                    wpmfGalleryImportModule.getGalleryFolders(1, ids, true);
                                }
                            }
                        }
                    });

                    // Render the tree view
                    wpmfGalleryImportModule.loadTreeView();
                    wpmfGalleryImportModule.handleClick();
                }, 50);
            });
        },

        handleClick: function () {
            $('.wpmf-check').unbind('click').bind('click', function () {
                if ($(this).closest('.wpmf-item-check').hasClass('wpmf_checked')) {
                    $(this).closest('.wpmf-item-check').removeClass('wpmf_checked').addClass('wpmf_notchecked');
                    $(this).closest('li').find('ul .wpmf-item-check').removeClass('wpmf_checked').addClass('wpmf_notchecked');
                } else {
                    $(this).closest('.wpmf-item-check').addClass('wpmf_checked').removeClass('wpmf_notchecked');
                    $(this).closest('li').find('ul .wpmf-item-check').addClass('wpmf_checked').removeClass('wpmf_notchecked');
                }
                var parents = $(this).parents('li');
                $.each(parents, function (i, parent) {
                    var checked_length = $(parent).find(' > .wpmf_trees > li > .wpmf-item .wpmf_checked').length;
                    var not_checked_length = $(parent).find(' > .wpmf_trees > li > .wpmf-item .wpmf_notchecked').length;
                    if (checked_length && not_checked_length) {
                        $(parent).find('> .wpmf-item .wpmf-item-check').removeClass('wpmf_checked wpmf_notchecked').addClass('wpmf_part_checked');
                    }

                    if (checked_length && !not_checked_length) {
                        $(parent).find('> .wpmf-item .wpmf-item-check').removeClass('wpmf_part_checked wpmf_notchecked').addClass('wpmf_checked');
                    }

                    if (!checked_length && not_checked_length) {
                        $(parent).find('> .wpmf-item .wpmf-item-check').removeClass('wpmf_part_checked wpmf_checked').addClass('wpmf_notchecked');
                    }
                });

                if ($('.wpmf_checked').length) {
                    $('.wpmf_import_selected_btn').show();
                    $('.wpmf_import_all_btn').hide();
                } else {
                    $('.wpmf_import_selected_btn').hide();
                    $('.wpmf_import_all_btn').show();
                }
            });
        },

        getGalleryFolders: function (paged, ids = [], first = true) {
            var data = {
                action: "wpmfgallery",
                task: 'wpmf_get_gallery_folders',
                theme: $('.wpmf_gallery_theme').val(),
                first: (first) ? 1 : 0,
                paged: paged,
                ids: ids.join(),
                wpmf_gallery_nonce: wpmf_glraddon.vars.wpmf_gallery_nonce
            };

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: data,
                beforeSend: function () {
                    if (!$('[data-id="wpmf-gallery-importing"]').length) {
                        wpmfSnackbarModule.show({
                            id: 'wpmf-gallery-importing',
                            content: wpmf_glraddon.l18n.gallery_importing,
                            is_progress: true,
                            auto_close: false
                        });
                    }
                },
                success: function (res) {
                    if (res.status) {
                        if (res.continue) {
                            wpmfGalleryImportModule.getGalleryFolders(parseInt(paged) + 1, ids, false);
                        } else {
                            // update parent and add object
                            wpmfGalleryImportModule.importGalleryFolders(1)
                        }
                    }
                }
            });
        },

        importGalleryFolders: function (paged) {
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: "wpmfgallery",
                    task: 'wpmf_import_gallery_folders',
                    paged: paged,
                    wpmf_gallery_nonce: wpmf_glraddon.vars.wpmf_gallery_nonce
                },
                success: function (res) {
                    if (res.status) {
                        if (res.continue) {
                            wpmfGalleryImportModule.importGalleryFolders(parseInt(paged) + 1)
                        } else {
                            wpmfSnackbarModule.close('wpmf-gallery-importing');
                            location.reload();
                        }
                    }
                }
            });
        },

        importCategories: function () {
            var folders_ordered = [];
            // Add each category
            $(wpmfGalleryImportModule.categories_order).each(function () {
                folders_ordered.push(wpmfGalleryImportModule.categories[this]);
            });

            // Reorder array based on children
            var folders_ordered_deep = [];
            var processed_ids = [];
            var loadChildren = function loadChildren(id) {
                if (processed_ids.indexOf(id) < 0) {
                    processed_ids.push(id);
                    for (var ij = 0; ij < folders_ordered.length; ij++) {
                        if (parseInt(folders_ordered[ij].parent_id) === parseInt(id) && folders_ordered[ij].label !== 'Gallery Upload') {
                            folders_ordered_deep.push(folders_ordered[ij]);
                            loadChildren(folders_ordered[ij].id);
                        }
                    }
                }
            };
            loadChildren(0);

            // Finally save it to the global var
            wpmfGalleryImportModule.categories = folders_ordered_deep;
        },

        /**
         * Render tree view inside content
         */
        loadTreeView: function () {
            $('.wpmf_categories_tree').html(wpmfGalleryImportModule.getRendering());
            wpmfSnackbarModule.close('wpmftree-loading');
        },

        /**
         * Get the html resulting tree view
         * @return {string}
         */
        getRendering: function () {
            var ij = 0;
            var content = '';

            /**
             * Recursively print list of folders
             * @return {boolean}
             */
            var generateList = function () {
                content += '<ul class="wpmf_trees">';
                while (ij < wpmfGalleryImportModule.categories.length) {
                    var className = 'closed ';
                    // Open li tag
                    content += '<li class="' + className + '" data-id="' + wpmfGalleryImportModule.categories[ij].id + '">';
                    content += '<div class="wpmf-item" data-id="' + wpmfGalleryImportModule.categories[ij].id + '">';
                    content += '<div class="wpmf-item-inside" data-id="' + wpmfGalleryImportModule.categories[ij].id + '">';
                    var a_tag = '<a class="wpmf-text-item" data-id="' + wpmfGalleryImportModule.categories[ij].id + '">';
                    if (wpmfGalleryImportModule.categories[ij + 1] && wpmfGalleryImportModule.categories[ij + 1].depth > wpmfGalleryImportModule.categories[ij].depth) {
                        // The next element is a sub folder
                        content += '<a class="wpmf-toggle-icon" onclick="wpmfGalleryImportModule.toggle(' + wpmfGalleryImportModule.categories[ij].id + ')"><i class="material-icons wpmfgallery-arrow">arrow_right</i></a>';
                    } else {
                        content += '<a class="wpmf-toggle-icon wpmf-notoggle-icon"><i class="material-icons wpmfgallery-arrow">arrow_right</i></a>';
                    }

                    if (parseInt(wpmfGalleryImportModule.categories[ij].id) !== 0) {
                        content += '<a class="wpmf-item-check wpmf_notchecked"><span class="material-icons wpmf-check wpmf-item-checkbox-checked"> check_box </span><span class="material-icons wpmf-check wpmf-item-checkbox"> check_box_outline_blank </span><span class="material-icons wpmf-check wpmf-item-part-checkbox"> indeterminate_check_box </span></a>';
                    }
                    content += a_tag;

                    if (parseInt(wpmfGalleryImportModule.categories[ij].id) === 0) {
                        content += '<i class="wpmf-icon-root"></i>';
                    } else {
                        content += '<i class="material-icons wpmf-item-icon">folder</i>';
                    }
                    content += '<span class="wpmf-item-title" data-id="'+ wpmfGalleryImportModule.categories[ij].id +'">' + wpmfGalleryImportModule.categories[ij].label + '</span>';
                    content += '</a>';
                    content += '</div>';
                    content += '</div>';

                    // This is the end of the array
                    if (wpmfGalleryImportModule.categories[ij + 1] === undefined) {
                        // Let's close all opened tags
                        for (var ik = wpmfGalleryImportModule.categories[ij].depth; ik >= 0; ik--) {
                            content += '</li>';
                            content += '</ul>';
                        }

                        // We are at the end don't continue to process array
                        return false;
                    }

                    if (wpmfGalleryImportModule.categories[ij + 1].depth > wpmfGalleryImportModule.categories[ij].depth) {
                        // The next element is a sub folder
                        // Recursively list it
                        ij++;
                        if (generateList() === false) {
                            // We have reached the end, let's recursively end
                            return false;
                        }
                    } else if (wpmfGalleryImportModule.categories[ij + 1].depth < wpmfGalleryImportModule.categories[ij].depth) {
                        // The next element don't have the same parent
                        // Let's close opened tags
                        for (var _ik = wpmfGalleryImportModule.categories[ij].depth; _ik > wpmfGalleryImportModule.categories[ij + 1].depth; _ik--) {
                            content += '</li>';
                            content += '</ul>';
                        }

                        // We're not at the end of the array let's continue processing it
                        return true;
                    }

                    // Close the current element
                    content += '</li>';
                    ij++;
                }
            };
            // Start generation
            generateList();
            return content;
        },

        /**
         * Toggle the open / closed state of a folder
         * @param folder_id
         */
        toggle: function (folder_id) {
            // Check is folder has closed class
            if ($('.wpmf_categories_tree').find('li[data-id="' + folder_id + '"]').hasClass('closed')) {
                // Open the folder
                wpmfGalleryImportModule.openFolder(folder_id);
            } else {
                // Close the folder
                wpmfGalleryImportModule.closeFolder(folder_id);
                // close all sub folder
                $('li[data-id="' + folder_id + '"]').find('li').addClass('closed');
            }
        },

        /**
         * Open a folder to show children
         */
        openFolder: function (folder_id) {
            $('.wpmf_categories_tree').find('li[data-id="' + folder_id + '"]').removeClass('closed');
        },

        /**
         * Close a folder and hide children
         */
        closeFolder: function (folder_id) {
            $('.wpmf_categories_tree').find('li[data-id="' + folder_id + '"]').addClass('closed');
        }
    };

    $(document).ready(function () {
        wpmfGalleryImportModule.init();
    });
})(jQuery);