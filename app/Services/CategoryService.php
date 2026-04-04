<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;

class CategoryService
{
    public function create(User $user, string $name): Category
    {
        try {
            $category = Category::create([
                'name' => $name,
                'user_id' => $user->id,
            ]);

            Log::info('Category created', [
                'user_id' => $user->id,
                'category_id' => $category->id,
                'name' => $name,
            ]);

            return $category;
        } catch (Exception $exception) {
            Log::error('Failed to create category', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function update(Category $category, string $name): Category
    {
        try {
            $category->update([
                'name' => $name,
            ]);

            Log::info('Category updated', [
                'category_id' => $category->id,
                'name' => $name,
            ]);

            return $category;
        } catch (Exception $exception) {
            Log::error('Failed to update category', [
                'category_id' => $category->id,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function delete(Category $category): void
    {
        try {
            $categoryId = $category->id;
            $category->delete();

            Log::info('Category deleted', [
                'category_id' => $categoryId,
            ]);
        } catch (Exception $exception) {
            Log::error('Failed to delete category', [
                'category_id' => $category->id,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
