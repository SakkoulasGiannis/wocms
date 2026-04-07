<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('properties')) {
            return;
        }

        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('description')->nullable();
            $table->string('property_type')->default('apartment');
            $table->string('status')->default('for_sale');
            $table->decimal('price', 15, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->decimal('area', 10, 2)->nullable();
            $table->decimal('land_size', 10, 2)->nullable();
            $table->integer('bedrooms')->nullable();
            $table->integer('bathrooms')->nullable();
            $table->integer('rooms')->nullable();
            $table->integer('garages')->default(0);
            $table->integer('floor')->nullable();
            $table->integer('year_built')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('Greece');
            $table->string('postal_code')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('featured_image')->nullable();
            $table->json('gallery')->nullable();
            $table->string('video_url')->nullable();
            $table->string('virtual_tour_url')->nullable();
            $table->json('features')->nullable();
            $table->json('nearby_amenities')->nullable();
            $table->json('floor_plans')->nullable();
            $table->json('attachments')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->boolean('active')->default(false);
            $table->boolean('featured')->default(false);
            $table->integer('views')->default(0);
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index('property_type');
            $table->index('status');
            $table->index('active');
            $table->index('featured');
            $table->index('city');
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
