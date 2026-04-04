<script setup>
const model = defineModel({ type: String, default: 'expense' });
</script>

<template>
    <div class="inline-flex rounded-lg border border-base overflow-hidden">
        <button
            type="button"
            :class="[
                'px-4 py-2 text-sm font-medium transition-colors',
                model === 'expense'
                    ? 'bg-rose-500/20 text-rose-400 border-r border-base'
                    : 'text-secondary hover:text-primary border-r border-base',
            ]"
            v-on:click="model = 'expense'"
        >
            ↓ Dépense
        </button>
        <button
            type="button"
            :class="[
                'px-4 py-2 text-sm font-medium transition-colors',
                model === 'income'
                    ? 'bg-emerald-500/20 text-emerald-400'
                    : 'text-secondary hover:text-primary',
            ]"
            v-on:click="model = 'income'"
        >
            ↑ Entrée
        </button>
    </div>
</template>
