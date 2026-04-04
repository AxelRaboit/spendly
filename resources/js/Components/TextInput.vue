<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({ type: String });

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="w-full px-3 py-2 border border-gray-700 rounded-md bg-gray-700 text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
        v-model="model"
        ref="input"
        v-bind="$attrs"
    />
</template>
