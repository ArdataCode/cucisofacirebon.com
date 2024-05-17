<?php

namespace WP_Media_Folder\Aws\Api\ErrorParser;

use WP_Media_Folder\Psr\Http\Message\ResponseInterface;
/**
 * Parsers JSON-RPC errors.
 */
class JsonRpcErrorParser
{
    use JsonParserTrait;
    public function __invoke(\WP_Media_Folder\Psr\Http\Message\ResponseInterface $response)
    {
        $data = $this->genericHandler($response);
        // Make the casing consistent across services.
        if ($data['parsed']) {
            $data['parsed'] = array_change_key_case($data['parsed']);
        }
        if (isset($data['parsed']['__type'])) {
            $parts = explode('#', $data['parsed']['__type']);
            $data['code'] = isset($parts[1]) ? $parts[1] : $parts[0];
            $data['message'] = isset($data['parsed']['message']) ? $data['parsed']['message'] : null;
        }
        return $data;
    }
}
