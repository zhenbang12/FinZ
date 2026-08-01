<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('accounts', 'is_pinned')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->boolean('is_pinned')->default(false)->after('balance');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('accounts', 'is_pinned')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->dropColumn('is_pinned');
            });
        }
    }
};
