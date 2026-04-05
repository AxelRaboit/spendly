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
        Schema::table('budget_items', function (Blueprint $table) {
            $table->string('target_type')->nullable()->after('carried_over');
            $table->decimal('target_amount', 10, 2)->nullable()->after('target_type');
            $table->date('target_deadline')->nullable()->after('target_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budget_items', function (Blueprint $table) {
            $table->dropColumn(['target_type', 'target_amount', 'target_deadline']);
        });
    }
};
