<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\HttpStatus;
use App\Filters\CategoryFilter;
use App\Http\Requests\DestroyCategoryRequest;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request, CategoryFilter $filter): Response
    {
        $categories = Category::query()
            ->where('user_id', $request->user()->id)
            ->where('is_system', false)
            ->filter($filter)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Categories/Form');
    }

    public function store(StoreCategoryRequest $request, CategoryService $categoryService): RedirectResponse
    {
        $categoryService->create($request->user(), $request->validated()['name']);

        return redirect()->route('categories.index')->with('success', 'Category created successfully.');
    }

    public function show(Request $request, Category $category): Response
    {
        $this->authorize('view', $category);

        return Inertia::render('Categories/Show', [
            'category' => $category,
        ]);
    }

    public function edit(Request $request, Category $category): Response
    {
        $this->authorize('update', $category);

        return Inertia::render('Categories/Form', [
            'category' => $category,
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category, CategoryService $categoryService): RedirectResponse
    {
        abort_if($category->is_system, HttpStatus::Forbidden->value);

        $categoryService->update($category, $request->validated()['name']);

        return redirect()->route('categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(DestroyCategoryRequest $request, Category $category, CategoryService $categoryService): RedirectResponse
    {
        abort_if($category->is_system, HttpStatus::Forbidden->value);

        $categoryService->delete($category);

        return redirect()->route('categories.index')->with('success', 'Category deleted successfully.');
    }
}
