<script setup>
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Rechercher…' },
});

const emit = defineEmits(['update:modelValue']);

const search = ref(props.modelValue);

let timeout = null;

watch(search, (value) => {
    emit('update:modelValue', value);
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get(
            window.location.pathname,
            value ? { search: value } : {},
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 300);
});
</script>

<template>
    <div class="relative">
        <input
            v-model="search"
            type="text"
            :placeholder="placeholder"
            class="w-full px-3 py-2 pl-9 border border-base rounded-md bg-surface-2 text-primary placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
        >
        <svg class="absolute left-2.5 top-2.5 h-4 w-4 text-muted pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" />
        </svg>
    </div>
</template>
