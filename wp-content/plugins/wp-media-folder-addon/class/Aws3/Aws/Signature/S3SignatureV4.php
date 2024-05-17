<?php

namespace WP_Media_Folder\Aws\Signature;

use WP_Media_Folder\Aws\Credentials\CredentialsInterface;
use WP_Media_Folder\Psr\Http\Message\RequestInterface;
/**
 * Amazon S3 signature version 4 support.
 */
class S3SignatureV4 extends \WP_Media_Folder\Aws\Signature\SignatureV4
{
    /**
     * Always add a x-amz-content-sha-256 for data integrity.
     */
    public function signRequest(\WP_Media_Folder\Psr\Http\Message\RequestInterface $request, \WP_Media_Folder\Aws\Credentials\CredentialsInterface $credentials)
    {
        if (!$request->hasHeader('x-amz-content-sha256')) {
            $request = $request->withHeader('X-Amz-Content-Sha256', $this->getPayload($request));
        }
        return parent::signRequest($request, $credentials);
    }
    /**
     * Always add a x-amz-content-sha-256 for data integrity.
     */
    public function presign(\WP_Media_Folder\Psr\Http\Message\RequestInterface $request, \WP_Media_Folder\Aws\Credentials\CredentialsInterface $credentials, $expires, array $options = [])
    {
        if (!$request->hasHeader('x-amz-content-sha256')) {
            $request = $request->withHeader('X-Amz-Content-Sha256', $this->getPresignedPayload($request));
        }
        return parent::presign($request, $credentials, $expires, $options);
    }
    /**
     * Override used to allow pre-signed URLs to be created for an
     * in-determinate request payload.
     */
    protected function getPresignedPayload(\WP_Media_Folder\Psr\Http\Message\RequestInterface $request)
    {
        return \WP_Media_Folder\Aws\Signature\SignatureV4::UNSIGNED_PAYLOAD;
    }
    /**
     * Amazon S3 does not double-encode the path component in the canonical request
     */
    protected function createCanonicalizedPath($path)
    {
        // Only remove one slash in case of keys that have a preceding slash
        if (substr($path, 0, 1) === '/') {
            $path = substr($path, 1);
        }
        return '/' . $path;
    }
}
