<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('entitlements.coupon_redemptions_table', 'entitlement_coupon_redemptions'), function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('coupon_id');
            $table->morphs('owner');
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamps();

            $table->index('coupon_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('entitlements.coupon_redemptions_table', 'entitlement_coupon_redemptions'));
    }
};
