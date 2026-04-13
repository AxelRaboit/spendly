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
        $oldTitle = $note->title;
        $note->update($data);

        // Rename wiki-links in all other notes when title changes
        $newTitle = $data['title'] ?? null;
        $user = $note->user;
        if ($oldTitle && $newTitle && $oldTitle !== $newTitle && $user !== null) {
            $this->renameWikiLinks($user, $note->id, $oldTitle, $newTitle);
        }

        return $note;
    }

    /**
     * Replace [[oldTitle]] with [[newTitle]] in all notes belonging to the user.
     */
    private function renameWikiLinks(User $user, int $excludeId, string $oldTitle, string $newTitle): void
    {
        $oldPattern = '[['.$oldTitle.']]';
        $newPattern = '[['.$newTitle.']]';

        $notes = $user->notes()
            ->where('id', '!=', $excludeId)
            ->get(['id', 'content']);

        foreach ($notes as $note) {
            if (! $note->content) {
                continue;
            }

            if (! str_contains((string) $note->content, $oldPattern)) {
                continue;
            }

            $note->update([
                'content' => str_replace($oldPattern, $newPattern, $note->content),
            ]);
        }
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

    /**
     * Find notes that contain [[title]] wiki-links pointing to the given note.
     */
    public function backlinks(User $user, Note $note): array
    {
        $title = $note->title;
        if (! $title) {
            return [];
        }

        $pattern = '[['.mb_strtolower($title).']]';

        return $user->notes()
            ->where('id', '!=', $note->id)
            ->get(['id', 'title', 'content'])
            ->filter(fn (Note $other) => $other->content && str_contains(mb_strtolower($other->content), $pattern))
            ->map(fn (Note $other) => ['id' => $other->id, 'title' => $other->title])
            ->values()
            ->all();
    }

    /**
     * Find notes that mention the given note's title in their content
     * without using [[]] wiki-link syntax.
     */
    public function unlinkedMentions(User $user, Note $note): array
    {
        $title = $note->title;
        if (! $title) {
            return [];
        }

        $titleLower = mb_strtolower($title);
        $linkedPattern = '[['.$titleLower.']]';

        return $user->notes()
            ->where('id', '!=', $note->id)
            ->get(['id', 'title', 'content'])
            ->filter(function (Note $other) use ($titleLower, $linkedPattern) {
                if (! $other->content) {
                    return false;
                }

                $contentLower = mb_strtolower($other->content);

                // Contains the title text but NOT as a wiki-link
                return str_contains($contentLower, $titleLower) && ! str_contains($contentLower, $linkedPattern);
            })
            ->map(fn (Note $other) => ['id' => $other->id, 'title' => $other->title])
            ->values()
            ->all();
    }

    /**
     * Return all wiki-link connections between user notes for the graph view.
     *
     * @return array{nodes: array, edges: array}
     */
    public function graph(User $user): array
    {
        $notes = $user->notes()->get(['id', 'title', 'content']);
        $titleToId = [];
        foreach ($notes as $note) {
            if ($note->title) {
                $titleToId[mb_strtolower((string) $note->title)] = $note->id;
            }
        }

        $nodes = $notes->map(fn (Note $note) => [
            'id' => $note->id,
            'title' => $note->title ?? 'Untitled',
        ])->values()->all();

        $edges = [];
        foreach ($notes as $note) {
            if (! $note->content) {
                continue;
            }

            preg_match_all('/\[\[([^\]]+)\]\]/', (string) $note->content, $matches);
            foreach ($matches[1] as $match) {
                $targetTitle = mb_strtolower(explode('#', $match)[0]);
                if ($targetTitle && isset($titleToId[$targetTitle]) && $titleToId[$targetTitle] !== $note->id) {
                    $edges[] = [
                        'source' => $note->id,
                        'target' => $titleToId[$targetTitle],
                    ];
                }
            }
        }

        return ['nodes' => $nodes, 'edges' => $edges];
    }

    public function delete(Note $note): void
    {
        $note->delete();
    }
}
