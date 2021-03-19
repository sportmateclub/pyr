<?php

namespace Beat\Pyr\Http;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\IpUtils;

class IpWhitelistMiddleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        $ipWhitelist = config('pyr.allowed_ips');
        if (empty($ipWhitelist)) {
            return $next($request);
        }

        abort_unless(IpUtils::checkIp($request->ip(), $ipWhitelist), 404);

        return $next($request);
    }
}
