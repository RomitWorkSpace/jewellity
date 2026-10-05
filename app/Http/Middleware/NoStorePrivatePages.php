<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sign-in, account, wishlist, bag, checkout and order pages are personal and change with the session.
 * `no-store` stops the browser keeping a copy, so the Back button always asks the server again:
 * a signed-in customer is not shown the login page, a signed-out one is not shown their account,
 * and the bag is never stale.
 */
class NoStorePrivatePages
{
    private const PRIVATE_PATHS = ['account', 'account/*', 'wishlist', 'cart', 'cart/*', 'checkout', 'checkout/*', 'order/*'];

    public function handle(Request $request, Closure $next): Response
    {
        return self::apply($request, $next($request));
    }

    /** Also used for responses built by the exception handler (e.g. a guest redirected to sign in). */
    public static function apply(Request $request, Response $response): Response
    {
        if ($request->isMethodSafe() && $request->is(...self::PRIVATE_PATHS)) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }
}
