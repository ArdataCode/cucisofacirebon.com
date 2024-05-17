/**
 * Folder tree for WP Media Folder
 */
let wpmfGalleryTree;
(function ($) {
    wpmfGalleryTree = {
        galleries_tree: {},
        /**
         * Initialize module related things
         */
        initModule: function ($current) {
            // Render the tree view
            let top_gallery_id = $current.data('id');  // get gallery ID inserted content
            //$current.html(wpmfGalleryTree.getRendering($current));
            $current.find('.wpmf-gallery-toggle-icon').on('click', function () {
                // single click
                if ($(this).closest('li').hasClass('closed')) {
                    $(this).closest('li').removeClass('closed');
                } else {
                    $(this).closest('li').addClass('closed');
                }
            });

            // Initialize double click to folder title on tree view
            $current.find('.wpmf-gallery-tree-item').on('click', function (e) {
                if ($(e.target).hasClass('wpmf-gallery-arrow')) {
                    return;
                }

                // single click
                let id = $(this).data('id');
                $current.find('ul li').removeClass('open selected');
                $(this).closest('li').addClass('open selected');
                wpmfGalleryTree.changeFolder($current, id, $('.wpmf_gallery_tree[data-id="' + top_gallery_id + '"]'));
                wpmfGalleryTree.loadGallery($current, id, top_gallery_id);
            });

            let galleryIdStart = wpmfGalleryTree.getGalleryIDStart($current);
            let tree_hash = window.location.hash;
            tree_hash = tree_hash.replace('#', '');
            if (tree_hash !== '') {
                let hasha = tree_hash.split('-');
                if (hasha[1].indexOf('wpmfgallery') !== -1) {
                    let args = hasha[1].split('+');
                    if (galleryIdStart !== args[1]) {
                        wpmfGalleryTree.changeFolder($current, galleryIdStart, $('.wpmf_gallery_tree[data-id="' + args[1] + '"]'));
                        wpmfGalleryTree.loadGallery($current, galleryIdStart, top_gallery_id);
                    }
                }
            } else {
                $('.wpmf_gallery_wrap').each(function () {
                    var $this = $(this);
                    var type = $this.data('selected');
                    if (type === 'child') {
                        var selected_id = $this.find('.wpmf_gallery_tree >ul>li>ul>li').data('id');
                        if (typeof selected_id !== "undefined") {
                            wpmfGalleryTree.changeFolder($this, selected_id, $this);
                            wpmfGalleryTree.loadGallery($this, selected_id, $this.data('id'));
                        }
                    }
                });

            }
        },

        loadGallery: function ($current, galleryId, top_gallery_id) {
            let $container = $('.wpmf_gallery_tree[data-id="' + top_gallery_id + '"]').closest('.wpmf_gallery_wrap');
            let settings = $container.data('top-gallery-settings');
            let selector = $container.data('selector');
            var data = {
                action: "wpmf_load_gallery",
                gallery_id: galleryId,
                settings: settings,
                selector: selector
            };

            if (parseInt(galleryId) === parseInt(top_gallery_id)) {
                data.settings = $('.wpmf_gallery_wrap[data-id="' + galleryId + '"]').data('top-gallery-settings');
            }
            $.ajax({
                url: wpmfgallery.ajaxurl,
                method: "POST",
                dataType: 'json',
                data: data,
                beforeSend: function () {
                    $container.find('.wpmf_gallery_box *').hide();
                    $container.find('.wpmf_gallery_box .loading_gallery').show();
                },
                success: function (res) {
                    if (res.status) {
                        window.location.hash = '#' + galleryId + '-wpmfgallery+' + top_gallery_id;
                        $container.find('.wpmf_gallery_box').html('').append(res.html);
                        wpmfGallery.initGallery();
                    }
                }
            });
        },

        /**
         * Change the selected folder in tree view
         */
        changeFolder: function ($current, folder_id, $gallery) {
            // Remove previous selection
            $gallery.find('li').removeClass('selected');

            // Select the folder
            $gallery.find('li[data-id="' + folder_id + '"]').addClass('selected').// Open parent folders
            parents('.wpmf_gallery_tree li.closed').removeClass('closed');
        },

        getGalleryIDStart: function ($current) {
            let gallery_id = $current.data('id');  // get gallery ID inserted content
            let tree_hash = window.location.hash;
            tree_hash = tree_hash.replace('#', '');
            if (tree_hash !== '') {
                let hasha = tree_hash.split('-');
                if (hasha[1].indexOf('wpmfgallery') !== -1) {
                    gallery_id = parseInt(hasha[0]);
                }
            }

            return gallery_id;
        }
    };

    // Let's initialize WPMF folder tree features
    $(document).ready(function () {
        $('.wpmf_gallery_tree').each(function () {
            wpmfGalleryTree.initModule($(this));
        });

        $('.menu-item a').on('click', function () {
            setTimeout(function () {
                var hash = window.location.hash;
                if (hash.indexOf('wpmfgallery') !== -1) {
                    hash = hash.replace('#', '');
                    var ids = hash.split('-wpmfgallery+');
                    var galleryId = ids[0];
                    var top_gallery_id = ids[1];
                    wpmfGalleryTree.loadGallery(galleryId, top_gallery_id);
                    $('.wpmf_gallery_tree li').removeClass('open selected');
                    $('.wpmf_gallery_tree li[data-id="'+ galleryId +'"]').addClass('open selected');
                }
            },200);
        });
    });
})(jQuery);