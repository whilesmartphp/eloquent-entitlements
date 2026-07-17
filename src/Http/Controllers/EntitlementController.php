<?php

namespace Whilesmart\Entitlements\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Whilesmart\Entitlements\Enums\CouponType;
use Whilesmart\Entitlements\Exceptions\CouponRedemptionException;
use Whilesmart\Entitlements\Http\Requests\RedeemCouponRequest;
use Whilesmart\Entitlements\Http\Requests\ShowEntitlementRequest;
use Whilesmart\Entitlements\Http\Resources\EntitlementSnapshotResource;
use Whilesmart\Entitlements\Models\Coupon;
use Whilesmart\Entitlements\Models\Subscription;
use Whilesmart\Entitlements\Services\CouponService;

class EntitlementController extends Controller
{
    /**
     * Current entitlement snapshot for an owner: active plan, features, limits.
     */
    public function show(ShowEntitlementRequest $request): JsonResponse
    {
        $ownerType = (string) $request->input('owner_type');
        $ownerId = $request->input('owner_id');

        $subscription = $this->subscriptionModel()::query()
            ->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->active()
            ->latest('id')
            ->first();

        $plan = $subscription?->plan;

        $features = (array) ($plan?->features ?? []);
        $limits = (array) ($plan?->limits ?? []);

        foreach ($this->overridesFor($ownerType, $ownerId) as $override) {
            if ($override->feature !== null) {
                $features[$override->feature] = true;
            }
            if ($override->limit_key !== null) {
                $limits[$override->limit_key] = $override->limit_value;
            }
        }

        return response()->json([
            'success' => true,
            'data' => new EntitlementSnapshotResource([
                'owner_type' => $ownerType,
                'owner_id' => $ownerId,
                'plan' => $plan ? ['key' => $plan->key, 'name' => $plan->name] : null,
                'status' => $subscription?->status?->value,
                'unlimited' => $subscription !== null && $plan === null,
                'features' => (object) $features,
                'limits' => (object) $limits,
            ]),
        ]);
    }

    /**
     * Redeem a coupon for the owner. Returns whatever grant it produced.
     */
    public function redeem(RedeemCouponRequest $request, CouponService $coupons): JsonResponse
    {
        $coupon = $this->couponModel()::query()
            ->where('code', $request->input('code'))
            ->first();

        if ($coupon === null) {
            throw new NotFoundHttpException('Coupon not found.');
        }

        $owner = $this->resolveOwner((string) $request->input('owner_type'), $request->input('owner_id'));

        try {
            $grant = $coupons->redeem($coupon, $owner);
        } catch (CouponRedemptionException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $isSubscription = in_array($coupon->type, [CouponType::Comp, CouponType::PlanGrant], true);

        return response()->json([
            'success' => true,
            'data' => [
                'grant' => $isSubscription ? 'subscription' : 'entitlement',
                'id' => $grant->getKey(),
            ],
        ], 201);
    }

    private function resolveOwner(string $ownerType, mixed $ownerId): Model
    {
        $class = Relation::getMorphedModel($ownerType) ?? $ownerType;

        return $class::query()->findOrFail($ownerId);
    }

    private function overridesFor(string $ownerType, mixed $ownerId)
    {
        $model = config('entitlements.models.entitlement');

        return $model::query()
            ->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->get();
    }

    private function subscriptionModel(): string
    {
        return config('entitlements.models.subscription', Subscription::class);
    }

    private function couponModel(): string
    {
        return config('entitlements.models.coupon', Coupon::class);
    }
}
