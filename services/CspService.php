<?php

declare(strict_types=1);

namespace Wobqqq\FortifyCsp\Services;

use App;
use Config;
use Illuminate\Support\Collection;
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

    /**
     * A source expression: no separator (";", ","), whitespace or control character that
     * would add a directive or break the header.
     */
    public const SOURCE_PATTERN = "/^[A-Za-z0-9\\-._~:\\/?#\\[\\]@!$&'()*+=%]{1,100}$/";

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

    public static function isValidSource(string $value): bool
    {
        return preg_match(self::SOURCE_PATTERN, $value) === 1;
    }

    public function disable(): void
    {
        $csp = Fortify::get('csp');

        if ($csp instanceof Collection) {
            $csp = $csp->toArray();
        }

        $csp = is_array($csp) ? $csp : [];

        $csp['cms_enabled'] = false;

        Fortify::set('csp', $csp);
    }

    private function overrideConfig(): void
    {
        $middleware = Config::get('cms.middleware_group', []);
        $middleware = is_string($middleware) ? [$middleware] : (is_array($middleware) ? $middleware : []);
        $middleware = array_filter($middleware, static fn (mixed $name): bool => is_string($name) && $name !== '');

        $middleware[] = CspMiddleware::ALIAS;

        Config::set('cms.middleware_group', array_values(array_unique($middleware)));
    }
}
