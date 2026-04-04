<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DateInput from '@/components/form/DateInput.vue';
import TypeToggle from '@/components/form/TypeToggle.vue';
import DonutChart from '@/components/ui/DonutChart.vue';
import { useBudgetTotals } from '@/composables/useBudgetTotals';
import { useBudgetItems } from '@/composables/useBudgetItems';
import { useTransactionPanel } from '@/composables/useTransactionPanel';
import { useItemTransactions } from '@/composables/useItemTransactions';
import { useCurrency } from '@/composables/useCurrency';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, nextTick, toRef } from 'vue';

// ─── Props ───────────────────────────────────────────────────────────────────
const props = defineProps({
    wallet:       Object,
    budget:       Object,
    sections:     Object,
    categories:   Array,
    prevMonth:    String,
    nextMonth:    String,
    startBalance: Number,
});

// ─── Section meta ────────────────────────────────────────────────────────────
const SECTION_META = {
    income:   { label: 'Revenus',       color: 'text-emerald-400', bg: 'bg-emerald-400/10', border: 'border-emerald-400/30', positiveIsGood: true  },
    savings:  { label: 'Épargne',       color: 'text-sky-400',     bg: 'bg-sky-400/10',     border: 'border-sky-400/30',     positiveIsGood: false },
    bills:    { label: 'Charges fixes', color: 'text-amber-400',   bg: 'bg-amber-400/10',   border: 'border-amber-400/30',   positiveIsGood: false },
    expenses: { label: 'Dépenses',      color: 'text-rose-400',    bg: 'bg-rose-400/10',    border: 'border-rose-400/30',    positiveIsGood: false },
    debt:     { label: 'Dettes',        color: 'text-purple-400',  bg: 'bg-purple-400/10',  border: 'border-purple-400/30',  positiveIsGood: false },
};

// ─── Reactive prop refs for composables ──────────────────────────────────────
const walletId    = computed(() => props.wallet.id);
const budget      = toRef(props, 'budget');
const sections    = toRef(props, 'sections');
const startBalance = toRef(props, 'startBalance');

// ─── Row flash ───────────────────────────────────────────────────────────────
const FLASH_COLORS = {
    indigo:  'rgba(99, 102, 241, 0.25)',
    emerald: 'rgba(52, 211, 153, 0.25)',
    rose:    'rgba(251, 113, 133, 0.25)',
};

async function flash(id, color = 'indigo') {
    await nextTick();
    const row = document.querySelector(`tr[data-row-id="${id}"]`);
    if (!row) return;
    row.style.transition = 'none';
    row.style.backgroundColor = FLASH_COLORS[color] ?? FLASH_COLORS.indigo;
    row.offsetHeight; // force reflow
    setTimeout(() => {
        row.style.transition = 'background-color 2s ease-out';
        row.style.backgroundColor = '';
    }, 800);
}

// ─── Totals ───────────────────────────────────────────────────────────────────
const { totals, totalIncome, totalExpenses, cashFlow, leftToSpend } =
    useBudgetTotals(sections, startBalance);

// ─── Budget rows ─────────────────────────────────────────────────────────────
const {
    editingId, editForm, startEditing, cancelEditing, submitEdit,
    addingType, addForm, startAdding, cancelAdding, submitAdd, deleteBudgetItem,
} = useBudgetItems(walletId, sections, budget, flash);

// ─── Transaction panel ───────────────────────────────────────────────────────
const { txPanel, txPrefillLabel, txForm, openTxPanel, closeTxPanel, submitTx } =
    useTransactionPanel(walletId, budget, sections, flash);

// ─── Item transactions panel ─────────────────────────────────────────────────
const { open: txDetailOpen, loading: txDetailLoading, transactions: txDetailList, currentItem: txDetailItem, openPanel: openTxDetail, closePanel: closeTxDetail } =
    useItemTransactions(walletId);

// ─── Copy from previous month ────────────────────────────────────────────────
const isBudgetEmpty = computed(() =>
    Object.values(props.sections).every(arr => arr.length === 0)
);

function copyFromPrevious() {
    if (!confirm('Copier toutes les lignes du mois précédent ?')) return;
    router.post(`/wallets/${props.wallet.id}/budget/copy-previous`, { month: props.budget.month });
}

