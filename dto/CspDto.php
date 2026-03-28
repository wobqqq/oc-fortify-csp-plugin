<?php

declare(strict_types=1);

namespace Wobqqq\FortifyCsp\Dto;

final readonly class CspDto
{
    public function __construct(
        public bool $cmsEnabled,
        public ?string $cmsHeaderValue = null,
    ) {
    }
}
