<?php

namespace FlexaCms\Laravel\Exceptions;

/**
 * Thrown by findOrFail() when the entry does not exist. Rendered as a 404.
 */
class EntryNotFoundException extends FlexaException
{
    public static function for(string $entity, int|string $idOrSlug): self
    {
        return new self("Flexa entry [{$idOrSlug}] of [{$entity}] was not found.", 404);
    }

    public function render(): never
    {
        abort(404);
    }
}
