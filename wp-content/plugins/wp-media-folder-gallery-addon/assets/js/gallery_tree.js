/**
 * Folder tree for WP Media Folder
 */
var wpmfGalleryTreeModule;
(function ($) {
    /**
     * Main folder tree function
     */
    wpmfGalleryTreeModule = {
        categories : [], // categories
        folders_states : [], // Contains open or closed status of galleries
        /**
         * Folder tree init
         */
        init: function () {
            wpmfGalleryTreeModule.categories_order = wpmf_glraddon.vars.categories_order;
            wpmfGalleryTreeModule.categories = wpmf_glraddon.vars.categories;

            wpmfGalleryTreeModule.importCategories();
            $gallerylist = $('.gallerylist');
            if ($gallerylist.length === 0) {
                return;
            }

            wpmfGalleryTreeModule.loadTreeView();

            // find previou selected gallery
            var first_id = wpmfGalleryTreeModule.getSelectedId();
            if (first_id !== 0) {
                $('.wpmf-gtree-item[data-id="'+ first_id +'"]').parents('li').removeClass('closed');
                wpmfGalleryTreeModule.glrTitleopengallery(first_id);
            }

            // Initialize change keyword to search folder
            $('.search_gallery_btn').on('click', function (e) {
                wpmfGalleryTreeModule.doSearch();
            });

            // search with enter key
            $('.wpmf_search_gallery_input').on('keyup', function (e) {
                wpmfGalleryTreeModule.doSearch();
            });

            wpmfGalleryModule.galleryEvent();
        },

        /**
         * Find previou selected gallery
         */
        getSelectedId: function() {
            var data_params = $('#gallerylist').data('edited');
            var prev_selected_id = wpmfGalleryModule.getCookie('wpmf_gallery_selected_' + wpmf_glraddon.vars.site_url);
            var prev_find_id = false;
            if (parseInt(data_params.gallery_id) === 0) {
                if (typeof prev_selected_id !== "undefined" && prev_selected_id !== '' && prev_selected_id !== null) {
                    if ($('.wpmf-gtree-item[data-id="'+ prev_selected_id +'"]').length) {
                        prev_find_id = true;
                    }
                }
            }

            if (!prev_find_id) {
                var first_id = data_params.gallery_id;
                if (parseInt(first_id) === 0) {
                    first_id = $('#gallerylist').find('.tree_view ul li:nth-child(2)').data('id');
                }
            } else {
                first_id = prev_selected_id;
            }
            return first_id;
        },

        /**
         *  Do search folder
         */
        doSearch: function () {
            // search on folder tree
            var keyword = $('.wpmf_search_gallery_input').val().trim().toLowerCase();
            var search_folders = [];
            // get folder when disable folders on right bar
            var folder_search = [];
            for (var folder_id in wpmfGalleryTreeModule.categories) {
                if (keyword !== '') {
                    keyword = keyword.trim().toLowerCase();
                    var folder_name = wpmfGalleryTreeModule.categories[folder_id].label;
                    folder_name = folder_name.trim().toLowerCase();
                    if (folder_name.indexOf(keyword) !== -1) {
                        folder_search.push(wpmfGalleryTreeModule.categories[folder_id].id);
                    }
                }
            }
            search_folders = folder_search;
            if (keyword !== '') {
                $('.wpmf-gallery-list li').not('.wpmf-gtree-item[data-id="0"]').addClass('folderhide');
                $.each(search_folders, function (i, v) {
                    $('.wpmf-gallery-list li[data-id="' + v + '"]').addClass('foldershow').removeClass('folderhide closed');
                    $('.wpmf-gallery-list li[data-id="' + v + '"]').parents('li').addClass('foldershow').removeClass('folderhide');
                });
            } else {
                $('.wpmf-gallery-list li').removeClass('foldershow folderhide');
            }
        },

        loadTreeView: function () {
            wpmfGalleryTreeModule.getTreeElement().html(wpmfGalleryTreeModule.getRendering());
            $('.wpmf-gtree-item').unbind('click').bind('click', function (e) {
                if (!$(e.target).hasClass('tree_arrow_right_icon')) {
                    var id = $(this).data('id');
                    wpmfGalleryTreeModule.glrTitleopengallery(id);
                }
            });

            wpmfGalleryTreeModule.initContainerResizing();
            $('.tree-left-wrap').scrollbar();
            $(window).on('resize', function () {
                wpmfGalleryTreeModule.initContainerResizing();
            });

            $(document).on('wp-collapse-menu', function (state) {
                wpmfGalleryTreeModule.initContainerResizing();
            });

            $('.tree_view .wpmf-gtree-item').unbind('contextmenu').bind('contextmenu', function (e) {
                if (parseInt($(e.target).data('id')) === 0 || $(e.target).closest('li').data('id') === 0) {
                    return false;
                }

                wpmfGalleryModule.houtside();
                let x = e.clientX;     // Get the horizontal coordinate
                let y = e.clientY;
                if ($(e.target).hasClass('wpmf-gtree-item')) {
                    wpmfGalleryModule.target_gallery = $(e.target).data('id');
                } else {
                    wpmfGalleryModule.target_gallery = $(e.target).closest('li').data('id');
                }

                if (x + $('.wpmf-gallery-contextmenu').width() + 236 > $(window).width()) {
                    $('.wpmf-gallery-contextmenu').slideDown(200).css({
                        'right': $(window).width() - x + 'px',
                        'left': 'auto',
                        'top': y + 'px'
                    });
                } else {
                    $('.wpmf-gallery-contextmenu').slideDown(200).css({
                        'left': x + 'px',
                        'right': 'auto',
                        'top': y + 'px'
                    });
                }

                return false;
            });

            $('body').bind('click', function (e) {
                wpmfGalleryModule.houtside();
            });

            wpmfGalleryTreeModule.getTreeElement().find('.wpmf-gallery-list').sortable({
                items: 'li:not(li[data-id="0"])',
                placeholder: 'wpmf_gallery_drop_sort',
                delay: 100, // Prevent dragging when only trying to click
                distance: 10,
                cursorAt: {top: 10, left: 10},
                revert: true,
                revertDuration: 1000,
                /*tolerance: "intersect",*/
                helper: function (ui) {
                    var helper = '<div class="wpmf-move-gallery-element">';
                    helper += '<span class="mdc-list-item__start-detail"><i class="material-icons wpmf-icon-category">folder</i></span>';
                    helper += '<span class="mdc-list-item__text"> '+ wpmf_glraddon.l18n.gallery_moving_text +' </span>';
                    helper += '</div>';
                    return helper;
                },
                /** Prevent firefox bug positionnement **/
                start: function (event, ui) {
                    wpmfGalleryTreeModule.getTreeElement().addClass('wpmf_gallery_sorting');
                    var userAgent = navigator.userAgent.toLowerCase();
                    if (ui.helper !== "undefined" && userAgent.match(/firefox/)) {
                        ui.helper.css('position', 'absolute');
                    }
                },
                stop: function (event, ui) {
                    wpmfGalleryTreeModule.getTreeElement().removeClass('wpmf_tree_sorting');
                },
                beforeStop: function (event, ui) {

                },
                update: function (event, ui) {
                    var order = '';
                    $(event.target).find('li').each(function (i, val) {
                        var id = $(val).data('id');
                        if (id !== 0) {
                            if (order !== '') {
                                order += ',';
                            }
                            order += '"' + i + '":' + id;
                        }
                    });

                    order = '{' + order + '}';

                    $.ajax({
                        type: "POST",
                        url: ajaxurl,
                        data: {
                            action: "wpmfgallery",
                            task: "reorder_gallery",
                            order: order,
                            wpmf_gallery_nonce: wpmf_glraddon.vars.wpmf_gallery_nonce
                        },
                        success: function (res) {
                            wpmfSnackbarModule.show({
                                id: 'undo_movefolder',
                                content: wpmf_glraddon.l18n.moved_gallery,
                                icon: '<span class="material-icons-outlined wpmf-snack-icon">trending_flat</span>',
                            });
                        }
                    });
                }
            }).disableSelection();

            if ($().droppable) {
                // Initialize dropping folder on tree view
                wpmfGalleryTreeModule.getTreeElement().find('ul li .wpmf-gallery-item-inside').droppable({
                    hoverClass: "wpmf-hover-gallery",
                    tolerance: 'pointer',
                    over: function (event, ui) {
                        $('.wpmf_gallery_drop_sort').hide();
                    },
                    out: function (event, ui) {
                        $('.wpmf_gallery_drop_sort').show();
                    },
                    drop: function (event, ui) {
                        event.stopPropagation();
                        $(ui.helper).addClass('wpmf-gallery-dragout');
                        wpmfGalleryTreeModule.moveGallery($(ui.draggable).data('id'), $(this).data('id'));
                    }
                });
            }
        },

        moveGallery: function(folder_id, folder_to_id) {
            return $.ajax({
                type: "POST",
                url: wpmf.vars.ajaxurl,
                data: {
                    action: "wpmfgallery",
                    task: "move_gallery",
                    id: folder_id,
                    id_category: folder_to_id,
                    selected_gallery: wpmfGalleryModule.wpmf_current_gallery,
                    wpmf_gallery_nonce: wpmf_glraddon.vars.wpmf_gallery_nonce
                },
                beforeSend: function () {

                },
                success: function (res) {
                    wpmfSnackbarModule.show({
                        id: 'undo_movefolder',
                        content: wpmf_glraddon.l18n.moved_gallery,
                        icon: '<span class="material-icons-outlined wpmf-snack-icon">trending_flat</span>',
                    });

                    if (res.status) {
                        wpmfGalleryModule.updateDropdownParent(res.dropdown_gallery);
                        // Update the categories variables
                        wpmfGalleryTreeModule.categories = res.categories;
                        wpmfGalleryTreeModule.categories_order = res.categories_order;
                        wpmfGalleryTreeModule.importCategories();
                        // Reload the folders
                        wpmfGalleryTreeModule.loadTreeView();
                    }
                }
            });
        },

        /**
         * import gallery category
         */
        importCategories: function () {
            var galleries_ordered = [];

            // Add each category
            $(wpmfGalleryTreeModule.categories_order).each(function (i, v) {
                galleries_ordered.push(wpmfGalleryTreeModule.categories[this]);
            });
            galleries_ordered = galleries_ordered.sort(function(a, b){return a.order - b.order});
            // Reorder array based on children
            var galleries_ordered_deep = [];
            var processed_ids = [];
            const loadChildren = function (id) {
                if (processed_ids.indexOf(id) < 0) {
                    processed_ids.push(id);
                    for (var ij = 0; ij < galleries_ordered.length; ij++) {
                        if (galleries_ordered[ij].parent_id === id) {
                            galleries_ordered_deep.push(galleries_ordered[ij]);
                            loadChildren(galleries_ordered[ij].id);
                        }
                    }
                }
            };
            loadChildren(0);

            // Finally save it to the global var
            wpmfGalleryTreeModule.categories = galleries_ordered_deep;
            if (wpmfGalleryTreeModule.categories.length <= 1) {
                $('.form_edit_gallery').hide();
            } else {
                $('.form_edit_gallery').show();
            }
        },

        /**
         * Get the html resulting tree view
         * @return {string}
         */
        getRendering: function () {
            var ij = 0;
            var content = ''; // Final tree view content

            // get last status folder tree
            var lastStatusGalleryTree = wpmfGalleryModule.getCookie('lastStatusGalleryTree_' + wpmf_glraddon.vars.site_url);
            if (lastStatusGalleryTree !== '') {
                lastStatusGalleryTree = JSON.parse(lastStatusGalleryTree);
            }

            /**
             * Recursively print list of folders
             * @return {boolean}
             */
            const generateList = function () {
                content += '<ul class="wpmf-gallery-list">';
                var lists = wpmfGalleryTreeModule.categories;
                while (ij < lists.length) {
                    // Open li tag
                    var className = '';
                    if (lastStatusGalleryTree.indexOf(lists[ij].id) !== -1 || parseInt(lists[ij].id) === 0) {
                        className += 'open ';
                    } else {
                        className += 'closed ';
                    }

                    var first_id = wpmfGalleryTreeModule.getSelectedId();
                    if (first_id !== 0 && parseInt(lists[ij].id) === parseInt(first_id)) {
                        className += 'selected';
                    }

                    var pad = (lists[ij].depth) * 30;
                    content += '<li class="'+ className +'" data-id="' + lists[ij].id + '" data-parent_id="' + lists[ij].parent_id + '">';

                    content += '<div class="wpmf-gtree-item" data-id="' + lists[ij].id + '" data-parent_id="' + lists[ij].parent_id + '">';
                    content += '<div class="wpmf-gallery-item-inside" data-id="' + lists[ij].id + '" data-parent_id="' + lists[ij].parent_id + '" style="padding-left: '+ pad +'px">';
                    if (parseInt(lists[ij].id) === 0) {
                        content += '<i class="wpmf-gallery-item-icon wpmf-gallery-item-icon-root"></i>';
                    } else {
                        if (lists[ij + 1] && lists[ij + 1].depth > lists[ij].depth) {
                            // The next element is a sub folder
                            content += '<a class="wpmf-toggle" onclick="wpmfGalleryTreeModule.toggle(' + lists[ij].id + ')"><i class="tree_arrow_right_icon wpmf-arrow"></i></a>';
                        } else {
                            content += '<a class="wpmf-toggle wpmf-no-toggle" onclick="wpmfGalleryTreeModule.toggle(' + lists[ij].id + ')"><i class="tree_arrow_right_icon wpmf-arrow"></i></a>';
                        }

                        if (typeof lists[ij].feature_image !== "undefined" && lists[ij].feature_image !== '') {
                            content += '<img class="wpmf-gallery-thumbnail-icon" src="'+ lists[ij].feature_image +'">';
                        } else {
                            content += '<img class="wpmf-gallery-thumbnail-icon wpmf-gallery-thumbnail-icon-default" src="'+ wpmf_glraddon.vars.plugin_url_image +'image-gallery-icon.png">';
                        }
                    }
                    content += '<div class="wpmf-gallery-text">' + lists[ij].label + '</div>';
                    content += '</div>';
                    content += '</div>';
                    // This is the end of the array
                    if (lists[ij + 1] === undefined) {
                        // Let's close all opened tags
                        for (var ik = lists[ij].depth; ik >= 0; ik--) {
                            content += '</li>';
                            content += '</ol>';
                        }

                        // We are at the end don't continue to process array
                        return false;
                    }

                    if (lists[ij + 1].depth > lists[ij].depth) {
                        // The next element is a sub folder
                        // Recursively list it
                        ij++;
                        if (generateList() === false) {
                            // We have reached the end, let's recursively end
                            return false;
                        }
                    } else if (lists[ij + 1].depth < lists[ij].depth) {
                        // The next element don't have the same parent
                        // Let's close opened tags
                        for (var _ik = lists[ij].depth; _ik > lists[ij + 1].depth; _ik--) {
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
         * Initialize folder tree resizing
         */
        initContainerResizing: function() {
            var window_width = $(window).width();
            if (window_width <= 768) {
                return;
            }

            var is_resizing = false;
            $(window).on('resize', function () {
                $('.tree-left-wrap').scrollbar();
            });
            // Main upload.php page
            var $main = $('#WpmfGalleryList');
            var $tree = $('.gallerylist');
            var $right_min_width = 500;
            var $tree_min_width = 300;
            if (!$tree.find('.gallerylist-resize').length) {
                $('<div class="gallerylist-resize"></div>').appendTo($tree);
            }
            var $handle = $tree.find('.gallerylist-resize');
            $handle.on('mousedown', function (e) {
                is_resizing = true;
                $('body').css('user-select', 'none'); // prevent content selection while moving
            });

            var tree_width = parseInt(wpmfGalleryModule.getCookie('wpmf-gallery-tree-size'));
            if (tree_width < $tree_min_width) tree_width = $tree_min_width;
            var right_width = $main.width() - tree_width;
            if (right_width < $right_min_width) {
                right_width = $right_min_width;
                tree_width = $main.width() - $right_min_width;
            }

            if (window_width > 1024 && right_width < 850) {
                $('.WpmfGalleryList').addClass('wpmf-small-right-screen');
            } else {
                $('.WpmfGalleryList').removeClass('wpmf-small-right-screen');
            }

            if (typeof tree_width !== "undefined" && parseFloat(tree_width) > 0 && tree_width != 300) {
                $tree.css({ 'width': parseFloat(tree_width) + 'px' });
                if ($('body').hasClass('media_page_media-folder-galleries')) {
                    $('.form_edit_gallery').css({'width': (right_width + 20) + 'px', 'margin-left': tree_width + 'px'});
                } else {
                    $('.form_edit_gallery').css({'width': right_width + 'px', 'margin-left': tree_width + 'px'});
                }
            }

            $(document).on('mousemove', function (e) {
                // we don't want to do anything if we aren't resizing.
                if (!is_resizing || !wpmfGalleryModule.is_resizing) return;
                // Calculate tree width
                var tree_width = parseInt(e.clientX - $tree.offset().left);
                if (tree_width < $tree_min_width) tree_width = $tree_min_width;
                var right_width = $main.width() - tree_width;
                if (right_width < $right_min_width) {
                    right_width = $right_min_width;
                    tree_width = $main.width() - $right_min_width;
                }

                if (window_width > 1024 && right_width < 850) {
                    $('.WpmfGalleryList').addClass('wpmf-small-right-screen');
                } else {
                    $('.WpmfGalleryList').removeClass('wpmf-small-right-screen');
                }

                $tree.css('width', tree_width + 'px');
                // We have to set margin if we are in a fixed tree position or in list page
                if ($('body').hasClass('media_page_media-folder-galleries')) {
                    $('.form_edit_gallery').css({'width': (right_width + 20) + 'px', 'margin-left': tree_width + 'px'});
                } else {
                    $('.form_edit_gallery').css({'width': right_width + 'px', 'margin-left': tree_width + 'px'});
                }
                wpmfGalleryModule.setCookie('wpmf-gallery-tree-size', tree_width, 365);
            }).on('mouseup', function (e) {
                if (is_resizing) {
                    // stop resizing
                    is_resizing = false;
                    $('body').css('user-select', '');
                    $(window).trigger('resize');
                }
            });
        },

        /**
         * Toggle the open / closed state of a gallery
         * @param gallery_id
         */
        toggle : function(gallery_id) {
            // Check is gallery has closed class
            if (wpmfGalleryTreeModule.getTreeElement().find('li[data-id="' + gallery_id + '"]').hasClass('closed')) {
                // Open the gallery
                wpmfGalleryTreeModule.glropengallery(gallery_id);
            } else {
                // Close the gallery
                wpmfGalleryTreeModule.glrclosedir(gallery_id);
                // close all sub gallery
                $('li[data-id="' + gallery_id + '"]').find('li').addClass('closed');
            }

            var lastStatusGalleryTree = [];
            wpmfGalleryTreeModule.getTreeElement().find('li:not(.closed)').each(function (i, v) {
                var id = $(v).data('id');
                lastStatusGalleryTree.push(id);
            });
            // set last status folder tree
            wpmfGalleryModule.setCookie("lastStatusGalleryTree_" + wpmf_glraddon.vars.site_url, JSON.stringify(lastStatusGalleryTree), 365);
        },

        /**
         * open gallery tree by dir name
         * @param gallery_id
         */
        glropengallery : function(gallery_id) {
            wpmfGalleryTreeModule.getTreeElement().find('li[data-id="' + gallery_id + '"]').removeClass('closed');
            wpmfGalleryTreeModule.folders_states[gallery_id] = 'open';
        },

        /**
         * open gallery tree by dir name
         */
        glrTitleopengallery : function(gallery_id, reload = false) {
            if (parseInt(gallery_id) === 0 || (wpmfGalleryModule.wpmf_current_gallery === gallery_id && !reload)) {
                return;
            }

            if (wpmfGalleryModule.is_gallery_loading) {
                return;
            }

            if (wpmfGalleryModule.shouldconfirm) {
                showDialog({
                    title: wpmf_glraddon.l18n.save_changes,
                    text: wpmf_glraddon.l18n.leave_site_msg_1 + '<br>' + wpmf_glraddon.l18n.leave_site_msg_2,
                    cancelable: true,
                    closeicon: false,
                    id: 'wpmf-gallery-save',
                    negative: {
                        title: wpmf_glraddon.l18n.discard,
                        id: 'wpmf-dl-cancel-edit-image',
                        onClick: function () {
                            wpmfGalleryModule.shouldconfirm = false;
                            wpmfGalleryTreeModule.doChangeGallery(gallery_id);
                        }
                    },
                    positive: {
                        title: wpmf_glraddon.l18n.save_settings,
                        onClick: function () {
                            $('.gallery-toolbar .btn_edit_gallery').trigger('click');
                        }
                    }
                });
                return;
            }

            wpmfGalleryTreeModule.doChangeGallery(gallery_id);
        },

        doChangeGallery: function(gallery_id) {
            wpmfGalleryTreeModule.getTreeElement().find('li').removeClass('selected');
            wpmfGalleryTreeModule.getTreeElement().find('li[data-id="' + gallery_id + '"]').addClass('selected');
            //wpmfGalleryTreeModule.folders_states[gallery_id] = 'open';
            wpmfGalleryModule.changeGallery(gallery_id);

            wpmfGalleryModule.wpmf_current_gallery = gallery_id;
            $('.select_gallery_id').val(gallery_id);
        },

        /**
         * Close a gallery and hide children
         * @param gallery_id
         */
        glrclosedir : function(gallery_id) {
            wpmfGalleryTreeModule.getTreeElement().find('li[data-id="' + gallery_id + '"]').addClass('closed');
            wpmfGalleryTreeModule.folders_states[gallery_id] = 'close';
        },

        /**
         * Retrieve the Jquery tree view element
         * of the current frame
         * @return jQuery
         */
        getTreeElement : function() {
            return $('.tree_view');
        },

        /**
         * init event click to open/close gallery tree
         */
        deleteGallery: function (id) {
            /* Delete gallery */
            showDialog({
                title: wpmf_glraddon.l18n.delete_gallery,
                negative: {
                    title: wpmf_glraddon.l18n.cancel
                },
                positive: {
                    title: wpmf_glraddon.l18n.delete,
                    onClick: function () {
                        $.ajax({
                            type: "POST",
                            url: ajaxurl,
                            data: {
                                action: "wpmfgallery",
                                task: "delete_gallery",
                                id: id,
                                wpmf_gallery_nonce: wpmf_glraddon.vars.wpmf_gallery_nonce
                            },
                            success: function (res) {
                                /* remove gallery html */
                                if (res.status) {
                                    $('#gallerylist').find('[data-id="' + id + '"]').remove();
                                    $('.wpmf-gallery-categories option[value="' + id + '"]').remove();
                                    var first_id = $('#gallerylist').find('.tree_view ul li:nth-child(2)').data('id');
                                    wpmfGalleryTreeModule.glrTitleopengallery(first_id);

                                    /* display notification */
                                    wpmfSnackbarModule.show({
                                        id: 'gallery_deleted',
                                        content : wpmf_glraddon.l18n.delete_glr,
                                        auto_close_delay: 2000
                                    });
                                }
                            }
                        });
                    }
                }
            });
        }
    };

    // initialize WPMF gallery tree features
    $(document).ready(function () {
        wpmfGalleryTreeModule.init();
    });
})(jQuery);


