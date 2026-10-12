<?php

// Generated from flexacms/php by scripts/sync-php-core.php. Do not edit: change packages/php instead.

namespace FlexaCms\Joomla\Core\Http;

/**
 * Sends HTTP requests. Implement it to use your platform's HTTP client
 * (e.g. wp_remote_request() in WordPress, HttpFactory in Joomla).
 */
interface Transport
{
    /**
     * @throws TransportException when the request could not be completed.
     */
    public function send(Request $request): Response;
}
