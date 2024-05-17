<?php

use WP_Media_Folder\Aws\S3\S3Client;
use WP_Media_Folder\Aws\Sdk;

/**
 * Class WpmfAddonAWS3
 * WP_Media_Folder\Providers
 */
class WpmfAddonAWS3
{

    /**
     * Aws client
     *
     * @var Sdk
     */
    public $sdk_client;

    /**
     * S3 Client
     *
     * @var S3Client
     */
    public $aws3_client;

    /**
     * Regions list
     *
     * @var array
     */
    public $regions = array(
        'us-east-1'      => 'US East (N. Virginia)',
        'us-east-2'      => 'US East (Ohio)',
        'us-west-1'      => 'US West (N. California)',
        'us-west-2'      => 'US West (Oregon)',
        'ca-central-1'   => 'Canada (Central)',
        'ap-south-1'     => 'Asia Pacific (Mumbai)',
        'ap-northeast-2' => 'Asia Pacific (Seoul)',
        'ap-southeast-1' => 'Asia Pacific (Singapore)',
        'ap-southeast-2' => 'Asia Pacific (Sydney)',
        'ap-northeast-1' => 'Asia Pacific (Tokyo)',
        'eu-central-1'   => 'EU (Frankfurt)',
        'eu-west-1'      => 'EU (Ireland)',
        'eu-west-2'      => 'EU (London)',
        'eu-west-3'      => 'EU (Paris)',
        'sa-east-1'      => 'South America (Sao Paulo)'
    );

