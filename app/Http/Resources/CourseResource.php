<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            "courses" => [
                "class_code" => $this['courses']['class_code'],
                "name" => $this['courses']['name']
            ],
            "student_joined" => collect($this['student_joined'])->map(function ($item) {
                return [
                    "user_id" => $item['user_id']
                ];
            }),
            "documents" => collect($this['documents'])->map(function ($item) {
                return [
                    "link" => $item['link'],
                    "name" => $item['name'],
                    "public" => $item['public'],
                    "slug" => $item['slug'],
                ];
            }),
            "labs" => collect($this['labs'])->map(function ($item) {
                return [
                    "name" => $item['name'],
                    "level" => $item['level'],
                    "slug" => $item['slug'],
                    "count_submit" => count($item['point_submit']) ?? 0,
                    "deadlines" => [
                        "time_end" => $item['deadlines']['time_end'] ?? null,
                        "time_start" => $item['deadlines']['time_start'] ?? null
                    ],
                    "password" => ($item['deadlines'] != null && $item['deadlines'] != '') ? true : false
                ];
            }),
            "quizs" => collect($this['quizs'])->map(function ($item) {
                return [
                    "name" => $item['name'],
                    "level" => $item['level'],
                    "slug" => $item['slug'],
                    "count_submit" => count($item['point_submit']) ?? 0,
                    "deadlines" => [
                        "time_end" => $item['deadlines']['time_end'] ?? null,
                        "time_start" => $item['deadlines']['time_start'] ?? null
                    ],
                    "password" => ($item['deadlines'] != null && $item['deadlines'] != '') ? true : false
                ];
            }),
        ];
    }
}
