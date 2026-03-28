<?php

declare(strict_types=1);

namespace Wobqqq\FortifyCsp\Transformers;

use Arr;
use Wobqqq\Fortify\Models\Fortify;
use Wobqqq\FortifyCsp\Dto\CspDto;
use Wobqqq\FortifyCsp\Services\CspService;

final readonly class FortifyTransformer
{
    public static function cspDto(): CspDto
    {
        /** @var bool|int|null $cmsEnabled */
        $cmsEnabled = Fortify::get('csp.cms_enabled');
        $cmsEnabled = (bool)$cmsEnabled;

        $directives = [];

        foreach (CspService::DIRECTIVES as $code => $name) {
            /** @var array<int, mixed>|null $values */
            $values = Fortify::get(sprintf('csp.%s', $code));
            $values = !is_array($values) ? [] : $values;
            $values = array_map(function ($values) {
                /** @var array<string, string|null> $values */
                /** @var string|null $value */
                $value = Arr::get($values, 'v');
                $value = (string)$value;
                $value = trim($value);
                $value = empty($value) ? null : $value;

                return $value;
            }, $values);
            $values = array_filter($values);
            /** @var array<int, string> $values */
            $values = array_unique($values);

            if (empty($values)) {
                continue;
            }

            $directives[] = sprintf('%s %s', $name, implode(' ', $values));
        }

        $cmsHeaderValue = !empty($directives) ? implode(';', $directives) : null;

        return new CspDto(
            $cmsEnabled,
            $cmsHeaderValue,
        );
    }
}
