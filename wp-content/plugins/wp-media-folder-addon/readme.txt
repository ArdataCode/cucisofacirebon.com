=== WP Media Folder ===
Tags: media, folder
Requires at least: 4.7.0
Tested up to: 6.1.1
Requires PHP: 5.6
Stable tag: 3.6.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WP media Folder Addon is a WordPress plugin that enhance the WordPress media manager by adding cloud features.

== Description ==

The WP Media Folder addon brings amazing features to your WordPress media manager.
Besides the WP Media folder plugin features you'll get the possibility to connect and synchronize the WordPress media manager with the Dropbox and Google Drive cloud services.
Everything is included in a single plugin addon, plus, a responsive PDF embed feature.

= Changelog =

= 3.6.7 =
 * Fix : Auto update plugin

= 3.6.6 =
 * Fix : Auto update plugin

= 3.6.5 =
 * Fix : Wrong media path after upload to Amazon S3, Wasabi, DigitalOcean, Linode, Google Cloud Storage

= 3.6.4 =
 * Fix : Sync Google Drive
 * Fix : Offload Amazon S3: Remove after upload doesn't work in some cases

= 3.6.3 =
 * Fix : Connect Google Cloud

= 3.6.2 =
 * Add : Offload media: Support Google Cloud Storage

= 3.6.1 =
 * Add : Offload media: Support Linode cloud
 * Fix : Copy bucket not work on all storages
 * Fix : Create bucket on Wasabi
 * Fix : Validate bucket name before create
 * Fix : Hide secret access key in settings

= 3.6.0 =
 * Add : Offload media: Support DigitalOcean cloud
 * Add : Offload media: Support Wasabi cloud
 * Fix : Load cloud thumbnail on Media Library

= 3.5.4 =
 * Fix : Load cloud thumbnail on grid view

= 3.5.3 =
 * Add : Cloud media folder access by users and user roles
 * Fix : Remove file from local after uploading to Amazon S3
 * Fix : Loading OneDrive thumbnails in Media Library list view

= 3.5.2 =
 * Fix : Update Dropbox API using version 2

= 3.5.1 =
 * Fix : Import dropbox file to media library
 * Fix : Load cloud image on list view
 * Fix : Sync Google Drive with G-suite
 * Fix : Upload file to Amazon S3

= 3.5.0 =
 * Add : Generate thumbnail option for cloud images
 * Add : Allow to move a file from Wordpress to cloud folder
 * Fix : Slow process when sync to Amazon S3

= 3.4.19 =
 * Fix : Fatal error PHP when use auto connect Google Drive

= 3.4.18 =
 * Fix : Remove local file after upload to Amazon S3
 * Fix : Compatible with BuddyBoss plugin

= 3.4.17 =
 * Fix : Warning from Amazon S3
 * Fix : Use tippy js instead qtip

= 3.4.16 =
 * Fix : Some JS warnings
 * Fix : Remove unused CSS file

= 3.4.15 =
 * Fix : Get all files from Amazon S3
 * Fix : Get cloud file URL
 * Fix : OneDrive business connect

= 3.4.14 =
 * Add : Update to using global background tasks manager

= 3.4.13 =
 * Fix : Auto sync cloud files

= 3.4.12 =
 * Fix : Connect with OneDrive
 * Add : Automatic configuration for Google Photo connection

= 3.4.11 =
 * Fix : Update OneDrive API
 * Fix : Reconnect to OneDrive Business

= 3.4.10 =
 * Fix : Mime type of the files wrong after sync
 * Fix : Sync Dropbox wrong structure

= 3.4.9 =
 * Fix : Cloud sync with special character in file name

= 3.4.8 =
 * Add : Automatic configuration for Google Drive connection
 * Add : Automatic configuration for Dropbox connection
 * Add : Automatic configuration for Onedrive and Onedrive Business connection

= 3.4.7 =
 * Fix : Dropbox sync file
 * Fix : OneDrive Business full sync

