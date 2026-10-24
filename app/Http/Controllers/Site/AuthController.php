<?php

namespace App\Http\Controllers\Site;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Grade;
use App\Models\User;
use App\Support\EgyptianPhone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->merge([
            'phone' => EgyptianPhone::normalize($request->input('phone')),
            'parent_phone' => EgyptianPhone::normalize($request->input('parent_phone')),
        ]);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'second_name' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'regex:/^01[0125]\d{8}$/', 'unique:users,phone'],
            'parent_phone' => ['required', 'regex:/^01[0125]\d{8}$/'],
            'password' => ['required', 'string', 'min:8'],
            'grade' => ['required', 'string', 'exists:grades,slug'],
        ], [
            'first_name.required' => 'اكتب الاسم الأول.',
            'second_name.required' => 'اكتب الاسم الثاني.',
            'phone.required' => 'اكتب رقم التليفون.',
            'phone.regex' => 'اكتب رقم موبايل مصري صحيح.',
            'phone.unique' => 'رقم التليفون مسجّل بالفعل.',
            'parent_phone.required' => 'اكتب رقم تليفون ولي الأمر.',
            'parent_phone.regex' => 'اكتب رقم موبايل مصري صحيح لولي الأمر.',
            'password.min' => 'كلمة المرور لازم تكون 8 حروف على الأقل.',
            'grade.exists' => 'اختار صف صحيح.',
            'grade.required' => 'اختار الصف.',
        ]);

        $user = User::create([
            'first_name' => $data['first_name'],
            'second_name' => $data['second_name'],
            'name' => trim($data['first_name'].' '.$data['second_name']),
            'phone' => $data['phone'],
            'parent_phone' => $data['parent_phone'],
            'password' => $data['password'],
            'grade_id' => Grade::where('slug', $data['grade'])->value('id'),
            'role' => UserRole::Student,
            'status' => AccountStatus::Active,
        ]);

        $user->load('grade');

        return response()->json([
            'token' => $user->createToken('web')->plainTextToken,
            'user' => new UserResource($user),
        ], 201);
    }

    public function login(Request $request)
    {
        $rawPhone = trim((string) $request->input('phone', ''));
        if ($rawPhone !== '' && str_contains($rawPhone, '@')) {
            $request->merge([
                'email' => strtolower($rawPhone),
                'phone' => null,
            ]);
        } elseif ($request->filled('phone')) {
            $request->merge(['phone' => EgyptianPhone::normalize($request->input('phone'))]);
        }

        $data = $request->validate([
            'phone' => ['required_without:email', 'nullable', 'string'],
            'email' => ['required_without:phone', 'nullable', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = ! empty($data['phone'])
            ? User::where('phone', $data['phone'])->first()
            : User::where('email', $data['email'])->first();

        $field = ! empty($data['phone']) ? 'phone' : 'email';
        $invalid = $field === 'phone'
            ? 'رقم التليفون أو كلمة المرور غير صحيحة.'
            : 'البريد أو كلمة المرور غير صحيحة.';

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                $field => [$invalid],
            ]);
        }

        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                $field => ['الحساب متوقف. راجع المدرس.'],
            ]);
        }

        $user->load('grade');

        return response()->json([
            'token' => $user->createToken('web')->plainTextToken,
            'user' => new UserResource($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['ok' => true]);
    }

    public function me(Request $request)
    {
        return new UserResource($request->user()->load('grade'));
    }
}
