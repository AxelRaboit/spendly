<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppToast from '@/components/ui/AppToast.vue';
import ConfirmModal from '@/components/ui/ConfirmModal.vue';
import CopyBudgetModal from '@/components/ui/CopyBudgetModal.vue';
import NoteTooltip from '@/components/ui/NoteTooltip.vue';
import DonutChart from '@/components/ui/DonutChart.vue';
import BudgetGauge from '@/components/ui/BudgetGauge.vue';
import BudgetTxPanel from '@/components/budget/BudgetTxPanel.vue';
import BudgetDetailPanel from '@/components/budget/BudgetDetailPanel.vue';
import GoalDepositModal from '@/components/budget/GoalDepositModal.vue';
import { useBudgetTotals }   from '@/composables/budget/useBudgetTotals';
import { useBudgetItems }    from '@/composables/budget/useBudgetItems';
import { useCopyPrevious }   from '@/composables/budget/useCopyPrevious';
import { useCopyRepeat }  from '@/composables/budget/useCopyRepeat';
import { useBudgetExport }   from '@/composables/budget/useBudgetExport';
import { useBudgetNotes }    from '@/composables/budget/useBudgetNotes';
import { useSectionMeta }    from '@/composables/budget/useSectionMeta';
import { useDonutSegments }  from '@/composables/budget/useDonutSegments';
import { useSectionCollapse } from '@/composables/budget/useSectionCollapse';
import { useRowFlash }       from '@/composables/budget/useRowFlash';
import { useFmtMonth }       from '@/composables/core/useFmtMonth';
import { useTransactionPanel } from '@/composables/budget/useTransactionPanel';
import { useItemTransactions } from '@/composables/budget/useItemTransactions';
import { useCurrency }       from '@/composables/core/useCurrency';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref, toRef } from 'vue';
import { useI18n } from 'vue-i18n';

