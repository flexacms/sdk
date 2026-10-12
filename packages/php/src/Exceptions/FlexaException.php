<?php

namespace FlexaCms\Client\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Thrown for every failed request to the Flexa content API.
 */
class FlexaException extends RuntimeException
{
    /**
     * @param  int  $status  HTTP status, or 0 when the request never reached Flexa.
     * @param  array<string, array<int, string>>  $errors  Validation errors keyed by field (422).
     * @param  array<string, mixed>|null  $body  Decoded response body.
     */
    public function __construct(
        string $message,
        public readonly int $status = 0,
        public readonly array $errors = [],
        public readonly ?array $body = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
    }

    public function isNotFound(): bool
    {
        return $this->status === 404;
    }

    public function isValidationError(): bool
    {
        return $this->status === 422;
    }
}
