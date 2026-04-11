<script setup>
import { ref, computed, watch, onBeforeUnmount, provide } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { marked } from 'marked';
import DOMPurify from 'dompurify';
import axios from 'axios';
import { Check, Loader2, Plus, Trash2, Eye, Pencil, X, FilePlus, Search, ChevronLeft } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppPageHeader from '@/components/ui/AppPageHeader.vue';
import ConfirmModal from '@/components/ui/ConfirmModal.vue';
import NoteTree from '@/components/notes/NoteTree.vue';
import { useNoteTree } from '@/composables/notes/useNoteTree';

marked.setOptions({ breaks: true, gfm: true });

const { t } = useI18n();

const props = defineProps({
    notes: { type: Array, default: () => [] },
});

const {
    tree,
    selectedNoteId,
    loadedNote,
    loadingNote,
    selectNote,
    createNote,
    deleteNote,
    updateNoteTitle,
    searchQuery,
    draggingId,
    dragOverId,
    onDragStart,
    onDragEnd,
    onDragOver,
    onDrop,
} = useNoteTree(props.notes);

// Provide drag state to all NoteTreeItem instances (avoids prop drilling in recursive tree)
provide('draggingId',  draggingId);
provide('dragOverId',  dragOverId);
provide('onDragStart', onDragStart);
provide('onDragEnd',   onDragEnd);
provide('onDragOver',  onDragOver);
provide('onDrop',      onDrop);

// ── Mobile panel state ────────────────────────────────────────────────────────
const mobilePanel = ref('list'); // 'list' | 'editor'

watch(selectedNoteId, (id) => {
    if (id) mobilePanel.value = 'editor';
});

// ── Editor state ──────────────────────────────────────────────────────────────
const editTitle   = ref('');
const editContent = ref('');
const editTags    = ref([]);
const tagInput    = ref('');
const isPreview   = ref(false);
const saveStatus  = ref('idle'); // idle | saving | saved
let saveTimer     = null;

watch(loadedNote, (note) => {
    if (!note) return;
    editTitle.value   = note.title   ?? '';
    editContent.value = note.content ?? '';
    editTags.value    = [...(note.tags ?? [])];
    isPreview.value   = false;
    saveStatus.value  = 'idle';
});

watch([editTitle, editContent, editTags], () => {
    if (!loadedNote.value) return;
    scheduleSave();
}, { deep: true });

function scheduleSave() {
    saveStatus.value = 'saving';
    clearTimeout(saveTimer);
    saveTimer = setTimeout(() => save(), 1500);
}

async function save() {
    if (!loadedNote.value) return;
    clearTimeout(saveTimer);
    saveStatus.value = 'saving';
    try {
        await axios.put(route('notes.update', loadedNote.value.id), {
            title:   editTitle.value,
            content: editContent.value,
            tags:    editTags.value,
        });
        saveStatus.value = 'saved';
        updateNoteTitle(loadedNote.value.id, editTitle.value);
    } catch {
        saveStatus.value = 'idle';
    }
}

onBeforeUnmount(() => clearTimeout(saveTimer));

const renderedContent = computed(() =>
    DOMPurify.sanitize(marked.parse(editContent.value || ''))
);

// ── Tags ──────────────────────────────────────────────────────────────────────
function addTag() {
    const tag = tagInput.value.trim().toLowerCase().replace(/\s+/g, '-');
    if (!tag || editTags.value.includes(tag)) {
        tagInput.value = '';
        return;
    }
    editTags.value.push(tag);
    tagInput.value = '';
}

function removeTag(tag) {
    editTags.value = editTags.value.filter((existingTag) => existingTag !== tag);
}

function onTagKeydown(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        addTag();
    } else if (event.key === 'Backspace' && tagInput.value === '' && editTags.value.length > 0) {
        editTags.value.pop();
    }
}

// ── Delete ────────────────────────────────────────────────────────────────────
const confirmDeleteId = ref(null);