    /**
     * WpmfAddonAWS3 constructor.
     *
     * @param string $region Region
     */
    public function __construct($region = '')
    {
        // Autoloader.
        require_once WPMFAD_PLUGIN_DIR . '/class/Aws3/aws-autoloader.php';
        // get selected cloud endpoint
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
        if (isset($_GET['cloud'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
            $cloud_endpoint = $_GET['cloud'];
        } else {
            $cloud_endpoint = get_option('wpmf_cloud_endpoint');
            if (empty($cloud_endpoint)) {
                $cloud_endpoint = 'aws3';
            }
        }

        if ($cloud_endpoint === 'wasabi') {
            $this->regions = array(
                'us-east-1'      => 'US East (N. Virginia)',
                'us-east-2'      => 'US East (Ohio)',
                'us-west-1'      => 'US West (N. California)',
                'ca-central-1'   => 'Canada (Central)',
                'ap-northeast-2' => 'Asia Pacific (Seoul)',
                'ap-southeast-1' => 'Asia Pacific (Singapore)',
                'ap-southeast-2' => 'Asia Pacific (Sydney)',
                'ap-northeast-1' => 'Asia Pacific (Tokyo)',
                'eu-central-1'   => 'EU (Frankfurt)',
                'eu-central-2'   => 'eu-central-2',
                'us-central-1'   => 'us-central-1',
                'eu-west-1'      => 'EU (Ireland)',
                'eu-west-2'      => 'EU (London)',
            );
        }

        if ($cloud_endpoint === 'digitalocean') {
            $this->regions = array(
                'nyc3'      => 'New York',
                'ams3'      => 'Amsterdam',
                'sgp1'      => 'Singapore',
                'sfo2'      => 'San Francisco 2',
                'sfo3'      => 'San Francisco 3',
                'fra1'      => 'Frankfurt'
            );
        }

        if ($cloud_endpoint === 'linode') {
            $this->regions = array(
                'ap-south-1' => 'Singapore, SG',
                'eu-central-1' => 'Frankfurt, DE',
                'us-southeast-1' => 'Atlanta, GA',
                'us-east-1' => 'Newark, NJ'
            );
        }

        if ($cloud_endpoint === 'google_cloud_storage') {
            $this->regions = array(
                'northamerica-northeast1' => 'Montréal',
                'northamerica-northeast2' => 'Toronto',
                'southamerica-east1' => 'São Paulo',
                'southamerica-west1' => 'Santiago',
                'us' => 'United States',
                'us-central1' => 'Iowa',
                'us-east1' => 'South Carolina',
                'us-east4' => 'Northern Virginia',
                'us-east5' => 'Columbus',
                'us-south1' => 'Dallas',
                'us-west1' => 'Oregon',
                'us-west2' => 'Los Angeles',
                'us-west3' => 'Salt Lake City',
                'us-west4' => 'Las Vegas',
                'europe-central2' => 'Warsaw',
                'europe-north1' => 'Finland',
                'europe-southwest1' => 'Madrid',
                'europe-west1' => 'Belgium',
                'europe-west2' => 'London',
                'europe-west3' => 'Frankfurt',
                'europe-west4' => 'Netherlands',
                'europe-west6' => 'Zurich',
                'europe-west8' => 'Milan',
                'europe-west9' => 'Paris',
                'asia-east1' => 'Taiwan',
                'asia-east2' => 'Hong Kong',
                'asia-northeast1' => 'Tokyo',
                'asia-northeast2' => 'Osaka',
                'asia-northeast3' => 'Seoul',
                'asia-south1' => 'Mumbai',
                'asia-south2' => 'Delhi',
                'asia-southeast1' => 'Singapore',
                'asia-southeast2' => 'Jakarta',
                'australia-southeast1' => 'Sydney',
                'australia-southeast2' => 'Melbourne'
            );
        }

        if ($cloud_endpoint !== 'google_cloud_storage') {
            $args = getOffloadOption($cloud_endpoint);
            if (!empty($args)) {
                if ($cloud_endpoint === 'digitalocean') {
                    $args['signature_version'] = 'v4-unsigned-body';
                } else {
                    $args['signature_version'] = 'v4';
                }

                if (empty($args['version'])) {
                    $args['version'] = '2006-03-01';
                }

                if (!empty($region) && isset($this->regions[$region])) {
                    $args['region'] = $region;
                } else {
                    if (empty($args['region']) || empty($this->regions[$args['region']])) {
                        $firstValue = reset($this->regions);
                        $firstKey = key($this->regions);
                        $args['region'] = $firstKey;
                    }
                }

                if (isset($cloud_endpoint)) {
                    switch ($cloud_endpoint) {
                        case 'wasabi':
                            $args['endpoint']               = 'https://s3.'. $args['region'] .'.wasabisys.com';
                            $args['use_path_style_endpoint'] = true;
                            break;
                        case 'digitalocean':
                            $args['endpoint']               = 'https://'. $args['region'] .'.digitaloceanspaces.com';
                            $args['use_path_style_endpoint'] = true;
                            break;
                        case 'linode':
                            $args['endpoint']               = 'https://'. $args['region'] .'.linodeobjects.com';
                            $args['use_path_style_endpoint'] = true;
                            break;
                    }
                }

                try {
                    self::getClient($args);
                    $this->aws3_client = self::getServiceClient($args);
                } catch (Exception $e) {
                    echo esc_html($e->getMessage());
                }
            }
        }
    }

    /**
     * Get client for the provider's SDK.
     *
     * @param array $args Params
     *
     * @return void
     */
    public function getClient($args)
    {
        $this->sdk_client = new Sdk($args);
    }

    /**
     * Get service client
     *
     * @param array $args Params
     *
     * @return S3Client
     */
    public function getServiceClient($args)
    {
        if (empty($args['region']) || $args['region'] === 'us-east-1') {
            $this->aws3_client = $this->sdk_client->createMultiRegionS3($args);
        } else {
            $this->aws3_client = $this->sdk_client->createS3($args);
        }

        return $this->aws3_client;
    }

    /**
     * Get all folders and files in a folder
     *
     * @param array $params Params list
     *
     * @return array
     */
    public function getFoldersFilesFromBucket($params)
    {
        $childs     = array();
        $token  = null;
        do {
            try {
                if ($token) {
                    $params['ContinuationToken'] = $token;
                }

                $objects = $this->aws3_client->listObjectsV2($params);
                $childs    = array_merge($childs, $objects['Contents']);
                $token = $objects['NextContinuationToken'];
            } catch (Exception $e) {
                print 'An error occurred: ' . esc_html($e->getMessage());
                $token = null;
            }
        } while ($token);

        return $childs;
    }

    /**
     * Create bucket.
     *
     * @param array $args Params
     *
     * @return void
     */
    public function createBucket($args)
    {
        $this->aws3_client->createBucket($args);
    }

    /**
     * Delete bucket.
     *
     * @param array $args Params
     *
     * @return \WP_Media_Folder\Aws\Result
     */
    public function deleteBucket($args)
    {
        return $this->aws3_client->deleteBucket($args);
    }

    /**
     * Check whether bucket exists.
     *
     * @param string $bucket Bucket name
     *
     * @return boolean
     */
    public function doesBucketExist($bucket)
    {
        return $this->aws3_client->doesBucketExist($bucket);
    }

    /**
     * Get region for bucket.
     *
     * @param array $args Params
     *
     * @return boolean|string
     */
    public function getBucketLocation($args)
    {
        try {
            $location = $this->aws3_client->getBucketLocation($args);
            $region   = empty($location['LocationConstraint']) ? '' : $location['LocationConstraint'];
            $region = strip_tags($region);
            return $region;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get bucket details
     *
     * @param array $args Params
     *
     * @return \WP_Media_Folder\Aws\Result
     */
    public function getPublicAccessBlock($args = array())
    {
        $result = $this->aws3_client->getPublicAccessBlock($args);
        return $result;
    }

    /**
     * List buckets.
     *
     * @param array $args Params
     *
     * @return array
     */
    public function listBuckets($args = array())
    {
        return $this->aws3_client->listBuckets($args)->toArray();
    }

    /**
     * Check object exists in bucket.
     *
     * @param string $bucket  Bucket name
     * @param string $key     Object Key
     * @param array  $options Óptions
     *
     * @return boolean
     */
    public function doesObjectExist($bucket, $key, $options = array())
    {
        return $this->aws3_client->doesObjectExist($bucket, $key, $options);
    }

    /**
     * List objects.
     *
     * @param array $args Params list
     *
     * @return array
     */
    public function listObjects($args = array())
    {
        return $this->aws3_client->listObjects($args)->toArray();
    }

    /**
     * Upload file to bucket.
     *
     * @param array $args Params list
     *
     * @return void
     */
    public function uploadObject($args)
    {
        $this->aws3_client->putObject($args);
    }

    /**
     * Delete object from bucket.
     *
     * @param array $args Params list
     *
     * @return void
     */
    public function deleteObject($args)
    {
        $this->aws3_client->deleteObject($args);
    }

    /**
     * Copies object
     *
     * @param array $item Params
     *
     * @return \WP_Media_Folder\Aws\Result
     */
    public function copyObject($item)
    {
        return $this->aws3_client->copyObject($item);
    }

    /**
     * Get object
     *
     * @param array $args Params list
     *
     * @return void
     */
    public function getObject($args)
    {
        $this->aws3_client->getObject($args);
    }
}
