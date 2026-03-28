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
     * @param Request $request
     * @param Closure $next
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response|mixed
     */
    public function handle(Request $request, Closure $next)
    {
        /** @var Response $response */
        $response = $next($request);

        $cspDto = CspDtoInstance::instance()->get();

        if (!$cspDto->cmsEnabled || empty($cspDto->cmsHeaderValue)) {
            return $response;
        }

        $response->headers->set('Content-Security-Policy', $cspDto->cmsHeaderValue);

        return $response;
    }
}