// ─── Projection fin de mois ──────────────────────────────────────────────────
const isCurrentMonth = computed(() =>
    props.budget.month === new Date().toISOString().slice(0, 7)
);

const projectedExpenses = computed(() => {
    if (!isCurrentMonth.value) return null;
    const today = new Date();
    const daysElapsed  = today.getDate();
    const daysInMonth  = new Date(today.getFullYear(), today.getMonth() + 1, 0).getDate();
    return (totalExpenses.value.actual / daysElapsed) * daysInMonth;
});

// ─── Donut segments ───────────────────────────────────────────────────────────
const SECTION_COLORS = {
    income:   '#34d399',
    savings:  '#38bdf8',
    bills:    '#fbbf24',
    expenses: '#fb7185',
    debt:     '#c084fc',
};

const donutSegments = computed(() =>
    Object.entries(totals.value)
        .filter(([, t]) => t.actual > 0)
        .map(([key, t]) => ({
            label: SECTION_META[key].label,
            value: t.actual,
            color: SECTION_COLORS[key],
        }))
);

// ─── Cross-cancel wrappers ───────────────────────────────────────────────────
function openTxPanelFromRow(categoryId, label, type) {
    openTxPanel(categoryId, label, type, { cancelEditing, cancelAdding });
}
function startEditingItem(item) {
    startEditing(item, { cancelAdding });
}
function startAddingItem(type) {
    startAdding(type, { cancelEditing });
}

// ─── Currency ────────────────────────────────────────────────────────────────
const { fmt, symbol } = useCurrency();

// ─── Helpers ─────────────────────────────────────────────────────────────────
function progress(planned, actual) {
    if (!planned) return 0;
    return Math.min(100, Math.round((actual / planned) * 100));
}

function diffClass(diff, positiveIsGood) {
    if (diff === 0) return 'text-gray-400';
    const good = positiveIsGood ? diff > 0 : diff < 0;
    return good ? 'text-emerald-400' : 'text-rose-400';
}

function onKeydown(e, submitFn, cancelFn) {
    if (e.key === 'Enter')  { e.preventDefault(); submitFn(); }
    if (e.key === 'Escape') { e.preventDefault(); cancelFn(); }
}
</script>

