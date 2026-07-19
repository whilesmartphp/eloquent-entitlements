## [2.0.0] - 2026-07-19
- Breaking: the owner subscriptions relation is renamed from `subscriptions()` to `entitlementSubscriptions()`, so the trait composes with billing libraries (Cashier, Paddle) that add their own `subscriptions()` to the same model. Update `$owner->subscriptions()` calls to `$owner->entitlementSubscriptions()`; `activeSubscription()` is unchanged.

## [1.0.0] - 2026-07-17
- Provider-neutral entitlements seam: allow-all default keeps self-hosting free, hosts rebind to enforce
- Plan source seam: the resolution rules stay put while plans and subscriptions may live wherever a host keeps them
- Feature gating middleware and an owner resolver seam, so a route gates on a feature and denials answer 402
- Access results carry the denial reason, so a caller can tell the user why rather than a bare no
- Migrations can be turned off, so a host that brings its own plan source takes the contract without the tables
- Plans with feature flags, limits and metered allowances, scoped per polymorphic owner via owner-access
- Subscriptions with a lifecycle (active, trialing, past due, canceled, comped) and an active scope
- Per-owner entitlement overrides that win over the plan
- Coupons (comp, plan grant, percent off, fixed off) with auditable redemptions and expiry/redemption caps
- Metering delegated to a host-bound usage meter, defaulting to unlimited so the package owns no usage table
- Billing seam for a host-supplied provider; no payment SDK is imported
