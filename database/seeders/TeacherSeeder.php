<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeacherSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('TEACHER_EMAIL');
        $password = env('TEACHER_PASSWORD');
        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            return;
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => 'محمد المغازي',
                'password' => $password,
                'role' => UserRole::Teacher,
                'status' => AccountStatus::Active,
            ],
        );
    }
}
