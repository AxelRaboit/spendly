<script setup>
import AppTooltip from '@/components/ui/AppTooltip.vue';
import { useCurrency } from '@/composables/core/useCurrency';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();
const { fmt } = useCurrency();

function fmtDay(date) {
    if (!date) return '';
    return new Intl.DateTimeFormat(locale.value, { day: 'numeric', month: 'short', timeZone: 'UTC' })
        .format(new Date(date));
}

const props = defineProps({
    open:         { type: Boolean, required: true },
    item:         { type: Object,  default: null },
    loading:      { type: Boolean, default: false },
    transactions: { type: Array,   default: () => [] },
});

const emit = defineEmits(['close', 'edit', 'delete']);

const total = computed(() => props.transactions.reduce((s, tx) => s + tx.amount, 0));
</script>

<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="translate-x-full opacity-0"
        enter-to-class="translate-x-0 opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="translate-x-0 opacity-100"
        leave-to-class="translate-x-full opacity-0"
    >
        <div v-if="open" class="fixed inset-y-0 right-0 z-50 flex">
            <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" v-on:click="emit('close')" />

            <div
                class="relative ml-auto w-[480px] bg-surface border-l border-base shadow-2xl flex flex-col"
                v-on:keydown.esc="emit('close')"
            >
                <div class="flex items-center justify-between px-6 py-4 border-b border-base">
                    <div>
                        <h3 class="font-semibold text-primary">{{ item?.label }}</h3>
                        <p class="text-xs text-secondary mt-0.5">{{ t('budgets.detailPanel.subtitle') }}</p>
                    </div>
                    <button class="text-secondary hover:text-primary transition-colors" v-on:click="emit('close')">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-4">
                    <div v-if="loading" class="flex items-center justify-center py-12 text-muted text-sm">
                        {{ t('budgets.detailPanel.loading') }}
                    </div>
                    <template v-else-if="transactions.length">
                        <div
                            v-for="tx in transactions"
                            :key="tx.id"
                            class="group flex items-center justify-between py-3 border-b border-subtle last:border-0"
                        >
                            <div class="min-w-0">
                                <p class="text-sm text-primary truncate">{{ tx.description || '—' }}</p>
                                <p class="text-xs text-muted mt-0.5">{{ fmtDay(tx.date) }}</p>
                            </div>
                            <div class="flex items-center gap-2 ml-4 shrink-0">
                                <div v-if="!tx.transfer_id" class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <AppTooltip :text="t('budgets.detailPanel.edit')">
                                        <button class="text-muted hover:text-sky-400 transition-colors" v-on:click="emit('edit', tx)">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </button>
                                    </AppTooltip>
                                    <AppTooltip :text="t('budgets.detailPanel.delete')">
                                        <button class="text-muted hover:text-rose-400 transition-colors" v-on:click="emit('delete', tx)">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </AppTooltip>
                                </div>
                                <span
                                    class="font-mono text-sm font-medium"
                                    :class="tx.type === 'income' ? 'text-emerald-400' : 'text-rose-400'"
                                >
                                    {{ fmt(tx.type === 'income' ? tx.amount : -tx.amount, true) }}
                                </span>
                            </div>
                        </div>
                    </template>
                    <p v-else class="text-sm text-muted text-center py-12">{{ t('budgets.detailPanel.none') }}</p>
                </div>

                <div class="px-6 py-4 border-t border-base flex items-center justify-between">
                    <span class="text-sm text-secondary">{{ t('budgets.detailPanel.total') }}</span>
                    <span class="font-mono font-bold text-primary">{{ fmt(total) }}</span>
                </div>
            </div>
        </div>
    </Transition>
</template>
