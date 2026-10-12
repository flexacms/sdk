<?php

namespace FlexaCms\Client\Tests;

use FlexaCms\Client\Http\Request;
use FlexaCms\Client\Http\Response;
use FlexaCms\Client\Http\Transport;
use FlexaCms\Client\Http\TransportException;

/**
 * Returns queued responses and records the requests it receives.
 */
final class FakeTransport implements Transport
{
    /**
     * @var array<int, Request>
     */
    public array $requests = [];

    /**
     * @param  array<int, Response|TransportException>  $responses
     */
    public function __construct(private array $responses = []) {}

    public static function json(mixed $body, int $status = 200, array $headers = []): Response
    {
        return new Response($status, (string) json_encode($body), $headers);
    }

    public function send(Request $request): Response
    {
        $this->requests[] = $request;
        $next = array_shift($this->responses) ?? throw new \LogicException('Unexpected request to '.$request->url);

        if ($next instanceof TransportException) {
            throw $next;
        }

        return $next;
    }
}
