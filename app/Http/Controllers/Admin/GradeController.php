<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankUnit;
use App\Models\Course;
use App\Models\Grade;
use App\Models\StudyMonth;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GradeController extends Controller
{
    public function store(Request $request)
    {
        $data = $this->fields($request, true);
        $accents = ['cyan', 'amber', 'emerald', 'violet'];

        $grade = Grade::query()->create([
            'slug' => $this->slug(),
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? '',
            'lessons' => 0,
            'accent' => $accents[Grade::query()->count() % count($accents)],
            'sort' => (int) Grade::query()->max('sort') + 1,
        ]);

        return response()->json([
            'id' => $grade->id,
            'slug' => $grade->slug,
            'title' => $grade->title,
        ], 201);
    }

    public function update(Request $request, Grade $grade)
    {
        $data = $this->fields($request, false);
        $grade->update([
            'title' => $data['title'] ?? $grade->title,
            'subtitle' => array_key_exists('subtitle', $data) ? ($data['subtitle'] ?? '') : $grade->subtitle,
        ]);

        return response()->json(['ok' => true]);
    }

    public function destroy(Grade $grade)
    {
        $used = User::query()->where('grade_id', $grade->id)->exists()
            || Course::query()->where('grade_id', $grade->id)->exists()
            || StudyMonth::query()->where('grade_id', $grade->id)->exists()
            || BankUnit::query()->whereHas('bank', fn ($query) => $query->where('grade_id', $grade->id))->exists();

        if ($used) {
            throw ValidationException::withMessages([
                'title' => ['السنة دي فيها طلبة أو شهور. متقدرش تمسحها.'],
            ]);
        }

        $grade->delete();

        return response()->json(['ok' => true]);
    }

    private function fields(Request $request, bool $creating): array
    {
        return $request->validate([
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
        ], [
            'title.required' => 'اكتب اسم السنة الدراسية.',
        ]);
    }

    private function slug(): string
    {
        do {
            $slug = 'stage-'.Str::lower(Str::random(8));
        } while (Grade::query()->where('slug', $slug)->exists());

        return $slug;
    }
}
