<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->slug,
            'title' => $this->title,
            'lessons' => $this->lessons,
            'desc' => $this->description,
            'tag' => $this->tag,
            'mode' => $this->mode,
            'duration' => $this->duration,
            'price' => $this->price,
        ];
    }
}
