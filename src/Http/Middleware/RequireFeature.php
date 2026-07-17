<?php

namespace Whilesmart\Entitlements\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Whilesmart\Entitlements\Contracts\Entitlements;
use Whilesmart\Entitlements\Contracts\OwnerResolver;

/**
 * Gates a route on a feature: resolve the owner, check the feature, and answer
 * 402 with the denial reason if the owner is not entitled.
 *
 *   Route::middleware('feature:reports')->group(...);
 */
class RequireFeature
{
    public function __construct(
        private Entitlements $entitlements,
        private OwnerResolver $owners,
    ) {}

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $owner = $this->owners->resolve($request);

        $result = $this->entitlements->check($owner, $feature);

        if (! $result->allowed) {
            return new JsonResponse([
                'success' => false,
                'message' => 'This feature is not included in your plan.',
                'error' => 'PaymentRequired',
                'feature' => $feature,
                'reason' => $result->reason,
            ], 402);
        }

        return $next($request);
    }
}
