<?php

namespace FlexaCms\Laravel\Facades;

use FlexaCms\Laravel\EntityQuery;
use FlexaCms\Laravel\Flexa as FlexaClient;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array<int, array<string, mixed>> entities()
 * @method static EntityQuery entity(string $slug)
 * @method static mixed request(string $method, string $path, array<string, mixed> $query = [], array<string, mixed>|null $body = null)
 * @method static void flushCache()
 *
 * @see FlexaClient
 */
class Flexa extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FlexaClient::class;
    }
}
