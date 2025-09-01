<?php
// app/Services/Reference/TagService.php

namespace App\Services\Reference;

use App\Models\Tag;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TagService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $q = Tag::query();

        if (!empty($filters['q'])) {
            $s = $filters['q'];
            $q->where('name', 'like', "%{$s}%");
        }

        return $q->latest()->paginate(15);
    }

    public function create(array $data): array
    {
        $tags = [];

        foreach ($data['tags'] as $tagData) {
            $tag = new Tag();
            $tag->name = $tagData['name'];
            $tag->save();

            $tags[] = $tag;
        }

        return $tags;
    }


    // public function update(Tag $tag, array $data): Tag
    // {
    //     $tag->name  = $data['name'];
    //     $tag->save();

    //     return $tag;
    // }
    public function update(Tag $tag, array $data)
    {
        $tag->name        = $data['name']        ?? $tag->name;
        $tag->description = $data['description'] ?? $tag->description;

        $tag->save();
        return $tag;
    }

    public function delete(Tag $tag): void
    {
        $tag->delete();
    }
}
