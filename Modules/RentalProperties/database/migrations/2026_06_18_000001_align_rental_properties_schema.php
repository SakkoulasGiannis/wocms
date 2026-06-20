<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aligns the rental_properties table with the RentalProperty model + the CRM/
 * Hostaway sync mapping. The earlier add-column migrations are recorded as run
 * but their columns are missing (the table was recreated after them), so the
 * sync failed with "Unknown column" for every listing. This is fully
 * idempotent (hasColumn guards) and safe to run on any environment.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_properties', function (Blueprint $table) {
            if (! Schema::hasColumn('rental_properties', 'external_id')) {
                $table->string('external_id')->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('rental_properties', 'currency')) {
                $table->string('currency', 10)->default('EUR')->after('price');
            }
            if (! Schema::hasColumn('rental_properties', 'land_size')) {
                $table->decimal('land_size', 10, 2)->nullable()->after('area');
            }
            if (! Schema::hasColumn('rental_properties', 'bathrooms')) {
                $table->integer('bathrooms')->nullable()->after('bedrooms');
            }
            if (! Schema::hasColumn('rental_properties', 'rooms')) {
                $table->integer('rooms')->nullable()->after('bathrooms');
            }
            if (! Schema::hasColumn('rental_properties', 'garages')) {
                $table->integer('garages')->nullable()->after('rooms');
            }
            if (! Schema::hasColumn('rental_properties', 'floor')) {
                $table->integer('floor')->nullable()->after('garages');
            }
            if (! Schema::hasColumn('rental_properties', 'year_built')) {
                $table->integer('year_built')->nullable()->after('floor');
            }
            if (! Schema::hasColumn('rental_properties', 'address')) {
                $table->string('address')->nullable()->after('city');
            }
            if (! Schema::hasColumn('rental_properties', 'state')) {
                $table->string('state')->nullable()->after('address');
            }
            if (! Schema::hasColumn('rental_properties', 'country')) {
                $table->string('country')->default('Greece')->after('state');
            }
            if (! Schema::hasColumn('rental_properties', 'postal_code')) {
                $table->string('postal_code')->nullable()->after('country');
            }
            if (! Schema::hasColumn('rental_properties', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('postal_code');
            }
            if (! Schema::hasColumn('rental_properties', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
            if (! Schema::hasColumn('rental_properties', 'video_url')) {
                $table->string('video_url')->nullable();
            }
            if (! Schema::hasColumn('rental_properties', 'virtual_tour_url')) {
                $table->string('virtual_tour_url')->nullable();
            }
            if (! Schema::hasColumn('rental_properties', 'features')) {
                $table->json('features')->nullable();
            }
            if (! Schema::hasColumn('rental_properties', 'nearby_amenities')) {
                $table->json('nearby_amenities')->nullable();
            }
            if (! Schema::hasColumn('rental_properties', 'floor_plans')) {
                $table->json('floor_plans')->nullable();
            }
            if (! Schema::hasColumn('rental_properties', 'attachments')) {
                $table->json('attachments')->nullable();
            }
            if (! Schema::hasColumn('rental_properties', 'meta_title')) {
                $table->string('meta_title')->nullable();
            }
            if (! Schema::hasColumn('rental_properties', 'meta_description')) {
                $table->text('meta_description')->nullable();
            }
            if (! Schema::hasColumn('rental_properties', 'meta_keywords')) {
                $table->string('meta_keywords')->nullable();
            }
            if (! Schema::hasColumn('rental_properties', 'featured')) {
                $table->boolean('featured')->default(false)->after('active');
            }
            if (! Schema::hasColumn('rental_properties', 'views')) {
                $table->unsignedInteger('views')->default(0);
            }
        });
    }

    public function down(): void
    {
        // Non-destructive by design: leave the columns in place on rollback.
    }
};
