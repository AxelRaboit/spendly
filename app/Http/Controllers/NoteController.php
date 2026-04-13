<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\HttpStatus;
use App\Enums\PolicyAction;
use App\Http\Requests\NoteRequest;
use App\Http\Requests\ReorderRequest;
use App\Models\Note;
use App\Services\NoteService;
use App\Services\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NoteController extends Controller
{
    public function __construct(
        private readonly NoteService $noteService,
        private readonly PlanService $planService,
    ) {}

    public function index(Request $request): Response|RedirectResponse
    {
        if (! $this->planService->canNotes($request->user())) {
            return redirect()->route('plan.index')->with('info', __('flash.notes.proRequired'));
        }

        return Inertia::render('Notes/Index', [
            'notes' => $this->noteService->list($request->user()),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_if(! $this->planService->canNotes($request->user()), HttpStatus::Forbidden->value);

        $parentId = $request->input('parent_id') ? (int) $request->input('parent_id') : null;
        $note = $this->noteService->create($request->user(), $parentId);

        return response()->json($note);
    }

    public function show(Note $note, Request $request): JsonResponse|RedirectResponse
    {
        $this->authorize(PolicyAction::View->value, $note);

        if ($request->expectsJson()) {
            return response()->json($note);
        }

        return redirect()->route('notes.index');
    }

    public function update(NoteRequest $request, Note $note): JsonResponse
    {
        $this->authorize(PolicyAction::Update->value, $note);
        $updated = $this->noteService->update($note, $request->validated());

        return response()->json($updated);
    }

    public function move(Request $request, Note $note): JsonResponse
    {
        $this->authorize(PolicyAction::Update->value, $note);
        $parentId = $request->input('parent_id') ? (int) $request->input('parent_id') : null;
        $this->noteService->move($note, $parentId);

        return response()->json(['ok' => true]);
    }

    public function reorder(ReorderRequest $request): JsonResponse
    {
        abort_if(! $this->planService->canNotes($request->user()), HttpStatus::Forbidden->value);

        $this->noteService->reorder($request->user(), $request->validated()['ids']);

        return response()->json(['ok' => true]);
    }

    public function backlinks(Request $request, Note $note): JsonResponse
    {
        $this->authorize(PolicyAction::View->value, $note);

        return response()->json($this->noteService->backlinks($request->user(), $note));
    }

    public function graph(Request $request): JsonResponse
    {
        abort_if(! $this->planService->canNotes($request->user()), HttpStatus::Forbidden->value);

        return response()->json($this->noteService->graph($request->user()));
    }

    public function unlinkedMentions(Request $request, Note $note): JsonResponse
    {
        $this->authorize(PolicyAction::View->value, $note);

        return response()->json($this->noteService->unlinkedMentions($request->user(), $note));
    }

    public function destroy(Note $note): JsonResponse
    {
        $this->authorize(PolicyAction::Delete->value, $note);
        $this->noteService->delete($note);

        return response()->json(['ok' => true]);
    }
}
