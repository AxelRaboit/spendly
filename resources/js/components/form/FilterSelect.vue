<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    param: { type: String, required: true },
    placeholder: { type: String, default: 'Tous' },
});

function onChange(event) {
    const value = event.target.value;
    const params = new URLSearchParams(window.location.search);

    if (value) {
        params.set(props.param, value);
    } else {
        params.delete(props.param);
    }

    router.get(
        window.location.pathname,
        Object.fromEntries(params),
        { preserveState: true, preserveScroll: true, replace: true },
    );
}
</script>

<template>
    <select
        :value="modelValue || ''"
        class="px-3 py-2 border border-line rounded-md bg-surface-2 text-primary focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm"
        v-on:change="onChange"
    >
        <option value="">{{ placeholder }}</option>
        <slot />
    </select>
</template>
