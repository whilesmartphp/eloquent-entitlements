## [1.0.0] - 2026-07-12
- Provider-neutral entitlements seam: allow-all default keeps self-hosting free, hosts rebind to enforce
- Plan source seam: the resolution rules stay put while plans and subscriptions may live wherever a host keeps them
- Plans with feature flags, limits and metered allowances, scoped per polymorphic owner via owner-access
- Subscriptions with a lifecycle (active, trialing, past due, canceled, comped) and an active scope
- Per-owner entitlement overrides that win over the plan
- Coupons (comp, plan grant, percent off, fixed off) with auditable redemptions and expiry/redemption caps
- Metering delegated to a host-bound usage meter, defaulting to unlimited so the package owns no usage table
- Billing seam for a host-supplied provider; no payment SDK is imported
