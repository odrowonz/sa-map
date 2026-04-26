<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sa_elements', function (Blueprint $table) {
            $table->boolean('include_in_export_data')->default(true)->after('content');
            $table->boolean('include_in_export_md')->default(true)->after('include_in_export_data');
            $table->boolean('include_in_import')->default(true)->after('include_in_export_md');
        });

        if (Schema::hasColumn('sa_elements', 'include_in_export')) {
            DB::table('sa_elements')->update([
                'include_in_export_data' => DB::raw('include_in_export'),
                'include_in_export_md' => DB::raw('include_in_export'),
                'include_in_import' => DB::raw('include_in_export'),
            ]);
            Schema::table('sa_elements', function (Blueprint $table) {
                $table->dropColumn('include_in_export');
            });
        }

        if (Schema::hasColumn('sa_projects', 'export_ui_state')) {
            Schema::table('sa_projects', function (Blueprint $table) {
                $table->dropColumn('export_ui_state');
            });
        }
    }

    public function down(): void
    {
        Schema::table('sa_projects', function (Blueprint $table) {
            $table->json('export_ui_state')->nullable()->after('slug');
        });

        Schema::table('sa_elements', function (Blueprint $table) {
            $table->boolean('include_in_export')->default(true)->after('content');
        });

        DB::table('sa_elements')->update([
            'include_in_export' => DB::raw('include_in_export_data'),
        ]);

        Schema::table('sa_elements', function (Blueprint $table) {
            $table->dropColumn(['include_in_export_data', 'include_in_export_md', 'include_in_import']);
        });
    }
};
