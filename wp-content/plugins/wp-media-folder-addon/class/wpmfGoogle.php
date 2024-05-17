<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');

use Joomunited\Queue\V1_0_0\JuMainQueue;

/**
 * Class WpmfAddonGoogleDrive
 * This class that holds most of the admin functionality for Google Drive
 */
class WpmfAddonGoogleDrive
{

    /**
     * Params
     *
     * @var $param
     */
    protected $params;

    /**
     * Last error
     *
     * @var $lastError
     */
    protected $lastError;

    /**
     * Breadcrumb
     *
     * @var string
     */
    public $breadcrumb = '';

    /**
     * Files fields
     *
     * @var string
     */
    protected $wpmffilesfields = 'nextPageToken,items(thumbnailLink,alternateLink,id,description,labels(hidden,restricted,trashed),embedLink,etag,downloadUrl,iconLink,exportLinks,mimeType,modifiedDate,fileExtension,webContentLink,fileSize,userPermission,imageMediaMetadata(width,height),kind,permissions(kind,name,role,type,value,withLink),parents(id,isRoot,kind),title,openWithLinks),kind';

    /**
     * WpmfAddonGoogleDrive constructor.
     *
     * @param string $type Google photo or google drive
     */
    public function __construct($type = 'google-drive')
    {
        set_include_path(__DIR__ . PATH_SEPARATOR . get_include_path());
        require_once 'Google/autoload.php';
        $this->loadParams($type);
    }

    /**
     * Is Shared Drive
     *
     * @param array $configs Configs
     *
     * @return boolean
     */
    public function isTeamDrives($configs)
    {
        if (!empty($configs['drive_type']) && $configs['drive_type'] === 'team_drive') {
            return true;
        }

        return false;
    }

    /**
     * Get google drive config
     *
     * @return mixed
     */
    public function getAllCloudConfigs()
    {
        return WpmfAddonHelper::getAllCloudConfigs();
    }

    /**
     * Save google drive config
     *
     * @param array $data Data config
     *
     * @return boolean
     */
    public function saveCloudConfigs($data)
    {
        return WpmfAddonHelper::saveCloudConfigs($data);
    }

    /**
     * Get last error
     *
     * @return mixed
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Load google drive params
     *
     * @param string $type Google photo or google drive
     *
     * @return void
     */
    protected function loadParams($type = 'google-drive')
    {
        if ($type === 'google-drive') {
            $params = WpmfAddonHelper::getAllCloudConfigs();
        } elseif ($type === 'google_cloud') {
            $params = get_option('_wpmfAddon_google_cloud_storage_config');
        } else {
            $params = WpmfAddonHelper::getAllCloudConfigs($type);
        }

        $this->params = new stdClass();
        if ($type === 'google_cloud') {
            $this->params->google_client_id = isset($params['credentials']['key']) ? $params['credentials']['key'] : '';
            $this->params->google_client_secret = isset($params['credentials']['secret']) ? $params['credentials']['secret'] : '';
            $this->params->google_credentials = isset($params['googleCredentials']) ? $params['googleCredentials'] : '';
        } else {
            $this->params->google_client_id = isset($params['googleClientId']) ? $params['googleClientId'] : '';
            $this->params->google_client_secret = isset($params['googleClientSecret']) ? $params['googleClientSecret'] : '';
            $this->params->google_credentials = isset($params['googleCredentials']) ? $params['googleCredentials'] : '';
        }
    }

    /**
     * Save google drive params
     *
     * @return void
     */
    protected function saveParams()
    {
        $params                       = $this->getAllCloudConfigs();
        $params['googleClientId']     = $this->params->google_client_id;
        $params['googleClientSecret'] = $this->params->google_client_secret;
        $params['googleCredentials']  = $this->params->google_credentials;
        $this->saveCloudConfigs($params);
    }

    /**
     * Get author url
     *
     * @param string $uri  Redirect URIS
     * @param string $type Cloud type
     *
     * @return string
     */
    public function getAuthorisationUrl($uri = '', $type = '')
    {
        $client = new WpmfGoogle_Client();
        $client->setClientId($this->params->google_client_id);
        if ($uri === '') {
            $uri = admin_url('options-general.php?page=option-folder&task=wpmf&function=wpmf_authenticated');
        }
        $client->setRedirectUri($uri);
        $client->setAccessType('offline');
        $client->setApprovalPrompt('force');
        $client->setState('');

        if ($type === 'google_cloud_storage') {
            $client->setScopes(array(
                'https://www.googleapis.com/auth/devstorage.full_control'
            ));
        } else {
            $client->setScopes(array(
                'https://www.googleapis.com/auth/drive',
                'https://www.googleapis.com/auth/userinfo.email',
                'https://www.googleapis.com/auth/userinfo.profile',
                'https://www.googleapis.com/auth/drive.appdata',
                'https://www.googleapis.com/auth/drive.apps.readonly',
                'https://www.googleapis.com/auth/drive.file'
            ));
        }


        $tmpUrl = parse_url($client->createAuthUrl());
        $query  = explode('&', $tmpUrl['query']);
        $url    = $tmpUrl['scheme'] . '://' . $tmpUrl['host'];
        if (isset($tmpUrl['port'])) {
            $url .= $tmpUrl['port'] . $tmpUrl['path'] . '?' . implode('&', $query);
        } else {
            $url .= $tmpUrl['path'] . '?' . implode('&', $query);
        }

        return $url;
    }

    /**
     * Access google drive app
     *
     * @param string $type Google photo or google drive
     * @param string $url  Redirect Uri
     *
     * @return string
     */
    public function authenticate($type = 'google-drive', $url = '')
    {
        $code   = $this->getInput('code', 'GET', 'none');
        $client = new WpmfGoogle_Client();
        $client->setClientId($this->params->google_client_id);
        $client->setClientSecret($this->params->google_client_secret);
        if ($url === '') {
            if ($type === 'google-drive') {
                $url = admin_url('options-general.php?page=option-folder&task=wpmf&function=wpmf_authenticated');
            } else {
                $url = admin_url('options-general.php?page=option-folder&task=wpmf&function=wpmf_google_photo_authenticated');
            }
        }

        $client->setRedirectUri($url);
        return $client->authenticate($code);
    }

    /**
     * Logout google drive app
     *
     * @return void
     */
    public function logout()
    {
        $client = new WpmfGoogle_Client();
        $client->setClientId($this->params->google_client_id);
        $client->setClientSecret($this->params->google_client_secret);
        $client->setAccessToken($this->params->google_credentials);
        $client->revokeToken();
    }

    /**
     * Set credentials
     *
     * @param string $credentials Credentials
     *
     * @return void
     */
    public function storeCredentials($credentials)
    {
        $this->params->google_credentials = $credentials;
        $this->saveParams();
    }

    /**
     * Get credentials
     *
     * @return mixed
     */
    public function getCredentials()
    {
        return $this->params->google_credentials;
    }

