<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');
require_once(WPMFAD_PLUGIN_DIR . '/class/sdk/vendor/autoload.php');

use Joomunited\Queue\V1_0_0\JuMainQueue;
use GuzzleHttp\Client as GuzzleHttpClient;
use Krizalys\Onedrive\Client;
use Krizalys\Onedrive\Exception\ConflictException;
use Microsoft\Graph\Graph;
use Krizalys\Onedrive\File;
use Microsoft\Graph\Model\DriveItem;
use Microsoft\Graph\Model;
use Microsoft\Graph\Model\UploadSession;

/**
 * Class WpmfAddonOneDrive
 * This class that holds most of the admin functionality for OneDrive
 */
class WpmfAddonOneDriveBusiness
{

    /**
     * OneDrive Client
     *
     * @var OneDrive_Client
     */
    private $client = null;

    /**
     * File fields
     *
     * @var string
     */
    protected $apifilefields = 'thumbnails,children(top=1000;expand=thumbnails(select=medium,large,mediumSquare,c1500x1500))';

    /**
     * List files fields
     *
     * @var string
     */
    protected $apilistfilesfields = 'thumbnails(select=medium,large,mediumSquare,c1500x1500)';

    /**
     * BreadCrumb
     *
     * @var string
     */
    public $breadcrumb = '';

    /**
     * AccessToken
     *
     * @var string
     */
    private $accessToken;

    /**
     * Refresh token
     *
     * @var string
     */
    private $refreshToken;

