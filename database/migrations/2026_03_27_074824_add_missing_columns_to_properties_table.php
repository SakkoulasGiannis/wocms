<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            if (! Schema::hasColumn('properties', 'currency')) {
                $table->string('currency', 3)->default('EUR')->after('price');
            }
            if (! Schema::hasColumn('properties', 'land_size')) {
                $table->decimal('land_size', 10, 2)->nullable()->after('area');
            }
            if (! Schema::hasColumn('properties', 'rooms')) {
                $table->integer('rooms')->nullable()->after('bathrooms');
            }
            if (! Schema::hasColumn('properties', 'garages')) {
                $table->integer('garages')->default(0)->after('rooms');
            }
            if (! Schema::hasColumn('properties', 'floor')) {
                $table->integer('floor')->nullable()->after('garages');
            }
            if (! Schema::hasColumn('properties', 'year_built')) {
                $table->integer('year_built')->nullable()->after('floor');
            }
            if (! Schema::hasColumn('properties', 'address')) {
                $table->string('address')->nullable()->after('year_built');
            }
            if (! Schema::hasColumn('properties', 'state')) {
                $table->string('state')->nullable()->after('city');
            }
            if (! Schema::hasColumn('properties', 'country')) {
                $table->string('country')->default('Greece')->after('state');
            }
            if (! Schema::hasColumn('properties', 'postal_code')) {
                $table->string('postal_code')->nullable()->after('country');
            }
            if (! Schema::hasColumn('properties', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('postal_code');
            }
            if (! Schema::hasColumn('properties', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
            if (! Schema::hasColumn('properties', 'video_url')) {
                $table->string('video_url')->nullable()->after('longitude');
            }
            if (! Schema::hasColumn('properties', 'virtual_tour_url')) {
                $table->string('virtual_tour_url')->nullable()->after('video_url');
            }
            if (! Schema::hasColumn('properties', 'features')) {
                $table->json('features')->nullable()->after('virtual_tour_url');
            }
            if (! Schema::hasColumn('properties', 'nearby_amenities')) {
                $table->json('nearby_amenities')->nullable()->after('features');
            }
            if (! Schema::hasColumn('properties', 'floor_plans')) {
                $table->json('floor_plans')->nullable()->after('nearby_amenities');
            }
            if (! Schema::hasColumn('properties', 'attachments')) {
                $table->json('attachments')->nullable()->after('floor_plans');
            }
            if (! Schema::hasColumn('properties', 'meta_title')) {
                $table->string('meta_title')->nullable()->after('attachments');
            }
            if (! Schema::hasColumn('properties', 'meta_description')) {
                $table->text('meta_description')->nullable()->after('meta_title');
            }
            if (! Schema::hasColumn('properties', 'meta_keywords')) {
                $table->text('meta_keywords')->nullable()->after('meta_description');
            }
            if (! Schema::hasColumn('properties', 'views')) {
                $table->integer('views')->default(0)->after('featured');
            }
            if (! Schema::hasColumn('properties', 'order')) {
                $table->integer('order')->default(0)->after('views');
            }
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $columns = ['currency', 'land_size', 'rooms', 'garages', 'floor', 'year_built',
                'address', 'state', 'country', 'postal_code', 'latitude', 'longitude',
                'video_url', 'virtual_tour_url', 'features', 'nearby_amenities',
                'floor_plans', 'attachments', 'meta_title', 'meta_description',
                'meta_keywords', 'views', 'order'];

            foreach ($columns as $col) {
                if (Schema::hasColumn('properties', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
