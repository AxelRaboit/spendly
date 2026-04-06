<script setup>
defineProps({
    variant: {
        type: String,
        default: 'default',
        validator: (v) => ['default', 'amber', 'indigo', 'rose', 'green'].includes(v),
    },
});

const variantClasses = {
    default: 'bg-surface-3 text-secondary',
    amber:   'bg-amber-500/15 text-amber-400',
    indigo:  'bg-indigo-600/15 text-indigo-400',
    rose:    'bg-rose-600/15 text-rose-400',
    green:   'bg-green-600/15 text-green-400',
};
</script>

<template>
    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="variantClasses[variant]">
        <slot />
    </span>
</template>
