<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('image_maps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('image_id')->constrained('image_map_images')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->json('items')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['image_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('image_maps');
    }
};
