<script setup>
import { Search, AlertTriangle, Pencil, Trash2, Paperclip } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/components/ui/AppButton.vue';
import AppTooltip from '@/components/ui/AppTooltip.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import BudgetTxPanel from '@/components/budget/BudgetTxPanel.vue';
import ConfirmModal from '@/components/ui/ConfirmModal.vue';
import DateInput from '@/components/form/DateInput.vue';
import SelectInput from '@/components/form/SelectInput.vue';
import TransferModal from '@/components/wallet/TransferModal.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useCurrency } from '@/composables/core/useCurrency';
import { useSearchFilters } from '@/composables/search/useSearchFilters';
import { useTransactionDelete } from '@/composables/search/useTransactionDelete';
import { useSectionMeta } from '@/composables/budget/useSectionMeta';
import { useFmtDate } from '@/composables/core/useFmtDate';
import { useI18n } from 'vue-i18n';
import { TransactionType } from '@/enums/TransactionType';

const { t } = useI18n();
const { fmt } = useCurrency();
const { fmtDay } = useFmtDate();

const props = defineProps({
    transactions: Object,
    categories:   Array,
    wallets:      Array,
    filters:      Object,
    isFreeLimited: { type: Boolean, default: false },
    freeLimitDays: { type: Number, default: 90 },
});

const { filters: form, search, reset, filterByTag, hasFilters } = useSearchFilters(props.filters);

const { pendingDeleteTx, deleteTx, confirmDeleteTx, cancelDeleteTx } = useTransactionDelete();

const showTransferModal = ref(false);
const editingTransfer = ref(null);

function editTransfer(tx) {
    editingTransfer.value = {
        transfer_id: tx.transfer_id,
        amount:      tx.amount,
        date:        tx.date,
        description: tx.description ?? '',
    };
    showTransferModal.value = true;
}

function closeTransferModal() {
    showTransferModal.value = false;
    editingTransfer.value = null;
}

const { SECTION_META } = useSectionMeta();
const editPanel = ref(false);
const editingTx = ref(null);
const editForm = useForm({
    wallet_id: null,
    type: TransactionType.Expense,
    category_id: null,
    amount: '',
    description: '',
    date: '',
    tags: [],
});

function editTx(tx) {
    editingTx.value = tx;
    editForm.wallet_id = tx.wallet_id;
    editForm.type = tx.type;
    editForm.category_id = tx.category_id;
    editForm.amount = tx.amount;
    editForm.description = tx.description ?? '';
    editForm.date = tx.date?.slice(0, 10) ?? '';
    editForm.tags = tx.tags ?? [];
    editPanel.value = true;
}

function closeEditPanel() {
    editPanel.value = false;
    editingTx.value = null;
    editForm.reset();
}

function submitEdit() {
    if (!editingTx.value) return;
    editForm.put(`/transactions/${editingTx.value.id}`, {
        onSuccess: () => closeEditPanel(),
    });
}

</script>

