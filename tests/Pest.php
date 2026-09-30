<?php

declare(strict_types=1);

use Wobqqq\Fortify\Models\Fortify;
use Wobqqq\FortifyCsp\Tests\TestCase;

pest()->extend(TestCase::class)->in('Unit', 'Feature');

/**
 * @param array<string, mixed> $settings
 */
function configureCsp(array $settings): void
{
    Fortify::set('csp', array_merge(['cms_enabled' => true], $settings));

    TestCase::bootPlugins();
}
