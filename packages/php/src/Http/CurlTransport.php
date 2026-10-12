<?php

namespace FlexaCms\Client\Http;

/**
 * Default transport, built on ext-curl.
 */
final class CurlTransport implements Transport
{
    public function send(Request $request): Response
    {
        if (! function_exists('curl_init')) {
            throw new TransportException('The curl extension is required by the default Flexa transport.');
        }

        $headers = [];
        $handle = curl_init($request->url);

        curl_setopt_array($handle, [
            CURLOPT_CUSTOMREQUEST => $request->method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $request->timeout,
            CURLOPT_CONNECTTIMEOUT => min(10, $request->timeout),
            CURLOPT_HTTPHEADER => array_map(fn (string $name, string $value): string => "{$name}: {$value}", array_keys($request->headers), $request->headers),
            CURLOPT_HEADERFUNCTION => function ($handle, string $line) use (&$headers): int {
                if (str_contains($line, ':')) {
                    [$name, $value] = explode(':', $line, 2);
                    $headers[strtolower(trim($name))] = trim($value);
                }

                return strlen($line);
            },
        ]);

        if ($request->body !== null) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $request->body);
        }

        $body = curl_exec($handle);

        if ($body === false) {
            $error = curl_error($handle);

            throw new TransportException($error !== '' ? $error : 'The request failed.');
        }

        return new Response((int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), (string) $body, $headers);
    }
}
