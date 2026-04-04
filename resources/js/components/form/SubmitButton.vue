<script setup>
defineProps({
    disabled: { type: Boolean, default: false },
});
</script>

<template>
    <button
        type="submit"
        :disabled="disabled"
        class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-gray-900 disabled:opacity-50"
    >
        <slot />
    </button>
</template>
