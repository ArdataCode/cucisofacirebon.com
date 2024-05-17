<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');
require_once(WPMFAD_PLUGIN_DIR . '/class/wpmfHelper.php');

/**
 * Class WpmfAddonGooglePhoto
 * This class that holds most of the admin functionality for Google Drive
 */
class WpmfAddonGooglePhoto
{

    /**
     * Params
     *
     * @var $param
     */
    protected $params;

    /**
     * WpmfAddonGooglePhoto constructor.
     */
    public function __construct()
    {
        $this->loadParams();
        if ($this->isTokenExpired()) {
            $this->getAccessTokenFromRefresh();
        }
    }

    /**
     * Get google config
     *
     * @return mixed
     */
    public function getParams()
    {
        $default = array(
            'googleClientId' => '',
            'googleClientSecret' => ''
        );
        return get_option('_wpmfAddon_google_photo_config', $default);
    }

    /**
     * Set google config
     *
     * @param array  $data Data to set config
     * @param string $type Google photo or google drive
     *
     * @return void
     */
    public function setParams($data, $type = 'google-drive')
    {
        WpmfAddonHelper::saveCloudConfigs($data, $type);
    }

    /**
     * Load google drive params
     *
     * @return void
     */
    protected function loadParams()
    {
        $params = $this->getParams();
        $this->params = new stdClass();
        $this->params->google_client_id = isset($params['googleClientId']) ? $params['googleClientId'] : '';
        $this->params->google_client_secret = isset($params['googleClientSecret']) ? $params['googleClientSecret'] : '';
        $this->params->google_credentials = isset($params['googleCredentials']) ? $params['googleCredentials'] : '';
        $this->params->connected = isset($params['connected']) ? $params['connected'] : 0;
        if (isset($params['googleCredentials'])) {
            $googleCredentials = json_decode($params['googleCredentials']);
            $this->params->access_token = isset($googleCredentials->access_token) ? $googleCredentials->access_token : '';
            $this->params->refresh_token = isset($googleCredentials->refresh_token) ? $googleCredentials->refresh_token : '';
        }
    }

    /**
     * Save google drive params
     *
     * @return void
     */
    protected function saveParams()
    {
        $params = $this->getParams();
        $params['googleClientId'] = $this->params->google_client_id;
        $params['googleClientSecret'] = $this->params->google_client_secret;
        $params['googleCredentials'] = $this->params->google_credentials;
        $this->setParams($params, 'google-photo');
    }

