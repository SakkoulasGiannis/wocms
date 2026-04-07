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
        Schema::table('slides', function (Blueprint $table) {
            $table->string('media_type')->default('image')->after('button_text'); // image, video, youtube
            $table->string('video_url')->nullable()->after('media_type'); // YouTube URL
        });
    }

    public function down(): void
    {
        Schema::table('slides', function (Blueprint $table) {
            $table->dropColumn(['media_type', 'video_url']);
        });
    }
};
