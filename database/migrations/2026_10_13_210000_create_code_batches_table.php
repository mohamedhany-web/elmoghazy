<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('code_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issued_by')->constrained('users')->cascadeOnDelete();
            $table->string('type', 20);
            $table->foreignId('study_month_id')->nullable()->constrained('study_months')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('label')->nullable();
            $table->unsignedSmallInteger('code_valid_days')->nullable();
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->unsignedInteger('quantity')->default(0);
            $table->timestamps();
        });

        Schema::table('access_codes', function (Blueprint $table) {
            $table->foreignId('batch_id')->nullable()->after('issued_by')->constrained('code_batches')->nullOnDelete();
            $table->text('plain_code')->nullable()->after('hint');
        });
    }

    public function down(): void
    {
        Schema::table('access_codes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('batch_id');
            $table->dropColumn('plain_code');
        });
        Schema::dropIfExists('code_batches');
    }
};
