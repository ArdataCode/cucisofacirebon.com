var wpmfCloudQueue, wpmfCloudSyncCookie;
var cloud_syncing = false;
var wpmf_cloud_last_sync;
var cloud_sync_icon;
(function ($) {
    cloud_sync_icon = `<span title="${wpmfAutoSyncClouds.l18n.hover_cloud_syncing}" class="wpmf-loading-sync"><svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid" class="lds-dual-ring" style="
    height: 20px;
    width: 20px;
    vertical-align: sub !important;
"><circle cx="50" cy="50" ng-attr-r="{{config.radius}}" ng-attr-stroke-width="{{config.width}}" ng-attr-stroke="{{config.stroke}}" ng-attr-stroke-dasharray="{{config.dasharray}}" fill="none" stroke-linecap="round" r="40" stroke-width="12" stroke="#2196f3" stroke-dasharray="62.83185307179586 62.83185307179586" transform="rotate(53.6184 50 50)"><animateTransform attributeName="transform" type="rotate" calcMode="linear" values="0 50 50;360 50 50" keyTimes="0;1" dur="1s" begin="0s" repeatCount="indefinite"></animateTransform></circle></svg></span>`;
    wpmfCloudQueue = {
        /**
         * Sync the folders from Dropbox to Media library
         *
         * @param sync_token
         */
        addDropboxQueue: function (sync_token = false) {
            $.ajax({
                method: "POST",
                dataType: "json",
                url: ajaxurl,
                data: {
                    action: 'wpmf_dropbox_add_queue',
                    type: 'auto',
                    sync_token: sync_token,
                    wpmf_nonce: wpmfAutoSyncClouds.vars.wpmf_nonce
                },
                beforeSend: function () {
                    $('.spinner-cloud-sync').show();
                    if (!$('.dropbox_list .wpmf-loading-sync').length) {
                        $('.dropbox_list > .wpmf-item .wpmf-item-title').append(cloud_sync_icon);
                    }
                },
                success: function (response) {
                    $('.dropbox_list .wpmf-loading-sync').remove();
                    wpmfCloudQueue.addGoogleQueue();
                },
                error: function () {
                    wpmfCloudQueue.addDropboxQueue(sync_token);
                }
            });
        },

        /**
         * Sync the folders from Google Drive to Media library
         */
        addGoogleQueue: function () {
            $.ajax({
                method: "POST",
                dataType: "json",
                url: ajaxurl,
                data: {
                    action: 'wpmf_google_add_queue',
                    type: 'auto',
                    wpmf_nonce: wpmfAutoSyncClouds.vars.wpmf_nonce
                },
                beforeSend: function () {
                    $('.spinner-cloud-sync').show();
                    if (!$('.google_drive_list .wpmf-loading-sync').length) {
                        $('.google_drive_list > .wpmf-item .wpmf-item-title').append(cloud_sync_icon);
                    }
                },
                success: function (response) {
                    $('.google_drive_list .wpmf-loading-sync').remove();
                    wpmfCloudQueue.addOnedriveQueue();
                },
                error: function () {
                    wpmfCloudQueue.addGoogleQueue();
                }
            });
        },

        /**
         * Sync the folders from OneDrive to Media library
         */
        addOnedriveQueue: function () {
            $.ajax({
                method: "POST",
                dataType: "json",
                url: ajaxurl,
                data: {
                    action: 'wpmf_onedrive_add_queue',
                    type: 'auto',
                    wpmf_nonce: wpmfAutoSyncClouds.vars.wpmf_nonce
                },
                beforeSend: function () {
                    $('.spinner-cloud-sync').show();
                    if (!$('.onedrive_list .wpmf-loading-sync').length) {
                        $('.onedrive_list > .wpmf-item .wpmf-item-title').append(cloud_sync_icon);
                    }
                },
                success: function (response) {
                    $('.onedrive_list .wpmf-loading-sync').remove();
                    wpmfCloudQueue.addOnedriveBusinessQueue();
                },
                error: function () {
                    wpmfCloudQueue.addOnedriveQueue();
                }
            });
        },

        /**
         * Sync the folders from OneDrive Business to Media library
         */
        addOnedriveBusinessQueue: function () {
            $.ajax({
                method: "POST",
                dataType: "json",
                url: ajaxurl,
                data: {
                    action: 'wpmf_odvbs_add_queue',
                    type: 'auto',
                    wpmf_nonce: wpmfAutoSyncClouds.vars.wpmf_nonce
                },
                beforeSend: function () {
                    $('.spinner-cloud-sync').show();
                    if (!$('.onedrive_business_list .wpmf-loading-sync').length) {
                        $('.onedrive_business_list > .wpmf-item .wpmf-item-title').append(cloud_sync_icon);
                    }
                },
                success: function (response) {
                    $('.onedrive_business_list .wpmf-loading-sync').remove();
                    $.ajax({
                        method: "POST",
                        dataType: "json",
                        url: ajaxurl,
                        data: {
                            action: 'wpmf_update_cloud_last_sync',
                            wpmf_nonce: wpmfAutoSyncClouds.vars.wpmf_nonce
                        },
                        success: function (res) {
                            cloud_syncing = false;
                            wpmf_cloud_last_sync = res.time;
                        }
                    });
                },
                error: function () {
                    wpmfCloudQueue.addOnedriveBusinessQueue();
                }
            });
        }
    };
    wpmfCloudSyncCookie = {
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
    };

    jQuery(document).ready(function ($) {
        $('.btn-run-sync-cloud').on('click', function () {
            wpmfCloudQueue.addDropboxQueue();
        });

        wpmf_cloud_last_sync = wpmfAutoSyncClouds.vars.last_sync;
        if (wpmfAutoSyncClouds.vars.sync_method === 'ajax' && parseInt(wpmfAutoSyncClouds.vars.sync_periodicity) !== 0) {
            setInterval(function () {
                var $snack = wpmfSnackbarModule.getFromId('sync_drive');
                if ($snack !== null && $snack.length) {
                    return;
                }
                var now = Math.floor(Date.now() / 1000);
                if (now - wpmfAutoSyncClouds.vars.sync_periodicity >= wpmf_cloud_last_sync) {
                    if (wpmfAutoSyncClouds.vars.sync_method === 'ajax') {
                        if (!cloud_syncing) {
                            cloud_syncing = true;
                            var sync_token = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
                            wpmfCloudQueue.addDropboxQueue(sync_token);
                        }
                    }
                }
            },60000);
        }
    });
})(jQuery);