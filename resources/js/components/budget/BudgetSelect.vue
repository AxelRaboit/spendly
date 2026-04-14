<script setup>
defineOptions({ inheritAttrs: false });
const model = defineModel({ type: [String, Number] });
defineProps({
    error: { type: Boolean, default: false },
});
</script>

<template>
    <select
        v-model="model"
        v-bind="$attrs"
        :class="[
            'w-full bg-surface-3 text-primary rounded px-2 py-1 text-sm border focus:border-indigo-500 focus:outline-none',
            error ? 'border-rose-500' : 'border-line-strong',
        ]"
    >
        <slot />
    </select>
</template>
