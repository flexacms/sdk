<?php

namespace FlexaCms\Joomla\Http;

use FlexaCms\Joomla\Core\Http\Request;
use FlexaCms\Joomla\Core\Http\Response;
use FlexaCms\Joomla\Core\Http\Transport;
use FlexaCms\Joomla\Core\Http\TransportException;
use Joomla\CMS\Http\HttpFactory;
use Throwable;

\defined('_JEXEC') or exit;

/**
 * Sends Flexa requests through Joomla's HTTP client, so the site's proxy
 * settings apply.
 */
final class JoomlaTransport implements Transport
{
    public function send(Request $request): Response
    {
        $http = HttpFactory::getHttp();

        try {
            $response = match ($request->method) {
                'GET' => $http->get($request->url, $request->headers, $request->timeout),
                'POST' => $http->post($request->url, (string) $request->body, $request->headers, $request->timeout),
                'PUT' => $http->put($request->url, (string) $request->body, $request->headers, $request->timeout),
                'PATCH' => $http->patch($request->url, (string) $request->body, $request->headers, $request->timeout),
                'DELETE' => $http->delete($request->url, $request->headers, $request->timeout, $request->body),
                default => throw new TransportException("Unsupported method {$request->method}."),
            };
        } catch (TransportException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }

        $headers = [];

        foreach ($response->getHeaders() as $name => $values) {
            $headers[strtolower((string) $name)] = (string) end($values);
        }

        return new Response($response->getStatusCode(), (string) $response->getBody(), $headers);
    }
}
