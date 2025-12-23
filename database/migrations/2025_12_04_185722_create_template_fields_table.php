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
        Schema::create('template_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Field machine name (price, title, etc)
            $table->string('label'); // Human readable label
            $table->string('type'); // text, number, textarea, wysiwyg, image, repeater, relation, etc
            $table->text('description')->nullable();
            $table->json('validation_rules')->nullable(); // ['required', 'min:5', etc]
            $table->string('default_value')->nullable();
            $table->json('settings')->nullable(); // Type-specific settings
            $table->integer('order')->default(0);
            $table->boolean('is_required')->default(false);
            $table->timestamps();

            $table->index(['template_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_fields');
    }
};
