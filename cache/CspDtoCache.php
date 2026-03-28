<?php

declare(strict_types=1);

namespace Wobqqq\FortifyCsp\Cache;

use Illuminate\Support\Facades\Cache;
use Wobqqq\Fortify\Cache\BasicCache;
use Wobqqq\FortifyCsp\Dto\CspDto;
use Wobqqq\FortifyCsp\Transformers\FortifyTransformer;

final class CspDtoCache extends BasicCache
{
    public function get(): CspDto
    {
        $cacheKey = $this->cacheKey();

        /** @var CspDto $cspDto */
        $cspDto = Cache::remember($cacheKey, self::TTL, function () {
            return FortifyTransformer::cspDto();
        });

        return $cspDto;
    }

    public function clear(): void
    {
        Cache::forget($this->cacheKey());
    }
}
