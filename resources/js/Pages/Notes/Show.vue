<script setup>
import { ref, watch, computed, onBeforeUnmount } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { Trash2, Check, Loader2, X, Eye, Pencil } from 'lucide-vue-next';
import { marked } from 'marked';
import DOMPurify from 'dompurify';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppPageHeader from '@/components/ui/AppPageHeader.vue';
import ConfirmModal from '@/components/ui/ConfirmModal.vue';

marked.setOptions({ breaks: true, gfm: true });

const { t } = useI18n();

const props = defineProps({
    note: { type: Object, required: true },
});

const title   = ref(props.note.title   ?? '');
const content = ref(props.note.content ?? '');
const tags    = ref([...(props.note.tags ?? [])]);

const tagInput    = ref('');
const saveStatus  = ref('idle'); // idle | saving | saved
let saveTimer = null;

function scheduleSave() {
    saveStatus.value = 'saving';
    clearTimeout(saveTimer);
    saveTimer = setTimeout(() => save(), 1500);
}

function save() {
    clearTimeout(saveTimer);
    saveStatus.value = 'saving';
    router.put(route('notes.update', props.note.id), {
        title:   title.value,
        content: content.value,
        tags:    tags.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => { saveStatus.value = 'saved'; },
        onError:   () => { saveStatus.value = 'idle'; },
    });
}

watch([title, content, tags], () => {
    scheduleSave();
}, { deep: true });

function addTag() {
    const tag = tagInput.value.trim().toLowerCase().replace(/\s+/g, '-');
    if (!tag || tags.value.includes(tag)) {
        tagInput.value = '';
        return;
    }
    tags.value.push(tag);
    tagInput.value = '';
}

function removeTag(tag) {
    tags.value = tags.value.filter((existingTag) => existingTag !== tag);
}

function onTagKeydown(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        addTag();
    } else if (event.key === 'Backspace' && tagInput.value === '' && tags.value.length > 0) {
        tags.value.pop();
    }
}

onBeforeUnmount(() => clearTimeout(saveTimer));

const isPreview = ref(false);
const renderedContent = computed(() =>
    DOMPurify.sanitize(marked.parse(content.value || ''))
);

const showConfirmDelete = ref(false);

function deleteNote() {
    router.delete(route('notes.destroy', props.note.id));
}
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="note.title || t('notepad.untitled')" />
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <AppPageHeader
                    :crumbs="[
                        { label: t('notepad.title'), href: route('notes.index') },
                        { label: note.title || t('notepad.untitled') },
                    ]"
                />

                <div class="flex items-center gap-3 shrink-0">
                    <span
                        class="text-xs text-muted flex items-center gap-1.5 transition-opacity"
                        :class="saveStatus === 'idle' ? 'opacity-0' : 'opacity-100'"
                    >
                        <Loader2 v-if="saveStatus === 'saving'" class="w-3.5 h-3.5 animate-spin" />
                        <Check v-else class="w-3.5 h-3.5 text-emerald-500" />
                        {{ saveStatus === 'saving' ? t('notepad.saving') : t('notepad.saved') }}
                    </span>

                    <div class="flex items-center rounded-lg border border-line overflow-hidden text-xs">
                        <button
                            class="flex items-center gap-1.5 px-2.5 py-1.5 transition-colors"
                            :class="!isPreview ? 'bg-indigo-600/20 text-indigo-400' : 'text-muted hover:text-primary'"
                            v-on:click="isPreview = false"
                        >
                            <Pencil class="w-3.5 h-3.5" />
                            {{ t('notepad.edit') }}
                        </button>
                        <button
                            class="flex items-center gap-1.5 px-2.5 py-1.5 transition-colors"
                            :class="isPreview ? 'bg-indigo-600/20 text-indigo-400' : 'text-muted hover:text-primary'"
                            v-on:click="isPreview = true"
                        >
                            <Eye class="w-3.5 h-3.5" />
                            {{ t('notepad.preview') }}
                        </button>
                    </div>

                    <button
                        class="text-muted hover:text-rose-400 transition-colors"
                        v-on:click="showConfirmDelete = true"
                    >
                        <Trash2 class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </template>

        <div class="w-full">
            <div class="bg-surface border border-line rounded-2xl overflow-hidden">
                <div class="p-8 sm:p-12 space-y-4">
                    <input
                        v-model="title"
                        type="text"
                        :placeholder="t('notepad.titlePlaceholder')"
                        class="w-full bg-transparent text-2xl font-semibold text-primary placeholder:text-muted/40 border-none outline-none focus:outline-none"
                    >

                    <!-- Tags -->
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span
                            v-for="tag in tags"
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

                    <div class="border-t border-line/40" />

                    <textarea
                        v-if="!isPreview"
                        v-model="content"
                        :placeholder="t('notepad.contentPlaceholder')"
                        class="w-full min-h-[60vh] bg-transparent text-sm text-primary placeholder:text-muted/40 border-none outline-none focus:outline-none resize-none leading-7"
                    />
                    <div
                        v-else
                        class="prose prose-sm dark:prose-invert max-w-none min-h-[60vh] text-primary"
                        v-html="renderedContent"
                    />
                </div>
            </div>
        </div>

        <ConfirmModal
            :show="showConfirmDelete"
            :message="t('notepad.confirmDelete')"
            :confirm-label="t('common.delete')"
            confirm-variant="danger"
            v-on:confirm="deleteNote"
            v-on:cancel="showConfirmDelete = false"
        />
    </AuthenticatedLayout>
</template>
