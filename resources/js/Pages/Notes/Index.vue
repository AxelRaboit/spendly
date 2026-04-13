<script setup>
import { ref, computed, watch, onBeforeUnmount, provide, nextTick } from 'vue';
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
import { createWikiLinkExtension, useWikiLinkAutocomplete } from '@/composables/notes/useNoteWikiLinks';
import { createCalloutExtension } from '@/composables/notes/markedCallouts';
import { createEmbedExtension } from '@/composables/notes/markedEmbeds';
import { createCheckboxRenderer, resetCheckboxCounter, toggleCheckboxInContent } from '@/composables/notes/markedCheckboxes';
import { createHighlightRenderer } from '@/composables/notes/markedHighlight';
import { useNoteBacklinks } from '@/composables/notes/useNoteBacklinks';
import { useNoteOutline } from '@/composables/notes/useNoteOutline';
import { handleNoteShortcut } from '@/composables/notes/useNoteShortcuts';
import { useSlashCommands } from '@/composables/notes/useSlashCommands';
import { useNoteTemplates } from '@/composables/notes/useNoteTemplates';
import { BookTemplate, Save, CalendarDays } from 'lucide-vue-next';
import NoteGraph from '@/components/notes/NoteGraph.vue';
import { Link2, Unlink, List, GitBranch } from 'lucide-vue-next';
import '@css/notes/wiki-links.css';
import '@css/notes/callouts.css';
import '@css/notes/embeds.css';
import '@css/notes/checkboxes.css';
import '@css/notes/code-blocks.css';

marked.use({
    extensions: [createEmbedExtension(), createWikiLinkExtension(), createCalloutExtension()],
    renderer: {
        heading({ tokens, depth }) {
            const text = this.parser.parseInline(tokens);
            const slug = text.toLowerCase().replace(/<[^>]+>/g, '').replace(/[^\w]+/g, '-').replace(/(^-|-$)/g, '');
            return `<h${depth} id="${slug}">${text}</h${depth}>\n`;
        },
        ...createCheckboxRenderer(),
        ...createHighlightRenderer(),
    },
});
marked.setOptions({ breaks: true, gfm: true });

const { t } = useI18n();

const props = defineProps({
    notes: { type: Array, default: () => [] },
});

