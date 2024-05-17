<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');

use Joomunited\Queue\V1_0_0\JuMainQueue;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

/**
 * Class WpmfAddonDropbox
 * This class that holds most of the admin functionality for Dropbox
 */
class WpmfAddonDropbox
{
    /**
     * Params
     *
     * @var object
     */
    protected $params;

    /**
     * App name
     *
     * @var string
     */
    protected $appName = 'WpmfAddon/1.0';

    /**
     * Dropbox client
     *
     * @var WPMFDropbox\Client
     */
    protected $client;
    
    /**
     * Last Error
     *
     * @var string
     */
    protected $lastError;

    /**
     * WpmfAddonDropbox constructor.
     */
    public function __construct()
    {
        set_include_path(__DIR__ . PATH_SEPARATOR . get_include_path());
        require_once 'Dropbox/autoload.php';
        require_once 'sdk/vendor/autoload.php';
        $this->loadParams();
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
     * Get dropbox config by name
     *
     * @param string $name Name of option
     *
     * @return array|null
     */
    public function getDataConfigByDropbox($name)
    {
        return WpmfAddonHelper::getDataConfigByDropbox($name);
    }

    /**
     * Get dropbox config
     *
     * @return mixed
     */
    public function getAllDropboxConfigs()
    {
        return WpmfAddonHelper::getAllDropboxConfigs();
    }

    /**
     * Save dropbox config
     *
     * @param array $data Datas value
     *
     * @return boolean
     */
    public function saveDropboxConfigs($data)
    {
        return WpmfAddonHelper::saveDropboxConfigs($data);
    }

    /**
     * Load parameters
     *
     * @return void
     */
    protected function loadParams()
    {
        $params = $this->getDataConfigByDropbox('dropbox');

        $this->params = new stdClass();

        $this->params->dropboxKey    = isset($params['dropboxKey']) ? $params['dropboxKey'] : '';
        $this->params->dropboxSecret = isset($params['dropboxSecret']) ? $params['dropboxSecret'] : '';
        $this->params->dropboxToken  = isset($params['dropboxToken']) ? $params['dropboxToken'] : '';
    }

    /**
     * Save parameters
     *
     * @return void
     */
    protected function saveParams()
    {
        $params                  = $this->getAllDropboxConfigs();
        $params['dropboxKey']    = $this->params->dropboxKey;
        $params['dropboxSecret'] = $this->params->dropboxSecret;
        $params['dropboxToken']  = $this->params->dropboxToken;
        $this->saveDropboxConfigs($params);
    }

    /**
     * Get Redirect URL
     *
     * @return string|void
     */
    public function getRedirectUrl()
    {
        return admin_url('options-general.php?page=option-folder&task=wpmf&function=dropbox_authenticate');
    }

    /**
     * Generate Dropbox Authorization Provider
     *
     * @return \League\OAuth2\Client\Provider\GenericProvider
     */
    public function getAuthorizationProvider()
    {
        $dropboxKey = '';
        $dropboxSecret = 'dropboxSecret';

        if (!empty($this->params->dropboxKey)) {
            $dropboxKey = $this->params->dropboxKey;
        }
        if (!empty($this->params->dropboxSecret)) {
            $dropboxSecret = $this->params->dropboxSecret;
        }
        $provider = new League\OAuth2\Client\Provider\GenericProvider(array(
            'clientId'                => $dropboxKey,    // The client ID assigned to you by the provider
            'clientSecret'            => $dropboxSecret,    // The client password assigned to you by the provider
            'redirectUri'             => $this->getRedirectUrl(),
            'urlAuthorize'            => 'https://www.dropbox.com/oauth2/authorize',
            'urlAccessToken'          => 'https://api.dropboxapi.com/oauth2/token',
            'urlResourceOwnerDetails' => 'https://api.dropboxapi.com/2/check/user'
        ));

        return $provider;
    }

    /**
     * Generate authenticate URL for Dropbox
     *
     * @return string
     */
    public function getAuth2Url()
    {
        $provider = $this->getAuthorizationProvider();
        // Fetch the authorization URL from the provider; this returns the
        // urlAuthorize option and generates and applies any necessary parameters
        // (e.g. state).
        $authorizationUrl = $provider->getAuthorizationUrl(array(
            'token_access_type' => 'offline',
        ));

        // Get the state generated for you and store it to the session.
        update_option('wpmf_addon_dropbox_auth_state', $provider->getState());

        return $authorizationUrl;
    }

    /**
     * Authorization dropbox request
     *
     * @return boolean
     */
    public function authorization()
    {
        $provider = $this->getAuthorizationProvider();
        $authState = get_option('wpmf_addon_dropbox_auth_state', false);

        if (!$authState) {
            // No valid authstate
            return false;
        }

        // Get code
        if (!isset($_GET['code'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- It's OK
            // Authorization failed
            return false;
            // Check given state against previously stored one to mitigate CSRF attack
        } elseif (empty($_GET['state']) || ($authState && $_GET['state'] !== $authState)) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- It's OK
            if ($authState) {
                delete_option('wpmf_addon_dropbox_auth_state');
            }

            $this->lastError = 'Invalid state';

            return false;
        } else {
            try {
                // Try to get an access token using the authorization code grant.
                $accessToken = $provider->getAccessToken('authorization_code', array(
                    'code' => $_GET['code'] // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- It's OK
                ));

                // Save the access token
                $this->params->dropboxToken = json_encode($accessToken->jsonSerialize());
                $this->saveParams();

                return true;
            } catch (IdentityProviderException $e) {
                // Failed to get the access token or user details.
                $this->lastError = $e->getMessage();

                return false;
            } catch (Exception $e) {
                $this->lastError = $e->getMessage();

                return false;
            }
        }
    }
    /**
     * Get web authenticate
     *
     * @return \WPMFDropbox\WebAuthNoRedirect
     */
    public function getWebAuth()
    {
        $dropboxKey = '';
        $dropboxSecret = 'dropboxSecret';

        if (!empty($this->params->dropboxKey)) {
            $dropboxKey = $this->params->dropboxKey;
        }
        if (!empty($this->params->dropboxSecret)) {
            $dropboxSecret = $this->params->dropboxSecret;
        }

        $appInfo = new WPMFDropbox\AppInfo($dropboxKey, $dropboxSecret);

        $webAuth = new WPMFDropbox\WebAuthNoRedirect($appInfo, $this->appName);

        return $webAuth;
    }

    /**
     * Get authorize Url allow user
     *
     * @return string
     */
    public function getAuthorizeDropboxUrl()
    {
        return $this->getAuth2Url();
    }

    /**
     * Check Dropbox Token
     *
     * @return boolean
     */
    public function checkAuth()
    {
        $dropboxToken = $this->params->dropboxToken;
        if (!empty($dropboxToken)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Logout dropbox app
     *
     * @return void
     */
    public function logout()
    {
        $params                  = $this->getAllDropboxConfigs();
        $params['dropboxKey']    = $this->params->dropboxKey;
        $params['dropboxSecret'] = $this->params->dropboxSecret;
        $params['dropboxAuthor'] = '';
        $params['dropboxToken']  = '';
        $this->saveDropboxConfigs($params);
        delete_option('wpmf_dropbox_create_root');
        $this->redirect(admin_url('options-general.php?page=option-folder#dropbox_box'));
    }

    /**
     * Get Dropbox account
     *
     * @return \WPMFDropbox\Client
     */
    public function getAccount()
    {
        $accessToken = $this->checkAndRefreshToken();
        if (false === $accessToken) {
            throw new Exception('CheckAndRefreshToken error');
        }

        if (!$this->client) {
            $this->client = new WPMFDropbox\Client($accessToken->getToken(), $this->appName);
        }

        return $this->client;
    }

    /**
     * Check and refresh accessToken
     *
     * @return boolean|\League\OAuth2\Client\Token\AccessToken
     */
    public function checkAndRefreshToken()
    {
        try {
            $storedAccessToken = json_decode($this->params->dropboxToken, true);
            if (is_null($storedAccessToken)) {
                throw new \Exception('Store Access Token not vaild');
            }
            $existingAccessToken = new League\OAuth2\Client\Token\AccessToken($storedAccessToken);

            if ($existingAccessToken->hasExpired()) {
                $newAccessToken = $this->refreshDropboxToken($existingAccessToken->getRefreshToken());
                $storedAccessToken['access_token'] = $newAccessToken->getToken();
                $storedAccessToken['expires'] = $newAccessToken->getExpires();
                $renewedAccessToken = new League\OAuth2\Client\Token\AccessToken($storedAccessToken);
                $this->params->dropboxToken = json_encode($renewedAccessToken->jsonSerialize());

                $this->saveParams();

                return $renewedAccessToken;
            }

            return $existingAccessToken;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();

            return false;
        }
    }

    /**
     * Refresh the Dropbox token
     *
     * @param string $refreshToken Refresh token
     *
     * @return \League\OAuth2\Client\Token\AccessToken Access token object
     *
     * @throws Exception Throw exception on error
     */
    public function refreshDropboxToken($refreshToken)
    {
        $curl = curl_init();
        $basicAuthString = base64_encode($this->params->dropboxKey . ':' . $this->params->dropboxSecret);
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.dropbox.com/oauth2/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'grant_type=refresh_token&refresh_token=' . $refreshToken,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic ' . $basicAuthString,
                'Content-Type: application/x-www-form-urlencoded'
            ),
        ));

        $response = curl_exec($curl);
        $info = curl_getinfo($curl);
        if (curl_errno($curl) || intval($info['http_code']) !== 200) {
            /*
             * https://www.dropbox.com/developers/documentation/http/documentation#error-handling
             *
             * 400  Bad input parameter. The response body is a plaintext message with more information.
             * 401  Bad or expired token. This can happen if the access token is expired or if the access token has been revoked by Dropbox or the user. To fix this, you should re-authenticate the user.
             *      The Content-Type of the response is JSON of typeAuthError
             * 403  The user or team account doesn't have access to the endpoint or feature.
             *      The Content-Type of the response is JSON of typeAccessError
             * 409  Endpoint-specific error. Look to the JSON response body for the specifics of the error.
             * 429  Your app is making too many requests for the given user or team and is being rate limited. Your app should wait for the number of seconds specified in the "Retry-After" response header before trying again.
             *      The Content-Type of the response can be JSON or plaintext. If it is JSON, it will be typeRateLimitErrorYou can find more information in the data ingress guide.
             * 5xx  An error occurred on the Dropbox servers. Check status.dropbox.com for announcements about Dropbox service issues.
            */
            throw new Exception('Failed to refresh the Access Token! Error code: ' . $info['http_code']);
        }
        curl_close($curl);

        $accessTokenArray = $this->parseJson($response);

        return new League\OAuth2\Client\Token\AccessToken($accessTokenArray);
    }

    /**
     * Attempts to parse a JSON response.
     *
     * @param string $content JSON content from response body
     *
     * @return array Parsed JSON data
     *
     * @throws UnexpectedValueException If the content could not be parsed
     */
    protected function parseJson($content)
    {
        $content = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new UnexpectedValueException(sprintf(
                'Failed to parse JSON response: %s',
                json_last_error_msg() // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.json_last_error_msgFound -- Minimum php version is 5.6
            ));
        }

        return $content;
    }