= 3.4.6 =
 * Add : Implement sync Google Drive, Dropbox, OneDrive & OneDrive Business as a background task
 * Add : Implement sync and import Amazon S3 as a background task

= 3.4.5 =
 * Fix : JoomUnited Updater compatible with WordPress 5.5

= 3.4.4 =
 * Fix : Google Photos import
 * Fix : Sync cloud loader icon not showing
 * Fix : Dropbox synchronization: Missing files

= 3.4.3 =
 * Fix : Set default name of root folder on Amazon S3 to lowercase

= 3.4.2 =
 * Fix : White space in root folder name on Amazon S3
 * Fix : WordPress video shortcode not working with cloud video
 * Fix : Import Google Photos album
 * Fix : Sync to Amazon S3 missing scaled size
 * Fix : Remove file after upload to Amazon S3
 * Fix : Notification style in backend 

= 3.4.1 =
 * Fix : Select a bucket on Amazon S3
 * Fix : Disconnect Google Photos

= 3.4.0 =
 * Add : Implement Cloudfront for Amazon S3
 * Add : Import all the folders and files from S3 bucket to Media library
 * Add : Copy all the files from a bucket to another bucket
 * Add : Upload single file and multiple files from media library to S3 (right click)
 * Add : Filter media by type to upload on S3
 * Add : Optimization: remove unnecessary code and files

= 3.3.4 =
 * Fix : Image orientation when upload to cloud

= 3.3.3 =
 * Fix : Update Amazon S3 API
 * Fix : Upload file to S3 slowly

= 3.3.2 =
 * Fix : Update OneDrive API caused fatal error on WPMF settings page

= 3.3.1 =
 * Fix : List all albums of Google Photos in WordPress media library
 * Fix : Load Google image with private link
 * Fix : Conflict with WP File Download, can't connect to OneDrive

= 3.3.0 =
 * Add : Connect Google Team Drives media with the WordPress media library
 * Add : Manage media from Google shared drives with auto synchronization to WordPress
 * Add : Import/move media from Google shared drives to WordPress
 * Add : Determine if your Google shared drive embed media links are public or private
 * Add : Force Google shared drive single folder or global synchronization
 * Add : Determine Google shared drive automatic synchronization delay

= 3.2.1 =
 * Add : Quick copy buttons for clouds login information
 * Fix : Reconnect OneDrive after logout

= 3.2.0 =
 * Add : Connect WP Media Folder to Google Photos using a Google Cloud App
 * Add : Import a selection of Google Photos to the WordPress media library
 * Add : Import a Google Photos album to the WordPress media library
 * Add : Import a Google Photos album as new media folder
 * Add : Google photo automatic synchronization
 * Add : Google File import new designed popup

= 3.1.6 =
 * Fix : Preview file in Dropbox

= 3.1.5 =
 * Fix : Get shareable link for OneDrive personal
 * Fix : Missing file after synchronization

= 3.1.4 =
 * Fix : Sync cloud files
 * Fix : Remove file after upload to s3

= 3.1.3 =
 * Add : Add public link for Onedrive Business

= 3.1.2 =
 * Fix : Synchronization with Amazon S3 (get tables when run S3 sync)

= 3.1.1 =
 * Fix : Save Amamzon S3 info attachment
 * Fix : Get Dropbox filetype during synchronization
 * Fix : New connection to OneDrive & OneDrive Business

= 3.1.0 =
 * Add : Automatic synchronization for clouds
 * Add : Crontab cloud synchronization option
 * Add : AJAX cloud synchronization option
 * Add : Curl cloud synchronization option
 * Add : Define cloud media synchronization periodicity
 * Add : Loader when running a synchronization on folder tree

= 3.0.5 =
 * Fix : Add post meta when running a synchronization
 * Fix : Some image sizes missing when running a synchronization
 * Fix : Load media icon in list view
 * Fix : Upload file with size more than 5MB