// ─── i18n / currency ─────────────────────────────────────────────────────────
const { t, locale } = useI18n();
const { fmt } = useCurrency();
const { fmtMonth } = useFmtMonth();
const fmtDate = (d) => d ? new Intl.DateTimeFormat(locale.value, { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' }).format(new Date(d)) : '';
const page = usePage();
const isPro = computed(() => page.props.auth.user.plan === 'pro');

// ─── Props ───────────────────────────────────────────────────────────────────
const props = defineProps({
    wallet:        Object,
    budget:        Object,
    sections:      Object,
    unbudgeted:    { type: Object, default: () => ({ income: 0, expenses: 0 }) },
    categories:    Array,
    prevMonth:     String,
    nextMonth:     String,
    startBalance:  Number,
    flashCategory: { type: Number, default: null },
    prevItems:     { type: Array,  default: () => [] },
    goals:         { type: Array,  default: () => [] },
});

// ─── Reactive prop refs ───────────────────────────────────────────────────────
const walletId     = computed(() => props.wallet.id);
const budget       = toRef(props, 'budget');
const sections     = toRef(props, 'sections');
const unbudgeted   = toRef(props, 'unbudgeted');
const startBalance = toRef(props, 'startBalance');
const prevMonth    = toRef(props, 'prevMonth');
const categories   = computed(() => props.categories);

// ─── Section meta + donut ─────────────────────────────────────────────────────
const { SECTION_META } = useSectionMeta();

// ─── Totals / KPIs ───────────────────────────────────────────────────────────
const { totals, totalIncome, totalExpenses, cashFlow, leftToSpend, savingsRate, isCurrentMonth, projectedExpenses } =
    useBudgetTotals(sections, startBalance, budget, unbudgeted);

const hasUnbudgeted = computed(() => (unbudgeted.value?.income ?? 0) > 0 || (unbudgeted.value?.expenses ?? 0) > 0);

const donutSegments = useDonutSegments(totals, SECTION_META);

// ─── Row flash ────────────────────────────────────────────────────────────────
const { flash } = useRowFlash();

// ─── Budget rows ─────────────────────────────────────────────────────────────
const {
    editingId, editForm, startEditing, cancelEditing, submitEdit,
    addingType, addForm, startAdding, cancelAdding, submitAdd,
    pendingDeleteItem, deletingItem, requestDelete, confirmDelete, undoDelete, cancelDelete,
    draggingId, dragOverId, onDragStart, onDragOver, onDrop, onDragEnd,
    duplicateItem, toggleRepeat,
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

const isBudgetEmpty = computed(() => Object.values(sections.value).every((arr) => arr.length === 0));

const { showModal: showCopyPreviousModal, modalMessage: copyPreviousModalMessage, open: copyFromPrevious, confirm: confirmCopyPrevious, cancel: cancelCopyPrevious } =
    useCopyPrevious(walletId, budget, isBudgetEmpty, prevMonth, fmtMonth, t);

const { showModal: showCopyRepeatModal, open: copyRepeat, confirm: confirmCopyRepeat, cancel: cancelCopyRepeat } =
    useCopyRepeat(walletId, budget);

// ─── Budget notes ─────────────────────────────────────────────────────────────
const { budgetNotesOpen, budgetNotesText, saveBudgetNotes } =
    useBudgetNotes(walletId, budget);

// ─── Export XLSX ─────────────────────────────────────────────────────────────
const { exportXlsx } = useBudgetExport(sections, totals, SECTION_META, budget, t);

// ─── Overage count ───────────────────────────────────────────────────────────
const overageCount = computed(() =>
    Object.entries(props.sections)
        .flatMap(([stype, items]) =>
            SECTION_META.value[stype]?.positiveIsGood
                ? []
                : items.filter((i) => i.planned_amount > 0 && i.actual_amount > i.planned_amount)
        )
        .length
);

const overageGoodCount = computed(() =>
    Object.entries(props.sections)
        .flatMap(([stype, items]) =>
            SECTION_META.value[stype]?.positiveIsGood
                ? items.filter((i) => i.planned_amount > 0 && i.actual_amount > i.planned_amount)
                : []
        )
        .length
);

// ─── Goal deposit modal ───────────────────────────────────────────────────────
const depositGoal = ref(null);

const savingsCategories = computed(() => {
    const savingsItems = sections.value.savings ?? [];
    const ids = new Set(savingsItems.map(i => i.category_id).filter(Boolean));
    return props.categories.filter(c => ids.has(c.id));
});

// ─── Mobile row context menu ──────────────────────────────────────────────────
const mobileMenuOpenId = ref(null);
function toggleMobileMenu(id) { mobileMenuOpenId.value = mobileMenuOpenId.value === id ? null : id; }
function closeMobileMenu() { mobileMenuOpenId.value = null; }

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
    if (diff === 0) return 'text-secondary';
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
onMounted(async () => {
    document.addEventListener('keydown', onGlobalKeydown);
    document.addEventListener('click', closeMobileMenu);

    // Flash row when coming from dashboard
    if (props.flashCategory) {
        for (const items of Object.values(props.sections)) {
            const match = items.find(i => i.category_id === props.flashCategory);
            if (match) {
                await nextTick();
                document.querySelector(`tr[data-row-id="${match.id}"]`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                flash(match.id, 'indigo');
                break;
            }
        }
    }
});
onUnmounted(() => {
    document.removeEventListener('keydown', onGlobalKeydown);
    document.removeEventListener('click', closeMobileMenu);
});
</script>

<template>
    <Head :title="`Budget ${budget.month_label} — ${wallet.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <AppPageHeader
                :crumbs="[
                    { label: t('nav.wallets'), href: '/wallets' },
                    { label: wallet.name },
                ]"
            />
        </template>

        <div class="space-y-6">
            <div class="flex justify-end">
                <button
                    class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors"
                    v-on:click="openTxPanel(null, '', 'expense', { cancelEditing, cancelAdding })"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    {{ t('budgets.newTransaction') }}
                </button>
            </div>
            <div class="flex items-center justify-between">
                <Link
                    :href="`/wallets/${wallet.id}/budget?month=${prevMonth}`"
                    class="flex items-center gap-1 text-secondary hover:text-primary transition-colors text-sm capitalize"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    <span class="hidden sm:inline">{{ fmtMonth(prevMonth) }}</span>
                </Link>
                <div class="flex flex-col items-center gap-1">
                    <h2 class="text-xl font-bold text-primary capitalize">{{ fmtMonth(budget.month) }}</h2>
                    <Link
                        :href="`/wallets/${wallet.id}/budget/year?year=${budget.month.slice(0, 4)}`"
                        class="text-xs text-subtle hover:text-secondary transition-colors"
                    >
                        {{ t('budgets.year.viewYear', { year: budget.month.slice(0, 4) }) }}
                    </Link>
                </div>
                <Link
                    :href="`/wallets/${wallet.id}/budget?month=${nextMonth}`"
                    class="flex items-center gap-1 text-secondary hover:text-primary transition-colors text-sm capitalize"
                >
                    <span class="hidden sm:inline">{{ fmtMonth(nextMonth) }}</span>
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </Link>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                <div class="bg-surface border border-base/60 rounded-lg p-4">
                    <p class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('budgets.kpi.startBalance') }}</p>
                    <p class="text-lg font-bold text-primary font-mono">{{ fmt(startBalance) }}</p>
                </div>
                <div class="bg-surface border border-base/60 rounded-lg p-4">
                    <p class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('budgets.kpi.income') }}</p>
                    <p class="text-lg font-bold text-emerald-400 font-mono">{{ fmt(totalIncome.actual) }}</p>
                    <p class="text-xs text-muted mt-0.5">/ {{ fmt(totalIncome.planned) }} {{ t('budgets.kpi.planned') }}</p>
                </div>
                <div class="bg-surface border border-base/60 rounded-lg p-4">
                    <p class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('budgets.kpi.expenses') }}</p>
                    <p class="text-lg font-bold text-rose-400 font-mono">{{ fmt(totalExpenses.actual) }}</p>
                    <p class="text-xs text-muted mt-0.5">/ {{ fmt(totalExpenses.planned) }} {{ t('budgets.kpi.planned') }}</p>
                </div>
                <div class="bg-surface border border-base/60 rounded-lg p-4">
                    <p class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('budgets.kpi.cashFlow') }}</p>
                    <p class="text-lg font-bold font-mono" :class="cashFlow.actual >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                        {{ fmt(cashFlow.actual, true) }}
                    </p>
                    <p class="text-xs text-muted mt-0.5">/ {{ fmt(cashFlow.planned, true) }} {{ t('budgets.kpi.planned') }}</p>
                </div>
                <div class="bg-surface border border-base/60 rounded-lg p-4">
                    <p class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('budgets.kpi.savingsRate') }}</p>
                    <p class="text-lg font-bold font-mono" :class="savingsRate === null ? 'text-subtle' : savingsRate >= 20 ? 'text-emerald-400' : savingsRate >= 10 ? 'text-amber-400' : 'text-rose-400'">
                        {{ savingsRate !== null ? savingsRate + '%' : '—' }}
                    </p>
                    <p class="text-xs text-muted mt-0.5">{{ t('budgets.kpi.income') }} {{ fmt(totalIncome.actual) }}</p>
                </div>
            </div>

            <div v-if="!isBudgetEmpty && (overageCount > 0 || overageGoodCount > 0)" class="flex flex-col gap-2">
                <div
                    v-if="overageCount > 0"
                    class="flex items-center gap-3 bg-rose-500/10 border border-rose-500/30 rounded-lg px-4 py-3"
                >
                    <svg class="w-4 h-4 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                    <p class="text-sm text-rose-300">{{ t('budgets.overageAlert', overageCount, { count: overageCount }) }}</p>
                </div>
                <div
                    v-if="overageGoodCount > 0"
                    class="flex items-center gap-3 bg-emerald-500/10 border border-emerald-500/30 rounded-lg px-4 py-3"
                >
                    <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm text-emerald-300">{{ t('budgets.overageGood', overageGoodCount, { count: overageGoodCount }) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="bg-surface border border-base/60 rounded-lg p-4">
                    <p class="text-xs text-muted uppercase tracking-wide mb-3">{{ t('budgets.kpi.distribution') }}</p>
                    <DonutChart v-if="donutSegments.length" :segments="donutSegments" :size="120" />
                    <p v-else class="text-sm text-subtle">{{ t('budgets.noneThisMonth') }}</p>
                </div>
                <div class="bg-surface border border-base/60 rounded-lg p-4 flex flex-col justify-between gap-4">
                    <p class="text-xs text-muted uppercase tracking-wide">{{ t('budgets.kpi.leftToSpend') }}</p>
                    <div>
                        <div class="flex items-end justify-between mb-2">
                            <div>
                                <span class="text-2xl font-bold font-mono" :class="leftToSpend.actual < 0 ? 'text-rose-400' : 'text-emerald-400'">
                                    {{ fmt(leftToSpend.actual) }}
                                </span>
                                <span class="text-xs text-muted font-mono ml-1.5">/ {{ fmt(leftToSpend.planned) }} {{ t('budgets.kpi.planned') }}</span>
                            </div>
                            <span class="text-sm text-muted font-mono">
                                {{ totalIncome.actual > 0 ? Math.min(100, Math.round((totalExpenses.actual / totalIncome.actual) * 100)) : 0 }}%
                            </span>
                        </div>
                        <div class="h-3 bg-surface-3 rounded-full overflow-hidden">
                            <div
                                v-show="totalIncome.actual > 0"
                                class="h-full rounded-full transition-all duration-500"
                                :class="totalExpenses.actual > totalIncome.actual ? 'bg-rose-400' : 'bg-emerald-400'"
                                :style="{
                                    width: totalIncome.actual > 0 ? Math.min(100, (totalExpenses.actual / totalIncome.actual) * 100) + '%' : '0%',
                                }"
                            />
                        </div>
                        <div class="flex justify-between text-xs text-muted mt-1.5">
                            <span>{{ fmt(totalExpenses.actual) }} {{ t('budgets.kpi.spent') }}</span>
                            <span>{{ fmt(totalIncome.actual) }}</span>
                        </div>
                    </div>
                </div>
                <div class="bg-surface border border-base/60 rounded-lg p-4 flex flex-col justify-between">
                    <div>
                        <p class="text-xs text-muted uppercase tracking-wide mb-1">
                            {{ t('budgets.kpi.projection') }}
                            <span v-if="!isCurrentMonth" class="normal-case text-subtle ml-1">{{ t('budgets.projectionPast') }}</span>
                        </p>
                        <p v-if="projectedExpenses !== null" class="text-lg font-bold font-mono" :class="projectedExpenses > totalExpenses.planned ? 'text-rose-400' : 'text-emerald-400'">
                            {{ fmt(projectedExpenses) }}
                        </p>
                        <p v-else class="text-lg font-bold text-muted font-mono">—</p>
                        <p v-if="projectedExpenses !== null" class="text-xs text-muted mt-0.5">
                            vs {{ fmt(totalExpenses.planned) }} {{ t('budgets.kpi.planned') }}
                        </p>
                    </div>
                    <p v-if="projectedExpenses !== null" class="text-xs text-subtle mt-2">
                        {{ t('budgets.projectionBased', { days: new Date().getDate() }) }}
                    </p>
                </div>
            </div>

            <div v-if="isBudgetEmpty" class="bg-surface border border-dashed border-base rounded-lg p-8 text-center">
                <p class="text-secondary mb-4">{{ t('budgets.emptyBudget') }}</p>
                <button
                    class="inline-flex items-center gap-2 bg-surface-2 hover:bg-surface-3 text-primary text-sm font-medium px-4 py-2 rounded-lg border border-base transition-colors"
                    v-on:click="copyFromPrevious"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                    {{ t('budgets.copyFromPrevious', { month: fmtMonth(prevMonth) }) }}
                </button>
            </div>

            <div class="bg-surface border border-base/60 rounded-lg relative">
                <button
                    class="flex items-center justify-between w-full px-4 py-3 text-xs transition-colors text-muted hover:text-secondary cursor-pointer"
                    v-on:click="budgetNotesOpen = !budgetNotesOpen"
                >
                    <span class="flex items-center gap-2 uppercase tracking-wide font-medium">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        {{ t('budgets.notes.label') }}
                        <span v-if="budgetNotesText" class="text-indigo-400 normal-case tracking-normal font-normal truncate max-w-[120px] sm:max-w-xs">{{ budgetNotesText }}</span>
                    </span>
                    <div class="flex items-center gap-2">
                        <svg
                            class="w-3.5 h-3.5 transition-transform duration-200"
                            :class="budgetNotesOpen ? '' : '-rotate-90'"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </div>
                </button>
                <div v-if="budgetNotesOpen" class="px-4 pb-3">
                    <textarea
                        v-model="budgetNotesText"
                        :placeholder="t('budgets.notes.placeholder')"
                        rows="3"
                        class="w-full bg-surface-2 text-primary rounded-lg px-3 py-2 text-sm border border-base focus:border-indigo-500 focus:outline-none resize-none"
                        v-on:blur="saveBudgetNotes"
                        v-on:keydown.esc="saveBudgetNotes"
                    />
                </div>
            </div>

            <div v-if="goals.length > 0">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-secondary uppercase tracking-wide">{{ t('goals.title') }}</h3>
                    <Link href="/goals" class="text-xs text-muted hover:text-secondary transition-colors">{{ t('common.seeAll') }} →</Link>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                    <div
                        v-for="goal in goals"
                        :key="`goal-card-${goal.id}`"
                        class="bg-surface-2 border border-base rounded-xl p-4 flex flex-col gap-3"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-3 h-3 rounded-full shrink-0" :style="{ backgroundColor: goal.color }" />
                                <span class="text-sm font-medium text-primary truncate">{{ goal.name }}</span>
                            </div>
                            <button
                                v-if="!goal.category_id"
                                class="shrink-0 text-muted hover:text-emerald-400 transition-colors"
                                :title="t('goals.deposit')"
                                v-on:click="depositGoal = goal"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            </button>
                            <span v-else class="shrink-0 text-xs text-indigo-400">↻</span>
                        </div>

                        <div>
                            <div class="h-2 bg-surface-3 rounded-full overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :style="{ width: Math.min(goal.progress, 100) + '%', backgroundColor: goal.color }"
                                />
                            </div>
                            <div class="flex justify-between items-center mt-1.5">
                                <span class="text-xs text-muted font-mono">{{ fmt(goal.saved_amount) }}</span>
                                <span class="text-xs font-semibold" :style="{ color: goal.color }">
                                    {{ goal.progress >= 100 ? t('goals.completed') : goal.progress + '%' }}
                                </span>
                                <span class="text-xs text-muted font-mono">{{ fmt(goal.target_amount) }}</span>
                            </div>
                        </div>

                        <div class="text-xs text-muted">
                            <span v-if="goal.category_id" class="text-indigo-400">{{ t('goals.autoSync', { category: goal.category?.name }) }}</span>
                            <span v-else-if="goal.deadline">{{ t('goals.deadline', { date: fmtDate(goal.deadline) }) }}</span>
                            <span v-else class="invisible">—</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 -mb-1">
                <p class="hidden sm:block text-xs text-subtle">{{ isPro ? t('budgets.hint') : '' }}</p>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                    <AppButton
                        v-if="!isBudgetEmpty"
                        size="sm"
                        variant="secondary"
                        :disabled="!isPro"
                        :class="!isPro ? 'opacity-50 cursor-not-allowed' : ''"
                        v-on:click="isPro && copyFromPrevious()"
                    >
                        {{ t('budgets.copyFromPrevious', { month: fmtMonth(prevMonth) }) }}
                    </AppButton>
                    <AppButton
                        size="sm"
                        variant="secondary"
                        :disabled="!isPro"
                        :class="!isPro ? 'opacity-50 cursor-not-allowed' : ''"
                        v-on:click="isPro && copyRepeat()"
                    >
                        {{ t('budgets.copyRepeat') }}
                    </AppButton>
                    <AppButton
                        size="sm"
                        variant="secondary"
                        :disabled="!isPro"
                        :class="!isPro ? 'opacity-50 cursor-not-allowed' : ''"
                        v-on:click="isPro && exportXlsx()"
                    >
                        {{ t('budgets.exportXlsx') }}
                    </AppButton>
                    <span v-if="!isPro" class="text-xs font-semibold bg-amber-500 text-white px-2 py-1 rounded self-center">Pro</span>
                </div>
            </div>

            <div class="bg-surface border border-base/60 rounded-lg overflow-clip">
                <div class="md:hidden divide-y divide-base/40">
                    <template v-for="(items, type) in sections" :key="`mob-${type}`">
                        <div
                            :class="[SECTION_META[type].bg, SECTION_META[type].border, 'border-b px-4 py-2.5 flex flex-col gap-1 cursor-pointer select-none']"
                            v-on:click="toggleSection(type)"
                        >
                            <div class="flex items-center justify-between">
                                <span :class="[SECTION_META[type].color, 'font-semibold uppercase text-xs tracking-widest']">
                                    {{ SECTION_META[type].label }}
                                </span>
                                <svg
                                    class="w-3.5 h-3.5 transition-transform duration-200"
                                    :class="[SECTION_META[type].color, collapsedSections[type] ? '-rotate-90' : '']"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                            <div class="flex items-center gap-2">
                                <span :class="[SECTION_META[type].color, 'text-sm font-mono font-semibold']">{{ fmt(totals[type]?.actual ?? 0) }}</span>
                                <span class="text-xs text-muted font-mono">/ {{ fmt(totals[type]?.planned ?? 0) }}</span>
                                <span v-if="collapsedSections[type]" class="text-xs text-subtle ml-1">· {{ items.length }} ligne{{ items.length > 1 ? 's' : '' }}</span>
                            </div>
                        </div>

                        <template v-if="!collapsedSections[type]">
                            <template v-for="item in items" :key="`mob-item-${item.id}`">
                                <div v-if="editingId === item.id" class="bg-surface-2 px-4 py-3 space-y-2" data-editing>
                                    <input
                                        v-model="editForm.label"
                                        type="text"
                                        :placeholder="t('budgets.editRow.labelPlaceholder')"
                                        class="w-full bg-surface-3 text-primary rounded px-2 py-1.5 text-sm border border-strong focus:border-indigo-500 focus:outline-none"
                                    >
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <select
                                            v-model="editForm.category_id"
                                            class="bg-surface-3 text-primary rounded px-2 py-1.5 text-sm border border-strong focus:border-indigo-500 focus:outline-none"
                                        >
                                            <option :value="null">—</option>
                                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                        </select>
                                        <input
                                            v-model="editForm.planned_amount"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            placeholder="0,00"
                                            class="bg-surface-3 text-primary rounded px-2 py-1.5 text-sm border border-strong focus:border-indigo-500 focus:outline-none text-right font-mono"
                                        >
                                    </div>
                                    <textarea
                                        v-model="editForm.notes"
                                        :placeholder="t('budgets.editRow.notePlaceholder')"
                                        rows="2"
                                        class="w-full bg-surface-3 text-secondary rounded px-2 py-1 text-xs border border-strong focus:border-indigo-500 focus:outline-none resize-none"
                                    />
                                    <div class="flex items-center justify-between">
                                        <select
                                            v-model="editForm.type"
                                            class="bg-surface-3 text-primary rounded px-2 py-1 text-xs border border-strong focus:border-indigo-500 focus:outline-none"
                                        >
                                            <option v-for="(meta, stype) in SECTION_META" :key="stype" :value="stype">{{ meta.label }}</option>
                                        </select>
                                        <div class="flex gap-3">
                                            <button class="text-emerald-400 hover:text-emerald-300" v-on:click="submitEdit(item)">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                            </button>
                                            <button class="text-muted hover:text-secondary" v-on:click="cancelEditing">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-else
                                    class="px-4 py-3 flex flex-col gap-1.5"
                                    :class="[
                                        item.planned_amount > 0 && item.actual_amount > item.planned_amount
                                            ? SECTION_META[type]?.positiveIsGood ? 'border-l-2 border-l-emerald-500/60' : 'border-l-2 border-l-rose-500/60'
                                            : '',
                                        deletingItem && deletingItem.id === item.id ? 'opacity-40' : '',
                                    ]"
                                >
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="text-sm text-primary font-medium">{{ item.label }}</span>
                                            <NoteTooltip v-if="item.notes" :note="item.notes" />
                                            <span
                                                v-if="item.repeat_next_month"
                                                class="inline-flex items-center gap-0.5 text-[10px] text-indigo-400 border border-indigo-500/30 rounded px-1 py-0.5"
                                            >
                                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                            </span>
                                        </div>
                                        <div v-if="!item.category?.is_system" class="flex items-center gap-2 shrink-0">
                                            <button
                                                class="text-muted hover:text-indigo-400 transition-colors"
                                                v-on:click="openTxPanelFromRow(item.category_id, item.label, type === 'income' ? 'income' : 'expense', type)"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                            </button>
                                            <button class="text-muted hover:text-sky-400 transition-colors" v-on:click="startEditingItem(item)">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </button>
                                            <button class="text-muted hover:text-rose-400 transition-colors" v-on:click="requestDelete(item)">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                            <div class="relative">
                                                <button
                                                    class="text-muted hover:text-secondary transition-colors"
                                                    v-on:click.stop="toggleMobileMenu(item.id)"
                                                >
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="5" cy="12" r="2" /><circle cx="12" cy="12" r="2" /><circle cx="19" cy="12" r="2" /></svg>
                                                </button>
                                                <div
                                                    v-if="mobileMenuOpenId === item.id"
                                                    class="absolute right-0 top-6 z-20 w-44 bg-surface border border-base rounded-lg shadow-xl overflow-hidden"
                                                    v-on:click.stop
                                                >
                                                    <button
                                                        v-if="isPro"
                                                        class="w-full flex items-center gap-2.5 px-3 py-2.5 text-sm transition-colors hover:bg-surface-2"
                                                        :class="item.repeat_next_month ? 'text-indigo-400' : 'text-secondary'"
                                                        v-on:click="toggleRepeat(item); closeMobileMenu()"
                                                    >
                                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                                        {{ t('budgets.actions.toggleRepeat') }}
                                                    </button>
                                                    <button
                                                        v-if="isPro"
                                                        class="w-full flex items-center gap-2.5 px-3 py-2.5 text-sm text-secondary transition-colors hover:bg-surface-2"
                                                        v-on:click="duplicateItem(item); closeMobileMenu()"
                                                    >
                                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                                        {{ t('budgets.actions.duplicate') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between text-xs">
                                        <span v-if="item.category" :class="item.category.is_system ? 'bg-indigo-500/10 text-indigo-400 border-indigo-500/30' : 'bg-surface-2 text-secondary border-base'" class="inline-flex items-center rounded px-2 py-0.5 border text-xs">
                                            {{ item.category.name }}
                                        </span>
                                        <span v-else class="text-subtle">—</span>
                                        <div class="flex items-center gap-2 font-mono">
                                            <span class="text-muted">{{ fmt(item.planned_amount) }}</span>
                                            <button
                                                v-if="item.category_id && item.actual_amount > 0"
                                                :class="diffClass(item.actual_amount - item.planned_amount, SECTION_META[type].positiveIsGood)"
                                                class="font-semibold hover:underline decoration-dotted"
                                                v-on:click="openTxDetail(item)"
                                            >
                                                {{ fmt(item.actual_amount) }}
                                            </button>
                                            <span v-else :class="diffClass(item.actual_amount - item.planned_amount, SECTION_META[type].positiveIsGood)" class="font-semibold">{{ fmt(item.actual_amount) }}</span>
                                        </div>
                                    </div>
                                    <div v-if="item.planned_amount > 0" class="h-1 w-full bg-surface-3 rounded-full">
                                        <div
                                            v-show="progress(item.planned_amount, item.actual_amount) > 0"
                                            class="h-full rounded-full transition-all duration-300"
                                            :class="item.actual_amount > item.planned_amount ? 'bg-rose-400' : SECTION_META[type]?.barColor"
                                            :style="{ width: progress(item.planned_amount, item.actual_amount) + '%' }"
                                        />
                                    </div>
                                </div>
                            </template>

                            <div v-if="addingType === type" class="bg-surface-2/60 px-4 py-3 space-y-2" data-adding>
                                <input
                                    :id="`add-label-${type}`"
                                    v-model="addForm.label"
                                    type="text"
                                    :placeholder="t('budgets.addRow.labelPlaceholder')"
                                    class="w-full bg-surface-3 text-primary rounded px-2 py-1.5 text-sm border border-indigo-500/50 focus:border-indigo-500 focus:outline-none"
                                    v-on:keydown="onKeydown($event, submitAdd, cancelAdding)"
                                >
                                <div class="grid grid-cols-2 gap-2">
                                    <select
                                        v-model="addForm.category_id"
                                        class="bg-surface-3 text-primary rounded px-2 py-1.5 text-sm border border-strong focus:border-indigo-500 focus:outline-none"
                                        v-on:keydown="onKeydown($event, submitAdd, cancelAdding)"
                                    >
                                        <option :value="null">—</option>
                                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                    </select>
                                    <input
                                        v-model="addForm.planned_amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="0,00"
                                        class="bg-surface-3 text-primary rounded px-2 py-1.5 text-sm border border-strong focus:border-indigo-500 focus:outline-none text-right font-mono"
                                        v-on:keydown="onKeydown($event, submitAdd, cancelAdding)"
                                    >
                                </div>
                                <div class="flex justify-end gap-3">
                                    <button class="text-emerald-400 hover:text-emerald-300" v-on:click="submitAdd">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    </button>
                                    <button class="text-muted hover:text-secondary" v-on:click="cancelAdding">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                            </div>

                            <div class="px-4 py-2">
                                <button
                                    class="text-xs text-subtle hover:text-secondary transition-colors flex items-center gap-1"
                                    v-on:click="startAdding(type)"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                    {{ t('budgets.addRow.addLine') }}
                                </button>
                            </div>
                        </template>
                    </template>

                    <div v-if="hasUnbudgeted" class="border-t-2 border-amber-500/30 bg-amber-500/5 px-4 py-3 space-y-1">
                        <div class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>
                            <span class="text-xs font-semibold uppercase tracking-widest text-amber-400">{{ t('budgets.table.unbudgeted') }}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs font-mono">
                            <div v-if="unbudgeted.income > 0">
                                <p class="text-muted">{{ t('budgets.sections.income') }}</p>
                                <p class="font-semibold text-emerald-400">+{{ fmt(unbudgeted.income) }}</p>
                            </div>
                            <div v-if="unbudgeted.expenses > 0">
                                <p class="text-muted">{{ t('budgets.kpi.expenses') }}</p>
                                <p class="font-semibold text-rose-400">-{{ fmt(unbudgeted.expenses) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <table class="hidden md:table w-full text-sm">
                    <thead>
                        <tr class="border-b border-base/60 text-xs text-muted uppercase tracking-wider sticky top-0 z-10 bg-surface">
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
                            <tr
                                :class="[SECTION_META[type].bg, SECTION_META[type].border, 'border-b cursor-pointer select-none']"
                                v-on:click="toggleSection(type)"
                            >
                                <td class="px-4 py-2" colspan="2">
                                    <div class="flex items-center gap-3">
                                        <span :class="[SECTION_META[type].color, 'font-semibold uppercase text-xs tracking-widest']">
                                            {{ SECTION_META[type].label }}
                                        </span>
                                        <template v-if="!collapsedSections[type]">
                                            <div class="flex-1 max-w-[120px] h-1.5 bg-surface-3 rounded-full">
                                                <div
                                                    v-show="progress(totals[type]?.planned, totals[type]?.actual) > 0"
                                                    class="h-full rounded-full transition-all duration-300"
                                                    :class="SECTION_META[type].barColor"
                                                    :style="{ width: progress(totals[type]?.planned, totals[type]?.actual) + '%' }"
                                                />
                                            </div>
                                            <span class="text-xs text-muted">{{ progress(totals[type]?.planned, totals[type]?.actual) }}%</span>
                                        </template>
                                        <span v-else class="text-xs text-subtle">{{ items.length }} ligne{{ items.length > 1 ? 's' : '' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-2 text-right font-mono text-secondary text-xs">{{ fmt(totals[type]?.planned ?? 0) }}</td>
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

                            <template v-if="!collapsedSections[type]">
                                <template v-for="item in items" :key="item.id">
                                    <template v-if="editingId === item.id">
                                        <tr class="bg-surface-2 border-b border-base/40" data-editing>
                                            <td class="pl-8 pr-2 py-1.5">
                                                <input
                                                    :id="`edit-label-${item.id}`"
                                                    v-model="editForm.label"
                                                    type="text"
                                                    tabindex="1"
                                                    :placeholder="t('budgets.editRow.labelPlaceholder')"
                                                    class="w-full bg-surface-3 text-primary rounded px-2 py-1 text-sm border border-strong focus:border-indigo-500 focus:outline-none"
                                                    v-on:keydown="onKeydown($event, () => submitEdit(item), cancelEditing)"
                                                >
                                            </td>
                                            <td class="px-2 py-1.5">
                                                <select
                                                    v-model="editForm.category_id"
                                                    tabindex="2"
                                                    class="w-full bg-surface-3 text-primary rounded px-2 py-1 text-sm border border-strong focus:border-indigo-500 focus:outline-none"
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
                                                    class="w-full bg-surface-3 text-primary rounded px-2 py-1 text-sm border border-strong focus:border-indigo-500 focus:outline-none text-right font-mono"
                                                    v-on:keydown="onKeydown($event, () => submitEdit(item), cancelEditing)"
                                                >
                                            </td>
                                            <td class="px-2 py-1.5 text-right text-muted font-mono text-xs">{{ fmt(item.actual_amount) }}</td>
                                            <td />
                                            <td class="px-3 py-1.5">
                                                <div class="flex items-center gap-2 justify-end">
                                                    <button class="text-emerald-400 hover:text-emerald-300 transition-colors" :title="t('budgets.editRow.confirm')" v-on:click="submitEdit(item)">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                    </button>
                                                    <button class="text-muted hover:text-secondary transition-colors" :title="t('budgets.editRow.cancel')" v-on:click="cancelEditing">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr class="bg-surface-2 border-b border-base/40" data-editing>
                                            <td colspan="6" class="pl-8 pr-3 pb-2 space-y-1.5">
                                                <textarea
                                                    v-model="editForm.notes"
                                                    :placeholder="t('budgets.editRow.notePlaceholder')"
                                                    rows="2"
                                                    tabindex="4"
                                                    class="w-full bg-surface-3 text-secondary rounded px-2 py-1 text-xs border border-strong focus:border-indigo-500 focus:outline-none resize-none"
                                                    v-on:keydown="onNotesKeydown($event, item)"
                                                />
                                                <div class="flex items-center gap-2">
                                                    <label class="text-xs text-muted">{{ t('budgets.editRow.section') }}</label>
                                                    <select
                                                        v-model="editForm.type"
                                                        class="bg-surface-3 text-primary rounded px-2 py-0.5 text-xs border border-strong focus:border-indigo-500 focus:outline-none"
                                                    >
                                                        <option v-for="(meta, stype) in SECTION_META" :key="stype" :value="stype">{{ meta.label }}</option>
                                                    </select>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>

                                    <tr
                                        v-else
                                        draggable="true"
                                        class="border-b border-subtle/60 group hover:bg-surface-2/40 cursor-pointer transition-colors"
                                        :class="[
                                            item.planned_amount > 0 && item.actual_amount > item.planned_amount
                                                ? SECTION_META[type]?.positiveIsGood ? 'border-l-2 border-l-emerald-500/60' : 'border-l-2 border-l-rose-500/60'
                                                : '',
                                            deletingItem && deletingItem.id === item.id ? 'opacity-40 pointer-events-none' : '',
                                            draggingId === item.id ? 'opacity-50' : '',
                                            dragOverId === item.id ? 'bg-indigo-500/10' : '',
                                        ]"
                                        :data-row-id="item.id"
                                        v-on:click="!item.category?.is_system && startEditingItem(item)"
                                        v-on:dragstart="onDragStart($event, item)"
                                        v-on:dragend="onDragEnd"
                                        v-on:dragover="onDragOver($event, item)"
                                        v-on:drop.prevent="onDrop(item)"
                                    >
                                        <td class="pl-4 pr-4 py-2.5 text-primary">
                                            <div class="flex items-center gap-2">
                                                <div class="text-base opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                                                    <svg class="w-3 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <circle cx="9" cy="5" r="1.5" /><circle cx="15" cy="5" r="1.5" />
                                                        <circle cx="9" cy="12" r="1.5" /><circle cx="15" cy="12" r="1.5" />
                                                        <circle cx="9" cy="19" r="1.5" /><circle cx="15" cy="19" r="1.5" />
                                                    </svg>
                                                </div>
                                                <span>{{ item.label }}</span>
                                                <NoteTooltip v-if="item.notes" :note="item.notes" />
                                                <span
                                                    v-if="item.repeat_next_month"
                                                    class="inline-flex items-center gap-0.5 text-[10px] text-indigo-400 border border-indigo-500/30 rounded px-1 py-0.5 leading-none"
                                                    :title="t('budgets.repeatNextMonth')"
                                                >
                                                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <span v-if="item.category" :class="item.category.is_system ? 'bg-indigo-500/10 text-indigo-400 border-indigo-500/30' : 'bg-surface-2 text-secondary border-base'" class="inline-flex items-center text-xs rounded px-2 py-0.5 border">
                                                {{ item.category.name }}
                                            </span>
                                            <span v-else class="text-subtle">—</span>
                                        </td>
                                        <td class="px-4 py-2.5 text-right text-secondary font-mono">
                                            <div>{{ fmt(item.planned_amount) }}</div>
                                            <div v-if="item.planned_amount > 0" class="mt-1 h-0.5 w-full bg-surface-3 rounded-full">
                                                <div
                                                    v-show="progress(item.planned_amount, item.actual_amount) > 0"
                                                    class="h-full rounded-full transition-all duration-300"
                                                    :class="item.actual_amount > item.planned_amount ? (SECTION_META[type]?.positiveIsGood ? 'bg-emerald-400' : 'bg-rose-400') : SECTION_META[type]?.barColor"
                                                    :style="{ width: progress(item.planned_amount, item.actual_amount) + '%' }"
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
                                            <div v-if="!item.category?.is_system" class="flex items-center gap-1.5 justify-end opacity-0 group-hover:opacity-100 transition-opacity">
                                                <button
                                                    class="text-muted hover:text-indigo-400 transition-colors"
                                                    :title="t('budgets.actions.addTx')"
                                                    v-on:click.stop="openTxPanelFromRow(item.category_id, item.label, type === 'income' ? 'income' : 'expense', type)"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                                </button>
                                                <button
                                                    v-if="isPro"
                                                    class="transition-colors"
                                                    :class="item.repeat_next_month ? 'text-indigo-400 hover:text-indigo-300' : 'text-muted hover:text-indigo-400'"
                                                    :title="t('budgets.actions.toggleRepeat')"
                                                    v-on:click.stop="toggleRepeat(item)"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                                </button>
                                                <button
                                                    v-if="isPro"
                                                    class="text-muted hover:text-amber-400 transition-colors"
                                                    :title="t('budgets.actions.duplicate')"
                                                    v-on:click.stop="duplicateItem(item)"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                                </button>
                                                <button class="text-muted hover:text-sky-400 transition-colors" :title="t('budgets.actions.edit')" v-on:click.stop="startEditingItem(item)">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                </button>
                                                <button class="text-muted hover:text-rose-400 transition-colors" :title="t('budgets.actions.delete')" v-on:click.stop="requestDelete(item)">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>

                                <tr v-if="addingType === type" class="bg-surface-2/60 border-b border-base/40" data-adding>
                                    <td class="pl-8 pr-2 py-1.5">
                                        <input
                                            :id="`add-label-${type}`"
                                            v-model="addForm.label"
                                            type="text"
                                            :placeholder="t('budgets.addRow.labelPlaceholder')"
                                            class="w-full bg-surface-3 text-primary rounded px-2 py-1 text-sm border border-indigo-500/50 focus:border-indigo-500 focus:outline-none"
                                            v-on:keydown="onKeydown($event, submitAdd, cancelAdding)"
                                        >
                                    </td>
                                    <td class="px-2 py-1.5">
                                        <select
                                            v-model="addForm.category_id"
                                            class="w-full bg-surface-3 text-primary rounded px-2 py-1 text-sm border border-strong focus:border-indigo-500 focus:outline-none"
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
                                            class="w-full bg-surface-3 text-primary rounded px-2 py-1 text-sm border border-strong focus:border-indigo-500 focus:outline-none text-right font-mono"
                                            v-on:keydown="onKeydown($event, submitAdd, cancelAdding)"
                                        >
                                    </td>
                                    <td colspan="2" />
                                    <td class="px-3 py-1.5">
                                        <div class="flex items-center gap-2 justify-end">
                                            <button class="text-emerald-400 hover:text-emerald-300 transition-colors" :title="t('budgets.addRow.confirm')" v-on:click="submitAdd">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                            </button>
                                            <button class="text-muted hover:text-secondary transition-colors" :title="t('budgets.addRow.cancel')" v-on:click="cancelAdding">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="addingType !== type" class="border-b border-subtle/60">
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

                        <tr v-if="hasUnbudgeted" class="border-t border-amber-500/30 bg-amber-500/5">
                            <td class="px-4 py-2.5" colspan="2">
                                <div class="flex items-center gap-2">
                                    <svg class="w-3.5 h-3.5 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>
                                    <span class="text-xs font-semibold uppercase tracking-widest text-amber-400">{{ t('budgets.table.unbudgeted') }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-2.5 text-right font-mono text-xs text-muted">—</td>
                            <td class="px-4 py-2.5 text-right font-mono text-xs">
                                <span v-if="unbudgeted.income > 0" class="text-emerald-400">+{{ fmt(unbudgeted.income) }}</span>
                                <span v-if="unbudgeted.income > 0 && unbudgeted.expenses > 0" class="text-muted mx-1">/</span>
                                <span v-if="unbudgeted.expenses > 0" class="text-rose-400">-{{ fmt(unbudgeted.expenses) }}</span>
                            </td>
                            <td colspan="2" />
                        </tr>

                        <tr class="border-t-2 border-strong bg-surface-2/30">
                            <td class="px-4 py-3 font-semibold text-secondary text-xs uppercase tracking-wide" colspan="2">{{ t('budgets.table.cashFlow') }}</td>
                            <td class="px-4 py-3 text-right font-mono text-secondary text-sm">{{ fmt(cashFlow.planned, true) }}</td>
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

                        <tr class="bg-surface-2/30">
                            <td class="px-4 py-3 font-semibold text-secondary text-xs uppercase tracking-wide" colspan="2">{{ t('budgets.table.leftToSpend') }}</td>
                            <td class="px-4 py-3 text-right font-mono text-secondary text-sm">{{ fmt(leftToSpend.planned) }}</td>
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

        <GoalDepositModal
            :goal="depositGoal"
            v-on:close="depositGoal = null"
            v-on:success="depositGoal = null"
        />

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

        <BudgetDetailPanel
            :open="txDetailOpen"
            :item="txDetailItem"
            :loading="txDetailLoading"
            :transactions="txDetailList"
            v-on:close="closeTxDetail"
        />

        <CopyBudgetModal
            :show="showCopyPreviousModal"
            :items="prevItems"
            :title="t('budgets.copyFromPrevious', { month: fmtMonth(prevMonth) })"
            :message="copyPreviousModalMessage"
            v-on:confirm="confirmCopyPrevious"
            v-on:cancel="cancelCopyPrevious"
        />

        <CopyBudgetModal
            :show="showCopyRepeatModal"
            :items="prevItems.filter(i => i.repeat_next_month)"
            :title="t('budgets.copyRepeat')"
            :message="t('budgets.confirmCopyRepeat')"
            v-on:confirm="confirmCopyRepeat"
            v-on:cancel="cancelCopyRepeat"
        />

        <ConfirmModal
            :show="pendingDeleteItem !== null"
            :message="pendingDeleteItem ? t('budgets.actions.confirmDelete', { label: pendingDeleteItem.label }) : ''"
            v-on:confirm="confirmDelete"
            v-on:cancel="cancelDelete"
        />

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
