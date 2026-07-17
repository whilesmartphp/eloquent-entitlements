<?php

namespace Whilesmart\Entitlements\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wraps a prepared snapshot array (owner, plan, features, limits) built by the
 * controller. No model of its own; the snapshot is derived, not stored.
 */
class EntitlementSnapshotResource extends JsonResource
{
    public function toArray($request): array
    {
        return $this->resource;
    }
}
