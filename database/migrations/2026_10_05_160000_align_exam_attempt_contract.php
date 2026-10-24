<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->foreignId('study_month_id')->nullable()->after('grade_id')->constrained('study_months')->nullOnDelete();
            $table->unsignedTinyInteger('attempts_allowed')->default(0);
            $table->decimal('total_marks', 8, 2)->default(0);
            $table->decimal('passing_marks', 8, 2)->nullable();
            $table->timestamp('opens_at')->nullable();
            $table->boolean('show_results')->default(true);
            $table->boolean('show_answers')->default(true);
            $table->boolean('show_explanations')->default(true);
            $table->boolean('allow_review')->default(true);
            $table->boolean('prevent_tab_switch')->default(false);
            $table->boolean('active')->default(true);
        });

        Schema::table('exam_questions', function (Blueprint $table) {
            $table->decimal('points', 8, 2)->default(1)->change();
        });

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->string('status', 20)->default('in_progress');
            $table->json('answer_sheet')->nullable();
            $table->json('presentation')->nullable();
            $table->json('activity')->nullable();
            $table->unsignedInteger('time_taken')->nullable();
            $table->unsignedSmallInteger('tab_switches')->default(0);
            $table->boolean('auto_submitted')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->decimal('percentage', 6, 2)->nullable();
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->unsignedSmallInteger('time_limit')->nullable();
        });

        DB::table('exam_attempts')->whereNotNull('submitted_at')->update(['status' => 'completed']);

        DB::table('questions')->where('type', 'short')->orderBy('id')->lazy()->each(function ($row) {
            $settings = json_decode($row->settings ?? 'null', true);
            if (! is_array($settings) || empty($settings['answers']) || ! empty($settings['manual'])) {
                return;
            }
            DB::table('questions')->where('id', $row->id)->update(['type' => 'fill_blank']);
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('time_limit');
        });

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'answer_sheet',
                'presentation',
                'activity',
                'time_taken',
                'tab_switches',
                'auto_submitted',
                'ip_address',
                'user_agent',
                'percentage',
            ]);
        });

        Schema::table('exam_questions', function (Blueprint $table) {
            $table->unsignedSmallInteger('points')->default(1)->change();
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('study_month_id');
            $table->dropColumn([
                'attempts_allowed',
                'total_marks',
                'passing_marks',
                'opens_at',
                'show_results',
                'show_answers',
                'show_explanations',
                'allow_review',
                'prevent_tab_switch',
                'active',
            ]);
        });
    }
};
