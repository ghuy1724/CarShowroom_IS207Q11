<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rental_receipt', function (Blueprint $table) {
            // Modify the status enum to include 'Overdue'
            DB::statement("ALTER TABLE rental_receipt MODIFY COLUMN status ENUM('Active', 'Canceled', 'Completed', 'Overdue') DEFAULT 'Active'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rental_receipt', function (Blueprint $table) {
            // Revert back to original enum values
            DB::statement("ALTER TABLE rental_receipt MODIFY COLUMN status ENUM('Active', 'Canceled', 'Completed') DEFAULT 'Active'");
        });
    }
};
