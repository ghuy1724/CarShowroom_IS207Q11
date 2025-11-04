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
        Schema::create('rental_orders', function (Blueprint $table) {
            $table->id('order_id'); // Khóa chính tự động tăng
            $table->string('user_id'); // ID người dùng
            $table->unsignedBigInteger('rental_id'); // ID xe thuê
            $table->enum('status', ['Pending', 'Deposit Paid', 'Paid', 'Canceled'])->default('Pending');
            $table->timestamp('order_date')->useCurrent();
            $table->timestamps();

            // Khóa ngoại
            $table->foreign('user_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('rental_id')->references('rental_id')->on('rental_cars')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rental_orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['rental_id']);
        });

        Schema::dropIfExists('rental_orders');
    }
};
