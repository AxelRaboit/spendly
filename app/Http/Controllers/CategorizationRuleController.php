<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCategorizationRuleRequest;
use App\Models\CategorizationRule;
use App\Services\CategorizationRuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategorizationRuleController extends Controller
{
    public function __construct(private readonly CategorizationRuleService $ruleService) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('CategorizationRules/Index', [
            'rules' => $this->ruleService->listPaginated($user),
            'categories' => $user->categoryOptions(),
        ]);
    }

    public function suggest(Request $request): JsonResponse
    {
        return response()->json([
            'category_id' => $this->ruleService->suggest($request->user(), $request->input('description', '')),
        ]);
    }

    public function suggestBulk(Request $request): JsonResponse
    {
        return response()->json(
            $this->ruleService->suggestBulk($request->user(), $request->input('descriptions', []))
        );
    }

    public function update(UpdateCategorizationRuleRequest $updateCategorizationRuleRequest, CategorizationRule $categorizationRule): RedirectResponse
    {
        $this->authorize('update', $categorizationRule);

        $this->ruleService->updateCategory($categorizationRule, $updateCategorizationRuleRequest->validated()['category_id']);

        return back()->with('success', __('flash.rule.updated'));
    }

    public function destroy(Request $request, CategorizationRule $categorizationRule): RedirectResponse
    {
        $this->authorize('delete', $categorizationRule);

        $this->ruleService->delete($categorizationRule);

        return back()->with('success', __('flash.rule.deleted'));
    }
}