const {
    flatNotes,
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

// ── Backlinks & unlinked mentions ────────────────────────────────────────────
const { backlinks, unlinkedMentions, loadingBacklinks } = useNoteBacklinks(loadedNote);
const showBacklinks = ref(false);
const showUnlinked = ref(false);

// ── Wiki-links autocomplete ──────────────────────────────────────────────────
const {
    showSuggestions,
    suggestionIndex,
    suggestionPosition,
    filteredSuggestions,
    onInput: onWikiInput,
    onKeydown: onWikiKeydown,
    insertSuggestion,
    closeSuggestions,
} = useWikiLinkAutocomplete(flatNotes);

// ── Slash commands ───────────────────────────────────────────────────────────
const {
    showSlash,
    slashIndex,
    slashPosition,
    filteredCommands,
    onInput: onSlashInput,
    onKeydown: onSlashKeydown,
    insertCommand,
    closeSlash,
} = useSlashCommands();

const contentTextarea = ref(null);

function handleContentInput(event) {
    onWikiInput(event);
    onSlashInput(event);
}

function handleContentKeydown(event) {
    // Wiki-link autocomplete takes priority
    const selected = onWikiKeydown(event);
    if (selected) {
        const { newContent, newCursor } = insertSuggestion(
            contentTextarea.value,
            selected,
            editContent.value
        );
        editContent.value = newContent;
        nextTick(() => {
            contentTextarea.value.selectionStart = newCursor;
            contentTextarea.value.selectionEnd = newCursor;
        });
        return;
    }

    // Slash commands
    const slashCommand = onSlashKeydown(event);
    if (slashCommand) {
        const { newContent, newCursor } = insertCommand(
            contentTextarea.value,
            slashCommand,
            editContent.value
        );
        editContent.value = newContent;
        nextTick(() => {
            contentTextarea.value.selectionStart = newCursor;
            contentTextarea.value.selectionEnd = newCursor;
        });
        return;
    }

    // Keyboard shortcuts (Ctrl+B, Ctrl+I, etc.)
    const result = handleNoteShortcut(event, contentTextarea.value, editContent.value);
    if (result) {
        editContent.value = result.newContent;
        nextTick(() => {
            contentTextarea.value.selectionStart = result.cursorPos;
            contentTextarea.value.selectionEnd = result.cursorEnd ?? result.cursorPos;
        });
    }
}

function selectSuggestion(note) {
    const { newContent, newCursor } = insertSuggestion(
        contentTextarea.value,
        note,
        editContent.value
    );
    editContent.value = newContent;
    nextTick(() => {
        contentTextarea.value.focus();
        contentTextarea.value.selectionStart = newCursor;
        contentTextarea.value.selectionEnd = newCursor;
    });
}

function selectSlashCommand(command) {
    const { newContent, newCursor } = insertCommand(
        contentTextarea.value,
        command,
        editContent.value
    );
    editContent.value = newContent;
    nextTick(() => {
        contentTextarea.value.focus();
        contentTextarea.value.selectionStart = newCursor;
        contentTextarea.value.selectionEnd = newCursor;
    });
}

const previewContainer = ref(null);

function scrollToHeading(heading) {
    if (!previewContainer.value) return;
    const slug = heading.toLowerCase().replace(/[^\w]+/g, '-').replace(/(^-|-$)/g, '');
    const target = previewContainer.value.querySelector(`#${CSS.escape(slug)}`);
    if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

async function onPreviewClick(event) {
    // Wiki-link click → navigate to note (optionally to a heading)
    const link = event.target.closest('.wiki-link');
    if (link) {
        event.preventDefault();
        const title = link.dataset.noteTitle;
        const heading = link.dataset.heading;

        if (title) {
            const target = flatNotes.value.find(
                (note) => (note.title ?? '').toLowerCase() === title.toLowerCase()
            );
            if (target) {
                await selectNote(target.id);
                if (heading) {
                    nextTick(() => scrollToHeading(heading));
                }
            }
        } else if (heading) {
            scrollToHeading(heading);
        }
        return;
    }

    // Embed click → load content inline or navigate
    const embedHeader = event.target.closest('.note-embed-header');
    if (embedHeader) {
        const embed = embedHeader.closest('.note-embed');
        const title = embed?.dataset.embedTitle;
        if (!title) return;
        const target = flatNotes.value.find(
            (note) => (note.title ?? '').toLowerCase() === title.toLowerCase()
        );
        if (target) selectNote(target.id);
        return;
    }

    const embedPlaceholder = event.target.closest('.note-embed-placeholder');
    if (embedPlaceholder) {
        const embed = embedPlaceholder.closest('.note-embed');
        const title = embed?.dataset.embedTitle;
        if (!title) return;
        const target = flatNotes.value.find(
            (note) => (note.title ?? '').toLowerCase() === title.toLowerCase()
        );
        if (!target) return;

        const contentEl = embed.querySelector('.note-embed-content');
        contentEl.innerHTML = '<span class="note-embed-loading">Loading...</span>';

        try {
            const response = await axios.get(route('notes.show', target.id), {
                headers: { Accept: 'application/json' },
            });
            const noteContent = response.data.content ?? '';
            contentEl.innerHTML = DOMPurify.sanitize(marked.parse(noteContent), PURIFY_CONFIG);
        } catch {
            contentEl.innerHTML = '<span class="note-embed-placeholder">Failed to load</span>';
        }
    }

    // Checkbox click → toggle in source markdown
    const checkbox = event.target.closest('.task-checkbox');
    if (checkbox) {
        const index = parseInt(checkbox.dataset.checkboxIndex, 10);
        if (!isNaN(index)) {
            editContent.value = toggleCheckboxInContent(editContent.value, index);
        }
    }
}

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
    if (id) {
        mobilePanel.value = 'editor';
        showGraph.value = false;
    }
});

