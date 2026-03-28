<?php

declare(strict_types=1);

namespace Wobqqq\FortifyCsp;

use Event;
use System\Classes\PluginBase;
use Wobqqq\FortifyCsp\Console\CspDisableCommand;
use Wobqqq\FortifyCsp\Listeners\FortifyListener;
use Wobqqq\FortifyCsp\Services\CspService;

final class Plugin extends PluginBase
{
    /** @var array<int, string> */
    public $require = ['Wobqqq.Fortify'];

    public function register(): void
    {
        $this->registerConsoleCommand('wobqqq.fortify:csp:disable', CspDisableCommand::class);
    }

    public function boot(): void
    {
        $this->registerEvents();
        $this->runService();
    }

    private function registerEvents(): void
    {
        Event::subscribe(FortifyListener::class);
    }

    private function runService(): void
    {
        /** @var CspService $cspService */
        $cspService = app(CspService::class);
        $cspService->addMiddleware();
    }
}
