<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Plus, Trash2, TrendingUp, TrendingDown, Pencil, LayoutList, Settings, ChevronLeft, ChevronRight, Search, X } from 'lucide-vue-next';
import { useCurrency } from '@/composables/core/useCurrency';
import { useFmtDate } from '@/composables/core/useFmtDate';
import { useFmtMonth } from '@/composables/core/useFmtMonth';
import { useConfirmDelete } from '@/composables/ui/useConfirmDelete';
import { useI18n } from 'vue-i18n';
import { TransactionType } from '@/enums/TransactionType';
import { WalletRole } from '@/enums/WalletRole';
import { evalMath } from '@/utils/evalMath';
import FormHint from '@/components/form/FormHint.vue';
import BalanceAdjustmentModal from '@/components/wallet/BalanceAdjustmentModal.vue';

const { t }        = useI18n();
const { fmt }      = useCurrency();
const { fmtDate }  = useFmtDate();
const { fmtMonth } = useFmtMonth();

const props = defineProps({
    wallet:       { type: Object, required: true },
    transactions: { type: Array,  required: true },
    month:        { type: String, required: true },
    prevMonth:    { type: String, required: true },
    nextMonth:    { type: String, required: true },
});

const canEdit = computed(() => [WalletRole.Owner, WalletRole.Editor].includes(props.wallet.user_role));

// ── Month navigation ──────────────────────────────────────────────────────
function goToMonth(month) {
    router.visit(route('wallets.simple.show', props.wallet.id) + '?month=' + month, { preserveScroll: true });
}

// ── Filter ────────────────────────────────────────────────────────────────
const FILTERS = ['all', TransactionType.Income, TransactionType.Expense];

const activeFilter = ref('all');
const search = ref('');

const filteredTransactions = computed(() => {
    let result = props.transactions;
    if (activeFilter.value !== 'all') {
        result = result.filter(t => t.type === activeFilter.value);
    }
    if (search.value.trim()) {
        const needle = search.value.trim().toLowerCase();
        result = result.filter(t => (t.description ?? '').toLowerCase().includes(needle));
    }
    return result;
});

// ── Group by date ─────────────────────────────────────────────────────────
function dateGroupLabel(dateStr) {
    const today     = new Date().toISOString().slice(0, 10);
    const yesterday = new Date(Date.now() - 86400000).toISOString().slice(0, 10);
    if (dateStr === today)     return t('simple.today');
    if (dateStr === yesterday) return t('simple.yesterday');
    return fmtDate(dateStr);
}

const groupedTransactions = computed(() => {
    const groups = new Map();
    for (const transaction of filteredTransactions.value) {
        if (!groups.has(transaction.date)) groups.set(transaction.date, []);
        groups.get(transaction.date).push(transaction);
    }
    return [...groups.entries()].map(([date, items]) => ({ date, label: dateGroupLabel(date), items }));
});

// ── Form (create / edit) ──────────────────────────────────────────────────
const showForm            = ref(false);
const editingTransaction  = ref(null);
const showAdjustment      = ref(false);

const form = useForm({
    wallet_id:   props.wallet.id,
    type:        'expense',
    amount:      '',
    description: '',
    date:        new Date().toISOString().slice(0, 10),
});

function openCreate() {
    editingTransaction.value = null;
    form.reset();
    form.wallet_id   = props.wallet.id;
    form.type        = TransactionType.Expense;
    form.date        = new Date().toISOString().slice(0, 10);
    showForm.value   = true;
}

function openEdit(transaction) {
    editingTransaction.value = transaction;
    form.wallet_id   = props.wallet.id;
    form.type        = transaction.type;
    form.amount      = String(transaction.amount);
    form.description = transaction.description ?? '';
    form.date        = transaction.date;
    showForm.value   = true;
}

function handleAmountBlur() {
    const result = evalMath(form.amount);
    if (result !== null) {
        form.amount = String(result);
    }
}

function submit() {
    // Ensure amount is evaluated before submitting
    const result = evalMath(form.amount);
    if (result !== null) {
        form.amount = String(result);
    } else if (!form.amount) {
        // If empty or invalid, leave as is (validation will catch it)
        return;
    }

    if (editingTransaction.value) {
        form.put(route('wallets.simple.transactions.update', [props.wallet.id, editingTransaction.value.id]), {
            onSuccess: () => {
                showForm.value = false;
                editingTransaction.value = null;
                form.reset();
            },
        });
    } else {
        form.post(route('wallets.simple.transactions.store', props.wallet.id), {
            onSuccess: () => {
                showForm.value = false;
                form.reset();
            },
        });
    }
}