// ── Editor state ──────────────────────────────────────────────────────────────
const editTitle   = ref('');
const editContent = ref('');
const editTags    = ref([]);
const tagInput    = ref('');
const isPreview   = ref(false);
const showOutline    = ref(false);
const showGraph      = ref(false);
const showTemplates  = ref(false);

// ── Templates ────────────────────────────────────────────────────────────────
const { templates, addTemplate, deleteTemplate, getTemplate } = useNoteTemplates();

function saveAsTemplate() {
    const name = editTitle.value || 'Untitled template';
    addTemplate(name, editContent.value);
}

function applyTemplate(templateId) {
    const template = getTemplate(templateId);
    if (template) {
        editContent.value = template.content;
        showTemplates.value = false;
    }
}

// ── Daily note ───────────────────────────────────────────────────────────────
async function openDailyNote() {
    const today = new Date().toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    });
    const existing = flatNotes.value.find(
        (note) => note.title === today
    );
    if (existing) {
        await selectNote(existing.id);
    } else {
        const response = await axios.post(route('notes.store'), { parent_id: null });
        flatNotes.value.push(response.data);
        await selectNote(response.data.id);
        editTitle.value = today;
        editContent.value = `# ${today}\n\n`;
    }
}
const saveStatus  = ref('idle'); // idle | saving | saved

// ── Outline ──────────────────────────────────────────────────────────────────
const { headings } = useNoteOutline(editContent);
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

const PURIFY_CONFIG = {
    ADD_TAGS: ['svg', 'path', 'circle', 'line', 'polyline', 'polygon', 'rect'],
    ADD_ATTR: ['data-note-title', 'data-heading', 'data-embed-title', 'data-checkbox-index', 'viewBox', 'fill', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'd', 'cx', 'cy', 'r', 'x', 'y', 'x1', 'x2', 'y1', 'y2', 'rx', 'ry', 'width', 'height', 'points', 'xmlns'],
};

