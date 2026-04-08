<script setup>
import NoteTreeItem from '@/components/notes/NoteTreeItem.vue';

defineProps({
    nodes:      { type: Array,  default: () => [] },
    selectedId: { type: Number, default: null },
});

defineEmits(['select', 'create', 'delete']);
</script>

<template>
    <div class="py-1">
        <NoteTreeItem
            v-for="node in nodes"
            :key="node.id"
            :node="node"
            :selected-id="selectedId"
            :depth="0"
            v-on:select="$emit('select', $event)"
            v-on:create="$emit('create', $event)"
            v-on:delete="$emit('delete', $event)"
        />
    </div>
</template>
