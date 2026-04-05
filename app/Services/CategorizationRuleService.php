<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CategorizationRule;
use App\Models\User;
use App\Support\Text;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CategorizationRuleService
{
    public function learn(User $user, string $description, int $categoryId): void
    {
        $pattern = $this->normalize($description);

        if ($pattern === '') {
            return;
        }

        $rule = CategorizationRule::where('user_id', $user->id)
            ->where('pattern', $pattern)
            ->first();

        if (! $rule) {
            CategorizationRule::create([
                'user_id' => $user->id,
                'category_id' => $categoryId,
                'pattern' => $pattern,
                'hits' => 1,
            ]);

            return;
        }

        if ($rule->category_id === $categoryId) {
            $rule->increment('hits');
        } else {
            $rule->update(['category_id' => $categoryId, 'hits' => 1]);
        }
    }

    public function suggest(User $user, string $description): ?int
    {
        $pattern = $this->normalize($description);

        if ($pattern === '') {
            return null;
        }

        return CategorizationRule::where('user_id', $user->id)
            ->where('pattern', $pattern)
            ->value('category_id');
    }

    /**
     * @param  string[]  $descriptions
     * @return array<string, int> pattern => category_id
     */
    public function suggestBulk(User $user, array $descriptions): array
    {
        $patterns = array_filter(array_unique(array_map($this->normalize(...), $descriptions)));

        if ($patterns === []) {
            return [];
        }

        return CategorizationRule::where('user_id', $user->id)
            ->whereIn('pattern', $patterns)
            ->pluck('category_id', 'pattern')
            ->all();
    }

    public function listPaginated(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return CategorizationRule::where('user_id', $user->id)
            ->with('category:id,name')
            ->orderByDesc('hits')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function updateCategory(CategorizationRule $rule, int $categoryId): void
    {
        $rule->update(['category_id' => $categoryId]);
    }

    public function delete(CategorizationRule $rule): void
    {
        $rule->delete();
    }

    private function normalize(string $description): string
    {
        return mb_strtolower(Text::normalize($description));
    }
}
