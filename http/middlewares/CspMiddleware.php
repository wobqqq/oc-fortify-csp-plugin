<?php

declare(strict_types=1);

namespace Wobqqq\FortifyCsp\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Wobqqq\FortifyCsp\Instances\CspDtoInstance;

final class CspMiddleware
{
    public const ALIAS = 'fortify_cms_csp';

    /**
     * @param Closure(Request): mixed $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        $cspDto = CspDtoInstance::instance()->get();

        if (!$response instanceof Response || !$cspDto->cmsEnabled || $cspDto->cmsHeaderValue === null) {
            return $response;
        }

        $response->headers->set('Content-Security-Policy', $cspDto->cmsHeaderValue);

        return $response;
    }
}
