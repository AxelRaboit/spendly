<script setup>
defineProps({
    size: { type: String, default: 'sm' },
});
</script>

<template>
    <button
        type="button"
        :class="[
            'inline-flex items-center gap-1.5 text-indigo-400 hover:text-indigo-300 transition-colors',
            'text-xs',
        ]"
    >
        <slot />
    </button>
</template>
