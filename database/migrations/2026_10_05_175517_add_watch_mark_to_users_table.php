<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Access\WatchMark;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('watch_mark', 16)->nullable()->unique()->after('status');
        });

        User::query()
            ->where('role', UserRole::Student)
            ->whereNull('watch_mark')
            ->orderBy('id')
            ->each(function (User $user) {
                $user->forceFill(['watch_mark' => WatchMark::mint()])->save();
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['watch_mark']);
            $table->dropColumn('watch_mark');
        });
    }
};