async function confirmDelete() {
    const id = confirmDeleteId.value;
    confirmDeleteId.value = null;
    await deleteNote(id);
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="t('notepad.title')" />
        <template #header>
            <AppPageHeader :title="t('notepad.title')" />
        </template>

        <!-- Two-panel layout: editor left + tree sidebar right -->
        <div class="flex h-[calc(100vh-10rem)] rounded-xl overflow-hidden border border-base/60 bg-surface">
            <!-- ── Editor (left) ───────────────────────────────────────────── -->
            <div
                class="flex-1 flex-col min-w-0"
                :class="mobilePanel === 'editor' ? 'flex' : 'hidden md:flex'"
            >
                <!-- Empty state -->
                <div
                    v-if="!selectedNoteId"
                    class="flex-1 flex flex-col items-center justify-center gap-4 text-center p-8"
                >
                    <FilePlus class="w-10 h-10 text-muted/30" />
                    <p class="text-sm text-muted">{{ t('notepad.selectHint') }}</p>
                    <button
                        class="text-xs px-3 py-1.5 rounded-lg bg-indigo-600/15 text-indigo-400 hover:bg-indigo-600/25 transition-colors"
                        v-on:click="createNote(null)"
                    >
                        {{ t('notepad.newNote') }}
                    </button>
                </div>

                <!-- Loading -->
                <div v-else-if="loadingNote" class="flex-1 flex items-center justify-center">
                    <Loader2 class="w-5 h-5 text-muted animate-spin" />
                </div>

                <!-- Note editor -->
                <template v-else-if="loadedNote">
                    <!-- Editor toolbar -->
                    <div class="flex items-center justify-between gap-3 border-b border-base/60 px-4 py-2.5 shrink-0">
                        <div class="flex items-center gap-2">
                            <!-- Back to list (mobile only) -->
                            <button
                                class="md:hidden flex items-center justify-center w-7 h-7 rounded-md hover:bg-surface-2 text-muted transition-colors"
                                v-on:click="mobilePanel = 'list'"
                            >
                                <ChevronLeft class="w-4 h-4" />
                            </button>

                            <span
                                class="text-xs text-muted flex items-center gap-1.5 transition-opacity"
                                :class="saveStatus === 'idle' ? 'opacity-0' : 'opacity-100'"
                            >
                                <Loader2 v-if="saveStatus === 'saving'" class="w-3.5 h-3.5 animate-spin" />
                                <Check v-else class="w-3.5 h-3.5 text-emerald-500" />
                                <span class="hidden sm:inline">{{ saveStatus === 'saving' ? t('notepad.saving') : t('notepad.saved') }}</span>
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            <!-- Edit / Preview toggle -->
                            <div class="flex items-center rounded-lg border border-base overflow-hidden text-xs">
                                <button
                                    class="flex items-center gap-1.5 px-2.5 py-1.5 transition-colors"
                                    :class="!isPreview ? 'bg-indigo-600/20 text-indigo-400' : 'text-muted hover:text-primary'"
                                    v-on:click="isPreview = false"
                                >
                                    <Pencil class="w-3.5 h-3.5" />
                                    <span class="hidden sm:inline">{{ t('notepad.edit') }}</span>
                                </button>
                                <button
                                    class="flex items-center gap-1.5 px-2.5 py-1.5 transition-colors"
                                    :class="isPreview ? 'bg-indigo-600/20 text-indigo-400' : 'text-muted hover:text-primary'"
                                    v-on:click="isPreview = true"
                                >
                                    <Eye class="w-3.5 h-3.5" />
                                    <span class="hidden sm:inline">{{ t('notepad.preview') }}</span>
                                </button>
                            </div>

                            <!-- Delete -->
                            <button
                                class="text-muted hover:text-rose-400 transition-colors"
                                v-on:click="confirmDeleteId = loadedNote.id"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Content area -->
                    <div class="flex-1 overflow-auto px-8 py-6 space-y-4">
                        <!-- Title -->
                        <input
                            v-model="editTitle"
                            type="text"
                            :placeholder="t('notepad.titlePlaceholder')"
                            class="w-full bg-transparent text-2xl font-semibold text-primary placeholder:text-muted/40 border-none outline-none focus:outline-none"
                        >

                        <!-- Tags -->
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span
                                v-for="tag in editTags"
                                :key="tag"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-indigo-600/15 text-indigo-400"
                            >
                                #{{ tag }}
                                <button class="hover:text-rose-400 transition-colors" v-on:click="removeTag(tag)">
                                    <X class="w-3 h-3" />
                                </button>
                            </span>
                            <input
                                v-model="tagInput"
                                type="text"
                                :placeholder="t('notepad.tagPlaceholder')"
                                class="bg-transparent text-xs text-muted placeholder:text-muted/40 border-none outline-none focus:outline-none min-w-[100px]"
                                v-on:keydown="onTagKeydown"
                                v-on:blur="addTag"
                            >
                        </div>

                        <div class="border-t border-base/40" />

                        <!-- Editor / Preview -->
                        <textarea
                            v-if="!isPreview"
                            v-model="editContent"
                            :placeholder="t('notepad.contentPlaceholder')"
                            class="w-full min-h-[50vh] bg-transparent text-sm text-primary placeholder:text-muted/40 border-none outline-none focus:outline-none resize-none leading-7"
                        />
                        <div
                            v-else
                            class="prose prose-sm dark:prose-invert max-w-none min-h-[50vh] text-primary"
                            v-html="renderedContent"
                        />
                    </div>
                </template>
            </div>

            <!-- ── Sidebar (right) ─────────────────────────────────────────── -->
            <div
                class="flex-col shrink-0 bg-surface md:border-l border-base/60 w-full md:w-60"
                :class="mobilePanel === 'list' ? 'flex' : 'hidden md:flex'"
            >
                <!-- Sidebar header -->
                <div class="flex items-center justify-between px-3 py-2.5 border-b border-base/60 shrink-0">
                    <span class="text-xs font-medium text-muted uppercase tracking-wide">
                        {{ t('notepad.title') }}
                    </span>
                    <button
                        class="flex items-center justify-center w-6 h-6 rounded-md hover:bg-indigo-600/15 hover:text-indigo-400 text-muted transition-colors"
                        :title="t('notepad.newNote')"
                        v-on:click="createNote(null)"
                    >
                        <Plus class="w-3.5 h-3.5" />
                    </button>
                </div>

                <!-- Search -->
                <div class="px-2 py-2 border-b border-base/60 shrink-0">
                    <div class="relative">
                        <Search class="absolute left-2 top-1/2 -translate-y-1/2 w-3 h-3 text-muted/50 pointer-events-none" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            :placeholder="t('notepad.searchPlaceholder')"
                            class="w-full pl-6 pr-2 py-1.5 bg-surface-2 text-xs text-primary placeholder:text-muted/40 border border-base/60 rounded-md focus:border-indigo-500/60 focus:outline-none transition-colors"
                        >
                    </div>
                </div>

                <!-- Tree -->
                <div class="flex-1 overflow-auto px-1 flex flex-col">
                    <NoteTree
                        :nodes="tree"
                        :selected-id="selectedNoteId"
                        v-on:select="selectNote($event)"
                        v-on:create="createNote($event)"
                        v-on:delete="confirmDeleteId = $event"
                    />

                    <!-- Empty state -->
                    <div v-if="tree.length === 0" class="px-3 py-6 text-center">
                        <p class="text-xs text-muted/60">{{ t('notepad.empty') }}</p>
                    </div>

                    <!-- Root drop zone: empty space below the tree moves note to root -->
                    <div
                        class="flex-1 min-h-[2rem] rounded-md transition-colors"
                        :class="draggingId && dragOverId === null ? 'bg-indigo-600/10 ring-1 ring-inset ring-indigo-500/40' : ''"
                        v-on:dragover.prevent="onDragOver(null)"
                        v-on:drop.prevent="onDrop(null)"
                    />
                </div>
            </div>
        </div>

        <ConfirmModal
            :show="confirmDeleteId !== null"
            :message="t('notepad.confirmDelete')"
            :confirm-label="t('common.delete')"
            confirm-variant="danger"
            v-on:confirm="confirmDelete"
            v-on:cancel="confirmDeleteId = null"
        />
    </AuthenticatedLayout>
</template>
