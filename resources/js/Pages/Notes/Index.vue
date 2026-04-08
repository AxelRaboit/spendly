<script setup>
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { Search, Plus, Trash2, Clock, GripVertical } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppPageHeader from '@/components/ui/AppPageHeader.vue';
import AppButton from '@/components/ui/AppButton.vue';
import ConfirmModal from '@/components/ui/ConfirmModal.vue';
import { useDragDrop } from '@/composables/ui/useDragDrop';
import { useNoteFilters } from '@/composables/notes/useNoteFilters';
import { ref, watch } from 'vue';

const { t, d } = useI18n();

const props = defineProps({
    notes:   { type: Array,  default: () => [] },
    allTags: { type: Array,  default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const { filters: form, search, filterByTag, reset, hasFilters } = useNoteFilters(props.filters);

const localNotes = ref([...props.notes]);

watch(() => props.notes, (notes) => {
    localNotes.value = [...notes];
});

const { draggingId, dragOverId, onDragStart, onDragEnd, onDragOver, onDrop } = useDragDrop(
    localNotes,
    (ids) => router.patch(route('notes.reorder'), { ids }, { preserveScroll: true }),
);

const confirmNote = ref(null);

function createNote() {
    router.post(route('notes.store'));
}

function openNote(note) {
    router.visit(route('notes.show', note.id));
}

function deleteNote() {
    router.delete(route('notes.destroy', confirmNote.value.id), {
        onFinish: () => { confirmNote.value = null; },
    });
}

function formatDate(date) {
    return d(new Date(date), { day: '2-digit', month: 'short', year: 'numeric' });
}

function excerpt(content) {
    if (!content) return '';
    return content.length > 120 ? content.slice(0, 120) + '…' : content;
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <AppPageHeader :title="t('notepad.title')" />
        </template>

        <div class="space-y-4">
            <!-- Filters -->
            <div class="bg-surface border border-base/60 rounded-xl p-4 space-y-3">
                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <input
                            v-model="form.q"
                            type="text"
                            :placeholder="t('notepad.searchPlaceholder')"
                            class="w-full pl-9 pr-3 py-2 bg-surface-2 text-primary border border-base rounded-lg text-sm focus:border-indigo-500 focus:outline-none"
                            v-on:input="search"
                        >
                        <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted pointer-events-none" />
                    </div>
                    <AppButton v-if="hasFilters()" variant="secondary" size="sm" v-on:click="reset">
                        {{ t('search.reset') }}
                    </AppButton>
                </div>

                <div v-if="allTags.length > 0" class="flex flex-wrap gap-1.5">
                    <button
                        v-for="tag in allTags"
                        :key="tag"
                        class="px-2.5 py-0.5 rounded-full text-xs font-medium transition-colors border"
                        :class="form.tag === tag
                            ? 'bg-indigo-600/20 text-indigo-400 border-indigo-500/50'
                            : 'bg-surface-2 text-muted border-base hover:border-indigo-500/40 hover:text-primary'"
                        v-on:click="filterByTag(tag)"
                    >
                        #{{ tag }}
                    </button>
                </div>
            </div>

            <!-- Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="note in localNotes"
                    :key="note.id"
                    class="group relative overflow-hidden bg-surface border border-base/60 rounded-2xl p-5 transition-all cursor-grab active:cursor-grabbing select-none flex flex-col"
                    :class="{
                        'opacity-50 scale-95': draggingId === note.id,
                        'ring-2 ring-indigo-500/50 border-indigo-500/50': dragOverId === note.id,
                    }"
                    draggable="true"
                    v-on:dragstart="onDragStart($event, note)"
                    v-on:dragend="onDragEnd"
                    v-on:dragover="onDragOver($event, note)"
                    v-on:drop="onDrop($event, note)"
                    v-on:click="openNote(note)"
                >
                    <div class="pointer-events-none absolute -top-3 -right-3 h-16 w-16 rounded-full bg-indigo-500/10" />
                    <div class="pointer-events-none absolute -bottom-4 -left-4 h-20 w-20 rounded-full bg-indigo-500/5" />

                    <div class="absolute top-3 right-3 text-muted/40 pointer-events-none">
                        <GripVertical class="w-4 h-4" />
                    </div>

                    <div class="flex flex-col gap-2 pb-3 border-b border-base/40 flex-1">
                        <p class="text-base font-semibold text-primary pr-6 truncate">
                            {{ note.title || t('notepad.untitled') }}
                        </p>
                        <p class="text-xs text-secondary leading-relaxed line-clamp-3 whitespace-pre-wrap">
                            {{ excerpt(note.content) }}
                        </p>
                    </div>

                    <div class="flex items-center justify-between pt-3">
                        <div class="flex flex-wrap gap-1">
                            <span
                                v-for="tag in note.tags"
                                :key="tag"
                                class="text-xs px-1.5 py-0.5 rounded-full bg-indigo-600/15 text-indigo-400"
                                v-on:click.stop="filterByTag(tag)"
                                v-on:dragstart.prevent
                            >
                                #{{ tag }}
                            </span>
                        </div>

                        <div class="flex items-center gap-3 shrink-0">
                            <div class="flex items-center gap-1 text-xs text-muted">
                                <Clock class="w-3 h-3 shrink-0" />
                                <span>{{ formatDate(note.updated_at) }}</span>
                            </div>
                            <button
                                class="text-muted hover:text-rose-400 transition-colors"
                                v-on:click.stop="confirmNote = note"
                                v-on:dragstart.prevent
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <button
                    class="flex flex-col items-center justify-center gap-3 border-2 border-dashed border-base/60 rounded-xl p-5 min-h-[120px] hover:border-indigo-500/50 hover:bg-indigo-500/5 transition-all group"
                    v-on:click="createNote"
                >
                    <Plus class="w-6 h-6 text-muted group-hover:text-indigo-400 transition-colors" />
                    <span class="text-sm text-muted group-hover:text-indigo-400 transition-colors">{{ t('notepad.newNote') }}</span>
                </button>
            </div>
        </div>

        <ConfirmModal
            :show="confirmNote !== null"
            :message="t('notepad.confirmDelete')"
            :confirm-label="t('common.delete')"
            confirm-variant="danger"
            v-on:confirm="deleteNote"
            v-on:cancel="confirmNote = null"
        />
    </AuthenticatedLayout>
</template>
