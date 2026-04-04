<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppToast from '@/components/ui/AppToast.vue';
import ConfirmModal from '@/components/ui/ConfirmModal.vue';
import NoteTooltip from '@/components/ui/NoteTooltip.vue';
import DonutChart from '@/components/ui/DonutChart.vue';
import BudgetGauge from '@/components/ui/BudgetGauge.vue';
import BudgetTxPanel from '@/components/budget/BudgetTxPanel.vue';
import BudgetDetailPanel from '@/components/budget/BudgetDetailPanel.vue';
import { useBudgetTotals }   from '@/composables/budget/useBudgetTotals';
import { useBudgetItems }    from '@/composables/budget/useBudgetItems';
import { useBudgetActions }  from '@/composables/budget/useBudgetActions';
import { useBudgetExport }   from '@/composables/budget/useBudgetExport';
import { useBudgetNotes }    from '@/composables/budget/useBudgetNotes';
import { useSectionMeta }    from '@/composables/budget/useSectionMeta';
import { useSectionCollapse } from '@/composables/budget/useSectionCollapse';
import { useRowFlash }       from '@/composables/budget/useRowFlash';
import { useFmtMonth }       from '@/composables/core/useFmtMonth';
import { useTransactionPanel } from '@/composables/budget/useTransactionPanel';
import { useItemTransactions } from '@/composables/budget/useItemTransactions';
import { useCurrency }       from '@/composables/core/useCurrency';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, toRef } from 'vue';
import { useI18n } from 'vue-i18n';

// ─── i18n / currency ─────────────────────────────────────────────────────────
const { t }        = useI18n();
const { fmt } = useCurrency();
const { fmtMonth } = useFmtMonth();

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

// ─── Reactive prop refs ───────────────────────────────────────────────────────
const walletId     = computed(() => props.wallet.id);
const budget       = toRef(props, 'budget');
const sections     = toRef(props, 'sections');
const startBalance = toRef(props, 'startBalance');
const prevMonth    = toRef(props, 'prevMonth');
const categories   = computed(() => props.categories);

// ─── Section meta + donut ─────────────────────────────────────────────────────
const { SECTION_META, makeDonutSegments } = useSectionMeta();

// ─── Totals / KPIs ───────────────────────────────────────────────────────────
const { totals, totalIncome, totalExpenses, cashFlow, leftToSpend, savingsRate, isCurrentMonth, projectedExpenses } =
    useBudgetTotals(sections, startBalance, budget);

const donutSegments = makeDonutSegments(totals);

// ─── Row flash ────────────────────────────────────────────────────────────────
const { flash } = useRowFlash();

// ─── Budget rows ─────────────────────────────────────────────────────────────
const {
    editingId, editForm, startEditing, cancelEditing, submitEdit,
    addingType, addForm, startAdding, cancelAdding, submitAdd,
    pendingDeleteItem, deletingItem, requestDelete, confirmDelete, undoDelete, cancelDelete,
    draggingId, dragOverId, onDragStart, onDragOver, onDrop, onDragEnd,
    duplicateItem, toggleRecurring,
} = useBudgetItems(walletId, sections, budget, flash);

// ─── Transaction panel ───────────────────────────────────────────────────────
const { txPanel, txPrefillLabel, txForm, txSection, txFilteredCategories, openTxPanel, closeTxPanel, submitTx, onTxSectionChange } =
    useTransactionPanel(walletId, budget, sections, flash, categories);

// ─── Item transactions panel ─────────────────────────────────────────────────
const { open: txDetailOpen, loading: txDetailLoading, transactions: txDetailList, currentItem: txDetailItem, openPanel: openTxDetail, closePanel: closeTxDetail } =
    useItemTransactions(walletId);

// ─── Section collapse ────────────────────────────────────────────────────────
const { collapsedSections, toggleSection } =
    useSectionCollapse(walletId, computed(() => props.budget.month));

// ─── Budget actions (copy) ────────────────────────────────────────────────────
const { isBudgetEmpty, copyFromPrevious, copyRecurring } =
    useBudgetActions(walletId, budget, sections, prevMonth, fmtMonth, t);

