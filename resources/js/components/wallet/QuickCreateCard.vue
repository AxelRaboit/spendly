<script setup>
import { useCurrency } from '@/composables/core/useCurrency';
import { useI18n } from 'vue-i18n';

defineProps({
    name: { type: String, required: true },
    disabled: { type: Boolean, default: false },
});

defineEmits(['create']);

const { t } = useI18n();
const { fmt } = useCurrency();
</script>

<template>
    <button
        class="relative overflow-hidden border-2 border-dashed border-base/60 rounded-2xl p-5 text-left transition-all hover:border-indigo-500/50 hover:bg-indigo-500/5 group"
        :disabled="disabled"
        v-on:click="$emit('create', name)"
    >
        <div class="pointer-events-none absolute -top-3 -right-3 h-16 w-16 rounded-full bg-indigo-500/5" />
        <div class="pointer-events-none absolute -bottom-4 -left-4 h-20 w-20 rounded-full bg-indigo-500/5" />

        <div class="flex flex-col gap-3 pb-3 border-b border-base/40">
            <span class="text-base font-semibold text-muted group-hover:text-indigo-400 transition-colors">
                {{ name }}
            </span>
            <div>
                <p class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t('wallets.colBalance') }}</p>
                <p class="text-2xl font-bold font-mono text-muted">{{ fmt(0) }}</p>
                <p class="text-xs text-muted mt-0.5">{{ t('wallets.startBalance') }} {{ fmt(0) }}</p>
            </div>
        </div>

        <div class="pt-3 flex items-center gap-2 text-xs text-muted group-hover:text-indigo-400 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            {{ t('wallets.quickHint') }}
        </div>
    </button>
</template>
