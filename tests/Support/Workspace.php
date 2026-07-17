<?php

namespace Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Whilesmart\Entitlements\Traits\HasEntitlements;

class Workspace extends Model
{
    use HasEntitlements;

    protected $guarded = ['id'];

    protected $table = 'workspaces';
}
