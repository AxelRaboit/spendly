<script setup>
defineProps({
    label: { type: String, required: true },
});
</script>

<template>
    <div>
        <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ label }}</label>
        <slot />
    </div>
</template>
