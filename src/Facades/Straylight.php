<?php

declare(strict_types=1);

namespace Straylight\Facades;

use Illuminate\Support\Facades\Facade;
use Straylight\Services\StraylightService;

final class Straylight extends Facade
{
    #[\Override]
    protected static function getFacadeAccessor(): string
    {
        return StraylightService::class;
    }
}
