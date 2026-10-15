<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->after('name');
            $table->foreignId('bnnk_id')->nullable()->after('role')->constrained('bnnks')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['bnnk_id']);
            $table->dropColumn(['role', 'bnnk_id', 'is_active']);
        });
    }
};
