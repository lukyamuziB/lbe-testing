<?php

namespace App\Http\Middleware;

use Closure;
use App\Exceptions\AccessDeniedException;

class AdminMiddleware
{
    /**
     * Intercepts an incoming request and checks
     * if the request is made by an Admin User.
     *
     * @param  Request $request - request object
     * @param  Closure $next - callback method which continues a request
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        if (!in_array("LENKEN_ADMIN", $request->user()->roles)) {
            throw new AccessDeniedException("You do not have permission to perform this action.");
        }

        return $next($request);
    }
}
