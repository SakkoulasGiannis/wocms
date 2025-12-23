<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('image_sizes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., 'thumbnail', 'small_thumbnail'
            $table->string('label'); // e.g., 'Thumbnail', 'Small Thumbnail'
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->enum('mode', ['crop', 'fit', 'resize'])->default('crop'); // How to resize
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('image_sizes');
    }
};
