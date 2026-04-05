<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BudgetPreset;
use App\Models\User;
use App\Support\Text;
use Illuminate\Database\Eloquent\Collection;

class BudgetPresetService
{
    public function list(User $user): Collection
    {
        return BudgetPreset::where('user_id', $user->id)
            ->orderBy('position')
            ->get(['id', 'label', 'type', 'planned_amount', 'position']);
    }

    public function create(User $user, array $data): BudgetPreset
    {
        $position = BudgetPreset::where('user_id', $user->id)->max('position') ?? -1;

        return BudgetPreset::create([
            'user_id' => $user->id,
            'label' => Text::normalize($data['label']),
            'type' => $data['type'],
            'planned_amount' => $data['planned_amount'] ?? 0,
            'position' => $position + 1,
        ]);
    }

    public function update(BudgetPreset $preset, array $data): BudgetPreset
    {
        $preset->update([
            'label' => Text::normalize($data['label']),
            'type' => $data['type'],
            'planned_amount' => $data['planned_amount'] ?? 0,
        ]);

        return $preset;
    }

    public function delete(BudgetPreset $preset): void
    {
        $preset->delete();
    }

    /** @param array<int, int> $ids */
    public function reorder(User $user, array $ids): void
    {
        foreach ($ids as $position => $id) {
            BudgetPreset::where('id', $id)
                ->where('user_id', $user->id)
                ->update(['position' => $position]);
        }
    }
}
