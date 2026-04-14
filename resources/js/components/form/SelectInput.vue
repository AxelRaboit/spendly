<script setup>
const model = defineModel({ type: [String, Number] });
</script>

<template>
    <select
        v-model="model"
        class="w-full px-3 py-2 border border-line rounded-md bg-surface-3 text-primary focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
        v-bind="$attrs"
    >
        <slot />
    </select>
</template>
