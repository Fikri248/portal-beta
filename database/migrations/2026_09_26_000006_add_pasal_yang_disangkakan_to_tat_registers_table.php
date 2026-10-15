<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tat_registers', function (Blueprint $table): void {
            $table->text('pasal_yang_disangkakan')->nullable()->after('no_lkn_lp_li');
        });
    }

    public function down(): void
    {
        Schema::table('tat_registers', function (Blueprint $table): void {
            $table->dropColumn('pasal_yang_disangkakan');
        });
    }
};
