<?php

namespace App\Http\Controllers;

use App\Http\Requests\DestroyCategoryRequest;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = auth()->user()->categories()->get();

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function create()
    {
        return Inertia::render('Categories/Create');
    }

    public function store(StoreCategoryRequest $request, CategoryService $categoryService)
    {
        $categoryService->create(auth()->user(), $request->validated()['name']);

        return redirect()->route('categories.index')->with('success', 'Category created successfully.');
    }

    public function show(Category $category)
    {
        $this->authorize('view', $category);

        return Inertia::render('Categories/Show', [
            'category' => $category,
        ]);
    }

    public function edit(Category $category)
    {
        $this->authorize('update', $category);

        return Inertia::render('Categories/Edit', [
            'category' => $category,
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category, CategoryService $categoryService)
    {
        $categoryService->update($category, $request->validated()['name']);

        return redirect()->route('categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(DestroyCategoryRequest $request, Category $category, CategoryService $categoryService)
    {
        $categoryService->delete($category);

        return redirect()->route('categories.index')->with('success', 'Category deleted successfully.');
    }
}
