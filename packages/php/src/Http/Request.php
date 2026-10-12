<?php

namespace FlexaCms\Client\Http;

/**
 * An HTTP request to the Flexa API, handed to a Transport.
 */
final class Request
{
    /**
     * @param  array<string, string>  $headers
     */
    public function __construct(
        public readonly string $method,
        public readonly string $url,
        public readonly array $headers,
        public readonly ?string $body,
        public readonly int $timeout,
    ) {}
}