    /**
     * Create folder
     *
     * @param string $name Folder name
     * @param string $path Folder parent path
     *
     * @return array|null
     */
    public function doCreateFolder($name, $path)
    {
        $dropbox = $this->getAccount();
        try {
            $parent   = $path . '/' . $name;
            $result = $dropbox->createFolder($parent);
        } catch (Exception $e) {
            $parent   = $path . '/' . $name . '-' . time();
            $result = $dropbox->createFolder($parent);
        }
        return $result;
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
        $params = get_option('_wpmfAddon_dropbox_config');
        if (empty($params['dropboxToken'])) {
            wp_send_json(array('status' => false));
        }

        if (isset($_POST['type']) && $_POST['type'] === 'auto') {
            // only run auto sync in one tab
            if (!empty($_POST['sync_token'])) {
                if (!get_option('wpmf_cloud_sync_time', false) && !get_option('wpmf_cloud_sync_token', false)) {
                    add_option('wpmf_cloud_sync_time', time());
                    add_option('wpmf_cloud_sync_token', $_POST['sync_token']);
                } else {
                    if ($_POST['sync_token'] !== get_option('wpmf_cloud_sync_token')) {
                        // stop run
                        if (time() - (int)get_option('wpmf_cloud_sync_time') < 60) {
                            wp_send_json(array('status' => false, 'continue' => false));
                        } else {
                            update_option('wpmf_cloud_sync_token', $_POST['sync_token']);
                            update_option('wpmf_cloud_sync_time', time());
                        }
                    }
                }
            }
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
        if (empty($params['dropboxToken'])) {
            return;
        }
        $datas = array(
            'id' => '',
            'folder_parent' => 0,
            'name' => 'Dropbox',
            'action' => 'wpmf_sync_dropbox',
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
                    'value'     => 'dropbox',
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
        if ($datas['type'] === 'dropbox') {
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
            $cloud_id = wpmfGetCloudFolderID($datas['cloud_folder_id']);
            if ($file_uploaded_id) {
                $thumb_ids = array();
                $dropbox_config = get_option('_wpmfAddon_dropbox_config');
                foreach ($file_paths as $size => $file_path) {
                    if ($size === 'original') {
                        continue;
                    }

                    $info = pathinfo($file_path);
                    $file_path = str_replace(array('?dl=0&amp;raw=1', '?dl=0&raw=1'), '', $file_path);
                    $filesize = filesize($file_path);
                    $id_folder = ($cloud_id === 'root') ? '' : $cloud_id;
                    $f         = fopen($file_path, 'rb');
                    $dropbox   = $this->getAccount();
                    $path      = $id_folder . '/wpmfthumbs/' . $info['basename'];
                    $result = $dropbox->uploadFile($path, WPMFDropbox\WriteMode::add(), $f, $filesize);
                    // upload attachment to cloud
                    if (!empty($result)) {
                        if (isset($dropbox_config['link_type']) && $dropbox_config['link_type'] === 'public') {
                            // public file
                            $links = $dropbox->get_shared_links($result['path_display']);
                            if (!empty($links['links'])) {
                                $shared_links = $links['links'][0];
                            } else {
                                $shared_links = $dropbox->create_shared_link($result['path_display']);
                            }
                            $link = $shared_links['url'] . '&raw=1';
                        } else {
                            $link = admin_url('admin-ajax.php') . '?action=wpmf-dbxdownload-file&id=' . urlencode($result['id']) . '&link=true&dl=0';
                        }

                        $meta['sizes'][$size]['file'] = $link;
                        $thumb_ids[] = $result['id'];
                    }
                }

                update_post_meta($attachment_id, '_wp_attachment_metadata', $meta);
                update_post_meta($attachment_id, 'cloud_thumb_ids', $thumb_ids);
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
     * Get dropbox image thumbnail
     *
     * @param string $cloud_id Dropbox image ID
     * @param string $size     Dropbox Image size
     *
     * @return array|null
     */
    public function getThumbnail($cloud_id, $size = 'w640h480')
    {
        if ($cloud_id) {
            $dropbox    = $this->getAccount();
            $cloud_path = $dropbox->getFileByID($cloud_id);
            $fileExtension = strtolower(pathinfo($cloud_path['path_display'], PATHINFO_EXTENSION));
            if (in_array($fileExtension, array('jpeg', 'jpg', 'png'))) {
                if ($fileExtension === 'jpg') {
                    $fileExtension = 'jpeg';
                }

                $thumbs     = $dropbox->getThumbnail($cloud_path['path_display'], $fileExtension, $size);
            }
        }
        return $thumbs;
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
        $filename = str_replace('?dl=0&raw=1', '', $filename);
        $info = pathinfo($filename);
        $extension = $info['extension'];

        $dropbox    = $this->getAccount();
        $upload_dir = wp_upload_dir();
        require_once 'includes/mime-types.php';

        // get dropbox file path by ID
        $cloud_path = $dropbox->getFileByID($cloud_id);
        if (empty($cloud_path['path_display'])) {
            return false;
        }

        $extension   = strtolower($extension);
        $content     = $dropbox->get_filecontent($cloud_path['path_display']);
        $getMimeType = getMimeType($extension);
        $status = $this->insertAttachmentMetadata(
            $upload_dir['path'],
            $upload_dir['url'],
            $filename,
            $content,
            $getMimeType,
            $extension,
            $term_id
        );

        if ($status) {
            return true;
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
     * Download dropbox file
     *
     * @return void
     */
    public function downloadFile()
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.EscapeOutput.OutputNotEscaped -- download URL inserted post content
        if (isset($_REQUEST['id'])) {
            $id_file  = $_REQUEST['id'];
            $is_thumb = false;
            if (isset($_REQUEST['size'])) {
                $size = (isset($_REQUEST['size'])) ? $_REQUEST['size'] : 'medium';
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
                switch ($size) {
                    case 'thumbnail':
                        $size = 'w128h128';
                        break;
                    case 'medium':
                        $size = 'w640h480';
                        break;
                    case 'large':
                        $size = 'w1024h768';
                        break;
                    default:
                        $size = 'w640h480';
                }

                $thumb = $this->getThumbnail($id_file, $size);
                if (!empty($local_id)) {
                    $path = get_post_meta((int)$local_id, '_wp_attached_file', true);
                    $pinfo    = pathinfo($path);
                    include_once 'includes/mime-types.php';
                    $contenType = getMimeType($pinfo['extension']);
                    header('Content-Type: ' . $contenType);
                    echo $thumb;
                    die();
                } else {
                    $is_thumb = false;
                }
            }

            if (!$is_thumb) {
                $dropbox  = $this->getAccount();
                $getFile  = $dropbox->getMetadata($id_file);
                $pinfo    = pathinfo($getFile['path_lower']);
                $tempfile = $pinfo['basename'];
                include_once 'includes/mime-types.php';
                $contenType = getMimeType($pinfo['extension']);
                header('Content-Disposition: inline; filename="' . basename($tempfile) . '"');
                header('Content-Description: File Transfer');
                header('Content-Type: ' . $contenType);
                header('Content-Transfer-Encoding: binary');

                header('Pragma: public');
                header('Content-Length: ' . $getFile['size']);
                $content = $dropbox->get_filecontent($getFile['path_lower']);
                echo $content;
                die();
            }
        } else {
            wp_send_json(false);
        }
        // phpcs:enable
    }

    /**
     * Redirect url
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
            $this->doUpload($attachment_id, $folder_id, 'upload');
        }
    }

    /**
     * Do upload File
     *
     * @param integer $attachment_id Attachment ID
     * @param string  $folder_id     Folder id
     * @param string  $action        Action
     *
     * @return boolean|string
     */
    public function doUpload($attachment_id, $folder_id, $action = 'upload')
    {
        $cloud_id = wpmfGetCloudFolderID($folder_id);
        if ($cloud_id) {
            $cloud_type = wpmfGetCloudFolderType($folder_id);
            if ($cloud_type && $cloud_type === 'dropbox') {
                try {
                    $dropbox_config = get_option('_wpmfAddon_dropbox_config');
                    $filePath = get_attached_file($attachment_id);
                    /*$scaled = WpmfAddonHelper::fixImageOrientation(array('file' => $filePath));
                    $filePath = $scaled['file'];*/
                    $size = filesize($filePath);
                    if (file_exists($filePath)) {
                        $info = pathinfo($filePath);
                        $id_folder = ($cloud_id === 'root') ? '' : $cloud_id;
                        $f         = fopen($filePath, 'rb');
                        $dropbox   = $this->getAccount();
                        $path      = $id_folder . '/' . $info['basename'];

                        $result = $dropbox->uploadFile($path, WPMFDropbox\WriteMode::add(), $f, $size);
                        // upload attachment to cloud
                        if (!empty($result)) {
                            $metadata = $dropbox->getFileMetadata($result['path_display']);
                            // add attachment meta
                            global $wpdb;
                            add_post_meta($attachment_id, 'wpmf_drive_id', $result['id']);
                            add_post_meta($attachment_id, 'wpmf_drive_type', 'dropbox');

                            // update guid URL
                            $where = array('ID' => $attachment_id);
                            if (isset($dropbox_config['link_type']) && $dropbox_config['link_type'] === 'public') {
                                // public file
                                $links = $dropbox->get_shared_links($result['path_display']);
                                if (!empty($links['links'])) {
                                    $shared_links = $links['links'][0];
                                } else {
                                    $shared_links = $dropbox->create_shared_link($result['path_display']);
                                }
                                $link = $shared_links['url'] . '&raw=1';
                            } else {
                                $link = admin_url('admin-ajax.php') . '?action=wpmf-dbxdownload-file&id=' . urlencode($result['id']) . '&link=true&dl=0';
                            }

                            $wpdb->update($wpdb->posts, array('guid' => $link), $where);
                            add_post_meta($attachment_id, 'wpmf_drive_link', $link);

                            // add attachment metadata
                            $meta = array();
                            if (in_array(strtolower($info['extension']), array('jpg', 'jpeg', 'png', 'webp'))) {
                                list($width, $height) = getimagesize($filePath);
                                $meta['width'] = $width;
                                $meta['height'] = $height;
                            }

                            $meta['file'] = $link;
                            if (isset($metadata['size'])) {
                                $meta['filesize'] = $metadata['size'];
                            }

                            if ($action === 'upload') {
                                unlink($filePath);
                                // thumbnail
                                $sizes = $this->renderMetaSizes($info['extension'], $attachment_id, $result['id']);
                                if (!empty($sizes)) {
                                    $meta['sizes'] = $sizes;
                                }

                                add_post_meta($attachment_id, 'wpmf_attachment_metadata', $meta);
                            }

                            if ($action === 'move_file') {
                                update_post_meta($attachment_id, '_wp_attachment_metadata', $meta);
                            }

                            return $result['id'];
                        }
                    }
                    return false;
                } catch (Exception $e) {
                    return false;
                }
            }
        }
        return false;
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
            if (!empty($data) && !empty($meta)) {
                $meta = $data;
                update_post_meta($attachment_id, '_wp_attachment_metadata', $meta);
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
                if ($cloud_type && $cloud_type === 'dropbox') {
                    if ($cloud_id === 'root') {
                        $cloud_path = '';
                    } else {
                        $dropbox = $this->getAccount();
                        $cloud_id = $dropbox->getFileByID($cloud_id);
                        $cloud_path = $cloud_id['path_display'];
                    }

                    $folder = $this->doCreateFolder($name, $cloud_path);
                    add_term_meta($folder_id, 'wpmf_drive_id', $folder['id']);
                    add_term_meta($folder_id, 'wpmf_drive_type', 'dropbox');
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
                if ($cloud_type && $cloud_type === 'dropbox') {
                    $dropbox = $this->getAccount();
                    if ($cloud_id !== 'root' && $cloud_id !== '') {
                        $cloud_path = $dropbox->getFileByID($cloud_id);
                        $dropbox->delete($cloud_path['path_display']);
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
                if ($cloud_type && $cloud_type === 'dropbox') {
                    $dropbox = $this->getAccount();
                    if ($cloud_id !== 'root') {
                        $cloud_path = $dropbox->getFileByID($cloud_id);
                        $pathinfo = pathinfo($cloud_path['path_display']);
                        $dropbox->move($cloud_path['path_display'], rtrim($pathinfo['dirname'], '/') . '/' . urldecode($name));
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
                if ($cloud_type && $cloud_type === 'dropbox') {
                    if ($cloud_id !== 'root') {
                        $dropbox = $this->getAccount();
                        $cloud_parentid = wpmfGetCloudFolderID($parent_id);
                        $cloud_path = $dropbox->getFileByID($cloud_id);
                        $pathinfo = pathinfo($cloud_path['path_display']);
                        if ($cloud_parentid === 'root') {
                            $newpath = '/' . $pathinfo['filename'];
                        } else {
                            $cloud_parent_path = $dropbox->getFileByID($cloud_parentid);
                            $newpath = $cloud_parent_path['path_display'] . '/' . $pathinfo['filename'];
                        }

                        $dropbox->move($cloud_path['path_display'], $newpath);
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
                if ($cloud_type && $cloud_type === 'dropbox') {
                    $dropbox = $this->getAccount();
                    $cloud_parentid = wpmfGetCloudFolderID($parent_id);

                    $cloud_path = $dropbox->getFileByID($cloud_id);
                    $pathinfo = pathinfo($cloud_path['path_display']);
                    if ($cloud_parentid === 'root') {
                        $newpath = '/' . $pathinfo['basename'];
                    } else {
                        $cloud_parent_path = $dropbox->getFileByID($cloud_parentid);
                        $newpath = $cloud_parent_path['path_display'] . '/' . $pathinfo['basename'];
                    }

                    $dropbox->move($cloud_path['path_display'], $newpath);
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
                if ($cloud_type && $cloud_type === 'dropbox') {
                    $dropbox = $this->getAccount();
                    $cloud_path = $dropbox->getFileByID($cloud_id);
                    $dropbox->delete($cloud_path['path_display']);

                    $thumb_ids = get_post_meta($pid, 'cloud_thumb_ids', true);
                    foreach ($thumb_ids as $thumb_id) {
                        $cloud_path = $dropbox->getFileByID($thumb_id);
                        $dropbox->delete($cloud_path['path_display']);
                    }
                }
            }
        } catch (Exception $ex) {
            return false;
        }

        return true;
    }

    /**
     * Get file link
     *
     * @param string $id             Cloud file ID
     * @param array  $dropbox_config Dropbox settings
     * @param object $dropbox        Dropbox Client
     *
     * @return boolean|string
     */
    public function getLink($id, $dropbox_config, $dropbox)
    {
        try {
            $cloud_path = $dropbox->getFileByID($id);
            if (isset($dropbox_config['link_type']) && $dropbox_config['link_type'] === 'public') {
                // public file
                $links = $dropbox->get_shared_links($cloud_path['path_display']);
                if (!empty($links['links'])) {
                    $shared_links = $links['links'][0];
                } else {
                    $shared_links = $dropbox->create_shared_link($cloud_path['path_display']);
                }
                $link = $shared_links['url'] . '&raw=1';
            } else {
                $link = admin_url('admin-ajax.php') . '?action=wpmf-dbxdownload-file&id=' . urlencode($cloud_path['path_display']) . '&link=true&dl=0';
            }
        } catch (Exception $e) {
            $link = false;
        }

        return $link;
    }

    /**
     * Insert attachment
     *
     * @param array   $info        File info
     * @param array   $child       File details
     * @param integer $parent      Parent folder
     * @param array   $upload_path Upload path
     * @param string  $link        Link
     * @param string  $mimeType    Mime Type
     * @param integer $width       Width
     * @param integer $height      Height
     *
     * @return void
     */
    public function insertAttachment($info, $child, $parent, $upload_path, $link, $mimeType, $width = 0, $height = 0)
    {
        $attachment = array(
            'guid'           => $link,
            'post_mime_type' => $mimeType,
            'post_title'     => $info['filename'],
            'post_type'     => 'attachment',
            'post_status'    => 'inherit'
        );

        $attach_id   = wp_insert_post($attachment);
        $attached = trim($upload_path['subdir'], '/') . '/' . $child['name'];
        wp_set_object_terms((int) $attach_id, (int) $parent, WPMF_TAXO);

        update_post_meta($attach_id, '_wp_attached_file', $attached);
        update_post_meta($attach_id, 'wpmf_size', $child['size']);
        update_post_meta($attach_id, 'wpmf_filetype', $info['extension']);
        update_post_meta($attach_id, 'wpmf_order', 0);
        update_post_meta($attach_id, 'wpmf_drive_id', $child['id']);
        update_post_meta($attach_id, 'wpmf_drive_type', 'dropbox');

        $meta = array();
        if (strpos($mimeType, 'image') !== false) {
            if (!empty($width) && !empty($height)) {
                $meta['width'] = $width;
                $meta['height'] = $height;
            } else {
                list($width, $heigth) = wpmfGetImgSize($link);
                $meta['width'] = $width;
                $meta['height'] = $heigth;
            }
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
            if (empty($curent_parents)) {
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
        $params = get_option('_wpmfAddon_dropbox_config');
        if (empty($params['dropboxToken'])) {
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
        $params = get_option('_wpmfAddon_dropbox_config');
        if (!empty($params['dropboxToken'])) {
            // insert root folder on Media library
            if (!get_option('wpmf_dropbox_create_root', false)) {
                $inserted = wp_insert_term('Dropbox', WPMF_TAXO, array('parent' => 0));
                if (is_wp_error($inserted)) {
                    $folder_id = (int)$inserted->error_data['term_exists'];
                } else {
                    $folder_id = (int)$inserted['term_id'];
                }
                update_term_meta($folder_id, 'wpmf_drive_type', 'dropbox');
                update_term_meta($folder_id, 'wpmf_drive_root_id', '');
                add_option('wpmf_dropbox_create_root', 1, '', 'yes');
            }

            // add to queue
            $datas = array(
                'id' => '',
                'folder_parent' => 0,
                'name' => 'Dropbox',
                'action' => 'wpmf_sync_dropbox',
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
        $configs = get_option('_wpmfAddon_dropbox_config');
        if (empty($configs['dropboxToken'])) {
            return -1;
        }
        global $wpdb;
        $name = html_entity_decode($datas['name'], ENT_COMPAT, 'UTF-8');
        if ($datas['type'] === 'folder') {
            // check folder exists
            if ($datas['id'] === '') {
                $meta_key = 'wpmf_drive_root_id';
            } else {
                $meta_key = 'wpmf_drive_id';
            }
            $row = $wpdb->get_row($wpdb->prepare('SELECT term_id, meta_value FROM ' . $wpdb->termmeta . ' WHERE meta_key = %s AND BINARY meta_value = BINARY %s', array($meta_key, $datas['id'])));
            // if folder not exists
            if (!$row) {
                $inserted = wp_insert_term($name, WPMF_TAXO, array('parent' => (int)$datas['folder_parent']));
                if (is_wp_error($inserted)) {
                    $folder_id = (int)$inserted->error_data['term_exists'];
                } else {
                    $folder_id = (int)$inserted['term_id'];
                }
                if ($name === 'Dropbox' && (int)$datas['folder_parent'] === 0) {
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

            // find childs element to add to queue
            if (!empty($folder_id)) {
                $responses = array();
                $responses['folder_id'] = (int)$folder_id;
                update_term_meta($responses['folder_id'], 'wpmf_drive_type', 'dropbox');
                $wpmfQueue = JuMainQueue::getInstance('wpmf');
                $wpmfQueue->updateQueueTermMeta((int)$responses['folder_id'], (int)$element_id);
                $wpmfQueue->updateResponses((int)$element_id, $responses);
                $this->addChildsToQueue($datas['id'], $folder_id);
            }
        } else {
            $upload_path = wp_upload_dir();
            $info = pathinfo($name);
            $row = $wpdb->get_row($wpdb->prepare('SELECT post_id, meta_value FROM ' . $wpdb->postmeta . ' WHERE meta_key = %s AND BINARY meta_value = BINARY %s', array('wpmf_drive_id', $datas['id'])));
            if (!$row) {
                $dropbox      = $this->getAccount();
                $link = $this->getLink($datas['id'], $configs, $dropbox);
                if (!$link) {
                    return false;
                }

                // insert attachment
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

                update_post_meta($file_id, '_wp_attached_file', $link);
                update_post_meta($file_id, 'wpmf_size', $datas['size']);
                update_post_meta($file_id, 'wpmf_filetype', $info['extension']);
                update_post_meta($file_id, 'wpmf_order', 0);
                update_post_meta($file_id, 'wpmf_drive_id', $datas['id']);
                update_post_meta($file_id, 'wpmf_drive_type', 'dropbox');

                $meta = array('width' => 10, 'height' => 10, 'filesize' => 10);
                if (strpos($datas['file']['mimeType'], 'image') !== false) {
                    if (isset($child['image']['width']) && isset($datas['image']['height'])) {
                        $meta['width'] = $datas['image']['width'];
                        $meta['height'] = $datas['image']['height'];
                    } else {
                        list($width, $heigth) = wpmfGetImgSize($link);
                        $meta['width'] = $width;
                        $meta['height'] = $heigth;
                    }

                    $meta['file'] = $link;
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
                $this->updateAttachment($info, $file_id, $datas['folder_parent']);
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
                if (strpos($file->guid, 'wpmf-dbxdownload-file') !== false && $configs['link_type'] === 'public') {
                    $dropbox      = $this->getAccount();
                    $link = $this->getLink($datas['id'], $configs, $dropbox);
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
     *
     * @return array
     */
    public function renderMetaSizes($extension, $file_id, $drive_id)
    {
        $meta_sizes = array();
        $dropboxconfig = get_option('_wpmfAddon_dropbox_config');
        if (isset($dropboxconfig['generate_thumbnails']) && (int)$dropboxconfig['generate_thumbnails'] === 0) {
            return $meta_sizes;
        }
        $fileExtension = strtolower($extension);
        if (in_array($fileExtension, array('jpeg', 'jpg', 'png'))) {
            if ($fileExtension === 'jpg') {
                $fileExtension = 'jpeg';
            }

            $sizes = array('w128h128', 'w640h480', 'w1024h768');
            include_once 'includes/mime-types.php';
            $mimeType   = getMimeType($fileExtension);
            foreach ($sizes as $size) {
                $size_info = array();
                $thumb = admin_url('admin-ajax.php') . '?action=wpmf-dbxdownload-file&local_id='. $file_id .'&id=' . urlencode($drive_id) . '&link=true&dl=0&size=' . $size;
                switch ($size) {
                    case 'w128h128':
                        $size = 'thumbnail';
                        $size_info['width'] = 128;
                        $size_info['height'] = 128;
                        break;
                    case 'w640h480':
                        $size = 'medium';
                        $size_info['width'] = 640;
                        $size_info['height'] = 480;
                        break;
                    case 'w1024h768':
                        $size = 'large';
                        $size_info['width'] = 1024;
                        $size_info['height'] = 768;
                        break;
                }

                $size_info['file'] = $thumb;
                $size_info['mime-type'] = $mimeType;
                $meta_sizes[$size] = $size_info;
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
        $error = false;
        $has_more  = false;
        $cursor  = '';
        $childs = array();
        do {
            try {
                $dropbox = $this->getAccount();
                if ($has_more) {
                    $fs = $dropbox->getMoreChildrens(array('cursor' => $cursor));
                } else {
                    $fs = $dropbox->getMetadataWithChildren($folderID, false, array('limit' => 200));
                }

                $childs = array_merge($childs, $fs['entries']);
                $has_more = $fs['has_more'];
                $cursor = $fs['cursor'];
            } catch (Exception $e) {
                $error = true;
                $has_more = false;
            }
        } while ($has_more);

        if ($error) {
            return;
        }

        include_once 'includes/mime-types.php';
        // get folder childs list on cloud
        $cloud_folders_list = array();
        // get file childs list on cloud
        $cloud_files_list = array();
        // Create files in media library
        foreach ($childs as $child) {
            if (strpos($child['path_lower'], 'wpmfthumb') !== false) {
                continue;
            }

            $datas = array(
                'id' => $child['id'],
                'path_lower' => $child['path_lower'],
                'folder_parent' => $folder_parent,
                'name' => mb_convert_encoding($child['name'], 'HTML-ENTITIES', 'UTF-8'),
                'action' => 'wpmf_sync_dropbox',
                'cloud_parent' => $folderID
            );

            if ($child['.tag'] === 'file') {
                $cloud_files_list[] = $child['id'];
                $fileExtension = pathinfo($child['name'], PATHINFO_EXTENSION);
                $mimeType   = getMimeType($fileExtension);
                $datas['type'] = 'file';
                $datas['rev'] = $child['rev'];
                $datas['file'] = array('mimeType' => $mimeType);
                $datas['image'] = array();
                $datas['size'] = $child['size'];
                if (strpos($mimeType, 'image') !== false) {
                    $dimensions = array('width' => 0, 'height' => 0);
                    if (isset($child['media_info'])) {
                        if (empty($child['media_info']['metadata']['dimensions'])) {
                            $dimensions = array(
                                'width' => $child['media_info']['metadata']['dimensions']['width'],
                                'height' => $child['media_info']['metadata']['dimensions']['height']
                            );
                        }
                    }
                    $datas['image'] = $dimensions;
                }
            } else {
                $cloud_folders_list[] = $child['id'];
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
            'action' => 'wpmf_dropbox_remove',
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
