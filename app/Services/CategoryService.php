<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\User;
use App\Support\Text;
use Illuminate\Support\Facades\Log;

class CategoryService
{
    public function create(User $user, string $name): Category
    {
        $category = Category::create([
            'name' => Text::normalize($name),
            'user_id' => $user->id,
        ]);

        Log::info('Category created', [
            'user_id' => $user->id,
            'category_id' => $category->id,
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
