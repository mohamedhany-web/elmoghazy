<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Resources\GradeResource;
use App\Models\Grade;

class GradeController extends Controller
{
    public function index()
    {
        $grades = Grade::query()->orderBy('sort')->get();

        return GradeResource::collection($grades);
    }

    public function show(Grade $grade)
    {
        $grade->loadCount([
            'studyMonths as months_count' => fn ($query) => $query->where('published', true),
        ]);

        return new GradeResource($grade);
    }
}
