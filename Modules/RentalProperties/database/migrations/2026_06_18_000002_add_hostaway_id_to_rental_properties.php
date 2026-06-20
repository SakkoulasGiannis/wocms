<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the Hostaway listing id (the CRM exposes it as `hostaway_id`, distinct
 * from the CRM's own `id` we already store as `external_id`). Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_properties', function (Blueprint $table) {
            if (! Schema::hasColumn('rental_properties', 'hostaway_id')) {
                $table->string('hostaway_id')->nullable()->index()->after('external_id');
            }
        });
    }

    public function down(): void
    {
        // Non-destructive by design: leave the column in place on rollback.
    }
};
