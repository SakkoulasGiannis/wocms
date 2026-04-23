<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_tool_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('chat_message_id')->nullable()->index();
            $table->string('tool_name')->index();
            $table->string('provider')->nullable(); // claude | openai
            $table->json('args')->nullable();
            $table->json('result')->nullable();
            $table->json('undo_payload')->nullable();
            $table->boolean('confirmed')->default(false);
            $table->boolean('executed')->default(false);
            $table->boolean('success')->default(false);
            $table->text('error')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_tool_audits');
    }
};
