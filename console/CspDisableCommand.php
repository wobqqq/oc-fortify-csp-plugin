<?php

declare(strict_types=1);

namespace Wobqqq\FortifyCsp\Console;

use Illuminate\Console\Command;
use Wobqqq\FortifyCsp\Services\CspService;

final class CspDisableCommand extends Command
{
    /** @var string */
    protected $name = 'wobqqq.fortify:csp:disable';

    /** @var string */
    protected $description = 'Disable CSP.';

    public function handle(CspService $cspService): void
    {
        $cspService->disable();

        $this->info('CSP disabled.');
    }
}