// ─── Budget notes ─────────────────────────────────────────────────────────────
const { budgetNotesOpen, budgetNotesText, saveBudgetNotes } =
    useBudgetNotes(walletId, budget);

// ─── Export XLSX ─────────────────────────────────────────────────────────────
const { exportXlsx } = useBudgetExport(sections, totals, SECTION_META, budget, t);

// ─── Cross-cancel wrappers ────────────────────────────────────────────────────
function openTxPanelFromRow(categoryId, label, type, section) {
    openTxPanel(categoryId, label, type, { cancelEditing, cancelAdding }, section);
}
function startEditingItem(item) { startEditing(item, { cancelAdding }); }
function startAddingItem(type)  { startAdding(type, { cancelEditing }); }

// ─── Helpers ─────────────────────────────────────────────────────────────────
function progress(planned, actual) {
    if (!planned) return 0;
    return Math.min(100, Math.round((actual / planned) * 100));
}
function diffClass(diff, positiveIsGood) {
    if (diff === 0) return 'text-gray-400';
    return (positiveIsGood ? diff > 0 : diff < 0) ? 'text-emerald-400' : 'text-rose-400';
}
function onKeydown(e, submitFn, cancelFn) {
    if (e.key === 'Enter')  { e.preventDefault(); submitFn(); }
    if (e.key === 'Escape') { e.preventDefault(); cancelFn(); }
}
function onNotesKeydown(e, item) {
    if (e.key === 'Escape') { e.preventDefault(); cancelEditing(); }
    if (e.key === 'Tab' && !e.shiftKey) {
        e.preventDefault();
        document.getElementById(`edit-label-${item.id}`)?.focus();
    }
}

// ─── Keyboard navigation ──────────────────────────────────────────────────────
function onGlobalKeydown(e) {
    const tag = e.target.tagName;
    if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;
    if (e.key === 'ArrowLeft')  router.visit(`/wallets/${props.wallet.id}/budget?month=${props.prevMonth}`);
    if (e.key === 'ArrowRight') router.visit(`/wallets/${props.wallet.id}/budget?month=${props.nextMonth}`);
    if (e.key === 'n' || e.key === 'N') startAddingItem(Object.keys(props.sections)[0] ?? 'income');
}
onMounted(() => document.addEventListener('keydown', onGlobalKeydown));
onUnmounted(() => document.removeEventListener('keydown', onGlobalKeydown));
</script>