<template>
    <Head :title="t('search.title')" />

    <AuthenticatedLayout>
        <template #header>
            <AppPageHeader :title="t('search.title')" />
        </template>

        <div class="space-y-4">
            <div class="bg-surface border border-base/60 rounded-xl p-4 space-y-3">
                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <input
                            v-model="form.q"
                            type="text"
                            :placeholder="t('search.placeholder')"
                            class="w-full pl-9 pr-3 py-2 bg-surface-2 text-primary border border-base rounded-lg text-sm focus:border-indigo-500 focus:outline-none"
                            v-on:input="search"
                        >
                        <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted pointer-events-none" />
                    </div>
                    <AppButton v-if="hasFilters()" variant="secondary" size="sm" v-on:click="reset">
                        {{ t('search.reset') }}
                    </AppButton>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-2">
                    <SelectInput v-model="form.category_id" v-on:change="search">
                        <option value="">{{ t('search.allCategories') }}</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                    </SelectInput>

                    <SelectInput v-model="form.wallet_id" v-on:change="search">
                        <option value="">{{ t('search.allWallets') }}</option>
                        <option v-for="w in wallets" :key="w.id" :value="w.id">{{ w.name }}</option>
                    </SelectInput>

                    <SelectInput v-model="form.type" v-on:change="search">
                        <option value="">{{ t('search.allTypes') }}</option>
                        <option value="expense">{{ t('search.expense') }}</option>
                        <option value="income">{{ t('search.income') }}</option>
                    </SelectInput>

                    <div class="relative">
                        <input
                            v-model="form.tag"
                            type="text"
                            :placeholder="t('search.tagPlaceholder')"
                            class="w-full pl-7 pr-3 py-2 bg-surface-2 text-primary border border-base rounded-lg text-sm focus:border-indigo-500 focus:outline-none"
                            :class="form.tag ? 'border-indigo-500/60' : ''"
                            v-on:input="search"
                        >
                        <span class="absolute left-2.5 top-2 text-muted text-sm pointer-events-none">#</span>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-2">
                    <DateInput v-model="form.date_from" class="flex-1" v-on:change="search" />
                    <DateInput v-model="form.date_to" class="flex-1" v-on:change="search" />
                </div>
            </div>

            <div v-if="isFreeLimited" class="bg-amber-500/10 border border-amber-500/30 rounded-lg px-4 py-3 flex items-center gap-3">
                <AlertTriangle class="h-5 w-5 text-amber-400 shrink-0" :stroke-width="1.5" />
                <p class="text-sm text-amber-200">{{ t('search.freeLimitWarning', { days: freeLimitDays }) }}</p>
            </div>

            <div class="bg-surface border border-base/60 rounded-xl overflow-hidden">
                <template v-if="transactions.data.length > 0">
                    <div class="sm:hidden divide-y divide-base/40">
                        <div
                            v-for="tx in transactions.data"
                            :key="tx.id"
                            class="px-4 py-3 space-y-1.5"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-sm text-primary font-medium truncate">{{ tx.description ?? '—' }}</span>
                                <div class="flex items-center gap-2 shrink-0">
                                    <template v-if="tx.transfer_id">
                                        <button class="text-muted hover:text-sky-400 transition-colors" v-on:click="editTransfer(tx)">
                                            <Pencil class="w-3.5 h-3.5" />
                                        </button>
                                        <button class="text-muted hover:text-rose-400 transition-colors" v-on:click="deleteTx(tx)">
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </button>
                                    </template>
                                    <template v-else-if="!tx.split_id">
                                        <button class="text-muted hover:text-sky-400 transition-colors" v-on:click="editTx(tx)">
                                            <Pencil class="w-3.5 h-3.5" />
                                        </button>
                                        <button class="text-muted hover:text-rose-400 transition-colors" v-on:click="deleteTx(tx)">
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </button>
                                    </template>
                                    <span
                                        class="text-sm font-semibold font-mono"
                                        :class="tx.type === TransactionType.Income ? 'text-emerald-400' : 'text-rose-400'"
                                    >
                                        {{ tx.type === TransactionType.Income ? '+' : '-' }}{{ fmt(tx.amount) }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <AppTooltip v-if="tx.transfer_id" :text="t('search.transferTip')"><span class="rounded-full bg-badge-info-bg px-2 py-0.5 text-xs font-medium text-badge-info-text cursor-help">{{ t('transfers.badge') }}</span></AppTooltip>
                                <span v-else class="rounded-full bg-badge-primary-bg px-2 py-0.5 text-xs font-medium text-badge-primary-text">{{ tx.category?.name ?? '—' }}</span>
                                <span v-if="tx.split_id" class="rounded-full bg-badge-warning-bg px-2 py-0.5 text-xs font-medium text-badge-warning-text">{{ t('search.splitBadge') }}</span>
                                <a v-if="tx.attachment_url" :href="tx.attachment_url" target="_blank" class="text-indigo-400 hover:text-indigo-300 transition-colors"><Paperclip class="w-3 h-3" /></a>
                                <span class="text-xs text-muted">{{ tx.wallet?.name }}</span>
                                <span class="text-xs text-muted">{{ fmtDay(tx.date) }}</span>
                            </div>
                            <div v-if="tx.tags && tx.tags.length" class="flex flex-wrap gap-1">
                                <button
                                    v-for="tag in tx.tags"
                                    :key="tag"
                                    class="px-1.5 py-0.5 rounded text-xs text-indigo-400 hover:text-indigo-300 hover:bg-indigo-900/40 transition-colors"
                                    v-on:click="filterByTag(tag)"
                                >
                                    #{{ tag }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="hidden sm:block overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-surface-2/50 border-b border-base/40">
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('common.date') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('common.description') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('common.category') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('nav.wallets') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('common.tags') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('common.amount') }}</th>
                                    <th class="w-10" />
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-base/40">
                                <tr v-for="tx in transactions.data" :key="tx.id" class="group hover:bg-surface-2/40 transition-colors">
                                    <td class="px-4 py-3 text-sm text-secondary whitespace-nowrap">{{ fmtDay(tx.date) }}</td>
                                    <td class="px-4 py-3 text-sm text-primary">{{ tx.description ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <AppTooltip v-if="tx.transfer_id" :text="t('search.transferTip')"><span class="rounded-full bg-badge-info-bg px-2.5 py-0.5 text-xs font-medium text-badge-info-text cursor-help">{{ t('transfers.badge') }}</span></AppTooltip>
                                        <span v-else class="rounded-full bg-badge-primary-bg px-2.5 py-0.5 text-xs font-medium text-badge-primary-text">{{ tx.category?.name ?? '—' }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-secondary">{{ tx.wallet?.name }}</td>
                                    <td class="px-4 py-3">
                                        <div v-if="tx.tags && tx.tags.length" class="flex flex-wrap gap-1">
                                            <button
                                                v-for="tag in tx.tags"
                                                :key="tag"
                                                class="px-1.5 py-0.5 rounded text-xs text-indigo-400 hover:text-indigo-300 hover:bg-indigo-900/40 transition-colors"
                                                v-on:click="filterByTag(tag)"
                                            >
                                                #{{ tag }}
                                            </button>
                                        </div>
                                        <span v-else class="text-subtle text-xs">—</span>
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right text-sm font-semibold font-mono"
                                        :class="tx.type === TransactionType.Income ? 'text-emerald-400' : 'text-rose-400'"
                                    >
                                        {{ tx.type === TransactionType.Income ? '+' : '-' }}{{ fmt(tx.amount) }}
                                    </td>
                                    <td class="px-2 py-3">
                                        <div v-if="!tx.split_id" class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button
                                                class="text-muted hover:text-sky-400 transition-colors"
                                                v-on:click="tx.transfer_id ? editTransfer(tx) : editTx(tx)"
                                            >
                                                <Pencil class="w-3.5 h-3.5" />
                                            </button>
                                            <button class="text-muted hover:text-rose-400 transition-colors" v-on:click="deleteTx(tx)">
                                                <Trash2 class="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>

                <EmptyState v-else :message="t('search.noResults')" icon="search" />
            </div>

            <AppPagination :meta="transactions" />
        </div>
        <BudgetTxPanel
            :open="editPanel"
            :editing="true"
            :show-section-filter="false"
            :tx-form="editForm"
            :section-meta="SECTION_META"
            :filtered-categories="categories"
            v-on:close="closeEditPanel"
            v-on:submit="submitEdit"
        />

        <ConfirmModal
            :show="pendingDeleteTx !== null"
            :message="pendingDeleteTx?.transfer_id ? t('transfers.confirmDelete') : t('transactions.confirmDelete')"
            v-on:confirm="confirmDeleteTx"
            v-on:cancel="cancelDeleteTx"
        />

        <TransferModal
            :show="showTransferModal"
            :wallets="wallets"
            :editing="editingTransfer"
            v-on:close="closeTransferModal"
        />
    </AuthenticatedLayout>
</template>
