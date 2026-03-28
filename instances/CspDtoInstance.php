<?php

declare(strict_types=1);

namespace Wobqqq\FortifyCsp\Instances;

use October\Rain\Support\Traits\Singleton;
use Wobqqq\FortifyCsp\Cache\CspDtoCache;
use Wobqqq\FortifyCsp\Dto\CspDto;

final class CspDtoInstance
{
    use Singleton;

    private ?CspDto $cspDto = null;

    public function get(): CspDto
    {
        if ($this->cspDto instanceof CspDto) {
            return $this->cspDto;
        }

        /** @var CspDtoCache $cspDtoCache */
        $cspDtoCache = app(CspDtoCache::class);

        return $this->cspDto = $cspDtoCache->get();
    }
}
