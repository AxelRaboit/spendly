<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\HttpStatus;
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

    public function store(BudgetPresetRequest $budgetPresetRequest): JsonResponse
    {
        $preset = $this->presetService->create($budgetPresetRequest->user(), $budgetPresetRequest->validated());

        return response()->json($preset->only('id', 'label', 'type', 'planned_amount', 'position'), HttpStatus::Created->value);
    }

    public function update(BudgetPresetRequest $budgetPresetRequest, BudgetPreset $budgetPreset): JsonResponse
    {
        abort_if($budgetPreset->user_id !== $budgetPresetRequest->user()->id, HttpStatus::Forbidden->value);

        $preset = $this->presetService->update($budgetPreset, $budgetPresetRequest->validated());

        return response()->json($preset->only('id', 'label', 'type', 'planned_amount', 'position'));
    }

    public function destroy(Request $request, BudgetPreset $budgetPreset): JsonResponse
    {
        abort_if($budgetPreset->user_id !== $request->user()->id, HttpStatus::Forbidden->value);

        $this->presetService->delete($budgetPreset);

        return response()->json(null, HttpStatus::NoContent->value);
    }

    public function reorder(ReorderRequest $reorderRequest): JsonResponse
    {
        $this->presetService->reorder($reorderRequest->user(), $reorderRequest->validated()['ids']);

        return response()->json(null, HttpStatus::NoContent->value);
    }
}
