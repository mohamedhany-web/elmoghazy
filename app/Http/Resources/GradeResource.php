<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GradeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'lessons' => $this->lessons,
            'accent' => $this->accent,
            'courses' => CourseResource::collection($this->whenLoaded('courses')),
        ];
    }
}
