<?php

namespace AmjadIqbal\Kiln\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Wraps Laravel's own signature validation and additionally refuses any
 * request whose signature has no `expires` parameter — i.e. one generated
 * with URL::signedRoute() instead of URL::temporarySignedRoute().
 *
 * URL::signedRoute() alone produces a *permanent* credential: it stays
 * valid until APP_KEY is rotated. That URL is exactly the kind of thing
 * that ends up in a deploy script, CI logs, shell history, or `ps` output.
 * A leaked permanent URL for this endpoint is a standing, unauthenticated
 * remote OPcache-reset trigger — and repeated resets on a busy app force a
 * full bytecode recompilation storm, a real DoS. Requiring an expiry closes
 * that off even if someone builds the URL the "wrong" way by mistake.
 */
class RequireTemporarySignature
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid signature.');
        }

        if (! $request->query('expires')) {
            abort(403, 'This route requires a temporary signed URL (URL::temporarySignedRoute()) — a permanent signature is not accepted.');
        }

        return $next($request);
    }
}
