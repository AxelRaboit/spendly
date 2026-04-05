<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\BudgetPresetRequest;
use App\Http\Requests\ReorderRequest;
use App\Models\BudgetPreset;
use App\Services\BudgetPresetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetPresetController extends Controller
{
    public function __construct(private readonly BudgetPresetService $presetService) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->presetService->list($request->user()));
    }

    public function store(BudgetPresetRequest $request): JsonResponse
    {
        $preset = $this->presetService->create($request->user(), $request->validated());

        return response()->json($preset->only('id', 'label', 'type', 'planned_amount', 'position'), 201);
    }

    public function update(BudgetPresetRequest $request, BudgetPreset $budgetPreset): JsonResponse
    {
        abort_if($budgetPreset->user_id !== $request->user()->id, 403);

        $preset = $this->presetService->update($budgetPreset, $request->validated());

        return response()->json($preset->only('id', 'label', 'type', 'planned_amount', 'position'));
    }

    public function destroy(Request $request, BudgetPreset $budgetPreset): JsonResponse
    {
        abort_if($budgetPreset->user_id !== $request->user()->id, 403);

        $this->presetService->delete($budgetPreset);

        return response()->json(null, 204);
    }

    public function reorder(ReorderRequest $request): JsonResponse
    {
        $this->presetService->reorder($request->user(), $request->validated()['ids']);

        return response()->json(null, 204);
    }
}
