<?php

// The module guards against direct access; tests run it as WordPress would.
define('ABSPATH', __DIR__.'/');

use FlexaCms\Client\Client;
use FlexaCms\Client\Http\Request;
use FlexaCms\Client\Http\Response;
use FlexaCms\Client\Http\Transport;

/**
 * The client returned by the platform's flexa() function in these tests.
 */
function flexa(): ?Client
{
    return $GLOBALS['flexa_client'] ?? null;
}

/**
 * A Flexa client answering from canned responses keyed by path.
 *
 * @param  array<string, array<string, mixed>>  $responses
 */
function fakeFlexa(array $responses): Client
{
    $transport = new class($responses) implements Transport
    {
        public array $paths = [];

        public function __construct(private array $responses) {}

        public function send(Request $request): Response
        {
            $path = substr($request->url, strlen('https://cms.test/api/v1'));
            $this->paths[] = $path;
            $key = strtok($path, '?');

            return isset($this->responses[$key])
                ? new Response(200, (string) json_encode($this->responses[$key]))
                : new Response(404, '{"message":"Not found."}');
        }
    };

    $GLOBALS['flexa_transport'] = $transport;

    return $GLOBALS['flexa_client'] = new Client('https://cms.test', 'flx_test', ['transport' => $transport, 'cache_ttl' => 0, 'retries' => 0, 'locale' => 'es', 'fallback_locales' => ['en']]);
}

/**
 * @return array<int, array<string, mixed>>
 */
function workspaceSchemas(): array
{
    return json_decode((string) file_get_contents(__DIR__.'/../../cli/test/fixtures/schemas.json'), true);
}
