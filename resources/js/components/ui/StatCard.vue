<script setup>
defineProps({
    label: { type: String, required: true },
    valueClass: { type: String, default: 'text-primary' },
});
</script>

<template>
    <div class="bg-surface border border-line/60 rounded-xl p-4">
        <p class="text-xs text-muted uppercase tracking-wide mb-1">{{ label }}</p>
        <p class="text-xl font-bold font-mono" :class="valueClass">
            <slot />
        </p>
    </div>
</template>
