<?php

namespace FlexaCms\WordPress\Http;

use FlexaCms\WordPress\Core\Http\Request;
use FlexaCms\WordPress\Core\Http\Response;
use FlexaCms\WordPress\Core\Http\Transport;
use FlexaCms\WordPress\Core\Http\TransportException;

/**
 * Sends Flexa requests through the WordPress HTTP API, so proxies, SSL
 * settings and the `http_request_args` filter apply.
 */
final class WordPressTransport implements Transport
{
    public function send(Request $request): Response
    {
        $response = wp_remote_request($request->url, [
            'method' => $request->method,
            'headers' => $request->headers,
            'body' => $request->body,
            'timeout' => $request->timeout,
            'redirection' => 3,
        ]);

        if (is_wp_error($response)) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- never printed raw; the plugin escapes it on display.
            throw new TransportException($response->get_error_message());
        }

        $headers = [];

        foreach (wp_remote_retrieve_headers($response) as $name => $value) {
            $headers[strtolower((string) $name)] = is_array($value) ? (string) end($value) : (string) $value;
        }

        return new Response((int) wp_remote_retrieve_response_code($response), (string) wp_remote_retrieve_body($response), $headers);
    }
}