= 3.0.4 =
 * Fix : Load file with private link for OneDrive & OneDrive business
 * Fix : Sync google files with WordPress media
 * Fix : Don't remove the files from cloud system

= 3.0.3 =
 * Fix : JU Update process
 * Fix : S3 return wrong URL after image crop

= 3.0.2 =
 * Fix : Only load juupdater from admin
 * Fix : Conflict between sync S3 and regenerate thumbnail
 * Fix : Do not run upload S3 if accessing cloud folder

= 3.0.1 =
 * Fix : Upload cloud file
 * Fix : Enhanced requirements tests

= 3.0.0 =
 * Add : Implement OneDrive Business connection with the WordPress media library
 * Add : Integrate Google Drive media in WordPress media library folder tree
 * Add : Integrate Dropbox media in WordPress media library folder tree
 * Add : Integrate OneDrive & OneDrive Business media in WordPress media library folder tree
 * Add : Auto select Amazon S3 bucket on fresh install
 * Add : Auto run full synchronization on fresh install
 * Add : Select public or pricate links for cloud media links
 * Add : Remove the old cloud file management UX

= 2.2.3 =
 * Fix : Create Bucket Amazon S3
 * Fix : Warning PHP on file download

= 2.2.2 =
 * Fix : Check version requirements

= 2.2.1 =
 * Fix : Amazon S3 bucket creation
 * Fix : Add Gutenberg blocks tag

= 2.2.0 =
 * Add : Amazon S3 support: copy and load media from Amazon S3
 * Add : Offload your media from your media library an load from Amazon S3
 * Add : Amazon S3: automatic and manual media synchronization option
 * Add : Retrieve media links and files from Amazon S3
 * Add : Create and manage S3 buckets from the plugin configuration

= 2.1.8 =
 * Add : Requirements to check if WPMF version installed is compatible
 * Fix : JUUpdater login enhancement

= 2.1.7 =
 * Add : Add some hooks for developers

= 2.1.6 =
 * Add : New settings UX and design
 * Add : Possibility to search in plugin menus and settings
 * Add : Plugin installer with quick configuration
 * Add : Environment checker on install (PHP Version, PHP Extensions, Apache Modules)
 * Add : System Check menu to notify of server configuration problems after install
 * Add : Server testing before plugin activation to avoid all fatal errors

= 2.1.5 =
 * Add : Gutenberg Compatiblility: add new cloud blocks in embed section
 * Add : Gutenberg Compatiblility: insert Google Drive media in blocks
 * Add : Gutenberg Compatiblility: insert Dropbox media in blocks
 * Add : Gutenberg Compatiblility: insert OneDrive media in blocks

= 2.1.4 =
 * Fix : Insert Google Drive image in content

= 2.1.3 =
 * Fix : Check connected addon to display/hide cloud menu in media manager
 * Fix : Change capabilities of addon menu
 * Fix : Display file on frontend

= 2.1.2 =
 * Fix : Enhance code readability and performance

= 2.1.1 =
 * Fix : insert Onedrive image
 * Fix : insert dropbox file to content
 * Fix : display Dropbox file
 * Fix : import files to media library

= 2.1.0 =
 * Add : Rewrite code to be compatible with new 4.3 version of WP Media Folder

= 2.0.1 =
 * Fix : Error when no OneDrive auth url available

= 2.0.0 =
 * Add : OneDrive cloud support
 * Fix : Upload files using dragn'drop not working in edit post page

= 1.0.5 =
 * Fix : Conflict with Your Drive plugin
 * Fix : Updater does not work from the WordPress dashboard

= 1.0.4 =
 * Fix : Update the updater for WordPress 4.8

= 1.0.3 =
 * Fix : Use default en_US language

= 1.0.2 =
 * Add : Add builtin translation tool

= 1.0.1 =
 * Add : Add cloud configuration documentation link in settings
 * Fix : JoomUnited updater compatible with new WordPress 4.6 shiny updates

= 1.0.0 =
 * Add : Initial release

