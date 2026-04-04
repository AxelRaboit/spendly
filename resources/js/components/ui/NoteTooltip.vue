<script setup>
defineProps({ note: { type: String, required: true } });
</script>

<template>
    <span class="relative inline-flex items-center group/note" v-on:click.stop>
        <svg
            class="w-3 h-3 text-gray-600 hover:text-gray-400 transition-colors cursor-default"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
        </svg>
        <span class="pointer-events-none absolute bottom-full left-1/2 z-50 mb-2 w-56 -translate-x-1/2 rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-xs text-gray-200 shadow-xl opacity-0 transition-opacity duration-150 group-hover/note:opacity-100 whitespace-pre-wrap">
            {{ note }}
            <span class="absolute left-1/2 top-full -translate-x-1/2 border-4 border-transparent border-t-gray-700" />
        </span>
    </span>
</template>
