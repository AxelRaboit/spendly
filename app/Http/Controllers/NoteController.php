<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PolicyAction;
use App\Http\Requests\NoteRequest;
use App\Http\Requests\ReorderRequest;
use App\Models\Note;
use App\Services\NoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NoteController extends Controller
{
    public function __construct(private readonly NoteService $noteService) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['q', 'tag']);

        return Inertia::render('Notes/Index', [
            'notes' => $this->noteService->list($request->user(), $filters),
            'allTags' => $this->noteService->allTags($request->user()),
            'filters' => $filters,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $note = $this->noteService->create($request->user());

        return redirect()->route('notes.show', $note);
    }

    public function show(Note $note): Response
    {
        $this->authorize(PolicyAction::View->value, $note);

        return Inertia::render('Notes/Show', [
            'note' => $note,
        ]);
    }

    public function update(NoteRequest $request, Note $note): RedirectResponse
    {
        $this->authorize(PolicyAction::Update->value, $note);
        $this->noteService->update($note, $request->validated());

        return back()->with('success', __('flash.note.updated'));
    }

    public function reorder(ReorderRequest $request): RedirectResponse
    {
        $this->noteService->reorder($request->user(), $request->validated()['ids']);

        return back();
    }

    public function destroy(Note $note): RedirectResponse
    {
        $this->authorize(PolicyAction::Delete->value, $note);
        $this->noteService->delete($note);

        return redirect()->route('notes.index')->with('success', __('flash.note.deleted'));
    }
}
