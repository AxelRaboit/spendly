<script setup>
defineProps({
    label:    { type: String, required: true },
    required: { type: Boolean, default: false },
});
</script>

<template>
    <div>
        <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">
            {{ label }}<span v-if="required" class="text-red-500 ml-0.5">*</span>
        </label>
        <slot />
    </div>
</template>
