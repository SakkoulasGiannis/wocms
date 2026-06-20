<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persists a guest's booking through the checkout/payment flow. A row is
 * created (pending_payment) before payment; on a successful charge it becomes
 * paid → confirmed (with the Hostaway reservation id).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bookings')) {
            return;
        }

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('rental_property_id')->nullable()->index();
            $table->string('hostaway_id')->nullable()->index();

            $table->date('checkin');
            $table->date('checkout');
            $table->unsignedSmallInteger('nights')->default(0);
            $table->unsignedSmallInteger('adults')->default(1);
            $table->unsignedSmallInteger('children')->default(0);

            $table->string('guest_name');
            $table->string('guest_email');
            $table->string('guest_phone')->nullable();
            $table->text('message')->nullable();

            $table->string('currency', 10)->default('EUR');
            $table->decimal('accommodation', 10, 2)->default(0);
            $table->decimal('cleaning_fee', 10, 2)->default(0);
            $table->decimal('extra_guest_fee', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->decimal('amount_due', 10, 2)->default(0);

            // pending_payment | paid | confirmed | failed | cancelled | expired
            $table->string('status')->default('pending_payment')->index();

            $table->string('payment_provider')->nullable();
            $table->string('payment_ref')->nullable()->index();
            $table->string('payment_status')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->string('hostaway_reservation_id')->nullable()->index();

            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
