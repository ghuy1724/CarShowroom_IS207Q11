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
        Schema::create('overdue_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('receipt_id');
            $table->date('notification_date');
            $table->decimal('overdue_fee', 10, 2);
            $table->integer('overdue_days');
            $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('receipt_id')->references('receipt_id')->on('rental_receipt')->onDelete('cascade');
            
            // Prevent duplicate notifications on the same day for the same receipt
            $table->unique(['receipt_id', 'notification_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overdue_notifications');
    }
};
