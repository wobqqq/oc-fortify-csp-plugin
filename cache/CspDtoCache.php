<?php

declare(strict_types=1);

namespace Wobqqq\FortifyCsp\Cache;

use Illuminate\Support\Facades\Cache;
use Throwable;
use Wobqqq\Fortify\Cache\BasicCache;
use Wobqqq\FortifyCsp\Dto\CspDto;
use Wobqqq\FortifyCsp\Transformers\FortifyTransformer;

final class CspDtoCache extends BasicCache
{
    public function get(): CspDto
    {
        $cacheKey = $this->cacheKey();

        try {
            $cspDto = Cache::remember($cacheKey, self::TTL, FortifyTransformer::cspDto(...));
        } catch (Throwable) {
            Cache::forget($cacheKey);
            $cspDto = null;
        }

        return $cspDto instanceof CspDto ? $cspDto : FortifyTransformer::cspDto();
    }

    public function clear(): void
    {
        Cache::forget($this->cacheKey());
    }
}
