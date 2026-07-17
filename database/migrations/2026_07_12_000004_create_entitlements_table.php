<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('entitlements.entitlements_table', 'entitlements'), function (Blueprint $table) {
            $table->id();
            $table->morphs('owner');
            $table->string('feature')->nullable();
            $table->string('limit_key')->nullable();
            $table->integer('limit_value')->nullable();
            $table->string('meter')->nullable();
            $table->integer('meter_allowance')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_type', 'owner_id', 'feature']);
            $table->index(['owner_type', 'owner_id', 'limit_key']);
            $table->index(['owner_type', 'owner_id', 'meter']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('entitlements.entitlements_table', 'entitlements'));
    }
};
