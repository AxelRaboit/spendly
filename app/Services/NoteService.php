<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class NoteService
{
    public function list(User $user, array $filters = []): Collection
    {
        $query = $user->notes()->orderBy('position')->orderByDesc('created_at');

        // Tag filter can be done at SQL level (not encrypted)
        if (! empty($filters['tag'])) {
            $query->whereJsonContains('tags', $filters['tag']);
        }

        /** @var Collection<int, Note> $notes */
        $notes = $query->get();

        // Full-text search on title/content must be done after decryption
        if (! empty($filters['q'])) {
            $search = Str::lower($filters['q']);
            $notes = $notes->filter(fn (Note $note) => Str::contains(Str::lower($note->title ?? ''), $search)
                || Str::contains(Str::lower($note->content ?? ''), $search))->values();
        }

        return $notes;
    }

    public function allTags(User $user): array
    {
        return $user->notes()
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function create(User $user): Note
    {
        $maxPosition = $user->notes()->max('position') ?? -1;

        /** @var Note $note */
        $note = $user->notes()->create([
            'title' => null,
            'content' => null,
            'tags' => [],
            'position' => $maxPosition + 1,
        ]);

        return $note;
    }

    public function update(Note $note, array $data): Note
    {
        $note->update($data);

        return $note;
    }

    public function reorder(User $user, array $ids): void
    {
        foreach ($ids as $position => $id) {
            $user->notes()
                ->where('id', $id)
                ->update(['position' => $position]);
        }
    }

    public function delete(Note $note): void
    {
        $note->delete();
    }
}
