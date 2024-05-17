(function ($) {
    var wpmfGooglePhotoModule = {
        currentAlbum: '',
        pageToken: '',
        getListPhotos: function (album, pageToken = '') {
            if (wpmfGooglePhotoModule.currentAlbum === album && pageToken === '') {
                return;
            }
            
            wpmfGooglePhotoModule.currentAlbum = album;
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'wpmf-get-google-photos',
                    album: album,
                    pageToken: pageToken,
                    wpmf_nonce: wpmfgooglephoto.vars.wpmf_nonce
                },
                beforeSend: function () {
                    $('.google-photo-import-selection').removeClass('button_actived');
                    if (album === 'photos') {
                        $('.import-album-btn').hide();
                    } else {
                        $('.import-album-btn').show();
                    }
                    $('.photo-album-item[data-id="'+ album +'"] .album-title').append('<img class="google-photo-loader" src="'+ wpmfgooglephoto.vars.wpmf_images_path +'/spinner.gif">');
                    $('.google-photo-wrap-right').addClass('google-photo-loading-content');
                    if (pageToken === '') {
                        $('.photo-album-item').removeClass('selected');
                        $('.photo-album-item[data-id="'+ album +'"]').addClass('selected');
                        $('.load-more-photos').data('token', '').addClass('loadmore-disable').hide();
                    }
                },
                success: function (res) {
                    $('.google-photo-loader').remove();
                    $('.google-photo-wrap-right').removeClass('google-photo-loading-content');

                    if (pageToken === '') {
                        $('.photo-items-list').html('');
                    }

                    $('.photo-items-list').append(res.html);
                    if (!$('.photo-item').length) {
                        $('.google-photo-import-selection, .import-album-btn').hide();
                        return;
                    }
                    if (typeof res.pageToken !== "undefined") {
                        wpmfGooglePhotoModule.pageToken = res.pageToken;
                        $('.load-more-photos').data('token', wpmfGooglePhotoModule.pageToken).removeClass('loadmore-disable').show();
                    } else {
                        $('.load-more-photos').data('token', '').addClass('loadmore-disable')
                    }

                    wpmfGooglePhotoModule.clickElement();
                }
            });
        },

        importAllPhotosInAlbumToGallery: function(albumId, folder, pageToken = '') {
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'wpmf_import_all_photos_in_album_to_gallery',
                    albumId: albumId,
                    pageToken: pageToken,
                    folder: folder,
                    wpmf_nonce: wpmfgooglephoto.vars.wpmf_nonce
                },
                beforeSend: function () {
                    if (!$('.wpmf-snackbar[data-id="importing_cloud_file"]').length) {
                        wpmfSnackbarModule.show({
                            id: 'importing_cloud_file',
                            content: wpmf.l18n.importing_goolge_photo_album,
                            auto_close: false,
                            is_progress: true
                        });
                    }
                },
                success: function (res) {
                    if (res.status) {
                        if (res.continue) {
                            wpmfGooglePhotoModule.importAllPhotosInAlbumToGallery(albumId, folder, res.pageToken);
                        } else {
                            wpmfSnackbarModule.close('importing_cloud_file');
                            res.type = 'wpmf_google_photo_gallery_import';
                            window.postMessage(res, wpmfgooglephoto.vars.admin_url);
                        }
                    } else {
                        wpmfSnackbarModule.close('importing_cloud_file');
                    }
                },
            });
        },

        importSelectedGooglePhotoToGallery: function (files, mimeTypes, filenames, folder, page = 0) {
            if (!files.length) {
                return;
            }
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'wpmf_google_photo_gallery_import',
                    files: files.join(),
                    mimeTypes: mimeTypes.join(),
                    filenames: filenames.join(),
                    page: page,
                    folder: folder,
                    wpmf_nonce: wpmfgooglephoto.vars.wpmf_nonce
                },
                beforeSend: function () {
                    if (!$('.wpmf-snackbar[data-id="importing_cloud_file"]').length) {
                        wpmfSnackbarModule.show({
                            id: 'importing_cloud_file',
                            content: wpmf.l18n.importing_goolge_photo,
                            auto_close: false,
                            is_progress: true
                        });
                    }
                },
                success: function (res) {
                    if (res.status) {
                        if (res.continue) {
                            page++;
                            wpmfGooglePhotoModule.importSelectedGooglePhotoToGallery(files, mimeTypes, filenames, folder, page);
                        } else {
                            wpmfSnackbarModule.close('importing_cloud_file');
                            res.type = 'wpmf_google_photo_gallery_import';
                            window.postMessage(res, wpmfgooglephoto.vars.admin_url);
                        }
                    } else {
                        wpmfSnackbarModule.close('importing_cloud_file');
                    }
                },
            });
        },

        clickElement: function () {
            $('.google-photo-full').fancybox();

            $('.load-more-photos').unbind('click').bind('click', function () {
                if ($(this).hasClass('loadmore-disable')) {
                    return;
                }

                wpmfGooglePhotoModule.getListPhotos(wpmfGooglePhotoModule.currentAlbum, wpmfGooglePhotoModule.pageToken);
            });

            /* Select images */
            var singleIndex;
            $('.photo-item').unbind('click').bind('click', function (e) {
                var $this = $(this);
                if (!$(e.target).hasClass('google-photo-full')) {
                    var nodes = Array.prototype.slice.call( document.getElementById('photo-items-list').children );
                    if (!$('.photo-item-checked').length) {
                        singleIndex = nodes.indexOf( this );
                    }

                    // select multiple image use ctrl key or shift key
                    if ( e.ctrlKey || e.shiftKey ) {
                        if (!$('.photo-item-checked').length) {
                            $this.addClass('photo-item-checked');
                        } else {
                            var modelIndex  = nodes.indexOf( this ), i;
                            if ( singleIndex < modelIndex ) {
                                for (i = singleIndex; i<= (modelIndex + 1); i++) {
                                    $('.photo-item:nth-child('+ i +')').addClass('photo-item-checked');
                                }
                            } else {
                                for (i = modelIndex; i <= (singleIndex + 1); i++) {
                                    $('.photo-item:nth-child('+ (i + 1) +')').addClass('photo-item-checked');
                                }
                            }
                        }
                    } else {
                        if ($this.hasClass('photo-item-checked')) {
                            $this.removeClass('photo-item-checked');
                        } else {
                            $this.addClass('photo-item-checked');
                        }
                    }

                    if (!$('.photo-item-checked').length) {
                        $('.google-photo-import-selection').show().removeClass('button_actived');
                    } else {
                        $('.google-photo-import-selection').show().addClass('button_actived');
                    }
                }
            });
        }
    };

    $(document).ready(function ($) {
        wpmfGooglePhotoModule.getListPhotos('photos');
        $('.photo-album-item').on('click', function () {
            if ($('.google-photo-loader').length) {
                return;
            }
            var album = $(this).data('id');
            if (album === 'photos') {
                wpmfGooglePhotoModule.getListPhotos('photos');
            } else {
                wpmfGooglePhotoModule.getListPhotos(album);
            }
        });

        $('.google-photo-import-photo').on('click', function () {
            var links = [];
            var mimeTypes = [];
            var filenames = [];
            $('.photo-item-checked').each(function () {
                links.push($(this).data('full'));
                mimeTypes.push($(this).data('mimetype'));
                filenames.push($(this).data('filename'));
            });

            if (!links.length) {
                return;
            }

            wpmfImportCloudModule.showdialog(true, 'google-photo', links, mimeTypes, filenames, 'photos');
            wpmfImportCloudModule.initModule();
            $('.selection_folder_import[value="0"]').prop('checked', true);
        });

        $('.google-photo-import-album').on('click', function () {
            wpmfImportCloudModule.showdialog(true, 'google-photo', [], [], [], 'album');
            wpmfImportCloudModule.initModule();
            $('.selection_folder_import[value="0"]').prop('checked', true);
        });

        $('.google-photo-import-photo-to-gallery').on('click', function () {
            var files = [];
            var mimeTypes = [];
            var filenames = [];
            $('.photo-item-checked').each(function () {
                files.push($(this).data('full'));
                mimeTypes.push($(this).data('mimetype'));
                filenames.push($(this).data('filename'));
            });
            var gallery_id = $('.wpmf-google-photo-wrap').data('gallery_id');
            wpmfGooglePhotoModule.importSelectedGooglePhotoToGallery(files, mimeTypes, filenames, gallery_id);
        });

        $('.google-photo-import-album-to-gallery').on('click', function () {
            var gallery_id = $('.wpmf-google-photo-wrap').data('gallery_id');
            var albumId = $('.photo-album-item.selected').data('id');
            wpmfGooglePhotoModule.importAllPhotosInAlbumToGallery(albumId, gallery_id);
        });


    });
})(jQuery);