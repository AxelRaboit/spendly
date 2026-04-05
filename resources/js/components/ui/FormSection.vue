<script setup>
defineProps({
    borderClass: { type: String, default: 'border-base/60' },
});
</script>

<template>
    <div class="bg-surface border rounded-2xl p-6 shadow-sm" :class="borderClass">
        <slot />
    </div>
</template>