const renderedContent = computed(() => {
    resetCheckboxCounter();
    return DOMPurify.sanitize(marked.parse(editContent.value || ''), PURIFY_CONFIG);
});

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
                <!-- Graph view -->
                <div
                    v-if="showGraph"
                    class="flex-1 flex flex-col overflow-hidden"
                >
                    <div class="flex items-center justify-between px-4 py-2.5 border-b border-base/60 shrink-0">
                        <span class="text-sm font-medium text-primary">{{ t('notepad.graph') }}</span>
                        <button
                            class="text-xs px-2.5 py-1 rounded-md text-muted hover:text-primary hover:bg-surface-2 transition-colors"
                            v-on:click="showGraph = false"
                        >
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>
                    <div class="flex-1 overflow-hidden">
                        <NoteGraph v-on:select="selectNote($event); showGraph = false" />
                    </div>
                </div>

                <!-- Empty state -->
                <div
                    v-else-if="!selectedNoteId"
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

                            <!-- Outline toggle -->
                            <button
                                class="transition-colors"
                                :class="showOutline ? 'text-indigo-400' : 'text-muted hover:text-primary'"
                                :title="t('notepad.outline')"
                                v-on:click="showOutline = !showOutline"
                            >
                                <List class="w-4 h-4" />
                            </button>

                            <!-- Templates -->
                            <div class="relative">
                                <button
                                    class="transition-colors"
                                    :class="showTemplates ? 'text-indigo-400' : 'text-muted hover:text-primary'"
                                    :title="t('notepad.templates')"
                                    v-on:click="showTemplates = !showTemplates"
                                >
                                    <BookTemplate class="w-4 h-4" />
                                </button>
                                <div
                                    v-if="showTemplates"
                                    class="absolute right-0 top-8 z-50 w-56 max-h-64 overflow-auto rounded-lg border border-base/60 bg-surface shadow-lg"
                                >
                                    <div class="px-3 py-2 border-b border-base/60">
                                        <button
                                            class="flex items-center gap-1.5 text-xs text-indigo-400 hover:text-indigo-300 transition-colors"
                                            v-on:click="saveAsTemplate()"
                                        >
                                            <Save class="w-3 h-3" />
                                            {{ t('notepad.saveAsTemplate') }}
                                        </button>
                                    </div>
                                    <div v-if="templates.length === 0" class="px-3 py-3 text-xs text-muted/60 text-center">
                                        {{ t('notepad.noTemplates') }}
                                    </div>
                                    <div v-for="template in templates" :key="template.id" class="flex items-center justify-between px-3 py-2 hover:bg-surface-2 transition-colors">
                                        <button
                                            class="text-xs text-primary truncate flex-1 text-left"
                                            v-on:click="applyTemplate(template.id)"
                                        >
                                            {{ template.name }}
                                        </button>
                                        <button
                                            class="text-muted hover:text-rose-400 transition-colors ml-2 shrink-0"
                                            v-on:click="deleteTemplate(template.id)"
                                        >
                                            <X class="w-3 h-3" />
                                        </button>
                                    </div>
                                </div>
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

                    <!-- Outline panel -->
                    <div
                        v-if="showOutline && headings.length > 0"
                        class="border-b border-base/60 px-4 py-2 max-h-48 overflow-auto shrink-0"
                    >
                        <p class="text-xs font-medium text-muted uppercase tracking-wide mb-1.5">{{ t('notepad.outline') }}</p>
                        <button
                            v-for="(heading, index) in headings"
                            :key="index"
                            class="block w-full text-left text-xs text-primary hover:text-indigo-400 transition-colors truncate py-0.5"
                            :style="{ paddingLeft: (heading.level - 1) * 0.75 + 'rem' }"
                            v-on:click="isPreview = true; nextTick(() => scrollToHeading(heading.text))"
                        >
                            {{ heading.text }}
                        </button>
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
                        <div v-if="!isPreview" class="relative">
                            <textarea
                                ref="contentTextarea"
                                v-model="editContent"
                                :placeholder="t('notepad.contentPlaceholder')"
                                class="w-full min-h-[50vh] bg-transparent text-sm text-primary placeholder:text-muted/40 border-none outline-none focus:outline-none resize-none leading-7"
                                v-on:input="handleContentInput"
                                v-on:keydown="handleContentKeydown"
                                v-on:blur="closeSuggestions(); closeSlash()"
                            />

                            <!-- Wiki-link autocomplete dropdown -->
                            <div
                                v-if="showSuggestions && filteredSuggestions.length > 0"
                                class="absolute z-50 w-64 max-h-52 overflow-auto rounded-lg border border-base/60 bg-surface shadow-lg"
                                :style="{ top: suggestionPosition.top + 'px', left: suggestionPosition.left + 'px' }"
                            >
                                <button
                                    v-for="(note, index) in filteredSuggestions"
                                    :key="note.id"
                                    class="flex items-center gap-2 w-full px-3 py-2 text-left text-sm transition-colors"
                                    :class="index === suggestionIndex ? 'bg-indigo-600/20 text-indigo-400' : 'text-primary hover:bg-surface-2'"
                                    v-on:mousedown.prevent="selectSuggestion(note)"
                                >
                                    <span class="truncate">{{ note.title || t('notepad.untitled') }}</span>
                                </button>
                            </div>

                            <!-- Slash commands dropdown -->
                            <div
                                v-if="showSlash && filteredCommands.length > 0"
                                class="absolute z-50 w-56 max-h-64 overflow-auto rounded-lg border border-base/60 bg-surface shadow-lg"
                                :style="{ top: slashPosition.top + 'px', left: slashPosition.left + 'px' }"
                            >
                                <button
                                    v-for="(command, index) in filteredCommands"
                                    :key="command.id"
                                    class="flex items-center gap-2.5 w-full px-3 py-2 text-left text-sm transition-colors"
                                    :class="index === slashIndex ? 'bg-indigo-600/20 text-indigo-400' : 'text-primary hover:bg-surface-2'"
                                    v-on:mousedown.prevent="selectSlashCommand(command)"
                                >
                                    <span class="w-6 text-center text-xs font-mono text-muted shrink-0">{{ command.icon }}</span>
                                    <span class="truncate">{{ command.label }}</span>
                                </button>
                            </div>
                        </div>
                        <div
                            v-else
                            ref="previewContainer"
                            class="prose prose-sm dark:prose-invert max-w-none min-h-[50vh] text-primary wiki-link-preview"
                            v-on:click="onPreviewClick"
                            v-html="renderedContent"
                        />
                        <!-- Backlinks & Unlinked mentions -->
                        <div v-if="loadedNote?.title" class="border-t border-base/40 mt-4 pt-4 space-y-3">
                            <!-- Backlinks -->
                            <div>
                                <button
                                    class="flex items-center gap-1.5 text-xs font-medium text-muted hover:text-primary transition-colors"
                                    v-on:click="showBacklinks = !showBacklinks"
                                >
                                    <Link2 class="w-3.5 h-3.5" />
                                    {{ t('notepad.backlinks') }}
                                    <span class="text-muted/60">({{ backlinks.length }})</span>
                                </button>
                                <div v-if="showBacklinks" class="mt-2 space-y-1 pl-5">
                                    <div v-if="loadingBacklinks" class="text-xs text-muted">
                                        <Loader2 class="w-3 h-3 animate-spin inline" />
                                    </div>
                                    <p v-else-if="backlinks.length === 0" class="text-xs text-muted/60">
                                        {{ t('notepad.noBacklinks') }}
                                    </p>
                                    <button
                                        v-for="bl in backlinks"
                                        :key="bl.id"
                                        class="block text-xs text-indigo-400 hover:text-indigo-300 transition-colors truncate"
                                        v-on:click="selectNote(bl.id)"
                                    >
                                        {{ bl.title || t('notepad.untitled') }}
                                    </button>
                                </div>
                            </div>

                            <!-- Unlinked mentions -->
                            <div>
                                <button
                                    class="flex items-center gap-1.5 text-xs font-medium text-muted hover:text-primary transition-colors"
                                    v-on:click="showUnlinked = !showUnlinked"
                                >
                                    <Unlink class="w-3.5 h-3.5" />
                                    {{ t('notepad.unlinkedMentions') }}
                                    <span class="text-muted/60">({{ unlinkedMentions.length }})</span>
                                </button>
                                <div v-if="showUnlinked" class="mt-2 space-y-1 pl-5">
                                    <div v-if="loadingBacklinks" class="text-xs text-muted">
                                        <Loader2 class="w-3 h-3 animate-spin inline" />
                                    </div>
                                    <p v-else-if="unlinkedMentions.length === 0" class="text-xs text-muted/60">
                                        {{ t('notepad.noUnlinkedMentions') }}
                                    </p>
                                    <button
                                        v-for="um in unlinkedMentions"
                                        :key="um.id"
                                        class="block text-xs text-indigo-400 hover:text-indigo-300 transition-colors truncate"
                                        v-on:click="selectNote(um.id)"
                                    >
                                        {{ um.title || t('notepad.untitled') }}
                                    </button>
                                </div>
                            </div>
                        </div>
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
                    <div class="flex items-center gap-1">
                        <button
                            class="flex items-center justify-center w-6 h-6 rounded-md transition-colors"
                            :class="showGraph ? 'bg-indigo-600/20 text-indigo-400' : 'hover:bg-indigo-600/15 hover:text-indigo-400 text-muted'"
                            :title="t('notepad.graph')"
                            v-on:click="showGraph = !showGraph"
                        >
                            <GitBranch class="w-3.5 h-3.5" />
                        </button>
                        <button
                            class="flex items-center justify-center w-6 h-6 rounded-md hover:bg-indigo-600/15 hover:text-indigo-400 text-muted transition-colors"
                            :title="t('notepad.dailyNote')"
                            v-on:click="openDailyNote()"
                        >
                            <CalendarDays class="w-3.5 h-3.5" />
                        </button>
                        <button
                            class="flex items-center justify-center w-6 h-6 rounded-md hover:bg-indigo-600/15 hover:text-indigo-400 text-muted transition-colors"
                            :title="t('notepad.newNote')"
                            v-on:click="createNote(null)"
                        >
                            <Plus class="w-3.5 h-3.5" />
                        </button>
                    </div>
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