// ── Delete ─────────────────────────────────────────────────────────────────
const { isOpen, message, confirmDelete, onConfirm, onCancel } = useConfirmDelete(t('simple.confirmDelete'));
</script>

<template>
    <Head :title="wallet.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <AppPageHeader :crumbs="[{ label: t('wallets.title'), href: '/wallets' }, { label: wallet.name }]" />
                <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-500/15 px-2.5 py-1 text-xs font-medium text-indigo-400">
                    <LayoutList class="w-3 h-3" />
                    {{ t('wallets.modeSimpleTitle') }}
                </span>
            </div>
        </template>

        <div class="space-y-4">
            <div v-if="canEdit" class="flex justify-end">
                <button
                    class="flex w-full sm:w-auto items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors"
                    v-on:click="openCreate"
                >
                    <Plus class="w-4 h-4 shrink-0" />
                    {{ t('simple.addTransaction') }}
                </button>
            </div>

            <div class="bg-surface border border-line/60 rounded-2xl p-5 space-y-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('wallets.colBalance') }}</p>
                        <p
                            class="text-3xl font-bold font-mono"
                            :class="wallet.current_balance >= 0 ? 'text-primary' : 'text-rose-400'"
                        >
                            {{ fmt(wallet.current_balance) }}
                        </p>
                        <p class="text-xs text-muted mt-1">{{ t('wallets.startBalance') }} {{ fmt(wallet.start_balance) }}</p>
                    </div>
                    <button
                        v-if="canEdit"
                        class="flex items-center justify-center w-10 h-10 rounded-lg bg-indigo-500/15 hover:bg-indigo-500/25 text-indigo-400 transition-colors shrink-0"
                        :title="t('balanceAdjustment.button')"
                        v-on:click="showAdjustment = true"
                    >
                        <Settings class="w-5 h-5" />
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-1 border-t border-line/40">
                    <div class="flex items-center gap-2">
                        <div class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-500/15">
                            <TrendingUp class="w-3.5 h-3.5 text-emerald-400" />
                        </div>
                        <div>
                            <p class="text-xs text-muted">{{ t('simple.totalIncome') }}</p>
                            <p class="text-sm font-semibold font-mono text-emerald-400">+{{ fmt(wallet.income_sum) }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="flex h-7 w-7 items-center justify-center rounded-full bg-rose-500/15">
                            <TrendingDown class="w-3.5 h-3.5 text-rose-400" />
                        </div>
                        <div>
                            <p class="text-xs text-muted">{{ t('simple.totalExpense') }}</p>
                            <p class="text-sm font-semibold font-mono text-rose-400">−{{ fmt(wallet.expense_sum) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="relative">
                <Search class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted" />
                <input
                    v-model="search"
                    type="text"
                    :placeholder="t('simple.searchPlaceholder')"
                    class="w-full rounded-lg border border-line/60 bg-surface py-2 pl-9 pr-9 text-sm text-primary placeholder:text-muted focus:border-indigo-500/60 focus:outline-none"
                />
                <button
                    v-if="search"
                    type="button"
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted hover:text-primary transition-colors"
                    v-on:click="search = ''"
                >
                    <X class="w-4 h-4" />
                </button>
            </div>

            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-1">
                    <button
                        type="button"
                        class="flex h-7 w-7 items-center justify-center rounded-lg border border-line/60 bg-surface text-muted transition-colors hover:border-indigo-500/40 hover:text-indigo-400"
                        v-on:click="goToMonth(prevMonth)"
                    >
                        <ChevronLeft class="w-4 h-4" />
                    </button>
                    <span class="min-w-32 text-center text-sm font-medium text-primary capitalize">
                        {{ fmtMonth(month) }}
                    </span>
                    <button
                        type="button"
                        class="flex h-7 w-7 items-center justify-center rounded-lg border border-line/60 bg-surface text-muted transition-colors hover:border-indigo-500/40 hover:text-indigo-400"
                        v-on:click="goToMonth(nextMonth)"
                    >
                        <ChevronRight class="w-4 h-4" />
                    </button>
                </div>

                <div class="flex gap-2">
                    <button
                        v-for="filter in FILTERS"
                        :key="filter"
                        type="button"
                        class="flex-1 sm:flex-none rounded-lg px-3 py-1.5 text-xs font-medium transition-all border"
                        :class="activeFilter === filter
                            ? 'bg-indigo-500/15 border-indigo-500/60 text-indigo-400'
                            : 'bg-surface border-line/60 text-muted hover:border-indigo-500/40'"
                        v-on:click="activeFilter = filter"
                    >
                        {{ t(`simple.filter${filter.charAt(0).toUpperCase() + filter.slice(1)}`) }}
                    </button>
                </div>
            </div>

            <div v-if="groupedTransactions.length > 0" class="space-y-4">
                <div v-for="group in groupedTransactions" :key="group.date" class="space-y-1.5">
                    <p class="text-xs font-medium text-muted uppercase tracking-wide px-1">{{ group.label }}</p>
                    <div
                        v-for="transaction in group.items"
                        :key="transaction.id"
                        class="flex items-center gap-3 bg-surface border border-line/60 rounded-xl px-4 py-3"
                    >
                        <div
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full"
                            :class="transaction.type === TransactionType.Income ? 'bg-emerald-500/15' : 'bg-rose-500/15'"
                        >
                            <TrendingUp v-if="transaction.type === TransactionType.Income" class="w-4 h-4 text-emerald-400" />
                            <TrendingDown v-else class="w-4 h-4 text-rose-400" />
                        </div>

                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-primary truncate">
                                {{ transaction.description || t('simple.noDescription') }}
                            </p>
                        </div>

                        <div class="flex items-center gap-3 shrink-0">
                            <span
                                class="text-sm font-semibold font-mono"
                                :class="transaction.type === TransactionType.Income ? 'text-emerald-400' : 'text-rose-400'"
                            >
                                {{ transaction.type === TransactionType.Income ? '+' : '−' }}{{ fmt(transaction.amount) }}
                            </span>
                            <template v-if="canEdit">
                                <button class="text-muted hover:text-indigo-400 transition-colors" v-on:click="openEdit(transaction)">
                                    <Pencil class="w-4 h-4" />
                                </button>
                                <button class="text-muted hover:text-rose-400 transition-colors" v-on:click="confirmDelete(route('transactions.destroy', transaction.id))">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <EmptyState v-else-if="transactions.length === 0" :message="t('simple.none')" icon="wallet" />
            <EmptyState v-else-if="search" :message="t('simple.noneSearch')" icon="wallet" />
            <EmptyState v-else :message="t('simple.noneThisMonth')" icon="wallet" />
        </div>

        <AppModal :show="showForm" v-on:close="showForm = false">
            <h3 class="text-base font-semibold text-primary">
                {{ editingTransaction ? t('simple.editTransaction') : t('simple.addTransaction') }}
            </h3>

            <div class="flex gap-2">
                <button
                    type="button"
                    class="flex-1 rounded-lg border-2 py-2 text-sm font-medium transition-all"
                    :class="form.type === TransactionType.Expense
                        ? 'border-rose-500 bg-rose-500/10 text-rose-400'
                        : 'border-line/60 text-muted hover:border-rose-500/40'"
                    v-on:click="form.type = TransactionType.Expense"
                >
                    {{ t('simple.typeExpense') }}
                </button>
                <button
                    type="button"
                    class="flex-1 rounded-lg border-2 py-2 text-sm font-medium transition-all"
                    :class="form.type === TransactionType.Income
                        ? 'border-emerald-500 bg-emerald-500/10 text-emerald-400'
                        : 'border-line/60 text-muted hover:border-emerald-500/40'"
                    v-on:click="form.type = TransactionType.Income"
                >
                    {{ t('simple.typeIncome') }}
                </button>
            </div>

            <FormField :label="t('simple.fieldAmount')">
                <TextInput
                    v-model="form.amount"
                    type="text"
                    inputmode="decimal"
                    placeholder="0.00"
                    autofocus
                    v-on:blur="handleAmountBlur"
                />
                <FormHint>{{ t('simple.amountHint') }}</FormHint>
                <InputError :message="form.errors.amount" />
            </FormField>

            <FormField :label="t('simple.fieldDescription')">
                <TextInput
                    v-model="form.description"
                    type="text"
                    :placeholder="t('simple.descriptionPlaceholder')"
                />
                <InputError :message="form.errors.description" />
            </FormField>

            <FormField :label="t('simple.fieldDate')">
                <DateInput v-model="form.date" />
                <InputError :message="form.errors.date" />
            </FormField>

            <div class="flex justify-end gap-3 pt-2">
                <AppButton variant="secondary" v-on:click="showForm = false">{{ t('common.cancel') }}</AppButton>
                <AppButton :disabled="form.processing" v-on:click="submit">
                    {{ editingTransaction ? t('common.update') : t('common.create') }}
                </AppButton>
            </div>
        </AppModal>

        <ConfirmModal
            :show="isOpen"
            :message="message"
            v-on:confirm="onConfirm"
            v-on:cancel="onCancel"
        />

        <BalanceAdjustmentModal
            :show="showAdjustment"
            :wallet="wallet"
            v-on:close="showAdjustment = false"
        />
    </AuthenticatedLayout>
</template>
