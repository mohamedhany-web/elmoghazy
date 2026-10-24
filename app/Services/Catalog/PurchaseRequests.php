<?php

namespace App\Services\Catalog;

use App\Models\PurchaseRequest;
use App\Models\StudyMonth;
use App\Models\User;
use App\Support\EgyptianPhone;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseRequests
{
    public function place(StudyMonth $month, string $name, string $phone, ?string $note, ?User $user): PurchaseRequest
    {
        if (! $month->published) {
            abort(404);
        }

        $phone = EgyptianPhone::normalize($phone);
        if (! preg_match('/^01[0125]\d{8}$/', $phone)) {
            throw ValidationException::withMessages([
                'phone' => ['اكتب رقم موبايل مصري صحيح.'],
            ]);
        }

        $name = trim($name);
        if ($name === '') {
            throw ValidationException::withMessages([
                'name' => ['اكتب الاسم.'],
            ]);
        }

        $exists = PurchaseRequest::query()
            ->where('study_month_id', $month->id)
            ->where('phone', $phone)
            ->where('status', 'pending')
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'phone' => ['الطلب ده متسجل قبل كده. المدرس هيراجعه.'],
            ]);
        }

        return PurchaseRequest::query()->create([
            'study_month_id' => $month->id,
            'user_id' => $user?->id,
            'name' => $name,
            'phone' => $phone,
            'note' => $note ? trim($note) : null,
            'status' => 'pending',
        ]);
    }

    public function close(PurchaseRequest $request): PurchaseRequest
    {
        return DB::transaction(function () use ($request) {
            $request->status = 'done';
            $request->handled_at = now();
            $request->save();

            return $request->refresh();
        });
    }
}
