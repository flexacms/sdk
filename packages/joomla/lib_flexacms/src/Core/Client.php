<?php

// Generated from flexacms/php by scripts/sync-php-core.php. Do not edit: change packages/php instead.

namespace FlexaCms\Joomla\Core;

use FlexaCms\Joomla\Core\Cache\ArrayCache;
use FlexaCms\Joomla\Core\Cache\Cache;
use FlexaCms\Joomla\Core\Exceptions\FlexaException;
use FlexaCms\Joomla\Core\Http\CurlTransport;
use FlexaCms\Joomla\Core\Http\Request;
use FlexaCms\Joomla\Core\Http\Response;
use FlexaCms\Joomla\Core\Http\Transport;
use FlexaCms\Joomla\Core\Http\TransportException;
use InvalidArgumentException;

/**
 * Client for one Flexa workspace (the workspace is set by the API key).
 *
 *     $flexa = new Client('https://cms.example.com', 'flx_...');
 *     $posts = $flexa->entity('posts')->published()->list(perPage: 12);
 */
class Client
{
    public const VERSION = '0.3.1';

    private const RETRYABLE_STATUSES = [429, 502, 503, 504];

    private readonly string $baseUrl;

    private readonly Transport $transport;

    private readonly Cache $cache;

    /**
     * @var callable(int): void
     */
    private $sleep;

    /**
     * @param  array{
     *     api_path?: string,
     *     timeout?: int,
     *     retries?: int,
     *     cache_ttl?: int,
     *     locale?: string|null,
     *     fallback_locales?: array<int, string>,
     *     headers?: array<string, string>,
     *     transport?: Transport,
     *     cache?: Cache,
     *     sleep?: callable(int): void,
     * }  $options
     */
    public function __construct(
        string $url,
        private readonly string $apiKey,
        private readonly array $options = [],
    ) {
        if (trim($url) === '') {
            throw new InvalidArgumentException('Flexa: the URL is required.');
        }

        if (trim($apiKey) === '') {
            throw new InvalidArgumentException('Flexa: the API key is required.');
        }

        $this->baseUrl = rtrim($url, '/').'/'.trim($options['api_path'] ?? '/api/v1', '/');
        $this->transport = $options['transport'] ?? new CurlTransport;
        $this->cache = $options['cache'] ?? new ArrayCache;
        $this->sleep = $options['sleep'] ?? static fn (int $milliseconds) => usleep($milliseconds * 1000);
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Entities (content models) the API key can read.
     *
     * @return array<int, array<string, mixed>>
     */
    public function entities(): array
    {
        return $this->get('/entities')['data'] ?? [];
    }

    /**
     * Queries for the entries of one entity.
     */
    public function entity(string $slug): EntityQuery
    {
        return new EntityQuery($this, $slug);
    }

    /**
     * Build an Entry from an API payload, with this client's locales.
     *
     * @param  array<string, mixed>  $data
     */
    public function makeEntry(array $data): Entry
    {
        return Entry::fromArray($data, $this->options['locale'] ?? null, $this->options['fallback_locales'] ?? []);
    }

    /**
     * A cached GET request. A ttl of 0 skips the cache.
     *
     * @param  array<string, scalar>  $query
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = [], ?int $ttl = null): array
    {
        $ttl ??= (int) ($this->options['cache_ttl'] ?? 60);
        ksort($query);

        if ($ttl <= 0) {
            return $this->request('GET', $path, $query);
        }

        $key = 'flexa_'.md5($this->cacheVersion().'|'.$this->apiKey.'|'.$this->url($path, $query));
        $cached = $this->cache->get($key);

        if (is_array($cached)) {
            return $cached;
        }

        $response = $this->request('GET', $path, $query);
        $this->cache->set($key, $response, $ttl);

        return $response;
    }

    /**
     * Send a request to the content API. `$path` is relative to the API base (e.g. `/posts`).
     *
     * @param  array<string, scalar>  $query
     * @param  array<string, mixed>|null  $body
     * @return array<string, mixed>
     *
     * @throws FlexaException
     */
    public function request(string $method, string $path, array $query = [], ?array $body = null): array
    {
        $method = strtoupper($method);
        $attempts = $method === 'GET' ? max(0, (int) ($this->options['retries'] ?? 2)) + 1 : 1;
        $request = new Request(
            $method,
            $this->url($path, $query),
            [
                'Accept' => 'application/json',
                'User-Agent' => 'flexacms-php/'.self::VERSION,
                ...($body === null ? [] : ['Content-Type' => 'application/json']),
                ...($this->options['headers'] ?? []),
                'Authorization' => 'Bearer '.$this->apiKey,
            ],
            $body === null ? null : (string) json_encode($body),
            (int) ($this->options['timeout'] ?? 30),
        );

        for ($attempt = 1; ; $attempt++) {
            try {
                $response = $this->transport->send($request);
            } catch (TransportException $exception) {
                if ($attempt >= $attempts) {
                    // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- never printed raw; callers escape it.
                    throw new FlexaException('Flexa request failed: '.$exception->getMessage(), 0, previous: $exception);
                }

                ($this->sleep)($this->backoff($attempt, null));

                continue;
            }

            if ($response->status >= 200 && $response->status < 300) {
                return $response->json() ?? [];
            }

            if ($attempt < $attempts && in_array($response->status, self::RETRYABLE_STATUSES, true)) {
                ($this->sleep)($this->backoff($attempt, $response->header('Retry-After')));

                continue;
            }

            throw $this->toException($response); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- never printed raw; callers escape it.
        }
    }

    /**
     * Forget every cached response of this client.
     */
    public function flushCache(): void
    {
        $this->cache->set($this->versionKey(), $this->cacheVersion() + 1, 31_536_000);
    }

    /**
     * @param  array<string, scalar>  $query
     */
    private function url(string $path, array $query): string
    {
        return $this->baseUrl.$path.($query === [] ? '' : '?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986));
    }

    private function backoff(int $attempt, ?string $retryAfter): int
    {
        return $retryAfter !== null && is_numeric($retryAfter)
            ? min((int) $retryAfter * 1000, 30_000)
            : 250 * 2 ** ($attempt - 1);
    }

    private function toException(Response $response): FlexaException
    {
        $body = $response->json();
        $message = is_string($body['message'] ?? null) && $body['message'] !== ''
            ? $body['message']
            : "Flexa request failed with status {$response->status}.";

        return new FlexaException($message, $response->status, is_array($body['errors'] ?? null) ? $body['errors'] : [], $body);
    }

    private function versionKey(): string
    {
        return 'flexa_version_'.md5($this->baseUrl.'|'.$this->apiKey);
    }

    private function cacheVersion(): int
    {
        return (int) ($this->cache->get($this->versionKey()) ?? 1);
    }
}
