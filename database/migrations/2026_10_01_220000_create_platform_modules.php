<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('student')->after('password');
            $table->string('status')->default('active')->after('role');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->boolean('published')->default(true)->after('sort');
            $table->boolean('unlock_in_order')->default(false)->after('published');
            $table->string('access_unit')->nullable()->after('unlock_in_order');
            $table->unsignedSmallInteger('access_amount')->nullable()->after('access_unit');
            $table->timestamp('access_starts_at')->nullable()->after('access_amount');
            $table->timestamp('access_ends_at')->nullable()->after('access_starts_at');
        });

        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('published')->default(false);
            $table->timestamp('visible_from')->nullable();
            $table->timestamp('visible_until')->nullable();
            $table->string('kind')->default('text');
            $table->text('body')->nullable();
            $table->timestamps();
        });

        Schema::create('access_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issued_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('code_hash', 64)->unique();
            $table->string('hint', 8);
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->unsignedSmallInteger('max_uses')->default(1);
            $table->unsignedSmallInteger('uses')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('activations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('access_code_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('status')->default('active');
            $table->timestamp('locked_at')->nullable();
            $table->string('lock_source')->nullable();
            $table->timestamps();
        });

        Schema::create('lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'lesson_id']);
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('lesson_progress');
        Schema::dropIfExists('activations');
        Schema::dropIfExists('access_codes');
        Schema::dropIfExists('lessons');

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn([
                'published',
                'unlock_in_order',
                'access_unit',
                'access_amount',
                'access_starts_at',
                'access_ends_at',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'status']);
        });
    }
};
