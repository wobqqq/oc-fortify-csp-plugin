<?php

declare(strict_types=1);

namespace Wobqqq\FortifyCsp\Transformers;

use Wobqqq\Fortify\Models\Fortify;
use Wobqqq\FortifyCsp\Dto\CspDto;
use Wobqqq\FortifyCsp\Services\CspService;

final readonly class FortifyTransformer
{
    public static function cspDto(): CspDto
    {
        $directives = [];

        foreach (CspService::DIRECTIVES as $code => $name) {
            $rows = Fortify::get(sprintf('csp.%s', $code));
            $values = [];

            foreach (is_array($rows) ? $rows : [] as $row) {
                $value = is_array($row) && is_scalar($row['v'] ?? null) ? trim((string)$row['v']) : '';

                if (CspService::isValidSource($value)) {
                    $values[] = $value;
                }
            }

            $values = array_values(array_unique($values));

            if ($values !== []) {
                $directives[] = sprintf('%s %s', $name, implode(' ', $values));
            }
        }

        return new CspDto(
            (bool)Fortify::get('csp.cms_enabled'),
            $directives !== [] ? implode('; ', $directives) : null,
        );
    }
}