<template>
    <Head :title="`Budget ${budget.month_label} — ${wallet.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3 text-sm">
                    <Link href="/wallets" class="text-gray-400 hover:text-gray-200 transition-colors">{{ t('nav.wallets') }}</Link>
                    <span class="text-gray-600">/</span>
                    <span class="text-gray-100 font-medium">{{ wallet.name }}</span>
                </div>
                <button
                    class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors"
                    v-on:click="openTxPanel(null, '', 'expense', { cancelEditing, cancelAdding })"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    {{ t('budgets.newTransaction') }}
                </button>
            </div>
        </template>

        <!-- ── Month navigation ── -->
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <Link
                    :href="`/wallets/${wallet.id}/budget?month=${prevMonth}`"
                    class="flex items-center gap-1 text-gray-400 hover:text-gray-100 transition-colors text-sm capitalize"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    {{ fmtMonth(prevMonth) }}
                </Link>
                <div class="flex flex-col items-center gap-1">
                    <h2 class="text-xl font-bold text-gray-100 capitalize">{{ fmtMonth(budget.month) }}</h2>
                    <Link
                        :href="`/wallets/${wallet.id}/budget/year?year=${budget.month.slice(0, 4)}`"
                        class="text-xs text-gray-600 hover:text-gray-400 transition-colors"
                    >
                        {{ t('budgets.year.viewYear', { year: budget.month.slice(0, 4) }) }}
                    </Link>
                </div>
                <Link
                    :href="`/wallets/${wallet.id}/budget?month=${nextMonth}`"
                    class="flex items-center gap-1 text-gray-400 hover:text-gray-100 transition-colors text-sm capitalize"
                >
                    {{ fmtMonth(nextMonth) }}
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </Link>
            </div>

            <!-- ── KPI cards ── -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                <div class="bg-gray-900 border border-gray-700/60 rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">{{ t('budgets.kpi.startBalance') }}</p>
                    <p class="text-lg font-bold text-gray-100 font-mono">{{ fmt(startBalance) }}</p>
                </div>
                <div class="bg-gray-900 border border-gray-700/60 rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">{{ t('budgets.kpi.income') }}</p>
                    <p class="text-lg font-bold text-emerald-400 font-mono">{{ fmt(totalIncome.actual) }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">/ {{ fmt(totalIncome.planned) }} {{ t('budgets.kpi.planned') }}</p>
                </div>
                <div class="bg-gray-900 border border-gray-700/60 rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">{{ t('budgets.kpi.cashFlow') }}</p>
                    <p class="text-lg font-bold font-mono" :class="cashFlow.actual >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                        {{ fmt(cashFlow.actual, true) }}
                    </p>
                    <p class="text-xs text-gray-500 mt-0.5">/ {{ fmt(cashFlow.planned, true) }} {{ t('budgets.kpi.planned') }}</p>
                </div>
                <div class="bg-gray-900 border border-gray-700/60 rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">{{ t('budgets.kpi.leftToSpend') }}</p>
                    <p class="text-lg font-bold font-mono" :class="leftToSpend.actual >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                        {{ fmt(leftToSpend.actual) }}
                    </p>
                    <p class="text-xs text-gray-500 mt-0.5">/ {{ fmt(leftToSpend.planned) }} {{ t('budgets.kpi.planned') }}</p>
                </div>
                <div class="bg-gray-900 border border-gray-700/60 rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">{{ t('budgets.kpi.savingsRate') }}</p>
                    <p class="text-lg font-bold font-mono" :class="savingsRate === null ? 'text-gray-600' : savingsRate >= 20 ? 'text-emerald-400' : savingsRate >= 10 ? 'text-amber-400' : 'text-rose-400'">
                        {{ savingsRate !== null ? savingsRate + '%' : '—' }}
                    </p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ t('budgets.kpi.income') }} {{ fmt(totalIncome.actual) }}</p>
                </div>
            </div>

            <!-- ── Donut + gauge + projection ── -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="bg-gray-900 border border-gray-700/60 rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide mb-3">{{ t('budgets.kpi.distribution') }}</p>
                    <DonutChart v-if="donutSegments.length" :segments="donutSegments" :size="120" />
                    <p v-else class="text-sm text-gray-600">{{ t('budgets.noneThisMonth') }}</p>
                </div>
                <div class="bg-gray-900 border border-gray-700/60 rounded-lg p-4 flex flex-col items-center justify-center">
                    <p class="text-xs text-gray-500 uppercase tracking-wide mb-3 self-start">{{ t('budgets.kpi.leftToSpend') }}</p>
                    <BudgetGauge
                        :spent="totalExpenses.actual"
                        :total="totalIncome.actual"
                        :center="fmt(leftToSpend.actual)"
                        :sublabel="t('budgets.kpi.leftToSpend')"
                        :size="160"
                    />
                </div>
                <div class="bg-gray-900 border border-gray-700/60 rounded-lg p-4 flex flex-col justify-between">
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">
                            {{ t('budgets.kpi.projection') }}
                            <span v-if="!isCurrentMonth" class="normal-case text-gray-600 ml-1">{{ t('budgets.projectionPast') }}</span>
                        </p>
                        <p v-if="projectedExpenses !== null" class="text-lg font-bold font-mono" :class="projectedExpenses > totalExpenses.planned ? 'text-rose-400' : 'text-emerald-400'">
                            {{ fmt(projectedExpenses) }}
                        </p>
                        <p v-else class="text-lg font-bold text-gray-500 font-mono">—</p>
                        <p v-if="projectedExpenses !== null" class="text-xs text-gray-500 mt-0.5">
                            vs {{ fmt(totalExpenses.planned) }} {{ t('budgets.kpi.planned') }}
                        </p>
                    </div>
                    <p v-if="projectedExpenses !== null" class="text-xs text-gray-600 mt-2">
                        {{ t('budgets.projectionBased', { days: new Date().getDate() }) }}
                    </p>
                </div>
            </div>

            <!-- ── Copy from previous / empty state ── -->
            <div v-if="isBudgetEmpty" class="bg-gray-900 border border-dashed border-gray-700 rounded-lg p-8 text-center">
                <p class="text-gray-400 mb-4">{{ t('budgets.emptyBudget') }}</p>
                <button
                    class="inline-flex items-center gap-2 bg-gray-800 hover:bg-gray-700 text-gray-200 text-sm font-medium px-4 py-2 rounded-lg border border-gray-700 transition-colors"
                    v-on:click="copyFromPrevious"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                    {{ t('budgets.copyFromPrevious', { month: fmtMonth(prevMonth) }) }}
                </button>
            </div>

            <!-- ── Budget notes ── -->
            <div class="bg-gray-900 border border-gray-700/60 rounded-lg">
                <button
                    class="flex items-center justify-between w-full px-4 py-3 text-xs text-gray-500 hover:text-gray-300 transition-colors"
                    v-on:click="budgetNotesOpen = !budgetNotesOpen"
                >
                    <span class="flex items-center gap-2 uppercase tracking-wide font-medium">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        {{ t('budgets.notes.label') }}
                        <span v-if="budgetNotesText" class="text-indigo-400 normal-case tracking-normal font-normal">{{ budgetNotesText.length > 60 ? budgetNotesText.slice(0, 60) + '…' : budgetNotesText }}</span>
                    </span>
                    <svg
                        class="w-3.5 h-3.5 transition-transform duration-200"
                        :class="budgetNotesOpen ? '' : '-rotate-90'"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                </button>
                <div v-if="budgetNotesOpen" class="px-4 pb-3">
                    <textarea
                        v-model="budgetNotesText"
                        :placeholder="t('budgets.notes.placeholder')"
                        rows="3"
                        class="w-full bg-gray-800 text-gray-200 rounded-lg px-3 py-2 text-sm border border-gray-700 focus:border-indigo-500 focus:outline-none resize-none"
                        v-on:blur="saveBudgetNotes"
                        v-on:keydown.esc="saveBudgetNotes"
                    />
                </div>
            </div>

            <!-- ── Table toolbar ── -->
            <div class="flex items-center justify-between -mb-1">
                <p class="text-xs text-gray-600">{{ t('budgets.hint') }}</p>
                <div class="flex items-center gap-2">
                    <AppButton
                        v-if="!isBudgetEmpty"
                        size="sm"
                        variant="secondary"
                        v-on:click="copyFromPrevious"
                    >
                        {{ t('budgets.copyFromPrevious', { month: fmtMonth(prevMonth) }) }}
                    </AppButton>
                    <AppButton size="sm" variant="secondary" v-on:click="copyRecurring">
                        {{ t('budgets.copyRecurring') }}
                    </AppButton>
                    <AppButton size="sm" variant="secondary" v-on:click="exportXlsx">
                        {{ t('budgets.exportXlsx') }}
                    </AppButton>
                </div>
            </div>

            <!-- ── Budget table ── -->
            <div class="bg-gray-900 border border-gray-700/60 rounded-lg overflow-clip">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-700/60 text-xs text-gray-500 uppercase tracking-wider sticky top-0 z-10 bg-gray-900">
                            <th class="text-left px-4 py-3 font-medium w-[34%]">{{ t('budgets.table.label') }}</th>
                            <th class="text-left px-4 py-3 font-medium w-[20%]">{{ t('budgets.table.category') }}</th>
                            <th class="text-right px-4 py-3 font-medium w-[13%]">{{ t('budgets.table.planned') }}</th>
                            <th class="text-right px-4 py-3 font-medium w-[13%]">{{ t('budgets.table.actual') }}</th>
                            <th class="text-right px-4 py-3 font-medium w-[10%]">{{ t('budgets.table.diff') }}</th>
                            <th class="w-[10%]" />
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="(items, type) in sections" :key="type">
                            <!-- ── Section header ── -->
                            <tr
                                :class="[SECTION_META[type].bg, SECTION_META[type].border, 'border-y cursor-pointer select-none']"
                                v-on:click="toggleSection(type)"
                            >
                                <td class="px-4 py-2" colspan="2">
                                    <div class="flex items-center gap-3">
                                        <span :class="[SECTION_META[type].color, 'font-semibold uppercase text-xs tracking-widest']">
                                            {{ SECTION_META[type].label }}
                                        </span>
                                        <template v-if="!collapsedSections[type]">
                                            <div class="flex-1 max-w-[120px] h-1.5 bg-gray-700 rounded-full">
                                                <div
                                                    class="h-full rounded-full transition-all duration-300"
                                                    :class="SECTION_META[type].barColor"
                                                    :style="{ width: progress(totals[type]?.planned, totals[type]?.actual) + '%', boxShadow: progress(totals[type]?.planned, totals[type]?.actual) > 0 ? `0 0 6px 1px ${SECTION_META[type].glow}80` : 'none' }"
                                                />
                                            </div>
                                            <span class="text-xs text-gray-500">{{ progress(totals[type]?.planned, totals[type]?.actual) }}%</span>
                                        </template>
                                        <span v-else class="text-xs text-gray-600">{{ items.length }} ligne{{ items.length > 1 ? 's' : '' }}</span>
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
                                <td class="px-3 py-2 text-right">
                                    <svg
                                        class="w-3.5 h-3.5 inline transition-transform duration-200"
                                        :class="[SECTION_META[type].color, collapsedSections[type] ? '-rotate-90' : '']"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </td>
                            </tr>

                            <!-- ── Rows ── -->
                            <template v-if="!collapsedSections[type]">
                                <template v-for="item in items" :key="item.id">
                                    <!-- Inline edit -->
                                    <template v-if="editingId === item.id">
                                        <tr class="bg-gray-800 border-b border-gray-700/40" data-editing>
                                            <td class="pl-8 pr-2 py-1.5">
                                                <input
                                                    :id="`edit-label-${item.id}`"
                                                    v-model="editForm.label"
                                                    type="text"
                                                    tabindex="1"
                                                    :placeholder="t('budgets.editRow.labelPlaceholder')"
                                                    class="w-full bg-gray-700 text-gray-100 rounded px-2 py-1 text-sm border border-gray-600 focus:border-indigo-500 focus:outline-none"
                                                    v-on:keydown="onKeydown($event, () => submitEdit(item), cancelEditing)"
                                                >
                                            </td>
                                            <td class="px-2 py-1.5">
                                                <select
                                                    v-model="editForm.category_id"
                                                    tabindex="2"
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
                                                    tabindex="3"
                                                    placeholder="0,00"
                                                    class="w-full bg-gray-700 text-gray-100 rounded px-2 py-1 text-sm border border-gray-600 focus:border-indigo-500 focus:outline-none text-right font-mono"
                                                    v-on:keydown="onKeydown($event, () => submitEdit(item), cancelEditing)"
                                                >
                                            </td>
                                            <td class="px-2 py-1.5 text-right text-gray-500 font-mono text-xs">{{ fmt(item.actual_amount) }}</td>
                                            <td />
                                            <td class="px-3 py-1.5">
                                                <div class="flex items-center gap-2 justify-end">
                                                    <button class="text-emerald-400 hover:text-emerald-300 transition-colors" :title="t('budgets.editRow.confirm')" v-on:click="submitEdit(item)">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                    </button>
                                                    <button class="text-gray-500 hover:text-gray-300 transition-colors" :title="t('budgets.editRow.cancel')" v-on:click="cancelEditing">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr class="bg-gray-800 border-b border-gray-700/40" data-editing>
                                            <td colspan="6" class="pl-8 pr-3 pb-2 space-y-1.5">
                                                <textarea
                                                    v-model="editForm.notes"
                                                    :placeholder="t('budgets.editRow.notePlaceholder')"
                                                    rows="2"
                                                    tabindex="4"
                                                    class="w-full bg-gray-700 text-gray-300 rounded px-2 py-1 text-xs border border-gray-600 focus:border-indigo-500 focus:outline-none resize-none"
                                                    v-on:keydown="onNotesKeydown($event, item)"
                                                />
                                                <div class="flex items-center gap-2">
                                                    <label class="text-xs text-gray-500">{{ t('budgets.editRow.section') }}</label>
                                                    <select
                                                        v-model="editForm.type"
                                                        class="bg-gray-700 text-gray-200 rounded px-2 py-0.5 text-xs border border-gray-600 focus:border-indigo-500 focus:outline-none"
                                                    >
                                                        <option v-for="(meta, stype) in SECTION_META" :key="stype" :value="stype">{{ meta.label }}</option>
                                                    </select>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>

                                    <!-- Read + actions -->
                                    <tr
                                        v-else
                                        draggable="true"
                                        class="border-b border-gray-800/60 group hover:bg-gray-800/40 cursor-pointer transition-colors"
                                        :class="[
                                            item.planned_amount > 0 && item.actual_amount > item.planned_amount ? 'border-l-2 border-l-rose-500/60' : '',
                                            deletingItem && deletingItem.id === item.id ? 'opacity-40 pointer-events-none' : '',
                                            draggingId === item.id ? 'opacity-50' : '',
                                            dragOverId === item.id ? 'bg-indigo-500/10' : '',
                                        ]"
                                        :data-row-id="item.id"
                                        v-on:click="startEditingItem(item)"
                                        v-on:dragstart="onDragStart($event, item)"
                                        v-on:dragend="onDragEnd"
                                        v-on:dragover="onDragOver($event, item)"
                                        v-on:drop.prevent="onDrop(item)"
                                    >
                                        <td class="pl-4 pr-4 py-2.5 text-gray-200">
                                            <div class="flex items-center gap-2">
                                                <div class="text-gray-700 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                                                    <svg class="w-3 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <circle cx="9" cy="5" r="1.5" /><circle cx="15" cy="5" r="1.5" />
                                                        <circle cx="9" cy="12" r="1.5" /><circle cx="15" cy="12" r="1.5" />
                                                        <circle cx="9" cy="19" r="1.5" /><circle cx="15" cy="19" r="1.5" />
                                                    </svg>
                                                </div>
                                                <span>{{ item.label }}</span>
                                                <NoteTooltip v-if="item.notes" :note="item.notes" />
                                                <span
                                                    v-if="item.is_recurring"
                                                    class="inline-flex items-center gap-0.5 text-[10px] text-indigo-400 border border-indigo-500/30 rounded px-1 py-0.5 leading-none"
                                                    :title="t('budgets.recurring')"
                                                >
                                                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <span v-if="item.category" class="inline-flex items-center text-xs bg-gray-800 text-gray-300 rounded px-2 py-0.5 border border-gray-700">
                                                {{ item.category.name }}
                                            </span>
                                            <span v-else class="text-gray-600">—</span>
                                        </td>
                                        <td class="px-4 py-2.5 text-right text-gray-400 font-mono">
                                            <div>{{ fmt(item.planned_amount) }}</div>
                                            <div v-if="item.planned_amount > 0" class="mt-1 h-0.5 w-full bg-gray-700 rounded-full">
                                                <div
                                                    class="h-full rounded-full transition-all duration-300"
                                                    :class="item.actual_amount > item.planned_amount ? 'bg-rose-400' : SECTION_META[type]?.barColor"
                                                    :style="{ width: progress(item.planned_amount, item.actual_amount) + '%', boxShadow: progress(item.planned_amount, item.actual_amount) > 0 ? `0 0 4px 1px ${item.actual_amount > item.planned_amount ? '#fb7185' : SECTION_META[type]?.glow}80` : 'none' }"
                                                />
                                            </div>
                                        </td>
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
                                                    :title="t('budgets.actions.addTx')"
                                                    v-on:click.stop="openTxPanelFromRow(item.category_id, item.label, type === 'income' ? 'income' : 'expense', type)"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                                </button>
                                                <!-- Toggle recurring -->
                                                <button
                                                    class="transition-colors"
                                                    :class="item.is_recurring ? 'text-indigo-400 hover:text-indigo-300' : 'text-gray-500 hover:text-indigo-400'"
                                                    :title="t('budgets.actions.toggleRecurring')"
                                                    v-on:click.stop="toggleRecurring(item)"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                                </button>
                                                <!-- Duplicate -->
                                                <button
                                                    class="text-gray-500 hover:text-amber-400 transition-colors"
                                                    :title="t('budgets.actions.duplicate')"
                                                    v-on:click.stop="duplicateItem(item)"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                                </button>
                                                <button class="text-gray-500 hover:text-sky-400 transition-colors" :title="t('budgets.actions.edit')" v-on:click.stop="startEditingItem(item)">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                </button>
                                                <button class="text-gray-500 hover:text-rose-400 transition-colors" :title="t('budgets.actions.delete')" v-on:click.stop="requestDelete(item)">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>

                                <!-- ── Add budget row ── -->
                                <tr v-if="addingType === type" class="bg-gray-800/60 border-b border-gray-700/40" data-adding>
                                    <td class="pl-8 pr-2 py-1.5">
                                        <input
                                            :id="`add-label-${type}`"
                                            v-model="addForm.label"
                                            type="text"
                                            :placeholder="t('budgets.addRow.labelPlaceholder')"
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
                                            <button class="text-emerald-400 hover:text-emerald-300 transition-colors" :title="t('budgets.addRow.confirm')" v-on:click="submitAdd">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                            </button>
                                            <button class="text-gray-500 hover:text-gray-300 transition-colors" :title="t('budgets.addRow.cancel')" v-on:click="cancelAdding">
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
                                            {{ t('budgets.addRow.addLine') }}
                                        </AppButton>
                                    </td>
                                </tr>
                            </template><!-- end v-if !collapsedSections -->
                        </template>

                        <!-- ── Cash flow summary ── -->
                        <tr class="border-t-2 border-gray-600 bg-gray-800/30">
                            <td class="px-4 py-3 font-semibold text-gray-300 text-xs uppercase tracking-wide" colspan="2">{{ t('budgets.table.cashFlow') }}</td>
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
                            <td class="px-4 py-3 font-semibold text-gray-300 text-xs uppercase tracking-wide" colspan="2">{{ t('budgets.table.leftToSpend') }}</td>
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
        </div>

        <!-- ── Transaction slide-over ── -->
        <BudgetTxPanel
            :open="txPanel"
            :prefill-label="txPrefillLabel"
            :tx-section="txSection"
            :tx-form="txForm"
            :section-meta="SECTION_META"
            :filtered-categories="txFilteredCategories"
            v-on:close="closeTxPanel"
            v-on:submit="submitTx"
            v-on:section-change="onTxSectionChange"
        />

        <!-- ── Transaction detail slide-over ── -->
        <BudgetDetailPanel
            :open="txDetailOpen"
            :item="txDetailItem"
            :loading="txDetailLoading"
            :transactions="txDetailList"
            v-on:close="closeTxDetail"
        />

        <!-- ── Delete confirm modal ── -->
        <ConfirmModal
            :show="pendingDeleteItem !== null"
            :message="pendingDeleteItem ? t('budgets.actions.confirmDelete', { label: pendingDeleteItem.label }) : ''"
            v-on:confirm="confirmDelete"
            v-on:cancel="cancelDelete"
        />

        <!-- ── Undo delete toast ── -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-2"
        >
            <AppToast
                v-if="deletingItem"
                :message="t('budgets.toast.deleted', { label: deletingItem.label })"
                :action="t('budgets.toast.undo')"
                :duration="5000"
                v-on:action="undoDelete"
                v-on:dismiss="undoDelete"
            />
        </Transition>
    </AuthenticatedLayout>
</template>
