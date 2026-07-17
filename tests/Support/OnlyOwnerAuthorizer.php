<?php

namespace Tests\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Whilesmart\OwnerAccess\Contracts\OwnerAuthorizer;

/**
 * Strict authorizer for scoping tests: only the owner ids it was constructed
 * with are accessible; everything else is denied.
 */
class OnlyOwnerAuthorizer implements OwnerAuthorizer
{
    /**
     * @param  array<int, string>  $allowedIds
     */
    public function __construct(private array $allowedIds) {}

    public function authorize(?Authenticatable $user, string $ownerType, mixed $ownerId): bool
    {
        return in_array((string) $ownerId, $this->allowedIds, true);
    }

    public function scope(
        Builder $query,
        ?Authenticatable $user,
        string $ownerTypeColumn = 'owner_type',
        string $ownerIdColumn = 'owner_id',
    ): Builder {
        return $query->whereIn($ownerIdColumn, $this->allowedIds);
    }
}
