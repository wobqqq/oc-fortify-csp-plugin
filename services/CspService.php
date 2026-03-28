<?php

declare(strict_types=1);

namespace Wobqqq\FortifyCsp\Services;

use App;
use Config;
use October\Rain\Router\CoreRouter;
use Wobqqq\Fortify\Models\Fortify;
use Wobqqq\FortifyCsp\Http\Middlewares\CspMiddleware;
use Wobqqq\FortifyCsp\Instances\CspDtoInstance;

final class CspService
{
    public const DIRECTIVES = [
        'cms_default_src' => 'default-src',
        'cms_script_src' => 'script-src',
        'cms_style_src' => 'style-src',
        'cms_img_src' => 'img-src',
        'cms_font_src' => 'font-src',
        'cms_connect_src' => 'connect-src',
        'cms_media_src' => 'media-src',
        'cms_frame_src' => 'frame-src',
        'cms_object_src' => 'object-src',
        'cms_base_uri' => 'base-uri',
        'cms_form_action' => 'form-action',
        'cms_frame_ancestors' => 'frame-ancestors',
    ];

    private static bool $addMiddleware = false;

    public function addMiddleware(): void
    {
        if (self::$addMiddleware) {
            return;
        }

        self::$addMiddleware = true;

        $csp = CspDtoInstance::instance()->get();

        if (!$csp->cmsEnabled) {
            return;
        }

        /** @var CoreRouter $coreRoute */
        $coreRoute = App::make('router');
        $coreRoute->aliasMiddleware(CspMiddleware::ALIAS, CspMiddleware::class);

        $this->overrideConfig();
    }

    public function disable(): void
    {
        /** @var array<string, mixed>|\Illuminate\Support\Collection<int, mixed> $csp */
        $csp = Fortify::get('csp');

        if ($csp instanceof \Illuminate\Support\Collection) {
            $csp = $csp->toArray();
        }

        $csp = !is_array($csp) ? [] : $csp;

        $csp['cms_enabled'] = false;

        Fortify::set('csp', $csp);
    }

    private function overrideConfig(): void
    {
        /** @var string|null|array<int, string> $middleware */
        $middleware = Config::get('cms.middleware_group', []);

        if (is_string($middleware)) {
            $middleware = [$middleware];
        }

        if (empty($middleware)) {
            $middleware = [];
        }

        $middleware[] = CspMiddleware::ALIAS;
        /** @var array<int, string> $middleware */
        $middleware = array_unique($middleware);
        $middleware = array_filter($middleware);

        Config::set('cms.middleware_group', $middleware);
    }
}
