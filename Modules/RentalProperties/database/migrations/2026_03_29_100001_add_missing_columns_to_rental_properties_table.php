<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_properties', function (Blueprint $table) {
            if (! Schema::hasColumn('rental_properties', 'currency')) {
                $table->string('currency', 10)->default('EUR')->after('price');
            }
            if (! Schema::hasColumn('rental_properties', 'bathrooms')) {
                $table->integer('bathrooms')->nullable()->after('bedrooms');
            }
            if (! Schema::hasColumn('rental_properties', 'rooms')) {
                $table->integer('rooms')->nullable()->after('bathrooms');
            }
            if (! Schema::hasColumn('rental_properties', 'address')) {
                $table->string('address')->nullable()->after('area');
            }
            if (! Schema::hasColumn('rental_properties', 'state')) {
                $table->string('state')->nullable()->after('city');
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
            if (! Schema::hasColumn('rental_properties', 'features')) {
                $table->json('features')->nullable()->after('longitude');
            }
            if (! Schema::hasColumn('rental_properties', 'featured')) {
                $table->boolean('featured')->default(false)->after('active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rental_properties', function (Blueprint $table) {
            $table->dropColumn([
                'currency', 'bathrooms', 'rooms', 'address', 'state',
                'country', 'postal_code', 'latitude', 'longitude', 'features', 'featured',
            ]);
        });
    }
};
