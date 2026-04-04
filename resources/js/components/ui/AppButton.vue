<script setup>
defineProps({
    type: { type: String, default: 'button' },
    variant: { type: String, default: 'primary' },
    size: { type: String, default: 'md' },
    disabled: { type: Boolean, default: false },
});
</script>

<template>
    <button
        :type="type"
        :disabled="disabled"
        :class="[
            'inline-flex items-center font-bold rounded transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-900 disabled:opacity-50',
            size === 'sm' ? 'py-1.5 px-3 text-xs' : 'py-2 px-4',
            variant === 'secondary'
                ? 'bg-gray-600 hover:bg-gray-500 text-white focus:ring-gray-500'
                : variant === 'danger'
                    ? 'bg-red-600 hover:bg-red-700 text-white focus:ring-red-500'
                    : 'bg-indigo-600 hover:bg-indigo-700 text-white focus:ring-indigo-500',
        ]"
    >
        <slot />
    </button>
</template>
