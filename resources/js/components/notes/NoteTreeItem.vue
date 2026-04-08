<script setup>
import { ref, computed, inject } from 'vue';
import { ChevronRight, FileText, Folder, FolderOpen, Plus, Trash2 } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    node:       { type: Object, required: true },
    selectedId: { type: Number, default: null },
    depth:      { type: Number, default: 0 },
});

const emit = defineEmits(['select', 'create', 'delete']);

// Drag & drop state injected from the page (shared across recursive instances)
const draggingId  = inject('draggingId');
const dragOverId  = inject('dragOverId');
const onDragStart = inject('onDragStart');
const onDragEnd   = inject('onDragEnd');
const onDragOver  = inject('onDragOver');
const onDrop      = inject('onDrop');

const expanded = ref(true);
const hasChildren = computed(() => props.node.children?.length > 0);

const paddingLeft = computed(() => `${props.depth * 14 + 8}px`);

const isDragging  = computed(() => draggingId.value === props.node.id);
const isDragOver  = computed(() => dragOverId.value === props.node.id);

function handleDragStart(event) {
    event.dataTransfer.effectAllowed = 'move';
    onDragStart(props.node.id);
}

function handleDragOver(event) {
    event.preventDefault();
    event.stopPropagation(); // prevent bubbling to parent container
    event.dataTransfer.dropEffect = 'move';
    onDragOver(props.node.id);
}

function handleDrop(event) {
    event.preventDefault();
    event.stopPropagation();
    onDrop(props.node.id);
}
</script>

<template>
    <div>
        <!-- Row -->
        <div
            class="group flex items-center gap-1 py-[3px] pr-1 cursor-pointer rounded-md transition-colors select-none"
            :class="[
                selectedId === node.id && !isDragOver
                    ? 'bg-indigo-600/20 text-indigo-300'
                    : 'hover:bg-surface-2 text-secondary hover:text-primary',
                isDragging ? 'opacity-40' : '',
                isDragOver ? 'ring-1 ring-inset ring-indigo-500 bg-indigo-600/10 text-indigo-300' : '',
            ]"
            :style="{ paddingLeft }"
            draggable="true"
            v-on:dragstart="handleDragStart"
            v-on:dragend="onDragEnd"
            v-on:dragover="handleDragOver"
            v-on:drop="handleDrop"
            v-on:click="$emit('select', node.id)"
        >
            <!-- Chevron -->
            <button
                class="shrink-0 flex items-center justify-center w-4 h-4 rounded hover:bg-surface-3 transition-colors"
                v-on:click.stop="expanded = !expanded"
            >
                <ChevronRight
                    v-if="hasChildren"
                    class="w-3 h-3 transition-transform"
                    :class="expanded ? 'rotate-90' : ''"
                />
            </button>

            <!-- Icon -->
            <FolderOpen v-if="hasChildren && expanded" class="w-3.5 h-3.5 shrink-0 text-yellow-400/80" />
            <Folder v-else-if="hasChildren" class="w-3.5 h-3.5 shrink-0 text-yellow-400/60" />
            <FileText v-else class="w-3.5 h-3.5 shrink-0 text-muted/60" />

            <!-- Title -->
            <span class="text-xs truncate flex-1 min-w-0">
                {{ node.title || t('notepad.untitled') }}
            </span>

            <!-- Actions (visible on hover) -->
            <div class="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                <button
                    class="flex items-center justify-center w-5 h-5 rounded hover:bg-indigo-600/20 hover:text-indigo-400 transition-colors"
                    :title="t('notepad.newNote')"
                    v-on:click.stop="$emit('create', node.id)"
                >
                    <Plus class="w-3 h-3" />
                </button>
                <button
                    class="flex items-center justify-center w-5 h-5 rounded hover:bg-rose-600/20 hover:text-rose-400 transition-colors"
                    :title="t('common.delete')"
                    v-on:click.stop="$emit('delete', node.id)"
                >
                    <Trash2 class="w-3 h-3" />
                </button>
            </div>
        </div>

        <!-- Children (recursive) -->
        <div v-if="hasChildren && expanded">
            <NoteTreeItem
                v-for="child in node.children"
                :key="child.id"
                :node="child"
                :selected-id="selectedId"
                :depth="depth + 1"
                v-on:select="$emit('select', $event)"
                v-on:create="$emit('create', $event)"
                v-on:delete="$emit('delete', $event)"
            />
        </div>
    </div>
</template>
