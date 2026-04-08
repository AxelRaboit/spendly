<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class NoteService
{
    /**
     * Return a flat list of all user notes (frontend builds the tree).
     * Content is excluded — loaded on demand via show().
     */
    public function list(User $user): Collection
    {
        return $user->notes()
            ->orderBy('position')
            ->orderByDesc('created_at')
            ->get(['id', 'parent_id', 'position', 'title', 'tags', 'updated_at', 'created_at']);
    }

    public function create(User $user, ?int $parentId = null): Note
    {
        $maxPosition = $user->notes()->where('parent_id', $parentId)->max('position') ?? -1;

        /** @var Note $note */
        $note = $user->notes()->create([
            'parent_id' => $parentId,
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

    public function move(Note $note, ?int $parentId): void
    {
        $note->update(['parent_id' => $parentId]);
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