    /**
     * Get token from _wpmfAddon_onedrive_business_config option
     *
     * @return boolean|WP_Error
     */
    public function loadToken()
    {
        $onedriveconfig = get_option('_wpmfAddon_onedrive_business_config');
        if (empty($onedriveconfig['state']->token)) {
            return new WP_Error('broke', __("The plugin isn't yet authorized to use your OneDrive!
             Please (re)-authorize the plugin", 'wpmfAddon'));
        } else {
            $this->accessToken = $onedriveconfig['state']->token->data->access_token;
            $this->refreshToken = $onedriveconfig['state']->token->data->refresh_token;
        }

        return true;
    }

    /**
     * Revoke token
     * To-Do: Revoke Token is not yet possible with OneDrive API
     *
     * @return boolean
     */
    public function revokeToken()
    {
        $this->accessToken = '';
        $this->refreshToken = '';
        $onedriveconfig = get_option('_wpmfAddon_onedrive_business_config');
        $onedriveconfig['state'] = array();
        $onedriveconfig['connected'] = 0;
        update_option('_wpmfAddon_onedrive_business_config', $onedriveconfig);
        return true;
    }

    /**
     * Renews the access token from OAuth. This token is valid for one hour.
     *
     * @param object $client         Client
     * @param array  $onedriveconfig Setings
     *
     * @return Client
     */
    public function renewAccessToken($client, $onedriveconfig)
    {
        $client->renewAccessToken($onedriveconfig['OneDriveClientSecret']);
        $onedriveconfig['state'] = $client->getState();
        update_option('_wpmfAddon_onedrive_business_config', $onedriveconfig);
        $graph = new Graph();
        $graph->setAccessToken($client->getState()->token->data->access_token);
        $client = new Client(
            $onedriveconfig['OneDriveClientId'],
            $graph,
            new GuzzleHttpClient(),
            array(
                'state' => $client->getState()
            )
        );

        return $client;
    }

    /**
     * Read OneDrive app key and secret
     *
     * @return Client|OneDrive_Client|boolean
     */
    public function getClient()
    {
        $onedriveconfig = get_option('_wpmfAddon_onedrive_business_config');
        if (empty($onedriveconfig['OneDriveClientId']) && empty($onedriveconfig['OneDriveClientSecret'])) {
            return false;
        }

        try {
            if (isset($onedriveconfig['state']) && isset($onedriveconfig['state']->token->data->access_token)) {
                $graph = new Graph();
                $graph->setAccessToken($onedriveconfig['state']->token->data->access_token);
                $client = new Client(
                    $onedriveconfig['OneDriveClientId'],
                    $graph,
                    new GuzzleHttpClient(),
                    array(
                        'state' => $onedriveconfig['state']
                    )
                );

                if ($client->getAccessTokenStatus() === -2) {
                    $client = $this->renewAccessToken($client, $onedriveconfig);
                }
            } else {
                $client = new Client(
                    $onedriveconfig['OneDriveClientId'],
                    new Graph(),
                    new GuzzleHttpClient(),
                    null
                );
            }

            $this->client = $client;
            return $this->client;
        } catch (Exception $ex) {
            echo esc_html($ex->getMessage());
            return false;
        }
    }

    /**
     * Start OneDrive API Client with token
     *
     * @return OneDrive_Client|WP_Error
     */
    public function startClient()
    {
        if ($this->accessToken === false) {
            die();
        }

        return $this->client;
    }

    /**
     * Get DriveInfo
     *
     * @return boolean|null|OneDrive_Service_Drive_About|WP_Error
     */
    public function getDriveInfo()
    {
        if ($this->client === null) {
            return false;
        }

        $driveInfo = null;
        try {
            $driveInfo = $this->client->getDrives();
        } catch (Exception $ex) {
            return new WP_Error('broke', $ex->getMessage());
        }
        if ($driveInfo !== null) {
            return $driveInfo;
        } else {
            return new WP_Error('broke', 'drive null');
        }
    }

    /**
     * Get a $authorizeUrl
     *
     * @return string|WP_Error
     */
    public function getAuthUrl()
    {
        try {
            $onedriveconfig = get_option('_wpmfAddon_onedrive_business_config');
            $authorizeUrl = $this->client->getLogInUrl(array(
                'files.read',
                'files.read.all',
                'files.readwrite',
                'files.readwrite.all',
                'offline_access',
            ), admin_url('upload.php'), 'wpmf-onedrive-business');

            $onedriveconfig['state'] = $this->client->getState();
            update_option('_wpmfAddon_onedrive_business_config', $onedriveconfig);
        } catch (Exception $ex) {
            return new WP_Error('broke', __('Could not start authorization: ', 'wpmfAddon') . $ex->getMessage());
        }
        return $authorizeUrl;
    }

    /**
     * Set redirect URL
     *
     * @param string $location URL
     *
     * @return void
     */
    public function redirect($location)
    {
        if (!headers_sent()) {
            header('Location: ' . $location, true, 303);
        } else {
            // phpcs:ignore WordPress.Security.EscapeOutput -- Content already escaped in the method
            echo "<script>document.location.href='" . str_replace("'", '&apos;', $location) . "';</script>\n";
        }
    }

    /**
     * Create token after connected
     *
     * @param string $code Code to access to onedrive app
     *
     * @return boolean|WP_Error
     */
    public function createToken($code)
    {
        try {
            $onedriveconfig = get_option('_wpmfAddon_onedrive_business_config');
            $client = new Client(
                $onedriveconfig['OneDriveClientId'],
                new Graph(),
                new GuzzleHttpClient(),
                array(
                    'state' => $onedriveconfig['state']
                )
            );

            $blogname = trim(str_replace(array(':', '~', '"', '%', '&', '*', '<', '>', '?', '/', '\\', '{', '|', '}'), '', get_bloginfo('name')));
            if ($blogname === '') {
                $folderName = 'WP Media Folder';
            } else {
                $folderName = 'WP Media Folder - ' . $blogname;
            }
            $folderName = preg_replace('@["*:<>?/\\|]@', '', $folderName);
            /**
             * Filter to set root cloud folder name for automatic method
             *
             * @param string Folder name
             *
             * @return string
             *
             * @ignore Hook already documented
             */
            $folderName = apply_filters('wpmf_cloud_folder_name', $folderName);
            // Obtain the token using the code received by the OneDrive API.
            $client->obtainAccessToken($onedriveconfig['OneDriveClientSecret'], $code);
            $graph = new Graph();
            $graph->setAccessToken($client->getState()->token->data->access_token);

            if (empty($onedriveconfig['onedriveBaseFolder'])) {
                try {
                    $root = $client->getRoot()->createFolder($folderName);
                    $onedriveconfig['onedriveBaseFolder'] = array(
                        'id' => $root->id,
                        'name' => $root->name
                    );
                } catch (ConflictException $e) {
                    $root = $client->getDriveItemByPath('/' . $folderName);
                    $onedriveconfig['onedriveBaseFolder'] = array(
                        'id' => $root->id,
                        'name' => $root->name
                    );
                }
            } else {
                $root = $graph
                    ->createRequest('GET', '/me/drive/items/' . $onedriveconfig['onedriveBaseFolder']['id'])
                    ->setReturnType(Model\DriveItem::class)// phpcs:ignore PHPCompatibility.Constants.NewMagicClassConstant.Found -- Use to sets the return type of the response object
                    ->execute();

                if (!is_wp_error($root)) {
                    $onedriveconfig['onedriveBaseFolder'] = array(
                        'id' => $root->getId(),
                        'name' => $root->getName()
                    );
                }
            }

            $token = $client->getState()->token->data->access_token;
            $this->accessToken = $token;
            $onedriveconfig['connected'] = 1;
            $onedriveconfig['state'] = $client->getState();
            // update _wpmfAddon_onedrive_business_config option and redirect page
            update_option('_wpmfAddon_onedrive_business_config', $onedriveconfig);
            $this->redirect(admin_url('options-general.php?page=option-folder#one_drive_box'));
        } catch (Exception $ex) {
            ?>
            <div class="error" id="wpmf_error">
                <p>
                    <?php
                    if ((int)$ex->getCode() === 409) {
                        echo esc_html__('The root folder name already exists on cloud. Please rename or delete that folder before connect', 'wpmfAddon');
                    } else {
                        echo esc_html__('Error communicating with OneDrive API: ', 'wpmfAddon');
                        echo esc_html($ex->getMessage());
                    }
                    ?>
                </p>
            </div>
            <?php
            return new WP_Error(
                'broke',
                esc_html__('Error communicating with OneDrive API: ', 'wpmfAddon') . $ex->getMessage()
            );
        }

        return true;
    }

    /**
     * Do upload File
     *
     * @param string $filePath      File path
     * @param string $parentPath    Cloud parent path
     * @param string $name          File name
     * @param string $attachment_id Attachment ID
     * @param string $action        Action
     *
     * @return mixed
     */
    public function doUploadFile($filePath, $parentPath, $name, $attachment_id, $action = 'upload')
    {
        try {
            $content = file_get_contents($filePath);
            $onedriveconfig = get_option('_wpmfAddon_onedrive_business_config');
            $graph = new Graph();
            $graph->setAccessToken($onedriveconfig['state']->token->data->access_token);
            $res = $graph
                ->createRequest('POST', '/me' . $parentPath . '/' . $name . ':/createUploadSession')
                ->setReturnType(UploadSession::class)// phpcs:ignore PHPCompatibility.Constants.NewMagicClassConstant.Found -- Use to sets the return type of the response object
                ->execute();

            $uploadUrl = $res->getUploadUrl();
            $fragSize = 1024 * 5 * 1024;
            $fileSize = strlen($content);
            $numFragments = ceil($fileSize / $fragSize);
            $bytesRemaining = $fileSize;
            $i = 0;
            $ch = curl_init($uploadUrl);
            while ($i < $numFragments) {
                set_time_limit(60);
                $chunkSize = $fragSize;
                $numBytes = $fragSize;
                $start = $i * $fragSize;
                $end = $i * $fragSize + $chunkSize - 1;
                $offset = $i * $fragSize;
                if ($bytesRemaining < $chunkSize) {
                    $chunkSize = $bytesRemaining;
                    $numBytes = $bytesRemaining;
                    $end = $fileSize - 1;
                }

                $stream = fopen($filePath, 'r');
                if ($stream) {
                    // get contents using offset
                    $data = stream_get_contents($stream, $chunkSize, $offset);
                    fclose($stream);
                }

                $content_range = ' bytes ' . $start . '-' . $end . '/' . $fileSize;
                $headers = array(
                    'Content-Length: ' . $numBytes,
                    'Content-Range:' . $content_range
                );

                curl_setopt($ch, CURLOPT_URL, $uploadUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                $response_info = curl_exec($ch);
                curl_getinfo($ch);
                $bytesRemaining = $bytesRemaining - $chunkSize;
                $i++;

                $info_file = \GuzzleHttp\json_decode($response_info);
                if (!empty($info_file->id)) {
                    // add attachment meta
                    global $wpdb;
                    add_post_meta($attachment_id, 'wpmf_drive_id', $info_file->id);
                    add_post_meta($attachment_id, 'wpmf_drive_type', 'onedrive_business');

                    // update guid URL
                    $where = array('ID' => $attachment_id);
                    $link = $this->getLink($info_file->id);
                    $wpdb->update($wpdb->posts, array('guid' => $link), $where);
                    add_post_meta($attachment_id, 'wpmf_drive_link', $link);

                    // add attachment metadata
                    $upload_path = wp_upload_dir();
                    $attached = trim($upload_path['subdir'], '/') . '/' . $info_file->name;
                    $info = pathinfo($attached);
                    $meta = array();
                    if (strpos($info_file->file->mimeType, 'image') !== false) {
                        list($width, $height) = getimagesize($filePath);
                        $meta['width'] = $width;
                        $meta['height'] = $height;
                    }

                    $meta['file'] = $attached;
                    if (isset($info_file->size)) {
                        $meta['filesize'] = $info_file->size;
                    }

                    unlink($filePath);

                    $old_meta = get_post_meta($attachment_id, '_wp_attachment_metadata', true);
                    // thumbnail
                    $sizes = $this->renderMetaSizes($info['extension'], $attachment_id, $info_file->id, $action);
                    if ($action === 'upload') {
                        if (empty($old_meta['sizes']) && !empty($sizes)) {
                            $meta['sizes'] = $sizes;
                        }
                    } else {
                        $meta['sizes'] = $sizes;
                    }

                    if ($action === 'upload') {
                        add_post_meta($attachment_id, 'wpmf_attachment_metadata', $meta);
                    }

                    if ($action === 'move_file') {
                        update_post_meta($attachment_id, '_wp_attachment_metadata', $meta);
                    }

                    return $info_file;
                }
            }
        } catch (Exception $ex) {
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
     * @return \Krizalys\Onedrive\Folder
     */
    public function doCreateFolder($name, $parentID)
    {
        $client = $this->getClient();
        $folder = $client->createFolder($name, $parentID);
        return $folder;
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
        $client = $this->getClient();
        $upload_dir = wp_upload_dir();
        $file = new File($client, $cloud_id);
        if ($file) {
            $content = $file->fetchContent();
            include_once 'includes/mime-types.php';
            $mimeType = getMimeType($extension);
            $status = $this->insertAttachmentMetadata(
                $upload_dir['path'],
                $upload_dir['url'],
                $filename,
                $content,
                $mimeType,
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
    public function insertAttachmentMetadata($upload_path, $upload_url, $file, $content, $mime_type, $ext, $term_id)
    {
        $file = wp_unique_filename($upload_path, $file);
        $upload = file_put_contents($upload_path . '/' . $file, $content);
        if ($upload) {
            $attachment = array(
                'guid' => $upload_url . '/' . $file,
                'post_mime_type' => $mime_type,
                'post_title' => str_replace('.' . $ext, '', $file),
                'post_status' => 'inherit'
            );

            $image_path = $upload_path . '/' . $file;
            // Insert attachment
            $attach_id = wp_insert_attachment($attachment, $image_path);
            $attach_data = wp_generate_attachment_metadata($attach_id, $image_path);
            wp_update_attachment_metadata($attach_id, $attach_data);

            // set attachment to term
            wp_set_object_terms((int) $attach_id, (int) $term_id, WPMF_TAXO, true);

            return true;
        }
        return false;
    }

    /**
     * Download a file
     *
     * @return void
     */
    public function downloadFile()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- download URL inserted post content
        if (empty($_REQUEST['id'])) {
            wp_send_json(array('status' => false));
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.EscapeOutput.OutputNotEscaped -- download URL inserted post content
        $id = $_REQUEST['id'];
        $client = $this->getClient();
        $file = new File($client, $id);
        $infofile = pathinfo($file->getName());

        $contenType = 'application/octet-stream';
        if (isset($infofile['extension'])) {
            include_once 'includes/mime-types.php';
            $contenType = getMimeType($infofile['extension']);
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- download URL inserted post content
        if (!empty($_REQUEST['dl'])) {
            ob_end_clean();
            ob_start();
            header('Content-Disposition: attachment; filename="' . basename($file->getName()) . '"');
            header('Content-Description: File Transfer');
            header('Content-Type: ' . $contenType);
            header('Content-Transfer-Encoding: binary');
            header('Pragma: public');
            if ((int)$file->getSize() !== 0) {
                header('Content-Length: ' . $file->getSize());
            }
            ob_clean();
            flush();
        } else {
            header('Content-Type: ' . $contenType);
        }

        // phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.EscapeOutput.OutputNotEscaped -- download URL inserted post content
        if (isset($_REQUEST['size'])) {
            $size = $_REQUEST['size'];
            $local_id = '';
            if (isset($_REQUEST['local_id'])) {
                $local_id = $_REQUEST['local_id'];
            } else {
                global $wpdb;
                $row = $wpdb->get_row($wpdb->prepare('SELECT post_id FROM ' . $wpdb->prefix . 'postmeta WHERE meta_key = %s AND meta_value = %s', array('wpmf_drive_id', $_REQUEST['id'])));
                if (!empty($row)) {
                    $local_id = $row->post_id;
                }
            }

            if (!empty($local_id)) {
                if (!in_array($size, array('small', 'medium', 'large'))) {
                    echo $file->fetchContent();
                } else {
                    $thumb = $this->getThumbnailsBySize($local_id, $size);
                    if (!empty($thumb)) {
                        readfile($thumb['url']);
                    } else {
                        echo $file->fetchContent();
                    }
                }
            } else {
                echo $file->fetchContent();
            }
        } else {
            echo $file->fetchContent();
        }

        die();
        // phpcs:enable
    }

    /**
     * Get thumbnail by size
     *
     * @param integer $attachment_id Attachment ID
     * @param string  $size          Attachment size
     *
     * @return mixed
     */
    public function getThumbnailsBySize($attachment_id, $size)
    {
        if ($size === 'thumbnail') {
            $size = 'small';
        }
        $client = $this->getClient();
        $params = get_option('_wpmfAddon_onedrive_business_config');
        $graph = new Graph();
        $graph->setAccessToken($params['state']->token->data->access_token);
        $drive_id = get_post_meta($attachment_id, 'wpmf_drive_id', true);
        $contents = $graph
            ->createRequest('GET', '/me/drive/items/'. $drive_id .'/thumbnails/0/' . $size)
            ->setReturnType(Model\DriveItem::class)// phpcs:ignore PHPCompatibility.Constants.NewMagicClassConstant.Found -- Use to sets the return type of the response object
            ->execute();
        return $contents->getProperties();
    }

    /**
     * Send a raw HTTP header
     *
     * @param string  $file        File name
     * @param integer $size        File size
     * @param string  $contentType Content type
     * @param string  $download    Download
     *
     * @internal param string $contenType content type
     *
     * @return void
     */
    public function downloadHeader($file, $size, $contentType, $download = true)
    {
        ob_end_clean();
        ob_start();
        if ($download) {
            header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        } else {
            header('Content-Disposition: inline; filename="' . basename($file) . '"');
        }

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $contentType);
        header('Content-Transfer-Encoding: binary');
        header('Pragma: public');
        if ((int)$size !== 0) {
            header('Content-Length: ' . $size);
        }
        ob_clean();
        flush();
    }

    /**
     * Get share link
     *
     * @param string $id ID of item
     *
     * @return mixed
     */
    public function getLink($id)
    {
        $onedriveconfig = get_option('_wpmfAddon_onedrive_business_config');
        if (isset($onedriveconfig['link_type']) && $onedriveconfig['link_type'] === 'public') {
            // public file
            try {
                $graph = new Graph();
                $graph->setAccessToken($onedriveconfig['state']->token->data->access_token);
                $response = $graph
                    ->createRequest('POST', '/me/drive/items/' . $id . '/createLink')
                    ->attachBody(array('type' => 'view', 'scope' => 'anonymous'))
                    ->setReturnType(Model\Permission::class)// phpcs:ignore PHPCompatibility.Constants.NewMagicClassConstant.Found -- Use to sets the return type of the response object
                    ->execute();
                $links = $response->getLink();
                return $links->getWebUrl() . '?download=1';
            } catch (Exception $e) {
                $link = false;
            }
        } else {
            $link = admin_url('admin-ajax.php') . '?action=wpmf_onedrive_business_download&id=' . urlencode($id) . '&link=true&dl=0';
        }

        return $link;
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

        $params = get_option('_wpmfAddon_onedrive_business_config');
        if (empty($params['connected']) || empty($params['onedriveBaseFolder']['id'])) {
            wp_send_json(array('status' => false));
        }
        // add to queue
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
        if (empty($params['connected']) || empty($params['onedriveBaseFolder']['id'])) {
            return;
        }
        $datas = array(
            'id' => $params['onedriveBaseFolder']['id'],
            'folder_parent' => 0,
            'name' => 'Onedrive Business',
            'action' => 'wpmf_sync_onedrive_business',
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
                    'value'     => 'onedrive_business',
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
            if (empty($datas['cloud_folders_list']) || (is_array($datas['cloud_folders_list']) && !empty($datas['cloud_folders_list']) && !empty($drive_id) && !in_array($drive_id, $datas['cloud_folders_list']))) {
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
        if ($datas['type'] === 'onedrive_business') {
            $attachment_id = $datas['attachment_id'];
            $filePath = get_attached_file($attachment_id);
            if (!file_exists($filePath)) {
                return false;
            }

            // store old path to meta
            $meta       = get_post_meta($attachment_id, '_wp_attachment_metadata', true);
            $file_paths = WpmfAddonHelper::getAttachmentFilePaths($attachment_id, $meta);
            update_post_meta($attachment_id, 'wpmf_origin_file_paths', $file_paths);
            $info = pathinfo($filePath);
            // get client
            $client = $this->getClient();
            $onedriveconfig = get_option('_wpmfAddon_onedrive_business_config');
            $graph = new Graph();
            $graph->setAccessToken($onedriveconfig['state']->token->data->access_token);
            $item = $graph
                ->createRequest('GET', '/me/drive/items/' . $datas['cloud_folder_id'])
                ->setReturnType(Model\DriveItem::class)// phpcs:ignore PHPCompatibility.Constants.NewMagicClassConstant.Found -- Use to sets the return type of the response object
                ->execute();

            $parentPath = $item->getParentReference()->getPath() . '/' . $item->getName();
            // upload attachment to cloud
            $uploaded_file = $this->doUploadFile($filePath, $parentPath, $info['basename'], $attachment_id, 'move_file');
            if ($uploaded_file->id) {
                $saved_link = get_post_meta($attachment_id, 'wpmf_drive_link', true);
                if (!empty($saved_link)) {
                    // add to queue replace url action
                    $datas = array(
                        'attachment_id' => $attachment_id,
                        'action' => 'wpmf_replace_local_to_cloud'
                    );
                    $wpmfQueue = JuMainQueue::getInstance('wpmf');
                    $row = $wpmfQueue->checkQueueExist(json_encode($datas));
                    if (!$row) {
                        $wpmfQueue->addToQueue($datas);
                    }

                    return true;
                }
            }
        }

        return $result;
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
                if ($cloud_type && $cloud_type === 'onedrive_business') {
                    try {
                        $filePath = get_attached_file($attachment_id);
                        $scaled = WpmfAddonHelper::fixImageOrientation(array('file' => $filePath));
                        $filePath = $scaled['file'];
                        if (file_exists($filePath)) {
                            $info = pathinfo($filePath);
                            // get client
                            $client = $this->getClient();
                            $onedriveconfig = get_option('_wpmfAddon_onedrive_business_config');
                            $graph = new Graph();
                            $graph->setAccessToken($onedriveconfig['state']->token->data->access_token);
                            $item = $graph
                                ->createRequest('GET', '/me/drive/items/' . $cloud_id)
                                ->setReturnType(Model\DriveItem::class)// phpcs:ignore PHPCompatibility.Constants.NewMagicClassConstant.Found -- Use to sets the return type of the response object
                                ->execute();
                            $parentPath = $item->getParentReference()->getPath() . '/' . $item->getName();
                            // upload attachment to cloud
                            $this->doUploadFile($filePath, $parentPath, $info['basename'], $attachment_id);
                        }
                    } catch (Exception $e) {
                        echo esc_html($e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * Get thumbnails
     *
     * @param integer $attachment_id Image ID
     *
     * @return mixed
     */
    public function getThumbnails($attachment_id)
    {
        $client = $this->getClient();
        $params = get_option('_wpmfAddon_onedrive_business_config');
        $graph = new Graph();
        $graph->setAccessToken($params['state']->token->data->access_token);
        $drive_id = get_post_meta($attachment_id, 'wpmf_drive_id', true);
        $contents = $graph
            ->createRequest('GET', '/me/drive/items/'. $drive_id .'/?expand=thumbnails(select=small,medium,large)')
            ->setReturnType(Model\DriveItem::class)// phpcs:ignore PHPCompatibility.Constants.NewMagicClassConstant.Found -- Use to sets the return type of the response object
            ->execute();
        return $contents->getThumbnails();
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
            $data = get_post_meta($attachment_id, 'wpmf_attachment_metadata', true);
            if (!empty($data)) {
                $meta = $data;
                delete_post_meta($attachment_id, 'wpmf_attachment_metadata');
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
                if ($cloud_type && $cloud_type === 'onedrive_business') {
                    $folder = $this->doCreateFolder($name, $cloud_id);
                    add_term_meta($folder_id, 'wpmf_drive_id', $folder->getId());
                    add_term_meta($folder_id, 'wpmf_drive_type', 'onedrive_business');
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
                if ($cloud_type && $cloud_type === 'onedrive_business') {
                    $config = get_option('_wpmfAddon_onedrive_business_config');
                    if ($config['onedriveBaseFolder']['id'] !== $cloud_id) {
                        $client = $this->getClient();
                        $client->deleteDriveItem($cloud_id);
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
                if ($cloud_type && $cloud_type === 'onedrive_business') {
                    $config = get_option('_wpmfAddon_onedrive_business_config');
                    if ($config['onedriveBaseFolder']['id'] !== $cloud_id) {
                        if (isset($name)) {
                            $params = array('name' => $name);
                        } else {
                            $params = array();
                        }

                        $client = $this->getClient();
                        $client->updateDriveItem($cloud_id, $params);
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
                if ($cloud_type && $cloud_type === 'onedrive_business') {
                    $config = get_option('_wpmfAddon_onedrive_business_config');
                    if ($config['onedriveBaseFolder']['id'] !== $cloud_id) {
                        $cloud_parentid = wpmfGetCloudFolderID($parent_id);
                        $client = $this->getClient();
                        // Set new parent for item
                        $client->moveDriveItem($cloud_id, $cloud_parentid);
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
                if ($cloud_type && $cloud_type === 'onedrive_business') {
                    $cloud_parentid = wpmfGetCloudFolderID($parent_id);
                    $client = $this->getClient();
                    // Set new parent for item
                    $client->moveDriveItem($cloud_id, $cloud_parentid);
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
                if ($cloud_type && $cloud_type === 'onedrive_business') {
                    $client = $this->getClient();
                    $client->deleteDriveItem($cloud_id);
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
     * @param array   $child       File details
     * @param integer $parent      Parent folder
     * @param array   $upload_path Upload path
     * @param string  $link        Link
     *
     * @return void
     */
    public function insertAttachment($info, $child, $parent, $upload_path, $link)
    {
        $attachment = array(
            'guid' => $link,
            'post_mime_type' => $child['file']['mimeType'],
            'post_title' => $info['filename'],
            'post_type' => 'attachment',
            'post_status' => 'inherit'
        );

        $attach_id = wp_insert_post($attachment);
        $attached = trim($upload_path['subdir'], '/') . '/' . $child['name'];
        wp_set_object_terms((int)$attach_id, (int)$parent, WPMF_TAXO);
        update_post_meta($attach_id, '_wp_attached_file', $attached);
        update_post_meta($attach_id, 'wpmf_size', $child['size']);
        update_post_meta($attach_id, 'wpmf_filetype', $info['extension']);
        update_post_meta($attach_id, 'wpmf_order', 0);
        update_post_meta($attach_id, 'wpmf_drive_id', $child['id']);
        update_post_meta($attach_id, 'wpmf_drive_type', 'onedrive_business');
        $meta = array();
        if (strpos($child['file']['mimeType'], 'image') !== false) {
            if (isset($child['image']['width']) && isset($child['image']['height'])) {
                $meta['width'] = $child['image']['width'];
                $meta['height'] = $child['image']['height'];
            } else {
                list($width, $heigth) = wpmfGetImgSize($link);
                $meta['width'] = $width;
                $meta['height'] = $heigth;
            }

            $meta['file'] = $attached;
        }

        if (isset($child['size'])) {
            $meta['filesize'] = $child['size'];
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
        $params = get_option('_wpmfAddon_onedrive_business_config');
        if (empty($params['connected']) || empty($params['onedriveBaseFolder']['id'])) {
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
        $configs = get_option('_wpmfAddon_onedrive_business_config');
        if (!empty($configs['connected']) && !empty($configs['onedriveBaseFolder']['id'])) {
            // insert root folder on Media library
            if (!get_option('wpmf_onedrive_business_create_root', false)) {
                $inserted = wp_insert_term('Onedrive Business', WPMF_TAXO, array('parent' => 0));
                if (is_wp_error($inserted)) {
                    $folder_id = (int)$inserted->error_data['term_exists'];
                } else {
                    $folder_id = (int)$inserted['term_id'];
                }
                update_term_meta($folder_id, 'wpmf_drive_type', 'onedrive_business');
                update_term_meta($folder_id, 'wpmf_drive_root_id', $configs['onedriveBaseFolder']['id']);
                add_option('wpmf_onedrive_business_create_root', 1, '', 'yes');
            }

            $datas = array(
                'id' => $configs['onedriveBaseFolder']['id'],
                'folder_parent' => 0,
                'name' => 'Onedrive Business',
                'action' => 'wpmf_sync_onedrive_business',
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
        $configs = get_option('_wpmfAddon_onedrive_business_config');
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
                if ($name === 'Onedrive Business' && (int)$datas['folder_parent'] === 0) {
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
                update_term_meta($responses['folder_id'], 'wpmf_drive_type', 'onedrive_business');
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
                $link = $this->getLink($datas['id']);
                if (!$link) {
                    return false;
                }
                $attachment = array(
                    'guid'           => $link,
                    'post_mime_type' => $datas['file']['mimeType'],
                    'post_title'     => $info['filename'],
                    'post_author'   => (int)$datas['user_id'],
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
                update_post_meta($file_id, 'wpmf_drive_type', 'onedrive_business');

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

                // thumbnail
                $sizes = $this->renderMetaSizes($info['extension'], $file_id, $datas['id']);
                if (!empty($sizes)) {
                    $meta['sizes'] = $sizes;
                }
                update_post_meta($file_id, '_wp_attachment_metadata', $meta);
            } else {
                // update attachment
                $file_id = $row->post_id;
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
                if (strpos($file->guid, 'wpmf_onedrive_business_download') !== false && $configs['link_type'] === 'public') {
                    $link = $this->getLink($datas['id']);
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
     * Render meta sizes
     *
     * @param string  $extension Extension
     * @param integer $file_id   Image ID
     * @param string  $drive_id  Cloud file ID
     * @param string  $action    Action
     *
     * @return array
     */
    public function renderMetaSizes($extension, $file_id, $drive_id, $action = 'upload')
    {
        $meta_sizes = array();
        $business_config = get_option('_wpmfAddon_onedrive_business_config');
        // always generate thumbnails when move file
        if ($action !== 'move_file') {
            if (isset($business_config['generate_thumbnails']) && (int)$business_config['generate_thumbnails'] === 0) {
                return $meta_sizes;
            }
        }

        $fileExtension = strtolower($extension);
        if (in_array($fileExtension, array('jpeg', 'jpg', 'png', 'webp'))) {
            $thumbnails = $this->getThumbnails($file_id);
            if (!empty($thumbnails)) {
                $thumbnails = $thumbnails[0];
                $sizes = array('small', 'medium', 'large');
                include_once 'includes/mime-types.php';
                $mimeType   = getMimeType($fileExtension);
                foreach ($sizes as $size) {
                    $size_info = array();
                    $thumb = admin_url('admin-ajax.php') . '?action=wpmf_onedrive_business_download&local_id='. $file_id .'&id=' . urlencode($drive_id) . '&link=true&dl=0&size=' . $size;
                    if (isset($thumbnails[$size])) {
                        if ($size === 'small') {
                            $size = 'thumbnail';
                            $size_info['width'] = $thumbnails['small']['width'];
                            $size_info['height'] = $thumbnails['small']['height'];
                        } else {
                            $size_info['width'] = $thumbnails[$size]['width'];
                            $size_info['height'] = $thumbnails[$size]['height'];
                        }
                        $size_info['file'] = $thumb;
                        $size_info['mime-type'] = $mimeType;
                        $meta_sizes[$size] = $size_info;
                    }
                }
            }
        }
        return $meta_sizes;
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
        $client = $this->getClient();
        $params = get_option('_wpmfAddon_onedrive_business_config');
        $graph = new Graph();
        $graph->setAccessToken($params['state']->token->data->access_token);

        $nextLink  = null;
        $childs     = array();
        $error = false;
        do {
            try {
                if ($nextLink) {
                    $endpoint = str_replace('https://graph.microsoft.com/v1.0', '', $nextLink);
                    $contents = $graph
                        ->createRequest('GET', $endpoint)
                        ->execute();
                } else {
                    $contents = $graph
                        ->createRequest('GET', '/me/drive/items/'. $folderID .'/children?$top=200')
                        ->execute();
                }
                // phpcs:ignore PHPCompatibility.Constants.NewMagicClassConstant.Found -- We only supports PHP 5.6 and above
                $driveItems = $contents->getResponseAsObject(Model\DriveItem::class);
                $childs    = array_merge($childs, $driveItems);
                $nextLink = $contents->getNextLink();
            } catch (Exception $e) {
                $error = true;
                $nextLink = null;
            }
        } while ($nextLink);

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
                'id' => $child->getId(),
                'folder_parent' => $folder_parent,
                'name' => mb_convert_encoding($child->getName(), 'HTML-ENTITIES', 'UTF-8'),
                'action' => 'wpmf_sync_onedrive_business',
                'cloud_parent' => $folderID
            );

            $file = $child->getFile();
            if (!empty($file)) {
                $image = $child->getImage();
                $cloud_files_list[] = $child->getId();
                $datas['type'] = 'file';
                $datas['file'] = array('mimeType' => $file->getMimeType());
                $datas['image'] = array();
                $datas['size'] = $child->getSize();
                if (strpos($file->getMimeType(), 'image') !== false && isset($image)) {
                    $datas['image'] = $image;
                }
            } else {
                $cloud_folders_list[] = $child->getId();
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
            'action' => 'wpmf_onedrive_business_remove',
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