    /**
     * Check author
     *
     * @return array
     */
    public function checkAuth()
    {
        $client = new WpmfGoogle_Client();
        $client->setClientId($this->params->google_client_id);
        $client->setClientSecret($this->params->google_client_secret);

        try {
            $client->setAccessToken($this->params->google_credentials);
            $service = new WpmfGoogle_Service_Drive($client);
            $service->files->listFiles(array());
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
        return array('success' => true);
    }

    /**
     * Get Google Client
     *
     * @param array $config Google client config
     *
     * @return Google_Client
     */
    public function getClient($config)
    {
        $client                 = new WpmfGoogle_Client();
        $client->setClientId($config['googleClientId']);
        $client->setClientSecret($config['googleClientSecret']);
        $client->setAccessType('offline');
        if (!empty($config['googleCredentials'])) {
            $client->setAccessToken($config['googleCredentials']);
            if ($client->isAccessTokenExpired()) {
                $token = json_decode($config['googleCredentials'], true);
                $client->refreshToken($token['refresh_token']);
                $token = $client->getAccessToken();
                $client->setAccessToken($token);
                $new_config = get_option('_wpmfAddon_cloud_config');
                $new_config['googleCredentials'] = $token;
                update_option('_wpmfAddon_cloud_config', $new_config);
            }
        }
        return $client;
    }

    /**
     * Check folder exist
     *
     * @param integer $id Id of folder
     *
     * @return boolean
     */
    public function folderExists($id)
    {
        $config = get_option('_wpmfAddon_cloud_config');
        $client = $this->getClient($config);
        $service = new WpmfGoogle_Service_Drive($client);
        try {
            $file = $service->files->get(
                $id,
                array(
                    'supportsAllDrives' => $this->isTeamDrives($config)
                )
            );
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
        return true;
    }

    /**
     * Create folder
     *
     * @param string $name     Folder name
     * @param string $parentID Folder parent ID
     *
     * @return Google_Service_Drive_DriveFile
     */
    public function doCreateFolder($name, $parentID)
    {
        $config = get_option('_wpmfAddon_cloud_config');
        $client = $this->getClient($config);
        $service = new WpmfGoogle_Service_Drive($client);
        $file = new WpmfGoogle_Service_Drive_DriveFile();
        $file->name = $name;
        $file->mimeType = 'application/vnd.google-apps.folder';
        if ($parentID !== null) {
            $file->parents = array($parentID);
        }

        $fileId = $service->files->create($file, array('supportsAllDrives' => $this->isTeamDrives($config)));
        return $fileId;
    }

    /**
     * Add new folder when connect google drive
     *
     * @param string $title    Title of folder
     * @param null   $parentId Parent of folder
     *
     * @return boolean|Google_Service_Drive_DriveFile
     */
    public function createFolder($title, $parentId = null)
    {
        try {
            $config = get_option('_wpmfAddon_cloud_config');
            $client = $this->getClient($config);
            $client->setScopes(array(
                'https://www.googleapis.com/auth/drive',
                'https://www.googleapis.com/auth/drive.appdata',
                'https://www.googleapis.com/auth/drive.apps.readonly',
                'https://www.googleapis.com/auth/drive.file'
            ));
            $service = new WpmfGoogle_Service_Drive($client);
            if ($this->isTeamDrives($config)) {
                $drive = new WpmfGoogle_Service_Drive_Drive();
                $drive->name = $title;
                $fileId = $service->drives->create(time(), $drive);
            } else {
                $file           = new WpmfGoogle_Service_Drive_DriveFile();
                $file->name    = $title;
                $file->mimeType = 'application/vnd.google-apps.folder';
                if ($parentId !== null) {
                    $file->parents = array($parentId);
                }

                $fileId = $service->files->create($file, array('supportsAllDrives' => $this->isTeamDrives($config)));
            }
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
        return $fileId;
    }

    /**
     * Sync folders with media library
     *
     * @return void
     */
    public function ajaxAddToQueue()
    {
        if (empty($_POST['wpmf_nonce'])
            || !wp_verify_nonce($_POST['wpmf_nonce'], 'wpmf_nonce')) {
            die();
        }

        set_time_limit(0);
        $params = get_option('_wpmfAddon_cloud_config');
        if (empty($params['googleBaseFolder']) || empty($params['connected'])) {
            wp_send_json(array('status' => false));
        }
        $this->doAddToQueue($params);
        wp_send_json(array('status' => true));
    }

    /**
     * Do add to queue
     *
     * @param array $params Configs details
     *
     * @return void
     */
    public function doAddToQueue($params)
    {
        if (empty($params['googleBaseFolder']) || empty($params['connected'])) {
            return;
        }
        $datas = array(
            'id' => $params['googleBaseFolder'],
            'folder_parent' => 0,
            'name' => 'Google Drive',
            'action' => 'wpmf_sync_google_drive',
            'type' => 'folder'
        );
        $wpmfQueue = JuMainQueue::getInstance('wpmf');
        $row = $wpmfQueue->checkQueueExist(json_encode($datas));
        if (!$row) {
            $wpmfQueue->addToQueue($datas);
        } else {
            if ((int)$row->status !== 0) {
                $wpmfQueue->addToQueue($datas);
            }
        }
    }

    /**
     * Remove the files/folders when sync
     *
     * @param boolean $result     Result
     * @param array   $datas      Data details
     * @param integer $element_id ID of queue element
     *
     * @return boolean|integer
     */
    public function syncRemoveItems($result, $datas, $element_id)
    {
        remove_action('delete_attachment', array($this, 'deleteAttachment'));
        remove_action('wpmf_before_delete_folder', array($this, 'deleteFolderLibrary'));
        $args = array(
            'post_type' => 'attachment',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'meta_query' => array(
                array(
                    'key'       => 'wpmf_drive_type',
                    'value'     => 'google_drive',
                    'compare'   => '='
                )
            ),
            'tax_query'      => array(
                array(
                    'taxonomy'         => WPMF_TAXO,
                    'field'            => 'term_id',
                    'terms'            => (int)$datas['media_folder_id'],
                    'include_children' => false
                )
            ),
        );
        $media_library_files = get_posts($args);
        foreach ($media_library_files as $file) {
            $drive_id = get_post_meta($file->ID, 'wpmf_drive_id', true);
            if (empty($datas['cloud_files_list']) || (is_array($datas['cloud_files_list']) && !empty($datas['cloud_files_list']) && !empty($drive_id) && !in_array($drive_id, $datas['cloud_files_list']))) {
                wp_delete_attachment($file->ID);
            }
        }

        // get media library files in current folder, then remove the folder not exist
        $folders = get_categories(array('hide_empty' => false, 'taxonomy' => WPMF_TAXO, 'parent' => (int)$datas['media_folder_id']));
        foreach ($folders as $folder) {
            $drive_id = get_term_meta($folder->term_id, 'wpmf_drive_id', true);
            if (is_array($datas['cloud_folders_list']) && !empty($datas['cloud_folders_list']) && !empty($drive_id) && !in_array($drive_id, $datas['cloud_folders_list'])) {
                wp_delete_term($folder->term_id, WPMF_TAXO);
            }
        }
        return true;
    }

    /**
     * Move the files from server to cloud
     *
     * @param boolean $result     Result
     * @param array   $datas      Data details
     * @param integer $element_id ID of queue element
     *
     * @return boolean|integer
     */
    public function moveLocalToCloud($result, $datas, $element_id)
    {
        // upload to cloud
        if ($datas['type'] === 'google_drive') {
            $attachment_id = $datas['attachment_id'];
            $filePath = get_attached_file($attachment_id);
            if (!file_exists($filePath)) {
                return false;
            }

            // store old path to meta
            $meta       = get_post_meta($attachment_id, '_wp_attachment_metadata', true);
            $file_paths = WpmfAddonHelper::getAttachmentFilePaths($attachment_id, $meta);
            update_post_meta($attachment_id, 'wpmf_origin_file_paths', $file_paths);
            $file_uploaded_id = $this->doUpload($attachment_id, $datas['cloud_folder_id'], 'move_file');
            if ($file_uploaded_id) {
                $saved_link = get_post_meta($attachment_id, 'wpmf_drive_link', true);
                if (empty($saved_link)) {
                    global $wpdb;
                    $attachment = get_post($attachment_id);
                    $link = $this->getLink($file_uploaded_id, $attachment->post_mime_type);
                    if ($link) {
                        $configs = get_option('_wpmfAddon_cloud_config');
                        $where = array('ID' => $attachment_id);
                        $wpdb->update($wpdb->posts, array('guid' => $link), $where);
                        add_post_meta($attachment_id, 'wpmf_drive_link', $link);
                        // add to queue replace url action
                        $datas = array(
                            'attachment_id' => $attachment_id,
                            'action' => 'wpmf_replace_local_to_cloud'
                        );
                        $wpmfQueue = JuMainQueue::getInstance('wpmf');
                        $wpmfQueue->addToQueue($datas);

                        // update sizes meta
                        $sizes = array();
                        $meta = get_post_meta($attachment_id, '_wp_attachment_metadata', true);
                        if (!empty($meta) && !empty($meta['sizes'])) {
                            foreach ($meta['sizes'] as $size => $size_info) {
                                if ($size === 'full') {
                                    if ($configs['link_type'] === 'public') {
                                        $thumb_url = $link;
                                    } else {
                                        $thumb_url = admin_url('admin-ajax.php') . '?action=wpmf-download-file&id=' . urlencode($file_uploaded_id) . '&link=true&dl=0';
                                    }

                                    $size_info['file'] = $thumb_url;
                                    $size_info['url'] = $thumb_url;
                                    $sizes['full'] = $size_info;
                                } else {
                                    $thumb_url = admin_url('admin-ajax.php') . '?action=wpmf-download-file&local_id='. $attachment_id .'&id=' . urlencode($file_uploaded_id) . '&link=true&dl=0&sz=w' . $size_info['width'];
                                    $size_info['file'] = $thumb_url;
                                    $size_info['url'] = $thumb_url;
                                    $sizes[$size] = $size_info;
                                }
                            }
                            $meta['sizes'] = $sizes;
                            update_post_meta($attachment_id, '_wp_attachment_metadata', $meta);
                        }
                        return true;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Download google file
     *
     * @return void
     */
    public function downloadFile()
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- download URL inserted post content
        if (empty($_REQUEST['id'])) {
            wp_send_json(array('status' => false));
        }

        $id = $_REQUEST['id'];
        $download = (!empty($_REQUEST['dl'])) ? true : false;
        $config = get_option('_wpmfAddon_cloud_config');
        $client = $this->getClient($config);
        $service = new WpmfGoogle_Service_Drive($client);
        $file    = $service->files->get($id, array('fields' => 'id,parents,name,size,mimeType,fileExtension,thumbnailLink', 'supportsAllDrives' => $this->isTeamDrives($config)));
        if ($file !== null) {
            include_once 'includes/mime-types.php';
            $contenType = ($download) ? getMimeType($file->fileExtension) : $file->mimeType;
            if (!empty($_REQUEST['dl'])) {
                ob_end_clean();
                ob_start();
                header('Content-Disposition: attachment; filename="' . basename($file->name) . '"');
                header('Content-Type: ' . $contenType);
                header('Access-Control-Allow-Origin: ' . home_url());
                header('Content-Transfer-Encoding: binary');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                if ((int)$file->size !== 0) {
                    header('Content-Length: ' . $file->size);
                }
                ob_clean();
                flush();
            } else {
                header('Content-Type: ' . $contenType);
            }

            // phpcs:ignore WordPress.Security.EscapeOutput -- Content already escaped in the method
            if (!empty($_REQUEST['sz'])) {
                $explode_thumb = explode('=', $file->getThumbnailLink());
                if (isset($explode_thumb[1])) {
                    $size = str_replace('w', 's', $_REQUEST['sz']);
                    $thumb_url = $explode_thumb[0] . '=' . $size;
                    readfile($thumb_url);
                } else {
                    $content    = $service->files->get($id, array('alt' => 'media', 'supportsAllDrives' => $this->isTeamDrives($config)));
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- String is escaped
                    echo $content;
                }
            } else {
                $content    = $service->files->get($id, array('alt' => 'media', 'supportsAllDrives' => $this->isTeamDrives($config)));
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- String is escaped
                echo $content;
            }
        }

        die();
    }

    /**
     * Send a raw HTTP header
     *
     * @param string  $file       File name
     * @param integer $size       File size
     * @param string  $contenType Content type
     * @param boolean $download   Download
     *
     * @return void
     */
    public function downloadHeader($file, $size, $contenType, $download)
    {
        ob_end_clean();
        ob_start();
        if ($download) {
            header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        } else {
            header('Content-Disposition: inline; filename="' . basename($file) . '"');
        }
        header('Content-Type: ' . $contenType);
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        if ((int) $size !== 0) {
            header('Content-Length: ' . $size);
        }
        ob_clean();
        flush();
    }

    /**
     * Get publish link file
     *
     * @return void
     */
    public function previewFile()
    {
        if (empty($_REQUEST['wpmf_nonce'])
            || !wp_verify_nonce($_REQUEST['wpmf_nonce'], 'wpmf_nonce')) {
            die();
        }

        ob_start();
        $html = '';
        if (isset($_REQUEST['id']) && isset($_REQUEST['mimetype']) && isset($_REQUEST['ext'])) {
            $ext        = $_REQUEST['ext'];
            $imagesType = array('jpg', 'png', 'gif', 'jpeg', 'jpe', 'bmp', 'ico', 'tiff', 'tif', 'svg', 'svgz');
            $videoType  = array(
                'mp4',
                'wmv',
                'mpeg',
                'mpe',
                'mpg',
                'mov',
                'qt',
                'rv',
                'avi',
                'movie',
                'flv',
                'webm',
                'ogv'
            );//,'3gp'
            $audioType  = array(
                'mid',
                'midi',
                'mp2',
                'mp3',
                'mpga',
                'ram',
                'rm',
                'rpm',
                'ra',
                'wav'
            );  // ,'aif','aifc','aiff'
            if (in_array($ext, $imagesType)) {
                $mediaType = 'image';
            } elseif (in_array($ext, $videoType)) {
                $mediaType = 'video';
            } elseif (in_array($ext, $audioType)) {
                $mediaType = 'audio';
            } else {
                $mediaType = '';
            }

            $mimetype     = $_REQUEST['mimetype'];
            $downloadLink = admin_url('admin-ajax.php') . '?action=wpmf-download-file&id=' . urlencode($_REQUEST['id']) . '&link=true&dl=1';
            require(WPMFAD_PLUGIN_DIR . '/class/templates/media.php');
            $html = ob_get_contents();
            ob_end_clean();
            // phpcs:ignore WordPress.Security.EscapeOutput -- Content already escaped in the method
            echo $html;
        }
        die();
    }

    /**
     * Import file to media library
     *
     * @param string  $cloud_id  Cloud file ID
     * @param integer $term_id   Folder target ID
     * @param boolean $imported  Check imported
     * @param string  $filename  File name
     * @param string  $extension File extension
     *
     * @return boolean
     */
    public function importFile($cloud_id, $term_id, $imported, $filename, $extension)
    {
        $config = get_option('_wpmfAddon_cloud_config');
        $client = $this->getClient($config);
        $service = new WpmfGoogle_Service_Drive($client);

        $upload_dir = wp_upload_dir();
        $file         = $service->files->get($cloud_id, array('supportsAllDrives' => $this->isTeamDrives($config)));
        if (!empty($file)) {
            $content   = $service->files->get($cloud_id, array('alt' => 'media', 'supportsAllDrives' => $this->isTeamDrives($config)));
            $mime_type = strtolower($file->getMimeType());
            $status = $this->insertAttachmentMetadata(
                $upload_dir['path'],
                $upload_dir['url'],
                $filename,
                $content,
                $mime_type,
                $extension,
                $term_id
            );

            if ($status) {
                return true;
            }
        }

        return $imported;
    }

    /**
     * Insert a attachment to database
     *
     * @param string  $upload_path Wordpress upload path
     * @param string  $upload_url  Wordpress upload url
     * @param string  $file        File name
     * @param string  $content     Content of file
     * @param string  $mime_type   Mime type of file
     * @param string  $ext         Extension of file
     * @param integer $term_id     Media folder id to set file to folder
     *
     * @return boolean
     */
    public function insertAttachmentMetadata(
        $upload_path,
        $upload_url,
        $file,
        $content,
        $mime_type,
        $ext,
        $term_id
    ) {
        $file   = wp_unique_filename($upload_path, $file);
        $upload = file_put_contents($upload_path . '/' . $file, $content);
        if ($upload) {
            $attachment = array(
                'guid'           => $upload_url . '/' . $file,
                'post_mime_type' => $mime_type,
                'post_title'     => str_replace('.' . $ext, '', $file),
                'post_status'    => 'inherit'
            );

            $image_path = $upload_path . '/' . $file;
            // Insert attachment
            $attach_id   = wp_insert_attachment($attachment, $image_path);
            $attach_data = wp_generate_attachment_metadata($attach_id, $image_path);
            wp_update_attachment_metadata($attach_id, $attach_data);
            // set attachment to term
            wp_set_object_terms((int) $attach_id, (int) $term_id, WPMF_TAXO, true);
            return true;
        }
        return false;
    }

    /**
     * Do upload File
     *
     * @param string $client   Google client
     * @param string $filePath File path
     * @param string $parentID Cloud parent ID
     * @param string $name     File name
     * @param string $action   Action
     *
     * @return mixed
     */
    public function doUploadFile($client, $filePath, $parentID, $name, $action = 'upload')
    {
        /* Update Mime-type if needed (for IE8 and lower?) */
        include_once 'includes/mime-types.php';
        $config = get_option('_wpmfAddon_cloud_config');
        $fileExtension = pathinfo($name, PATHINFO_EXTENSION);
        $filetype    = getMimeType($fileExtension);
        $chunkSizeBytes = 1 * 1024 * 1024;
        try {
            /* Create new WpmfGoogle File */
            $googledrive_file = new WpmfGoogle_Service_Drive_DriveFile();
            $googledrive_file->setName($name);
            $googledrive_file->setMimeType($filetype);
            $googledrive_file->setParents(array($parentID));
            /* Call the API with the media upload, defer so it doesn't immediately return. */
            $service = new WpmfGoogle_Service_Drive($client);
            $client->setDefer(true);
            $request = $service->files->create($googledrive_file, array('supportsAllDrives' => $this->isTeamDrives($config)));
            $request->disableGzip();

            /* Create a media file upload to represent our upload process. */
            $media = new WpmfGoogle_Http_MediaFileUpload(
                $client,
                $request,
                $filetype,
                null,
                true,
                $chunkSizeBytes
            );

            $filesize = filesize($filePath);
            $media->setFileSize($filesize);

            /* Start partialy upload
              Upload the various chunks. $status will be false until the process is
              complete. */
            $uploadStatus = false;
            $handle       = fopen($filePath, 'rb');
            while (!$uploadStatus && !feof($handle)) {
                set_time_limit(60);
                $chunk        = fread($handle, $chunkSizeBytes);
                $uploadStatus = $media->nextChunk($chunk);
                if (!empty($uploadStatus)) {
                    return $uploadStatus;
                }
            }

            fclose($handle);
        } catch (Exception $ex) {
            return false;
        }

        return true;
    }

    /**
     * Get variable
     *
     * @param string $name   Input name
     * @param string $type   Input type
     * @param string $filter Filter
     *
     * @return null
     */
    public function getInput($name, $type = 'GET', $filter = 'cmd')
    {
        $input = null;
        switch (strtoupper($type)) {
            case 'GET':
                // phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing -- No action, nonce is not required
                if (isset($_GET[$name])) {
                    $input = $_GET[$name];
                }
                break;
            case 'POST':
                if (isset($_POST[$name])) {
                    $input = $_POST[$name];
                }
                // phpcs:enable
                break;
            case 'FILES':
                if (isset($_FILES[$name])) {
                    $input = $_FILES[$name];
                }
                break;
            case 'COOKIE':
                if (isset($_COOKIE[$name])) {
                    $input = $_COOKIE[$name];
                }
                break;
            case 'ENV':
                if (isset($_ENV[$name])) {
                    $input = $_ENV[$name];
                }
                break;
            case 'SERVER':
                if (isset($_SERVER[$name])) {
                    $input = $_SERVER[$name];
                }
                break;
            default:
                break;
        }

        switch (strtolower($filter)) {
            case 'cmd':
                $input = preg_replace('/[^a-z\.]+/', '', strtolower($input));
                break;
            case 'int':
                $input = intval($input);
                break;
            case 'bool':
                $input = $input ? 1 : 0;
                break;
            case 'string':
                $input = sanitize_text_field($input);
                break;
            case 'none':
                break;
            default:
                $input = null;
                break;
        }
        return $input;
    }

    /**
     * Add attachment to cloud
     *
     * @param integer $attachment_id Attachment ID
     *
     * @return void
     */
    public function addAttachment($attachment_id)
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No action, nonce is not required
        if (!empty($_POST['wpmf_folder'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No action, nonce is not required
            $folder_id = (int)$_POST['wpmf_folder'];
            $cloud_id = wpmfGetCloudFolderID($folder_id);
            if ($cloud_id) {
                $cloud_type = wpmfGetCloudFolderType($folder_id);
                if ($cloud_type && $cloud_type === 'google_drive') {
                    $this->doUpload($attachment_id, $cloud_id);
                }
            }
        }
    }

    /**
     * Do upload File
     *
     * @param integer $attachment_id Attachment ID
     * @param string  $folder_id     Cloud folder id
     * @param string  $action        Action
     *
     * @return boolean|string
     */
    public function doUpload($attachment_id, $folder_id, $action = 'upload')
    {
        try {
            $filePath = get_attached_file($attachment_id);
            $scaled = WpmfAddonHelper::fixImageOrientation(array('file' => $filePath));
            $filePath = $scaled['file'];
            if (file_exists($filePath)) {
                $info = pathinfo($filePath);
                $config = get_option('_wpmfAddon_cloud_config');
                $client = $this->getClient($config);

                // upload attachment to cloud
                $uploaded_file = $this->doUploadFile($client, $filePath, $folder_id, $info['basename'], $action);
                if ($uploaded_file) {
                    // add attachment meta
                    add_post_meta($attachment_id, 'wpmf_drive_id', $uploaded_file->id);
                    add_post_meta($attachment_id, 'wpmf_drive_type', 'google_drive');
                    if (isset($config['generate_thumbnails']) && (int)$config['generate_thumbnails'] === 0 && $action === 'upload') {
                        unlink($filePath);
                    }
                    return $uploaded_file->id;
                }
                return false;
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get link
     *
     * @param string $drive_id Cloud file ID
     * @param string $mimeType Mime type
     *
     * @return boolean|string
     */
    public function getLink($drive_id, $mimeType)
    {

        /*$service = new WpmfGoogle_Service_Drive($client);
        $cloud_parentid = wpmfGetCloudFolderID($parent_id);

        $file = $service->files->get($cloud_id, array('fields' => 'id,parents', 'supportsAllDrives' => $this->isTeamDrives($config)));*/
        $config = get_option('_wpmfAddon_cloud_config');
        $client = $this->getClient($config);
        $service     = new WpmfGoogle_Service_Drive($client);
        if (isset($config['link_type']) && $config['link_type'] === 'public') {
            try {
                $userPermission = new WpmfGoogle_Service_Drive_Permission(array(
                    'type' => 'anyone',
                    'role' => 'reader',
                ));
                $service->permissions->create($drive_id, $userPermission, array('fields' => 'id', 'supportsAllDrives' => $this->isTeamDrives($config)));
                if ($mimeType === 'audio/mpeg') {
                    //https://drive.google.com/uc?id=1nG7J_nRHosYZTgieDjIvh8o3Hf_ztEGH&export=download
                    /*        $config = get_option('_wpmfAddon_cloud_config');
                            $client = $this->getClient($config);
                            $service = new WpmfGoogle_Service_Drive($client);
                            //webViewLink
                            $upload_dir = wp_upload_dir();
                            $file         = $service->files->get('1nG7J_nRHosYZTgieDjIvh8o3Hf_ztEGH', array('fields' => 'webContentLink', 'supportsAllDrives' => $this->isTeamDrives($config)));
                            if ($file !== null) {
                                var_dump($file->getWebContentLink());
                            }
                    die;*/
                    $link = 'https://drive.google.com/uc?id=' . $drive_id . '&export=download';
                } else {
                    $link = 'https://drive.google.com/uc?id=' . $drive_id;
                }
            } catch (Exception $e) {
                $link = false;
            }
        } else {
            $link = admin_url('admin-ajax.php') . '?action=wpmf-download-file&id=' . urlencode($drive_id) . '&dl=0';
        }

        return $link;
    }

    /**
     * Update metadata for cloud file
     *
     * @param array   $meta          Meta data
     * @param integer $attachment_id Attachment ID
     *
     * @return mixed
     */
    public function wpGenerateAttachmentMetadata($meta, $attachment_id)
    {
        $drive_id = get_post_meta($attachment_id, 'wpmf_drive_id', true);
        if (!empty($drive_id)) {
            $drive_type = get_post_meta($attachment_id, 'wpmf_drive_type', true);
            if ($drive_type === 'google_drive') {
                // public file
                $saved_link = get_post_meta($attachment_id, 'wpmf_drive_link', true);
                if (empty($saved_link)) {
                    global $wpdb;
                    $attachment = get_post($attachment_id);
                    $link = $this->getLink($drive_id, $attachment->post_mime_type);
                    if ($link) {
                        $where = array('ID' => $attachment_id);
                        $wpdb->update($wpdb->posts, array('guid' => $link), $where);
                        add_post_meta($attachment_id, 'wpmf_drive_link', $link);
                    }
                }
            }
        }

        return $meta;
    }

    /**
     * Create cloud folder from media library
     *
     * @param integer $folder_id    Local folder ID
     * @param string  $name         Folder name
     * @param integer $parent_id    Local folder parent ID
     * @param array   $informations Informations
     *
     * @return boolean
     */
    public function createFolderLibrary($folder_id, $name, $parent_id, $informations)
    {
        try {
            $cloud_id = wpmfGetCloudFolderID($parent_id);
            if ($cloud_id) {
                $cloud_type = wpmfGetCloudFolderType($parent_id);
                if ($cloud_type && $cloud_type === 'google_drive') {
                    $folder = $this->doCreateFolder($name, $cloud_id);
                    add_term_meta($folder_id, 'wpmf_drive_id', $folder->getId());
                    add_term_meta($folder_id, 'wpmf_drive_type', 'google_drive');
                }
            }
        } catch (Exception $ex) {
            return false;
        }

        return true;
    }

    /**
     * Delete cloud folder from media library
     *
     * @param object $folder Local folder info
     *
     * @return boolean
     */
    public function deleteFolderLibrary($folder)
    {
        try {
            $cloud_id = wpmfGetCloudFolderID($folder->term_id);
            if ($cloud_id) {
                $cloud_type = wpmfGetCloudFolderType($folder->term_id);
                if ($cloud_type && $cloud_type === 'google_drive') {
                    $config = get_option('_wpmfAddon_cloud_config');
                    if ($config['googleBaseFolder'] !== $cloud_id) {
                        $client = $this->getClient($config);
                        $service = new WpmfGoogle_Service_Drive($client);
                        $service->files->delete($cloud_id, array('supportsAllDrives' => $this->isTeamDrives($config)));
                    }
                }
            }
        } catch (Exception $ex) {
            return false;
        }

        return true;
    }

    /**
     * Rename cloud folder from media library
     *
     * @param integer $id   Local folder ID
     * @param string  $name New name
     *
     * @return boolean
     */
    public function updateFolderNameLibrary($id, $name)
    {
        try {
            $cloud_id = wpmfGetCloudFolderID($id);
            if ($cloud_id) {
                $cloud_type = wpmfGetCloudFolderType($id);
                if ($cloud_type && $cloud_type === 'google_drive') {
                    $config = get_option('_wpmfAddon_cloud_config');
                    if ($config['googleBaseFolder'] !== $cloud_id) {
                        $client = $this->getClient($config);
                        $service = new WpmfGoogle_Service_Drive($client);
                        $file = new WpmfGoogle_Service_Drive_DriveFile();
                        $file->setName($name);
                        $service->files->update($cloud_id, $file, array('supportsAllDrives' => $this->isTeamDrives($config)));
                    }
                }
            }
        } catch (Exception $ex) {
            return false;
        }

        return true;
    }

    /**
     * Move cloud folder from media library
     *
     * @param integer $folder_id    Local folder ID
     * @param integer $parent_id    Local folder new parent ID
     * @param array   $informations Informations
     *
     * @return boolean
     */
    public function moveFolderLibrary($folder_id, $parent_id, $informations)
    {
        try {
            $cloud_id = wpmfGetCloudFolderID($folder_id);
            if ($cloud_id) {
                $cloud_type = wpmfGetCloudFolderType($folder_id);
                if ($cloud_type && $cloud_type === 'google_drive') {
                    $config = get_option('_wpmfAddon_cloud_config');
                    if ($config['googleBaseFolder'] !== $cloud_id) {
                        $client = $this->getClient($config);
                        $service = new WpmfGoogle_Service_Drive($client);
                        $cloud_parentid = wpmfGetCloudFolderID($parent_id);

                        $file = $service->files->get($cloud_id, array('fields' => 'id,parents', 'supportsAllDrives' => $this->isTeamDrives($config)));

                        $oldParents = $file->getParents();
                        $newFile = new WpmfGoogle_Service_Drive_DriveFile();
                        $service->files->update($cloud_id, $newFile, array(
                            'removeParents' => implode(',', $oldParents),
                            'addParents' => $cloud_parentid,
                            'supportsAllDrives' => $this->isTeamDrives($config)
                        ));
                    }
                }
            }
        } catch (Exception $ex) {
            return false;
        }

        return true;
    }

    /**
     * Move cloud folder from media library
     *
     * @param integer $fileid       Local file ID
     * @param integer $parent_id    Local folder new parent ID
     * @param array   $informations Informations
     *
     * @return boolean
     */
    public function moveFileLibrary($fileid, $parent_id, $informations)
    {
        try {
            $cloud_id = wpmfGetCloudFileID($fileid);
            if ($cloud_id) {
                $cloud_type = wpmfGetCloudFileType($fileid);
                if ($cloud_type && $cloud_type === 'google_drive') {
                    $cloud_parentid = wpmfGetCloudFolderID($parent_id);
                    $config = get_option('_wpmfAddon_cloud_config');
                    $client = $this->getClient($config);
                    $service = new WpmfGoogle_Service_Drive($client);
                    $file = $service->files->get($cloud_id, array('fields' => 'id,parents', 'supportsAllDrives' => $this->isTeamDrives($config)));
                    $oldParents = $file->getParents();
                    $newFile = new WpmfGoogle_Service_Drive_DriveFile();
                    $service->files->update($cloud_id, $newFile, array(
                        'removeParents' => implode(',', $oldParents),
                        'addParents' => $cloud_parentid,
                        'supportsAllDrives' => $this->isTeamDrives($config)
                    ));
                }
            }
        } catch (Exception $ex) {
            return false;
        }

        return true;
    }

    /**
     * Delete cloud attachment
     *
     * @param integer $pid Attachment ID
     *
     * @return boolean
     */
    public function deleteAttachment($pid)
    {
        try {
            $cloud_id = wpmfGetCloudFileID($pid);
            if ($cloud_id) {
                $cloud_type = wpmfGetCloudFileType($pid);
                if ($cloud_type && $cloud_type === 'google_drive') {
                    $config = get_option('_wpmfAddon_cloud_config');
                    $client = $this->getClient($config);
                    $service = new WpmfGoogle_Service_Drive($client);
                    $service->files->delete($cloud_id, array('supportsAllDrives' => $this->isTeamDrives($config)));
                }
            }
        } catch (Exception $ex) {
            return false;
        }

        return true;
    }

    /**
     * Insert attachment
     *
     * @param array   $info        File info
     * @param object  $child       File details
     * @param integer $parent      Parent folder
     * @param array   $upload_path Upload path
     *
     * @return void
     */
    public function insertAttachment($info, $child, $parent, $upload_path)
    {
        $link = $this->getLink($child->id, $child->mimeType);
        if (!$link) {
            return;
        }
        $attachment = array(
            'guid'           => $link,
            'post_mime_type' => $child->mimeType,
            'post_title'     => $info['filename'],
            'post_type'     => 'attachment',
            'post_status'    => 'inherit'
        );

        $attach_id   = wp_insert_post($attachment);
        $attached = trim($upload_path['subdir'], '/') . '/' . $child->name;
        wp_set_object_terms((int) $attach_id, (int) $parent, WPMF_TAXO);

        update_post_meta($attach_id, '_wp_attached_file', $attached);
        update_post_meta($attach_id, 'wpmf_size', $child->fileSize);
        update_post_meta($attach_id, 'wpmf_filetype', $info['extension']);
        update_post_meta($attach_id, 'wpmf_order', 0);
        update_post_meta($attach_id, 'wpmf_drive_id', $child->id);
        update_post_meta($attach_id, 'wpmf_drive_type', 'google_drive');

        $meta = array();
        if (strpos($child->mimeType, 'image') !== false) {
            $metadata = $child->getImageMediaMetadata();
            if (isset($metadata->width)) {
                $meta['width'] = $metadata->width;
            }

            if (isset($metadata->height)) {
                $meta['height'] = $metadata->height;
            }

            $meta['file'] = $attached;
        }

        if (isset($child->fileSize)) {
            $meta['filesize'] = $child->fileSize;
        }
        update_post_meta($attach_id, '_wp_attachment_metadata', $meta);
    }

    /**
     * Update attachment
     *
     * @param array   $info    File info
     * @param integer $file_id Attachment ID
     * @param integer $parent  Parent folder
     *
     * @return void
     */
    public function updateAttachment($info, $file_id, $parent)
    {
        $curent_parents = get_the_terms($file_id, WPMF_TAXO);
        if (isset($parent)) {
            if (!$curent_parents) {
                wp_set_object_terms((int) $file_id, (int)$parent, WPMF_TAXO);
            } else {
                foreach ($curent_parents as $curent_parent) {
                    if (!empty($parent) && (int)$curent_parent->term_id !== (int)$parent) {
                        wp_set_object_terms((int) $file_id, (int)$parent, WPMF_TAXO);
                    }
                }
            }
        }

        $attached_file = get_post_meta($file_id, '_wp_attached_file', true);
        $attached_info = pathinfo($attached_file);
        if ($info['filename'] !== $attached_info['filename']) {
            $new_path = str_replace($attached_info['filename'], $info['filename'], $attached_file);
            update_post_meta($file_id, '_wp_attached_file', $new_path);
        }
    }

    /**
     * Sync folders and files with crontab method
     *
     * @return void
     */
    public function autoSyncWithCrontabMethod()
    {
        $params = get_option('_wpmfAddon_cloud_config');
        if (empty($params['googleBaseFolder']) || empty($params['connected'])) {
            return;
        }
        if (!class_exists('\Joomunited\Queue\V1_0_0\JuMainQueue')) {
            require_once WP_MEDIA_FOLDER_PLUGIN_DIR . 'queue/JuMainQueue.php';
        }
        $args = wpmfGetQueueOptions(true);
        $wpmfQueue = JuMainQueue::getInstance('wpmf');
        $wpmfQueue->init($args);
        $this->doAddToQueue($params);
        $wpmfQueue->proceedQueueAsync();
    }

    /**
     * Add root to queue
     *
     * @return void
     */
    public function addRootToQueue()
    {
        if (!class_exists('\Joomunited\Queue\V1_0_0\JuMainQueue')) {
            require_once WP_MEDIA_FOLDER_PLUGIN_DIR . 'queue/JuMainQueue.php';
        }
        $configs = get_option('_wpmfAddon_cloud_config');
        if (!empty($configs['googleBaseFolder']) && !empty($configs['connected'])) {
            // insert root folder on Media library
            if (!get_option('wpmf_google_drive_create_root', false)) {
                $inserted = wp_insert_term('Google Drive', WPMF_TAXO, array('parent' => 0));
                if (is_wp_error($inserted)) {
                    $folder_id = (int)$inserted->error_data['term_exists'];
                } else {
                    $folder_id = (int)$inserted['term_id'];
                }
                update_term_meta($folder_id, 'wpmf_drive_type', 'google_drive');
                update_term_meta($folder_id, 'wpmf_drive_root_id', $configs['googleBaseFolder']);
                add_option('wpmf_google_drive_create_root', 1, '', 'yes');
            }

            $datas = array(
                'id' => $configs['googleBaseFolder'],
                'folder_parent' => 0,
                'name' => 'Google Drive',
                'action' => 'wpmf_sync_google_drive',
                'type' => 'folder'
            );
            $wpmfQueue = JuMainQueue::getInstance('wpmf');
            $row = $wpmfQueue->checkQueueExist(json_encode($datas));
            if (!$row) {
                $wpmfQueue->addToQueue($datas);
            }
        }
    }

    /**
     * Sync cloud folder and file from queue
     *
     * @param boolean $result     Result
     * @param array   $datas      Data details
     * @param integer $element_id ID of queue element
     *
     * @return boolean|integer
     */
    public function doSync($result, $datas, $element_id)
    {
        $configs = get_option('_wpmfAddon_cloud_config');
        if (empty($configs['connected'])) {
            return -1;
        }
        global $wpdb;
        $name = html_entity_decode($datas['name'], ENT_COMPAT, 'UTF-8');
        if ($datas['type'] === 'folder') {
            // check folder exists
            $row = $wpdb->get_row($wpdb->prepare('SELECT term_id, meta_value FROM ' . $wpdb->termmeta . ' WHERE meta_key = %s AND meta_value = %s', array('wpmf_drive_id', $datas['id'])));
            // if folder not exists
            if (!$row) {
                $inserted = wp_insert_term($name, WPMF_TAXO, array('parent' => (int)$datas['folder_parent']));
                if (is_wp_error($inserted)) {
                    $folder_id = (int)$inserted->error_data['term_exists'];
                } else {
                    $folder_id = (int)$inserted['term_id'];
                }
                if ($name === 'Google Drive' && (int)$datas['folder_parent'] === 0) {
                    update_term_meta($folder_id, 'wpmf_drive_root_id', $datas['id']);
                } else {
                    update_term_meta($folder_id, 'wpmf_drive_id', $datas['id']);
                }
            } else {
                $folder_id = (int)$row->term_id;
                $exist_folder = get_term($folder_id, WPMF_TAXO);
                // if folder exists, then update parent and name
                if (!empty($datas['folder_parent']) && (int)$exist_folder->parent !== (int)$datas['folder_parent']) {
                    $parent_exist = get_term((int)$datas['folder_parent'], WPMF_TAXO);
                    if (!is_wp_error($parent_exist)) {
                        wp_update_term($folder_id, WPMF_TAXO, array('parent' => (int) $datas['folder_parent']));
                    }
                }

                if ($name !== $exist_folder->name) {
                    wp_update_term($folder_id, WPMF_TAXO, array('name' => $name));
                }
            }

            if (!empty($folder_id)) {
                $responses = array();
                $responses['folder_id'] = (int)$folder_id;
                update_term_meta($responses['folder_id'], 'wpmf_drive_type', 'google_drive');
                $wpmfQueue = JuMainQueue::getInstance('wpmf');
                $wpmfQueue->updateQueueTermMeta((int)$responses['folder_id'], (int)$element_id);
                $wpmfQueue->updateResponses((int)$element_id, $responses);
                // find childs element to add to queue
                $this->addChildsToQueue($datas['id'], $folder_id);
            }
        } else {
            $upload_path = wp_upload_dir();
            $info = pathinfo($name);
            $row = $wpdb->get_row($wpdb->prepare('SELECT post_id, meta_value FROM ' . $wpdb->postmeta . ' WHERE meta_key = %s AND meta_value = %s', array('wpmf_drive_id', $datas['id'])));
            if (!$row) {
                $link = $this->getLink($datas['id'], $datas['file']['mimeType']);
                if (!$link) {
                    return false;
                }
                $attachment = array(
                    'guid'           => $link,
                    'post_mime_type' => $datas['file']['mimeType'],
                    'post_title'     => $info['filename'],
                    'post_author' => (int)$datas['user_id'],
                    'post_type'     => 'attachment',
                    'post_status'    => 'inherit'
                );

                $file_id   = wp_insert_post($attachment);
                $attached = trim($upload_path['subdir'], '/') . '/' . $name;
                wp_set_object_terms((int) $file_id, (int)$datas['folder_parent'], WPMF_TAXO);

                update_post_meta($file_id, '_wp_attached_file', $attached);
                update_post_meta($file_id, 'wpmf_size', $datas['size']);
                update_post_meta($file_id, 'wpmf_filetype', $info['extension']);
                update_post_meta($file_id, 'wpmf_order', 0);
                update_post_meta($file_id, 'wpmf_drive_id', $datas['id']);
                update_post_meta($file_id, 'wpmf_drive_type', 'google_drive');

                $meta = array();
                if (strpos($datas['file']['mimeType'], 'image') !== false) {
                    if (isset($child['image']['width']) && isset($datas['image']['height'])) {
                        $meta['width'] = $datas['image']['width'];
                        $meta['height'] = $datas['image']['height'];
                    } else {
                        list($width, $heigth) = wpmfGetImgSize($link);
                        $meta['width'] = $width;
                        $meta['height'] = $heigth;
                    }

                    $meta['file'] = $attached;
                }

                if (isset($datas['size'])) {
                    $meta['filesize'] = $datas['size'];
                }
                update_post_meta($file_id, '_wp_attachment_metadata', $meta);
            } else {
                // update attachment
                $file_id = $row->post_id;
                $metadata = get_post_meta($file_id, '_wp_attachment_metadata', true);
                if (empty($metadata) || !is_array($metadata)) {
                    $metadata = array();
                }
                $update = false;
                if (empty($metadata['width']) || empty($metadata['height'])) {
                    if (isset($datas['image']['width']) && isset($datas['image']['height'])) {
                        $metadata['width'] = $datas['image']['width'];
                        $metadata['height'] = $datas['image']['height'];
                        $update = true;
                    }
                }

                if (empty($metadata['filesize'])) {
                    if (isset($datas['size'])) {
                        $metadata['filesize'] = $datas['size'];
                        $update = true;
                    }
                }

                if ($update) {
                    update_post_meta($file_id, '_wp_attachment_metadata', $metadata);
                }

                $this->updateAttachment($info, $row->post_id, $datas['folder_parent']);
                $file = get_post($file_id);
                // update author
                if (empty($file->post_author)) {
                    $my_post = array(
                        'ID'           => $file_id,
                        'post_author'   => (int)$datas['user_id']
                    );

                    wp_update_post($my_post);
                }
                // update file URL
                if (strpos($file->guid, 'wpmf-download-file') !== false && $configs['link_type'] === 'public') {
                    $link = $this->getLink($datas['id'], $file->post_mime_type);
                    if (!$link) {
                        return false;
                    }

                    $wpdb->update(
                        $wpdb->posts,
                        array(
                            'guid' => $link
                        ),
                        array('ID' => $file_id),
                        array(
                            '%s'
                        ),
                        array('%d')
                    );

                    update_post_meta($file_id, 'wpmf_drive_link', $link);
                }
            }

            if (!empty($file_id)) {
                $responses = array();
                $responses['attachment_id'] = (int)$file_id;
                $wpmfQueue = JuMainQueue::getInstance('wpmf');
                $wpmfQueue->updateResponses((int)$element_id, $responses);
                $wpmfQueue->updateQueuePostMeta((int)$file_id, (int)$element_id);
            }
        }

        return true;
    }

    /**
     * Add child items to queue
     *
     * @param string  $folderID      ID of cloud folder
     * @param integer $folder_parent ID of folder parent on media library
     *
     * @return void
     */
    public function addChildsToQueue($folderID, $folder_parent)
    {
        $pageToken  = null;
        $childs     = array();
        $error = false;
        do {
            try {
                $configs = get_option('_wpmfAddon_cloud_config');
                $params = array(
                    'q'          => "'" . $folderID . "' in parents and trashed = false",
                    'supportsAllDrives' => $this->isTeamDrives($configs),
                    'fields' => '*'
                );

                if ($this->isTeamDrives($configs)) {
                    $params['includeItemsFromAllDrives'] = true;
                }

                if ($pageToken) {
                    $params['pageToken'] = $pageToken;
                }

                $client = $this->getClient($configs);
                $service     = new WpmfGoogle_Service_Drive($client);
                $files     = $service->files->listFiles($params);
                $childs    = array_merge($childs, $files->getFiles());
                $pageToken = $files->getNextPageToken();
            } catch (Exception $e) {
                $error = true;
                $pageToken = null;
            }
        } while ($pageToken);

        if ($error) {
            return;
        }

        // get folder childs list on cloud
        $cloud_folders_list = array();
        // get file childs list on cloud
        $cloud_files_list = array();
        // Create files in media library
        foreach ($childs as $child) {
            $datas = array(
                'id' => $child->id,
                'folder_parent' => $folder_parent,
                'name' => mb_convert_encoding($child->name, 'HTML-ENTITIES', 'UTF-8'),
                'action' => 'wpmf_sync_google_drive',
                'cloud_parent' => $folderID
            );

            if ($child->mimeType !== 'application/vnd.google-apps.folder') {
                $cloud_files_list[] = $child->id;
                $datas['type'] = 'file';
                $datas['file'] = array('mimeType' => $child->mimeType);
                $datas['image'] = array();
                $datas['size'] = $child->size;
                $datas['author'] = get_current_user_id();
                if (strpos($child->mimeType, 'image') !== false) {
                    $metadata = $child->getImageMediaMetadata();
                    $dimensions = array('width' => 0, 'height' => 0);
                    if (isset($metadata)) {
                        $dimensions = array(
                            'width' => $metadata->width,
                            'height' => $metadata->height
                        );
                    }

                    $datas['image'] = $dimensions;
                }
            } else {
                $cloud_folders_list[] = $child->id;
                $datas['type'] = 'folder';
            }
            $wpmfQueue = JuMainQueue::getInstance('wpmf');
            $wpmfQueue->addToQueue($datas);
        }

        // then remove the file and folder not exist
        $datas = array(
            'id' => '',
            'media_folder_id' => $folder_parent,
            'cloud_folder_id' => $folderID,
            'action' => 'wpmf_google_drive_remove',
            'cloud_files_list' => $cloud_files_list,
            'cloud_folders_list' => $cloud_folders_list,
            'time' => time()
        );
        $wpmfQueue = JuMainQueue::getInstance('wpmf');
        $row = $wpmfQueue->checkQueueExist(json_encode($datas));
        if (!$row) {
            $wpmfQueue->addToQueue($datas);
        } else {
            if ((int)$row->status !== 0) {
                $wpmfQueue->addToQueue($datas);
            }
        }
    }
}
