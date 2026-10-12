<?php

namespace FlexaCms\Laravel;

use FlexaCms\Laravel\Exceptions\FlexaException;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Throwable;

/**
 * Client for one Flexa workspace (the workspace is set by the API key).
 */
class Flexa
{
    /**
     * @param  array{url?: string|null, api_key?: string|null, api_path?: string, timeout?: int, retries?: int, cache?: array{store?: string|null, ttl?: int}}  $config
     */
    public function __construct(protected array $config) {}

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
     * Fluent queries for the entries of one entity.
     */
    public function entity(string $slug): EntityQuery
    {
        return new EntityQuery($this, $slug);
    }

    /**
     * A cached GET request. Pass a ttl of 0 to skip the cache.
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = [], ?int $ttl = null): array
    {
        $ttl ??= (int) ($this->config['cache']['ttl'] ?? 60);

        if ($ttl <= 0) {
            return $this->request('GET', $path, $query);
        }

        ksort($query);

        return $this->cache()->remember(
            'flexa:'.$this->cacheVersion().':'.sha1($this->baseUrl().$path.'?'.http_build_query($query)),
            $ttl,
            fn (): array => $this->request('GET', $path, $query),
        );
    }

    /**
     * Send a request to the content API. `$path` is relative to the API base (e.g. `/posts`).
     *
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>|null  $body
     * @return array<string, mixed>
     *
     * @throws FlexaException
     */
    public function request(string $method, string $path, array $query = [], ?array $body = null): array
    {
        $method = strtoupper($method);
        $url = $this->baseUrl().$path.($query === [] ? '' : '?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986));

        try {
            $response = $this->http($method === 'GET')->send($method, $url, $body === null ? [] : ['json' => $body]);
        } catch (ConnectionException $exception) {
            throw new FlexaException('Flexa request failed: '.$exception->getMessage(), 0, previous: $exception);
        } catch (RequestException $exception) {
            $response = $exception->response;
        }

        if ($response->failed()) {
            throw $this->toException($response);
        }

        return $response->json() ?? [];
    }

    /**
     * Forget every cached Flexa response.
     */
    public function flushCache(): void
    {
        $this->cache()->forever('flexa:version', $this->cacheVersion() + 1);
    }

    protected function http(bool $retryable): PendingRequest
    {
        $request = Http::acceptJson()
            ->withToken($this->apiKey())
            ->timeout((int) ($this->config['timeout'] ?? 30));

        $retries = (int) ($this->config['retries'] ?? 2);

        if (! $retryable || $retries <= 0) {
            return $request;
        }

        return $request->retry(
            $retries + 1,
            fn (int $attempt, Throwable $exception): int => $this->backoff($attempt, $exception),
            fn (Throwable $exception): bool => $exception instanceof ConnectionException
                || ($exception instanceof RequestException && in_array($exception->response->status(), [429, 502, 503, 504], true)),
            throw: false,
        );
    }

    protected function backoff(int $attempt, Throwable $exception): int
    {
        $retryAfter = $exception instanceof RequestException ? $exception->response->header('Retry-After') : '';

        return is_numeric($retryAfter)
            ? min((int) $retryAfter * 1000, 30_000)
            : 250 * 2 ** ($attempt - 1);
    }

    protected function toException(Response $response): FlexaException
    {
        $body = $response->json();
        $message = is_array($body) && is_string($body['message'] ?? null) && $body['message'] !== ''
            ? $body['message']
            : "Flexa request failed with status {$response->status()}.";

        return new FlexaException(
            $message,
            $response->status(),
            is_array($body) && is_array($body['errors'] ?? null) ? $body['errors'] : [],
            is_array($body) ? $body : null,
        );
    }

    protected function baseUrl(): string
    {
        $url = (string) ($this->config['url'] ?? '');

        if ($url === '') {
            throw new InvalidArgumentException('Flexa: set FLEXA_URL (config flexa.url).');
        }

        return rtrim($url, '/').'/'.trim((string) ($this->config['api_path'] ?? '/api/v1'), '/');
    }

    protected function apiKey(): string
    {
        $apiKey = (string) ($this->config['api_key'] ?? '');

        if ($apiKey === '') {
            throw new InvalidArgumentException('Flexa: set FLEXA_API_KEY (config flexa.api_key).');
        }

        return $apiKey;
    }

    protected function cache(): Repository
    {
        return Cache::store($this->config['cache']['store'] ?? null);
    }

    protected function cacheVersion(): int
    {
        return (int) $this->cache()->get('flexa:version', 1);
    }
}
