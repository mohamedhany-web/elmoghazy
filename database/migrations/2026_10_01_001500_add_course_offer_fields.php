<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('mode')->default('recorded')->after('tag');
            $table->string('duration')->default('الترم كامل')->after('mode');
            $table->unsignedInteger('price')->default(0)->after('duration');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['mode', 'duration', 'price']);
        });
    }
};
