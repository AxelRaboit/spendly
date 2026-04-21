<script setup>
defineProps({
    type: { type: String, default: 'button' },
    variant: { type: String, default: 'primary' },
    size: { type: String, default: 'md' },
    disabled: { type: Boolean, default: false },
});

const base = 'inline-flex items-center justify-center rounded-lg transition duration-150 ease-in-out focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed';

const variants = {
    primary: 'bg-indigo-600 hover:bg-indigo-700 text-white focus:ring-2 focus:ring-offset-2 focus:ring-offset-surface focus:ring-indigo-500 font-bold',
    secondary: 'bg-surface-3 hover:bg-surface-2 text-primary border border-line focus:ring-2 focus:ring-offset-2 focus:ring-offset-surface focus:ring-base font-bold',
    danger: 'bg-red-600 hover:bg-red-700 text-white focus:ring-2 focus:ring-offset-2 focus:ring-offset-surface focus:ring-red-500 font-bold',
    'danger-outline': 'bg-transparent hover:bg-rose-500/10 text-rose-400 border border-line focus:ring-2 focus:ring-offset-2 focus:ring-offset-surface focus:ring-rose-500 font-bold',
    ghost: 'bg-transparent hover:bg-surface-2 text-secondary hover:text-primary',
    link: 'bg-transparent text-muted hover:text-secondary underline p-0',
    icon: 'bg-transparent p-0',
};

const sizes = {
    sm: 'py-1.5 px-3 text-xs',
    md: 'py-2 px-4',
    none: '',
};
</script>

<template>
    <button
        :type="type"
        :disabled="disabled"
        :class="[base, variants[variant] || variants.primary, sizes[size] || sizes.md]"
    >
        <slot />
    </button>
</template>
