<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_lesson_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('bank_topic_id')->nullable()->after('bank_lesson_id')->constrained('bank_topics')->nullOnDelete();
            $table->string('type', 20)->default('single')->after('prompt');
            $table->unsignedTinyInteger('difficulty')->default(3);
            $table->decimal('marks', 6, 2)->default(1);
            $table->decimal('negative_mark', 6, 2)->nullable();
            $table->text('explanation')->nullable();
            $table->text('hint')->nullable();
            $table->string('status', 20)->default('published');
            $table->json('tags')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('favorite')->default(false);
            $table->index('type');
            $table->index('status');
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('shuffle_choices')->default(false);
            $table->string('explanation_when', 20)->default('after');
        });

        Schema::table('exam_questions', function (Blueprint $table) {
            $table->json('snapshot')->nullable();
        });

        Schema::table('exam_answers', function (Blueprint $table) {
            $table->decimal('awarded', 8, 2)->default(0);
            $table->decimal('max_points', 8, 2)->default(0);
            $table->text('response')->nullable();
            $table->boolean('pending')->default(false);
            $table->text('feedback')->nullable();
        });

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->decimal('score', 8, 2)->default(0)->change();
            $table->decimal('total', 8, 2)->default(0)->change();
        });

        $multiple = DB::table('question_options')
            ->select('question_id')
            ->where('is_correct', true)
            ->groupBy('question_id')
            ->havingRaw('count(*) > 1')
            ->pluck('question_id');

        if ($multiple->isNotEmpty()) {
            DB::table('questions')->whereIn('id', $multiple)->update(['type' => 'multiple']);
        }
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->unsignedSmallInteger('score')->default(0)->change();
            $table->unsignedSmallInteger('total')->default(0)->change();
        });

        Schema::table('exam_answers', function (Blueprint $table) {
            $table->dropColumn(['awarded', 'max_points', 'response', 'pending', 'feedback']);
        });

        Schema::table('exam_questions', function (Blueprint $table) {
            $table->dropColumn('snapshot');
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['shuffle_questions', 'shuffle_choices', 'explanation_when']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropIndex(['status']);
            $table->dropConstrainedForeignId('bank_topic_id');
            $table->dropColumn([
                'type',
                'difficulty',
                'marks',
                'negative_mark',
                'explanation',
                'hint',
                'status',
                'tags',
                'settings',
                'favorite',
            ]);
        });

        Schema::dropIfExists('bank_topics');
    }
};
