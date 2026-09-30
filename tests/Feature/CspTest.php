<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use System\Models\SettingModel;
use Wobqqq\Fortify\Models\Fortify;
use Wobqqq\FortifyCsp\Cache\CspDtoCache;
use Wobqqq\FortifyCsp\Http\Middlewares\CspMiddleware;
use Wobqqq\FortifyCsp\Transformers\FortifyTransformer;

const DEFAULT_POLICY = "default-src 'self'; script-src 'self' 'unsafe-inline' https:; style-src 'self' 'unsafe-inline' https:; "
    . "img-src 'self' data: blob: https:; font-src 'self' data: https:; connect-src 'self' https:; media-src 'self' blob: https:; "
    . "frame-src 'self' https:; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'";

function cspResponse(mixed $response): mixed
{
    return app(CspMiddleware::class)->handle(Request::create('/'), static fn (): mixed => $response);
}

/**
 * @param array<int, string> $values
 *
 * @return array<int, array{v: string}>
 */
function cspValues(array $values): array
{
    return array_map(static fn (string $value): array => ['v' => $value], $values);
}

it('builds the default policy for a new site', function (): void {
    SettingModel::clearInternalCache();

    $defaults = Fortify::instance()->csp;

    expect($defaults)->toHaveKey('cms_enabled', false);

    configureCsp(is_array($defaults) ? $defaults : []);

    expect(FortifyTransformer::cspDto()->cmsHeaderValue)->toBe(DEFAULT_POLICY);
});

it('skips the empty directives and repeats no value', function (): void {
    configureCsp([
        'cms_default_src' => cspValues(["'self'", "'self'", ' ']),
        'cms_img_src' => [],
        'cms_object_src' => 'not a table',
        'cms_frame_ancestors' => cspValues(["'none'"]),
    ]);

    expect(FortifyTransformer::cspDto()->cmsHeaderValue)->toBe("default-src 'self'; frame-ancestors 'none'");
});

it('has no policy when no directive has a value', function (): void {
    configureCsp([]);

    expect(FortifyTransformer::cspDto()->cmsHeaderValue)->toBeNull();
});

it('drops a stored value that would add a directive or break the header', function (string $value): void {
    configureCsp(['cms_script_src' => cspValues(["'self'", $value])]);

    expect(FortifyTransformer::cspDto()->cmsHeaderValue)->toBe("script-src 'self'");
})->with([
    "'self'; script-src *",
    'https://cdn.example.com, *',
    "https://a.example\r\nSet-Cookie: a=b",
    'https://a.example b.example',
    "https://a.example\x00b",
]);

it('refuses a value that would add a directive or break the header when the settings are saved', function (string $value, bool $passes): void {
    $form = new Backend\Widgets\Form(new System\Controllers\Settings(), Fortify::instance());
    Illuminate\Support\Facades\Event::dispatch('backend.form.extendFields', [$form]);
    $model = $form->model;

    if (!$model instanceof Fortify) {
        throw new UnexpectedValueException('The form is not the Fortify settings.');
    }

    $validator = Validator::make(
        ['config' => ['password_policy_min_length' => 12, 'session_lifetime' => 30], 'csp' => ['cms_script_src' => cspValues([$value])]],
        $model->rules,
    );

    expect($validator->passes())->toBe($passes);
})->with([
    ["'self'", true],
    ["'nonce-r4nd0m'", true],
    ["'sha256-abc+/def='", true],
    ['https://cdn.example.com/path', true],
    ['*.example.com', true],
    ['data:', true],
    ["'self'; script-src *", false],
    ['https://cdn.example.com, *', false],
    ["https://a.example\r\nX-Injected: 1", false],
    ['https://a.example b.example', false],
    [str_repeat('a', 101), false],
]);

it('sends the policy while enabled', function (): void {
    configureCsp(['cms_default_src' => cspValues(["'self'"])]);

    $response = cspResponse(new Response('page'));

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response instanceof Response ? $response->headers->get('Content-Security-Policy') : null)->toBe("default-src 'self'");
});

it('replaces a policy set before it and covers files and streams', function (): void {
    configureCsp(['cms_default_src' => cspValues(["'self'"])]);

    $file = new BinaryFileResponse(__FILE__);
    $stream = new StreamedResponse(static function (): void {
    });
    $page = new Response('page', 200, ['Content-Security-Policy' => 'default-src *']);

    foreach ([$file, $stream, $page] as $response) {
        cspResponse($response);

        expect($response->headers->get('Content-Security-Policy'))->toBe("default-src 'self'");
    }
});

it('sends nothing while disabled or without a directive', function (bool $enabled, bool $withDirective): void {
    configureCsp(['cms_enabled' => $enabled, 'cms_default_src' => $withDirective ? cspValues(["'self'"]) : []]);

    $response = new Response('page');
    cspResponse($response);

    expect($response->headers->has('Content-Security-Policy'))->toBeFalse();
})->with([
    [false, true],
    [true, false],
]);

it('leaves a response that is not an HTTP response alone', function (): void {
    configureCsp(['cms_default_src' => cspValues(["'self'"])]);

    expect(cspResponse('plain'))->toBe('plain');
});

it('adds its middleware to the site only, and only while enabled', function (): void {
    expect(Config::get('cms.middleware_group'))->toBe('web');

    configureCsp(['cms_default_src' => cspValues(["'self'"])]);

    expect(Config::get('cms.middleware_group'))->toBe(['web', CspMiddleware::ALIAS])
        ->and(Config::get('backend.middleware_group'))->toBe('web');
});

it('applies saved settings at once, even to a settings instance created before the module', function (): void {
    configureCsp(['cms_default_src' => cspValues(["'self'"])]);

    $cache = app(CspDtoCache::class);
    expect($cache->get()->cmsHeaderValue)->toBe("default-src 'self'");

    Fortify::set('csp', ['cms_enabled' => true, 'cms_default_src' => cspValues(["'none'"])]);

    expect($cache->get()->cmsHeaderValue)->toBe("default-src 'none'");

    Fortify::instance()->delete();

    expect($cache->get()->cmsEnabled)->toBeFalse();
});

it('rebuilds a cached policy the previous version wrote in another shape', function (): void {
    configureCsp(['cms_default_src' => cspValues(["'self'"])]);

    Illuminate\Support\Facades\Cache::shouldReceive('remember')->once()->andThrow(new TypeError('Cannot assign'));
    Illuminate\Support\Facades\Cache::shouldReceive('forget')->once();

    expect(app(CspDtoCache::class)->get()->cmsHeaderValue)->toBe("default-src 'self'");
});

it('turns itself off from the console and keeps the directives', function (): void {
    configureCsp(['cms_default_src' => cspValues(["'self'"])]);

    expect(Artisan::call('wobqqq.fortify:csp:disable'))->toBe(0)
        ->and(Artisan::output())->toContain('CSP disabled.')
        ->and(Fortify::get('csp.cms_enabled'))->toBeFalse()
        ->and(Fortify::get('csp.cms_default_src'))->toBe(cspValues(["'self'"]));
});