<template>
    <Head :title="`Budget ${budget.month_label} — ${wallet.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3 text-sm">
                    <Link href="/wallets" class="text-gray-400 hover:text-gray-200 transition-colors">Portefeuilles</Link>
                    <span class="text-gray-600">/</span>
                    <span class="text-gray-100 font-medium">{{ wallet.name }}</span>
                </div>
                <button
                    class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors"
                    v-on:click="openTxPanel(null, '', 'expense', { cancelEditing, cancelAdding })"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Nouvelle transaction
                </button>
            </div>
        </template>

        <!-- ── Month navigation ── -->
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <Link
                    :href="`/wallets/${wallet.id}/budget?month=${prevMonth}`"
                    class="flex items-center gap-1 text-gray-400 hover:text-gray-100 transition-colors text-sm"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    {{ prevMonth }}
                </Link>
                <h2 class="text-xl font-bold text-gray-100 capitalize">{{ budget.month_label }}</h2>
                <Link
                    :href="`/wallets/${wallet.id}/budget?month=${nextMonth}`"
                    class="flex items-center gap-1 text-gray-400 hover:text-gray-100 transition-colors text-sm"
                >
                    {{ nextMonth }}
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </Link>
            </div>

            <!-- ── KPI cards ── -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="bg-gray-900 border border-gray-700/60 rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Solde départ</p>
                    <p class="text-lg font-bold text-gray-100 font-mono">{{ fmt(startBalance) }}</p>
                </div>
                <div class="bg-gray-900 border border-gray-700/60 rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Revenus</p>
                    <p class="text-lg font-bold text-emerald-400 font-mono">{{ fmt(totalIncome.actual) }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">/ {{ fmt(totalIncome.planned) }} prévu</p>
                </div>
                <div class="bg-gray-900 border border-gray-700/60 rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Cash flow</p>
                    <p class="text-lg font-bold font-mono" :class="cashFlow.actual >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                        {{ fmt(cashFlow.actual, true) }}
                    </p>
                    <p class="text-xs text-gray-500 mt-0.5">/ {{ fmt(cashFlow.planned, true) }} prévu</p>
                </div>
                <div class="bg-gray-900 border border-gray-700/60 rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Reste à dépenser</p>
                    <p class="text-lg font-bold font-mono" :class="leftToSpend.actual >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                        {{ fmt(leftToSpend.actual) }}
                    </p>
                    <p class="text-xs text-gray-500 mt-0.5">/ {{ fmt(leftToSpend.planned) }} prévu</p>
                </div>
            </div>

            <!-- ── Donut + projection ── -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="bg-gray-900 border border-gray-700/60 rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide mb-3">Répartition réelle</p>
                    <DonutChart v-if="donutSegments.length" :segments="donutSegments" :size="120" />
                    <p v-else class="text-sm text-gray-600">Aucune dépense ce mois</p>
                </div>
                <div class="bg-gray-900 border border-gray-700/60 rounded-lg p-4 flex flex-col justify-between">
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">
                            Projection fin de mois
                            <span v-if="!isCurrentMonth" class="normal-case text-gray-600 ml-1">(mois passé)</span>
                        </p>
                        <p v-if="projectedExpenses !== null" class="text-lg font-bold font-mono" :class="projectedExpenses > totalExpenses.planned ? 'text-rose-400' : 'text-emerald-400'">
                            {{ fmt(projectedExpenses) }}
                        </p>
                        <p v-else class="text-lg font-bold text-gray-500 font-mono">—</p>
                        <p v-if="projectedExpenses !== null" class="text-xs text-gray-500 mt-0.5">
                            vs {{ fmt(totalExpenses.planned) }} prévu
                        </p>
                    </div>
                    <p v-if="projectedExpenses !== null" class="text-xs text-gray-600 mt-2">
                        Basé sur {{ new Date().getDate() }} jours écoulés
                    </p>
                </div>
            </div>

            <!-- ── Copy from previous / empty state ── -->
            <div v-if="isBudgetEmpty" class="bg-gray-900 border border-dashed border-gray-700 rounded-lg p-8 text-center">
                <p class="text-gray-400 mb-4">Ce budget est vide. Copier les lignes du mois précédent ?</p>
                <button
                    class="inline-flex items-center gap-2 bg-gray-800 hover:bg-gray-700 text-gray-200 text-sm font-medium px-4 py-2 rounded-lg border border-gray-700 transition-colors"
                    v-on:click="copyFromPrevious"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                    Copier depuis {{ prevMonth }}
                </button>
            </div>

            <!-- ── Budget table ── -->
            <div class="bg-gray-900 border border-gray-700/60 rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-700/60 text-xs text-gray-500 uppercase tracking-wider">
                            <th class="text-left px-4 py-3 font-medium w-[34%]">Libellé</th>
                            <th class="text-left px-4 py-3 font-medium w-[20%]">Catégorie</th>
                            <th class="text-right px-4 py-3 font-medium w-[13%]">Prévu</th>
                            <th class="text-right px-4 py-3 font-medium w-[13%]">Réel</th>
                            <th class="text-right px-4 py-3 font-medium w-[10%]">Écart</th>
                            <th class="w-[10%]" />
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="(items, type) in sections" :key="type">
                            <!-- ── Section header ── -->
                            <tr :class="[SECTION_META[type].bg, SECTION_META[type].border, 'border-y']">
                                <td class="px-4 py-2" colspan="2">
                                    <div class="flex items-center gap-3">
                                        <span :class="[SECTION_META[type].color, 'font-semibold uppercase text-xs tracking-widest']">
                                            {{ SECTION_META[type].label }}
                                        </span>
                                        <div class="flex-1 max-w-[120px] h-1.5 bg-gray-700 rounded-full overflow-hidden">
                                            <div
                                                class="h-full rounded-full transition-all duration-300"
                                                :class="SECTION_META[type].color.replace('text-', 'bg-')"
                                                :style="{ width: progress(totals[type]?.planned, totals[type]?.actual) + '%' }"
                                            />
                                        </div>
                                        <span class="text-xs text-gray-500">{{ progress(totals[type]?.planned, totals[type]?.actual) }}%</span>
                                    </div>
                                </td>
                                <td class="px-4 py-2 text-right font-mono text-gray-300 text-xs">{{ fmt(totals[type]?.planned ?? 0) }}</td>
                                <td class="px-4 py-2 text-right font-mono text-xs" :class="SECTION_META[type].color">{{ fmt(totals[type]?.actual ?? 0) }}</td>
                                <td
                                    class="px-4 py-2 text-right font-mono text-xs"
                                    :class="diffClass((totals[type]?.actual ?? 0) - (totals[type]?.planned ?? 0), SECTION_META[type].positiveIsGood)"
                                >
                                    {{ fmt((totals[type]?.actual ?? 0) - (totals[type]?.planned ?? 0), true) }}
                                </td>
                                <td />
                            </tr>

                            <!-- ── Rows ── -->
                            <template v-for="item in items" :key="item.id">
                                <!-- Inline edit -->
                                <template v-if="editingId === item.id">
                                    <tr class="bg-gray-800 border-b border-gray-700/40">
                                        <td class="pl-8 pr-2 py-1.5">
                                            <input
                                                :id="`edit-label-${item.id}`"
                                                v-model="editForm.label"
                                                type="text"
                                                placeholder="Libellé"
                                                class="w-full bg-gray-700 text-gray-100 rounded px-2 py-1 text-sm border border-gray-600 focus:border-indigo-500 focus:outline-none"
                                                v-on:keydown="onKeydown($event, () => submitEdit(item), cancelEditing)"
                                            >
                                        </td>
                                        <td class="px-2 py-1.5">
                                            <select
                                                v-model="editForm.category_id"
                                                class="w-full bg-gray-700 text-gray-100 rounded px-2 py-1 text-sm border border-gray-600 focus:border-indigo-500 focus:outline-none"
                                                v-on:keydown="onKeydown($event, () => submitEdit(item), cancelEditing)"
                                            >
                                                <option :value="null">—</option>
                                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                            </select>
                                        </td>
                                        <td class="px-2 py-1.5">
                                            <input
                                                v-model="editForm.planned_amount"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                placeholder="0,00"
                                                class="w-full bg-gray-700 text-gray-100 rounded px-2 py-1 text-sm border border-gray-600 focus:border-indigo-500 focus:outline-none text-right font-mono"
                                                v-on:keydown="onKeydown($event, () => submitEdit(item), cancelEditing)"
                                            >
                                        </td>
                                        <td class="px-2 py-1.5 text-right text-gray-500 font-mono text-xs">{{ fmt(item.actual_amount) }}</td>
                                        <td />
                                        <td class="px-3 py-1.5">
                                            <div class="flex items-center gap-2 justify-end">
                                                <button class="text-emerald-400 hover:text-emerald-300 transition-colors" title="Valider (Entrée)" v-on:click="submitEdit(item)">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                </button>
                                                <button class="text-gray-500 hover:text-gray-300 transition-colors" title="Annuler (Échap)" v-on:click="cancelEditing">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="bg-gray-800 border-b border-gray-700/40">
                                        <td colspan="6" class="pl-8 pr-3 pb-2">
                                            <textarea
                                                v-model="editForm.notes"
                                                placeholder="Note (optionnelle)…"
                                                rows="2"
                                                class="w-full bg-gray-700 text-gray-300 rounded px-2 py-1 text-xs border border-gray-600 focus:border-indigo-500 focus:outline-none resize-none"
                                            />
                                        </td>
                                    </tr>
                                </template>

                                <!-- Read + actions -->
                                <tr
                                    v-else
                                    class="border-b border-gray-800/60 group hover:bg-gray-800/40"
                                    :data-row-id="item.id"
                                    v-on:dblclick="startEditingItem(item)"
                                >
                                    <td class="pl-8 pr-4 py-2.5 text-gray-200 cursor-pointer">
                                        <span>{{ item.label }}</span>
                                        <span
                                            v-if="item.notes"
                                            class="ml-1.5 text-gray-600 hover:text-gray-400 transition-colors cursor-default"
                                            :title="item.notes"
                                        >
                                            <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" /></svg>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <span v-if="item.category" class="inline-flex items-center text-xs bg-gray-800 text-gray-300 rounded px-2 py-0.5 border border-gray-700">
                                            {{ item.category.name }}
                                        </span>
                                        <span v-else class="text-gray-600">—</span>
                                    </td>
                                    <td class="px-4 py-2.5 text-right text-gray-400 font-mono">{{ fmt(item.planned_amount) }}</td>
                                    <td
                                        class="px-4 py-2.5 text-right font-mono"
                                        :class="diffClass(item.actual_amount - item.planned_amount, SECTION_META[type].positiveIsGood)"
                                    >
                                        <button
                                            v-if="item.category_id && item.actual_amount > 0"
                                            class="hover:underline decoration-dotted"
                                            :title="`Voir les transactions`"
                                            v-on:click.stop="openTxDetail(item)"
                                        >
                                            {{ fmt(item.actual_amount) }}
                                        </button>
                                        <span v-else>{{ fmt(item.actual_amount) }}</span>
                                    </td>
                                    <td
                                        class="px-4 py-2.5 text-right font-mono text-xs"
                                        :class="diffClass(item.actual_amount - item.planned_amount, SECTION_META[type].positiveIsGood)"
                                    >
                                        {{ fmt(item.actual_amount - item.planned_amount, true) }}
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <div class="flex items-center gap-1.5 justify-end opacity-0 group-hover:opacity-100 transition-opacity">
                                            <!-- Add a transaction for this row -->
                                            <button
                                                class="text-gray-500 hover:text-indigo-400 transition-colors"
                                                title="Ajouter une transaction"
                                                v-on:click.stop="openTxPanelFromRow(item.category_id, item.label, type === 'income' ? 'income' : 'expense')"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                            </button>
                                            <button class="text-gray-500 hover:text-sky-400 transition-colors" title="Modifier la ligne budget" v-on:click.stop="startEditingItem(item)">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </button>
                                            <button class="text-gray-500 hover:text-rose-400 transition-colors" title="Supprimer la ligne" v-on:click.stop="deleteBudgetItem(item)">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <!-- ── Add budget row ── -->
                            <tr v-if="addingType === type" class="bg-gray-800/60 border-b border-gray-700/40">
                                <td class="pl-8 pr-2 py-1.5">
                                    <input
                                        :id="`add-label-${type}`"
                                        v-model="addForm.label"
                                        type="text"
                                        placeholder="Nouveau libellé…"
                                        class="w-full bg-gray-700 text-gray-100 rounded px-2 py-1 text-sm border border-indigo-500/50 focus:border-indigo-500 focus:outline-none"
                                        v-on:keydown="onKeydown($event, submitAdd, cancelAdding)"
                                    >
                                </td>
                                <td class="px-2 py-1.5">
                                    <select
                                        v-model="addForm.category_id"
                                        class="w-full bg-gray-700 text-gray-100 rounded px-2 py-1 text-sm border border-gray-600 focus:border-indigo-500 focus:outline-none"
                                        v-on:keydown="onKeydown($event, submitAdd, cancelAdding)"
                                    >
                                        <option :value="null">—</option>
                                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                    </select>
                                </td>
                                <td class="px-2 py-1.5">
                                    <input
                                        v-model="addForm.planned_amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="0,00"
                                        class="w-full bg-gray-700 text-gray-100 rounded px-2 py-1 text-sm border border-gray-600 focus:border-indigo-500 focus:outline-none text-right font-mono"
                                        v-on:keydown="onKeydown($event, submitAdd, cancelAdding)"
                                    >
                                </td>
                                <td colspan="2" />
                                <td class="px-3 py-1.5">
                                    <div class="flex items-center gap-2 justify-end">
                                        <button class="text-emerald-400 hover:text-emerald-300 transition-colors" title="Ajouter (Entrée)" v-on:click="submitAdd">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        </button>
                                        <button class="text-gray-500 hover:text-gray-300 transition-colors" title="Annuler (Échap)" v-on:click="cancelAdding">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- ── Add row button ── -->
                            <tr v-if="addingType !== type" class="border-b border-gray-800/60">
                                <td colspan="6" class="pl-8 py-1.5">
                                    <AppButton
                                        size="sm"
                                        class="flex items-center gap-1.5"
                                        v-on:click="startAddingItem(type)"
                                    >
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                        Ajouter une ligne
                                    </AppButton>
                                </td>
                            </tr>
                        </template>

                        <!-- ── Cash flow summary ── -->
                        <tr class="border-t-2 border-gray-600 bg-gray-800/30">
                            <td class="px-4 py-3 font-semibold text-gray-300 text-xs uppercase tracking-wide" colspan="2">Cash Flow</td>
                            <td class="px-4 py-3 text-right font-mono text-gray-400 text-sm">{{ fmt(cashFlow.planned, true) }}</td>
                            <td
                                class="px-4 py-3 text-right font-mono text-sm font-semibold"
                                :class="cashFlow.actual >= 0 ? 'text-emerald-400' : 'text-rose-400'"
                            >
                                {{ fmt(cashFlow.actual, true) }}
                            </td>
                            <td
                                class="px-4 py-3 text-right font-mono text-xs"
                                :class="diffClass(cashFlow.actual - cashFlow.planned, true)"
                            >
                                {{ fmt(cashFlow.actual - cashFlow.planned, true) }}
                            </td>
                            <td />
                        </tr>

                        <!-- ── Left to spend ── -->
                        <tr class="bg-gray-800/30">
                            <td class="px-4 py-3 font-semibold text-gray-300 text-xs uppercase tracking-wide" colspan="2">Reste à dépenser</td>
                            <td class="px-4 py-3 text-right font-mono text-gray-400 text-sm">{{ fmt(leftToSpend.planned) }}</td>
                            <td
                                class="px-4 py-3 text-right font-mono text-sm font-bold"
                                :class="leftToSpend.actual >= 0 ? 'text-emerald-400' : 'text-rose-400'"
                            >
                                {{ fmt(leftToSpend.actual) }}
                            </td>
                            <td colspan="2" />
                        </tr>
                    </tbody>
                </table>
            </div>

            <p class="text-center text-xs text-gray-600">
                Double-clic pour modifier une ligne · Entrée pour valider · Échap pour annuler
            </p>
        </div>

        <!-- ── Transaction slide-over ── -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-x-full opacity-0"
            enter-to-class="translate-x-0 opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="translate-x-0 opacity-100"
            leave-to-class="translate-x-full opacity-0"
        >
            <div v-if="txPanel" class="fixed inset-y-0 right-0 z-50 flex">
                <!-- Overlay -->
                <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" v-on:click="closeTxPanel" />

                <!-- Panel -->
                <div
                    class="relative ml-auto w-full max-w-sm bg-gray-900 border-l border-gray-700 shadow-2xl flex flex-col"
                    v-on:keydown.esc="closeTxPanel"
                >
                    <!-- Panel header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-700">
                        <div>
                            <h3 class="font-semibold text-gray-100">Nouvelle transaction</h3>
                            <p v-if="txPrefillLabel" class="text-xs text-gray-400 mt-0.5">{{ txPrefillLabel }}</p>
                        </div>
                        <button class="text-gray-400 hover:text-gray-200 transition-colors" v-on:click="closeTxPanel">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <!-- Form -->
                    <form class="flex-1 overflow-y-auto px-6 py-6 space-y-5" v-on:submit.prevent="submitTx">
                        <!-- Type toggle -->
                        <div>
                            <label class="block text-xs text-gray-400 uppercase tracking-wide mb-2">Type</label>
                            <TypeToggle v-model="txForm.type" />
                        </div>

                        <!-- Amount — primary field -->
                        <div>
                            <label class="block text-xs text-gray-400 uppercase tracking-wide mb-2">Montant</label>
                            <div class="relative">
                                <input
                                    id="tx-amount"
                                    v-model="txForm.amount"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    placeholder="0,00"
                                    required
                                    class="w-full bg-gray-800 text-gray-100 text-2xl font-bold font-mono rounded-lg px-4 py-4 pr-10 border border-gray-700 focus:border-indigo-500 focus:outline-none text-right"
                                >
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-xl font-bold">{{ symbol }}</span>
                            </div>
                            <p v-if="txForm.errors.amount" class="text-rose-400 text-xs mt-1">{{ txForm.errors.amount }}</p>
                        </div>

                        <!-- Category -->
                        <div>
                            <label class="block text-xs text-gray-400 uppercase tracking-wide mb-2">Catégorie</label>
                            <select
                                v-model="txForm.category_id"
                                required
                                class="w-full bg-gray-800 text-gray-100 rounded-lg px-3 py-2.5 border border-gray-700 focus:border-indigo-500 focus:outline-none"
                            >
                                <option :value="null" disabled>Choisir une catégorie…</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                            <p v-if="txForm.errors.category_id" class="text-rose-400 text-xs mt-1">{{ txForm.errors.category_id }}</p>
                        </div>

                        <!-- Date picker -->
                        <div>
                            <label class="block text-xs text-gray-400 uppercase tracking-wide mb-2">Date</label>
                            <DateInput v-model="txForm.date" />
                            <p v-if="txForm.errors.date" class="text-rose-400 text-xs mt-1">{{ txForm.errors.date }}</p>
                        </div>

                        <!-- Description (optional) -->
                        <div>
                            <label class="block text-xs text-gray-400 uppercase tracking-wide mb-2">Description <span class="normal-case text-gray-600">(optionnel)</span></label>
                            <input
                                v-model="txForm.description"
                                type="text"
                                placeholder="Ex : Lidl, plein d'essence…"
                                class="w-full bg-gray-800 text-gray-100 rounded-lg px-3 py-2.5 border border-gray-700 focus:border-indigo-500 focus:outline-none"
                            >
                        </div>
                    </form>

                    <!-- Panel footer -->
                    <div class="px-6 py-4 border-t border-gray-700 flex gap-3">
                        <button
                            type="button"
                            :disabled="txForm.processing"
                            class="flex-1 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-semibold py-2.5 rounded-lg transition-colors"
                            v-on:click="submitTx"
                        >
                            {{ txForm.processing ? 'Enregistrement…' : 'Enregistrer' }}
                        </button>
                        <button
                            type="button"
                            class="px-4 py-2.5 text-gray-400 hover:text-gray-200 border border-gray-700 rounded-lg transition-colors"
                            v-on:click="closeTxPanel"
                        >
                            Annuler
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- ── Transaction detail slide-over ── -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-x-full opacity-0"
            enter-to-class="translate-x-0 opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="translate-x-0 opacity-100"
            leave-to-class="translate-x-full opacity-0"
        >
            <div v-if="txDetailOpen" class="fixed inset-y-0 right-0 z-50 flex">
                <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" v-on:click="closeTxDetail" />
                <div class="relative ml-auto w-full max-w-sm bg-gray-900 border-l border-gray-700 shadow-2xl flex flex-col" v-on:keydown.esc="closeTxDetail">
                    <!-- Panel header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-700">
                        <div>
                            <h3 class="font-semibold text-gray-100">{{ txDetailItem?.label }}</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Transactions du mois</p>
                        </div>
                        <button class="text-gray-400 hover:text-gray-200 transition-colors" v-on:click="closeTxDetail">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <!-- List -->
                    <div class="flex-1 overflow-y-auto px-6 py-4">
                        <div v-if="txDetailLoading" class="flex items-center justify-center py-12 text-gray-500 text-sm">
                            Chargement…
                        </div>
                        <template v-else-if="txDetailList.length">
                            <div
                                v-for="tx in txDetailList"
                                :key="tx.id"
                                class="flex items-center justify-between py-3 border-b border-gray-800 last:border-0"
                            >
                                <div class="min-w-0">
                                    <p class="text-sm text-gray-200 truncate">{{ tx.description || '—' }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ tx.date }}</p>
                                </div>
                                <span
                                    class="ml-4 font-mono text-sm font-medium shrink-0"
                                    :class="tx.type === 'income' ? 'text-emerald-400' : 'text-rose-400'"
                                >
                                    {{ fmt(tx.type === 'income' ? tx.amount : -tx.amount, true) }}
                                </span>
                            </div>
                        </template>
                        <p v-else class="text-sm text-gray-500 text-center py-12">Aucune transaction ce mois</p>
                    </div>

                    <!-- Footer total -->
                    <div class="px-6 py-4 border-t border-gray-700 flex items-center justify-between">
                        <span class="text-sm text-gray-400">Total</span>
                        <span class="font-mono font-bold text-gray-100">
                            {{ fmt(txDetailList.reduce((s, t) => s + t.amount, 0)) }}
                        </span>
                    </div>
                </div>
            </div>
        </Transition>
    </AuthenticatedLayout>
</template>
