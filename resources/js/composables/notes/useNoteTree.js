import { ref, computed } from 'vue';
import axios from 'axios';

export function useNoteTree(initialNotes) {
    const flatNotes = ref([...initialNotes]);

    const searchQuery = ref('');

    const tree = computed(() => {
        const query = searchQuery.value.trim().toLowerCase();
        if (!query) return buildTree(flatNotes.value);

        // Notes whose title matches
        const matchingIds = new Set(
            flatNotes.value.filter((note) => (note.title ?? '').toLowerCase().includes(query)).map((note) => note.id)
        );

        // Also include all ancestors so the tree structure is preserved
        const visibleIds = new Set(matchingIds);
        const noteById = Object.fromEntries(flatNotes.value.map((note) => [note.id, note]));
        matchingIds.forEach((id) => {
            let current = noteById[id];
            while (current?.parent_id) {
                visibleIds.add(current.parent_id);
                current = noteById[current.parent_id];
            }
        });

        return buildTree(flatNotes.value.filter((note) => visibleIds.has(note.id)));
    });

    function buildTree(notes) {
        const map = {};
        const roots = [];

        notes.forEach((note) => {
            map[note.id] = { ...note, children: [] };
        });

        notes.forEach((note) => {
            if (note.parent_id && map[note.parent_id]) {
                map[note.parent_id].children.push(map[note.id]);
            } else {
                roots.push(map[note.id]);
            }
        });

        return roots;
    }

    // ── Selection ─────────────────────────────────────────────────────────────
    const selectedNoteId = ref(null);
    const loadedNote = ref(null);
    const loadingNote = ref(false);

    async function selectNote(id) {
        if (selectedNoteId.value === id) return;
        selectedNoteId.value = id;
        loadedNote.value = null;
        loadingNote.value = true;
        try {
            const res = await axios.get(route('notes.show', id), {
                headers: { Accept: 'application/json' },
            });
            loadedNote.value = res.data;
        } finally {
            loadingNote.value = false;
        }
    }

    // ── CRUD ──────────────────────────────────────────────────────────────────
    async function createNote(parentId = null) {
        const res = await axios.post(route('notes.store'), { parent_id: parentId });
        flatNotes.value.push(res.data);
        await selectNote(res.data.id);
    }

    async function deleteNote(id) {
        await axios.delete(route('notes.destroy', id));
        const idsToRemove = collectDescendants(id);
        flatNotes.value = flatNotes.value.filter((note) => !idsToRemove.has(note.id));
        if (selectedNoteId.value && idsToRemove.has(selectedNoteId.value)) {
            selectedNoteId.value = null;
            loadedNote.value = null;
        }
    }

    function collectDescendants(rootId) {
        const ids = new Set([rootId]);
        const queue = [rootId];
        while (queue.length) {
            const parentId = queue.shift();
            flatNotes.value.forEach((note) => {
                if (note.parent_id === parentId) {
                    ids.add(note.id);
                    queue.push(note.id);
                }
            });
        }
        return ids;
    }

    async function moveNote(noteId, newParentId) {
        // Guard: no-op if already in the same parent
        const note = flatNotes.value.find((n) => n.id === noteId);
        if (note && note.parent_id === newParentId) return;

        // Guard: cannot move into own descendant
        if (newParentId !== null && collectDescendants(noteId).has(newParentId)) return;

        await axios.patch(route('notes.move', noteId), { parent_id: newParentId });
        const idx = flatNotes.value.findIndex((n) => n.id === noteId);
        if (idx !== -1) {
            flatNotes.value[idx] = { ...flatNotes.value[idx], parent_id: newParentId };
        }
    }

    function updateNoteTitle(id, title) {
        const idx = flatNotes.value.findIndex((note) => note.id === id);
        if (idx !== -1) {
            flatNotes.value[idx] = { ...flatNotes.value[idx], title };
        }
    }

    // ── Drag & drop state (shared via provide/inject) ─────────────────────────
    const draggingId = ref(null);
    const dragOverId = ref(null); // null = root drop zone

    function onDragStart(id) {
        draggingId.value = id;
    }

    function onDragEnd() {
        draggingId.value = null;
        dragOverId.value = null;
    }

    function onDragOver(id) {
        if (draggingId.value === null) return;
        if (draggingId.value === id) return;
        // Don't highlight own descendants as valid targets
        if (id !== null && collectDescendants(draggingId.value).has(id)) return;
        dragOverId.value = id;
    }

    async function onDrop(targetId) {
        if (draggingId.value === null) return;
        const sourceId = draggingId.value;
        onDragEnd();
        await moveNote(sourceId, targetId);
    }

    return {
        flatNotes,
        tree,
        searchQuery,
        selectedNoteId,
        loadedNote,
        loadingNote,
        selectNote,
        createNote,
        deleteNote,
        moveNote,
        updateNoteTitle,
        draggingId,
        dragOverId,
        onDragStart,
        onDragEnd,
        onDragOver,
        onDrop,
    };
}
