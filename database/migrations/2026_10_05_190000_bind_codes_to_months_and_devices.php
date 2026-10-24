<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('access_codes', function (Blueprint $table) {
            $table->foreignId('study_month_id')->nullable()->after('lesson_id')->constrained('study_months')->nullOnDelete();
        });

        Schema::table('activations', function (Blueprint $table) {
            $table->foreignId('study_month_id')->nullable()->after('lesson_id')->constrained('study_months')->nullOnDelete();
            $table->string('device_hash', 64)->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('activations', function (Blueprint $table) {
            $table->dropColumn('device_hash');
            $table->dropConstrainedForeignId('study_month_id');
        });

        Schema::table('access_codes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('study_month_id');
        });
    }
};
