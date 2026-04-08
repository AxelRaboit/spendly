<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\HttpStatus;
use App\Enums\PolicyAction;
use App\Filters\CategoryFilter;
use App\Http\Requests\DestroyCategoryRequest;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request, CategoryFilter $filter, CategoryService $categoryService): Response
    {
        $user = $request->user();

        return Inertia::render('Categories/Index', [
            'categories' => $categoryService->list($user, $filter, $request->input('wallet_id')),
            'wallets' => $user->walletOptions(),
            'filters' => $request->only('search', 'wallet_id'),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Categories/Form', [
            'wallets' => $request->user()->walletOptions(),
        ]);
    }

    public function store(StoreCategoryRequest $storeCategoryRequest, CategoryService $categoryService): RedirectResponse
    {
        $data = $storeCategoryRequest->validated();
        $categoryService->create($storeCategoryRequest->user(), $data['name'], (int) $data['wallet_id']);

        return redirect()->route('categories.index')->with('success', __('flash.category.created'));
    }

    public function storeQuick(StoreCategoryRequest $storeCategoryRequest, CategoryService $categoryService): JsonResponse
    {
        $data = $storeCategoryRequest->validated();

        $category = $categoryService->create($storeCategoryRequest->user(), $data['name'], (int) $data['wallet_id']);

        return response()->json(['id' => $category->id, 'name' => $category->name], HttpStatus::Created->value);
    }

    public function show(Request $request, Category $category): Response
    {
        $this->authorize(PolicyAction::View->value, $category);

        return Inertia::render('Categories/Show', [
            'category' => $category,
        ]);
    }

    public function edit(Request $request, Category $category): Response
    {
        $this->authorize(PolicyAction::Update->value, $category);

        return Inertia::render('Categories/Form', [
            'category' => $category,
            'wallets' => $request->user()->walletOptions(),
        ]);
    }

    public function update(UpdateCategoryRequest $updateCategoryRequest, Category $category, CategoryService $categoryService): RedirectResponse
    {
        abort_if($category->is_system, HttpStatus::Forbidden->value);

        $categoryService->update($category, $updateCategoryRequest->validated()['name']);

        return redirect()->route('categories.index')->with('success', __('flash.category.updated'));
    }

    public function destroy(DestroyCategoryRequest $destroyCategoryRequest, Category $category, CategoryService $categoryService): RedirectResponse
    {
        abort_if($category->is_system, HttpStatus::Forbidden->value);

        $categoryService->delete($category);

        return redirect()->route('categories.index')->with('success', __('flash.category.deleted'));
    }
}
