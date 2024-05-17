<?php

namespace WP_Media_Folder\Aws\Signature;

use WP_Media_Folder\Aws\Credentials\CredentialsInterface;
use WP_Media_Folder\Psr\Http\Message\RequestInterface;
/**
 * Provides anonymous client access (does not sign requests).
 */
class AnonymousSignature implements \WP_Media_Folder\Aws\Signature\SignatureInterface
{
    public function signRequest(\WP_Media_Folder\Psr\Http\Message\RequestInterface $request, \WP_Media_Folder\Aws\Credentials\CredentialsInterface $credentials)
    {
        return $request;
    }
    public function presign(\WP_Media_Folder\Psr\Http\Message\RequestInterface $request, \WP_Media_Folder\Aws\Credentials\CredentialsInterface $credentials, $expires)
    {
        return $request;
    }
}
