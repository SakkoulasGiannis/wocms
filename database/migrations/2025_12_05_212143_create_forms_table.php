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
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            // Email settings
            $table->string('submit_button_text')->default('Submit');
            $table->text('success_message')->nullable();
            $table->string('redirect_url')->nullable();

            // Notification settings
            $table->boolean('send_email_notification')->default(true);
            $table->text('notification_recipients')->nullable(); // JSON array of emails
            $table->string('notification_subject')->nullable();
            $table->text('notification_message')->nullable();

            // Auto-reply settings
            $table->boolean('send_auto_reply')->default(false);
            $table->string('auto_reply_email_field')->nullable(); // Which field contains user email
            $table->string('auto_reply_subject')->nullable();
            $table->text('auto_reply_message')->nullable();

            // Storage settings
            $table->boolean('store_submissions')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('label');
            $table->string('type'); // text, email, textarea, select, checkbox, radio, file, etc.
            $table->text('placeholder')->nullable();
            $table->text('default_value')->nullable();
            $table->text('help_text')->nullable();
            $table->boolean('is_required')->default(false);
            $table->text('validation_rules')->nullable(); // JSON
            $table->json('options')->nullable(); // For select, radio, checkbox
            $table->integer('order')->default(0);
            $table->json('settings')->nullable(); // Additional field settings
            $table->timestamps();
        });

        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->onDelete('cascade');
            $table->json('data'); // Form field data
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('referer')->nullable();
            $table->boolean('is_read')->default(false);
            $table->boolean('is_spam')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
        Schema::dropIfExists('form_fields');
        Schema::dropIfExists('forms');
    }
};
