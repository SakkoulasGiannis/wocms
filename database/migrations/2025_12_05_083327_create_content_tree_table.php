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
        Schema::create('content_tree', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('content_tree')->onDelete('cascade');

            // Content reference - polymorphic to any template model
            $table->string('content_type')->nullable(); // e.g., 'App\Models\BlogPost'
            $table->unsignedBigInteger('content_id')->nullable(); // ID in the template's table

            // URL and hierarchy
            $table->string('title');
            $table->string('slug');
            $table->string('url_path')->unique(); // Full path e.g., /blog/my-post
            $table->integer('level')->default(0);
            $table->string('tree_path'); // e.g., /1/5/12

            // Status
            $table->boolean('is_published')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['parent_id', 'sort_order']);
            $table->index('url_path');
            $table->index(['content_type', 'content_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_tree');
    }
};
