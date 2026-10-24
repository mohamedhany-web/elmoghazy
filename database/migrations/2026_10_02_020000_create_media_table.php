<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('disk');
            $table->string('key')->unique();
            $table->string('purpose');
            $table->string('mime');
            $table->unsignedBigInteger('size');
            $table->string('original_name');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('purpose');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
