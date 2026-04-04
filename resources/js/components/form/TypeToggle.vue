<script setup>
const model = defineModel({ type: String, default: 'expense' });
</script>

<template>
    <div class="inline-flex rounded-lg border border-gray-700 overflow-hidden">
        <button
            type="button"
            :class="[
                'px-4 py-2 text-sm font-medium transition-colors',
                model === 'expense'
                    ? 'bg-rose-500/20 text-rose-400 border-r border-gray-700'
                    : 'text-gray-400 hover:text-gray-200 border-r border-gray-700',
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
                    : 'text-gray-400 hover:text-gray-200',
            ]"
            v-on:click="model = 'income'"
        >
            ↑ Entrée
        </button>
    </div>
</template>
