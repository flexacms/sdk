<?php

namespace FlexaCms\Client\Http;

/**
 * An HTTP response returned by a Transport.
 */
final class Response
{
    /**
     * @param  array<string, string>  $headers  Header names in lower case.
     */
    public function __construct(
        public readonly int $status,
        public readonly string $body,
        public readonly array $headers = [],
    ) {}

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function json(): ?array
    {
        $decoded = json_decode($this->body, true);

        return is_array($decoded) ? $decoded : null;
    }
}
