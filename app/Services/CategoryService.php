<?php

declare(strict_types=1);

namespace App\Services;

use App\Filters\CategoryFilter;
use App\Models\Category;
use App\Models\User;
use App\Support\Text;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class CategoryService
{
    public function list(User $user, CategoryFilter $filter, ?string $walletId = null): LengthAwarePaginator
    {
        $query = Category::where('is_system', false)
            ->whereIn('wallet_id', $user->accessibleWallets()->select('id'))
            ->filter($filter);

        if ($walletId) {
            $query->where('wallet_id', $walletId);
        }

        return $query->with('wallet:id,name')
            ->paginate(10)
            ->withQueryString();
    }

    public function create(User $user, string $name, int $walletId): Category
    {
        $category = Category::create([
            'name' => Text::normalize($name),
            'user_id' => $user->id,
            'wallet_id' => $walletId,
        ]);

        Log::info('Category created', [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'wallet_id' => $walletId,
            'name' => $name,
        ]);

        return $category;
    }

    public function update(Category $category, string $name): Category
    {
        $category->update([
            'name' => Text::normalize($name),
        ]);

        Log::info('Category updated', [
            'category_id' => $category->id,
            'name' => $name,
        ]);

        return $category;
    }

    public function delete(Category $category): void
    {
        $categoryId = $category->id;
        $category->delete();

        Log::info('Category deleted', [
            'category_id' => $categoryId,
        ]);
    }
}
