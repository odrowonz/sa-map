<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sa_projects', function (Blueprint $table) {
            $table->json('export_ui_state')->nullable()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('sa_projects', function (Blueprint $table) {
            $table->dropColumn('export_ui_state');
        });
    }
};
