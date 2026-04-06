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
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'system_key']);
            $table->foreignId('wallet_id')->after('user_id')->constrained()->cascadeOnDelete();
            $table->unique(['wallet_id', 'name']);
            $table->unique(['wallet_id', 'system_key']);
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['wallet_id', 'system_key']);
            $table->dropUnique(['wallet_id', 'name']);
            $table->dropConstrainedForeignId('wallet_id');
            $table->unique(['user_id', 'system_key']);
        });
    }
};
