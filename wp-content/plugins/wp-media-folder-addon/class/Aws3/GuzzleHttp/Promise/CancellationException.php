<?php

namespace WP_Media_Folder\GuzzleHttp\Promise;

/**
 * Exception that is set as the reason for a promise that has been cancelled.
 */
class CancellationException extends \WP_Media_Folder\GuzzleHttp\Promise\RejectionException
{
}
