<script setup>
import { X, Pencil, Trash2, Paperclip, Plus } from 'lucide-vue-next';
import AppTooltip from '@/components/ui/AppTooltip.vue';
import { useCurrency } from '@/composables/core/useCurrency';
import { useFmtDate } from '@/composables/core/useFmtDate';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { TransactionType } from '@/enums/TransactionType';

const { t } = useI18n();
const { fmt } = useCurrency();
const { fmtDay } = useFmtDate();

const props = defineProps({
    open:         { type: Boolean, required: true },
    item:         { type: Object,  default: null },
    loading:      { type: Boolean, default: false },
    transactions: { type: Array,   default: () => [] },
});

const emit = defineEmits(['close', 'edit', 'delete', 'add']);

const total = computed(() => props.transactions.reduce((s, tx) => s + tx.amount, 0));

const previewUrl = ref(null);

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
        <div v-if="open" class="fixed inset-y-0 right-0 z-50 flex w-full sm:w-auto">
            <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" v-on:click="emit('close')" />

            <div
                data-tour="detail-panel"
                class="relative ml-auto w-full sm:max-w-sm bg-surface border-l border-line shadow-2xl flex flex-col"
                v-on:keydown.esc="emit('close')"
            >
                <div class="px-6 pt-4 pb-3 border-b border-line space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 data-tour="detail-panel-header" class="font-semibold text-primary">{{ item?.label }}</h3>
                            <p class="text-xs text-secondary mt-0.5">{{ t('budgets.detailPanel.subtitle') }}</p>
                        </div>
                        <button class="text-secondary hover:text-primary transition-colors" v-on:click="emit('close')">
                            <X class="w-5 h-5" />
                        </button>
                    </div>
                    <button
                        class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium transition-colors"
                        v-on:click="emit('add')"
                    >
                        <Plus class="w-4 h-4" />
                        {{ t('budgets.detailPanel.addTransaction') }}
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
                            class="group flex items-center justify-between py-3 border-b border-surface-2 last:border-0"
                        >
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm text-primary truncate">{{ tx.description || '—' }}</p>
                                    <span v-if="tx.split_id" class="rounded-full bg-badge-warning-bg px-1.5 py-0.5 text-2xs font-medium text-badge-warning-text shrink-0">{{ t('search.splitBadge') }}</span>
                                </div>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <p class="text-xs text-muted">{{ fmtDay(tx.date) }}</p>
                                    <button v-if="tx.attachment_url" class="text-indigo-400 hover:text-indigo-300 transition-colors" v-on:click="previewUrl = tx.attachment_url">
                                        <Paperclip class="w-3 h-3" />
                                    </button>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 ml-4 shrink-0">
                                <div v-if="!tx.transfer_id && !tx.split_id" class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <AppTooltip :text="t('budgets.detailPanel.edit')">
                                        <button class="text-muted hover:text-sky-400 transition-colors" v-on:click="emit('edit', tx)">
                                            <Pencil class="w-3.5 h-3.5" />
                                        </button>
                                    </AppTooltip>
                                    <AppTooltip :text="t('budgets.detailPanel.delete')">
                                        <button class="text-muted hover:text-rose-400 transition-colors" v-on:click="emit('delete', tx)">
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </button>
                                    </AppTooltip>
                                </div>
                                <span
                                    class="font-mono text-sm font-medium"
                                    :class="tx.type === TransactionType.Income ? 'text-emerald-400' : 'text-rose-400'"
                                >
                                    {{ fmt(tx.type === TransactionType.Income ? tx.amount : -tx.amount, true) }}
                                </span>
                            </div>
                        </div>
                    </template>
                    <EmptyState v-else :message="t('budgets.detailPanel.none')" icon="receipt" />
                </div>

                <div class="px-6 py-4 border-t border-line flex items-center justify-between">
                    <span class="text-sm text-secondary">{{ t('budgets.detailPanel.total') }}</span>
                    <span class="font-mono font-bold text-primary">{{ fmt(total) }}</span>
                </div>
            </div>
        </div>
    </Transition>

    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="previewUrl" class="fixed inset-0 z-[70] flex items-center justify-center" v-on:click.self="previewUrl = null">
                <div class="absolute inset-0 bg-black/70" />
                <div class="relative z-10 max-w-3xl max-h-[85vh] rounded-xl overflow-hidden shadow-2xl">
                    <button
                        class="absolute top-3 right-3 z-20 bg-black/50 hover:bg-black/70 text-white rounded-full p-1.5 transition-colors"
                        v-on:click="previewUrl = null"
                    >
                        <X class="w-5 h-5" />
                    </button>
                    <img :src="previewUrl" alt="Receipt" class="max-w-full max-h-[85vh] object-contain">
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