    /**
     * Get author url
     *
     * @return string
     */
    public function getAuthorisationUrl()
    {
        $client = new WpmfGoogle_Client();
        $client->setClientId($this->params->google_client_id);
        $uri = admin_url('options-general.php?page=option-folder&task=wpmf&function=wpmf_google_photo_authenticated');
        $client->setRedirectUri($uri);
        $client->setAccessType('offline');
        $client->setApprovalPrompt('force');
        $client->setState('');
        $client->setScopes(array(
            'https://www.googleapis.com/auth/photoslibrary.readonly'
        ));
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
     * Make an HTTP request
     *
     * @param string  $url             URL
     * @param string  $method          GET | POST | DELETE
     * @param null    $post_fields     Post fields
     * @param string  $user_agent      User agent
     * @param integer $timeout         Timeout
     * @param boolean $ssl_verify_peer SSL verify
     * @param array   $headers         Header
     * @param array   $cookies         Cookie
     *
     * @return array|WP_Error
     */
    public function http($url, $method = 'POST', $post_fields = null, $user_agent = null, $timeout = 90, $ssl_verify_peer = false, $headers = array(), $cookies = array())
    {
        $curl_args = array(
            'user-agent' => $user_agent,
            'timeout' => $timeout,
            'sslverify' => $ssl_verify_peer,
            'headers' => array_merge(array('Expect:'), $headers),
            'method' => $method,
            'body' => $post_fields,
            'cookies' => $cookies,
        );

        switch ($method) {
            case 'DELETE':
                if (!empty($post_fields)) {
                    $url = $url . '?' . $post_fields;
                }
                break;
        }

        $response = wp_remote_request($url, $curl_args);
        return $response;
    }

    /**
     * Get access token from refresh token
     *
     * @return void
     */
    public function getAccessTokenFromRefresh()
    {
        if (isset($this->params->google_client_id, $this->params->google_client_secret, $this->params->refresh_token)) {
            $response = $this->http('https://accounts.google.com/o/oauth2/token', 'POST', array(
                'client_id' => $this->params->google_client_id,
                'client_secret' => $this->params->google_client_secret,
                'refresh_token' => $this->params->refresh_token,
                'grant_type' => 'refresh_token'
            ));

            if (!is_wp_error($response)) {
                if (!is_wp_error($response)) {
                    $body = wp_remote_retrieve_body($response);
                    $body = json_decode($body);
                }

                $params = $this->getParams();
                if (isset($params['googleCredentials'])) {
                    $googleCredentials = json_decode($params['googleCredentials']);
                    if (!empty($body->access_token)) {
                        $googleCredentials->access_token = $body->access_token;
                        $this->params->access_token = isset($googleCredentials->access_token) ? $googleCredentials->access_token : '';
                        $googleCredentials = json_encode($googleCredentials);
                        $params['googleCredentials'] = $googleCredentials;
                        $params['token_expires'] = $body->expires_in;
                        $params['token_created'] = time();
                        $this->setParams($params, 'google-photo');
                        $this->params->google_credentials = $googleCredentials;
                    }
                }
            }
        }
    }

    /**
     * Check token expired
     *
     * @return boolean
     */
    public function isTokenExpired()
    {
        $params = $this->getParams();
        if (!isset($params['token_expires'])) {
            return true;
        }

        $current = time();
        if ($params['token_created'] + $params['token_expires'] < $current) {
            return true;
        }
        return false;
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
     * Create album
     *
     * @return mixed
     */
    public function getListAlbums()
    {
        $query_url = 'https://photoslibrary.googleapis.com/v1/albums';
        $query_url = add_query_arg('access_token', $this->params->access_token, $query_url);
        $albums = array();
        $pageToken = null;
        do {
            try {
                $additional = array();
                $additional['pageSize'] = 40;
                if ($pageToken) {
                    $additional['pageToken'] = $pageToken;
                }

                $call_args = array();
                $call_args['method'] = 'GET';
                $call_args['body'] = $additional;
                $response = wp_remote_request($query_url, $call_args);
                if (!is_wp_error($response)) {
                    $body = wp_remote_retrieve_body($response);
                    $body = json_decode($body);
                }

                $items = isset($body->albums) ? $body->albums : array();
                $albums = array_merge($albums, $items);
                if (isset($body->nextPageToken)) {
                    $pageToken = $body->nextPageToken;
                } else {
                    $pageToken = null;
                }
            } catch (Exception $e) {
                $pageToken = null;
            }
        } while ($pageToken);
        return $albums;
    }

    /**
     * Get all photos
     *
     * @param string $pageToken PageToken
     * @param string $pageSize  Limit
     *
     * @return mixed
     */
    public function getAllMediaItems($pageToken = '', $pageSize = 30)
    {
        $query_url = 'https://photoslibrary.googleapis.com/v1/mediaItems';
        $query_url = add_query_arg('access_token', $this->params->access_token, $query_url);
        try {
            $additional = array();
            $additional['pageSize'] = $pageSize;
            if (!empty($pageToken)) {
                $additional['pageToken'] = $pageToken;
            }

            $call_args = array();
            $call_args['method'] = 'GET';
            $call_args['body'] = $additional;
            $call_args['sslverify'] = false;
            $response = wp_remote_request($query_url, $call_args);
            if (!is_wp_error($response)) {
                $body = wp_remote_retrieve_body($response);
                $body = json_decode($body);
            }

            if (!empty($body->nextPageToken)) {
                $return = array('photos' => $body->mediaItems, 'pageToken' => $body->nextPageToken);
            } else {
                $return = array('photos' => $body->mediaItems);
            }
        } catch (Exception $e) {
            $return = array('photos' => array());
        }

        return $return;
    }

    /**
     * Get photos by album ID
     *
     * @param string $albumId   Album ID
     * @param string $pageSize  Limit
     * @param string $pageToken PageToken
     *
     * @return mixed
     */
    public function getMediaItemsByAlbumId($albumId, $pageSize = 30, $pageToken = '')
    {
        $query_url = 'https://photoslibrary.googleapis.com/v1/mediaItems:search';
        $query_url = add_query_arg('access_token', $this->params->access_token, $query_url);
        try {
            $additional = array();
            $additional['pageSize'] = $pageSize;
            $additional['albumId'] = $albumId;
            if (!empty($pageToken)) {
                $additional['pageToken'] = $pageToken;
            }

            $call_args = array();
            $call_args['method'] = 'POST';
            $call_args['body'] = $additional;
            $response = wp_remote_request($query_url, $call_args);
            if (!is_wp_error($response)) {
                $body = wp_remote_retrieve_body($response);
                $body = json_decode($body);
            }

            if (!empty($body->nextPageToken)) {
                $return = array('photos' => $body->mediaItems, 'pageToken' => $body->nextPageToken);
            } else {
                $return = array('photos' => $body->mediaItems);
            }
        } catch (Exception $e) {
            $return = array('photos' => array());
        }

        return $return;
    }
}
