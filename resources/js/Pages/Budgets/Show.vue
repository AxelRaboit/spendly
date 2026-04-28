<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { toast } from 'vue-sonner';
import ConfirmModal from '@/components/ui/ConfirmModal.vue';
import CopyBudgetModal from '@/components/ui/CopyBudgetModal.vue';
import AppLink from '@/components/ui/AppLink.vue';
import NoteTooltip from '@/components/ui/NoteTooltip.vue';
import DonutChart from '@/components/ui/DonutChart.vue';
import BudgetGauge from '@/components/ui/BudgetGauge.vue';
import BudgetTxPanel from '@/components/budget/BudgetTxPanel.vue';
import BudgetDetailPanel from '@/components/budget/BudgetDetailPanel.vue';
import BudgetInput from '@/components/budget/BudgetInput.vue';
import BudgetSelect from '@/components/budget/BudgetSelect.vue';
import AppTooltip from '@/components/ui/AppTooltip.vue';
import FormHint from '@/components/form/FormHint.vue';
import GoalDepositModal from '@/components/budget/GoalDepositModal.vue';
import BalanceAdjustmentModal from '@/components/wallet/BalanceAdjustmentModal.vue';
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
import { useFmtDate }        from '@/composables/core/useFmtDate';
import { useFmtMonth }       from '@/composables/core/useFmtMonth';
import { useTransactionPanel } from '@/composables/budget/useTransactionPanel';
import { useItemTransactions } from '@/composables/budget/useItemTransactions';
import { useUnbudgetedTransactions } from '@/composables/budget/useUnbudgetedTransactions';
import { useQuickCategoryCreate } from '@/composables/budget/useQuickCategoryCreate';
import { useCurrency }       from '@/composables/core/useCurrency';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { usePlanLimits } from '@/composables/ui/usePlanLimits';
import { useTour } from '@/composables/ui/useTour';
import { Plus, ChevronLeft, ChevronRight, ChevronDown, AlertTriangle, CheckCircle, Copy, Zap, Settings, FileText, Pencil, Trash2, Check, X, MoreHorizontal, Repeat, GripVertical, Eye } from 'lucide-vue-next';
import { computed, nextTick, onMounted, onUnmounted, ref, toRef, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { TransactionType } from '@/enums/TransactionType';
import { BudgetSection } from '@/enums/BudgetSection';
import { BudgetTargetType } from '@/enums/BudgetTargetType';

// ─── i18n / currency ─────────────────────────────────────────────────────────
const { t } = useI18n();
const { fmt } = useCurrency();
const { fmtMonth } = useFmtMonth();
const { fmtDate } = useFmtDate();
const { isPro } = usePlanLimits();
const { initForPage, tourActive } = useTour();
const showProFeatures = computed(() => isPro.value || tourActive.value);

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
    budgetPresets: { type: Array,  default: () => [] },
});

// ─── Reactive prop refs ───────────────────────────────────────────────────────
const walletId     = computed(() => props.wallet.id);
const budget       = toRef(props, 'budget');
const sections     = toRef(props, 'sections');
const unbudgeted   = toRef(props, 'unbudgeted');
const startBalance = toRef(props, 'startBalance');
const prevMonth    = toRef(props, 'prevMonth');
const localCategories = ref([...props.categories]);
const showAdjustment = ref(false);

const usedCategoryIds = computed(() => {
    const used = new Set();
    Object.values(sections.value).forEach(items => {
        items.forEach(item => { if (item.category_id) used.add(item.category_id); });
    });
    return used;
});

const availableCategories = computed(() =>
    localCategories.value.filter(c => !usedCategoryIds.value.has(c.id)),
);

const editingOriginalCategoryId = ref(null);
const availableCategoriesForEdit = computed(() =>
    localCategories.value.filter(c => !usedCategoryIds.value.has(c.id) || c.id === editingOriginalCategoryId.value),
);

const {
    creatingCategory,
    newCategoryName,
    creatingCategoryLoading,
    categoryTargetForm,
    onCategoryChange,
    createCategory,
    cancelCreateCategory,
} = useQuickCategoryCreate(props.wallet.id, localCategories);

async function handleSubmitAdd() {
    if (creatingCategory.value && categoryTargetForm.value === addForm && newCategoryName.value.trim()) {
        await createCategory();
    }
    submitAdd();
}

// ─── KPI toggle + mobile carousel ────────────────────────────────────────────
const showMoreKpi = ref(localStorage.getItem('budget-show-more-kpi') === 'true');
function toggleMoreKpi() {
    showMoreKpi.value = !showMoreKpi.value;
    localStorage.setItem('budget-show-more-kpi', String(showMoreKpi.value));
}

const kpiSlides = ref([0, 0, 0]);
const trackedCarousels = new Set();

function trackCarousel(el, index) {
    if (!el || trackedCarousels.has(index)) return;
    trackedCarousels.add(index);
    el.addEventListener('scroll', () => {
        const cardWidth = el.firstElementChild?.offsetWidth ?? 1;
        kpiSlides.value[index] = Math.round(el.scrollLeft / (cardWidth + 12));
    }, { passive: true });
}

// ─── Section meta + donut ─────────────────────────────────────────────────────
const { SECTION_META } = useSectionMeta();

// ─── Totals / KPIs ───────────────────────────────────────────────────────────
const { totals, totalCarriedOver, totalIncome, totalExpenses, cashFlow, leftToSpend, savingsRate, isCurrentMonth, projectedExpenses } =
    useBudgetTotals(sections, startBalance, budget, unbudgeted);

const hasUnbudgeted = computed(() => (unbudgeted.value?.income ?? 0) > 0 || (unbudgeted.value?.expenses ?? 0) > 0);

const donutSegments = useDonutSegments(totals, SECTION_META);

// ─── Row flash ────────────────────────────────────────────────────────────────
const { flash } = useRowFlash();

// ─── Budget rows ─────────────────────────────────────────────────────────────
const {
    editingId, editForm, startEditing, cancelEditing, submitEdit,
    addingType, addForm, addFormSubmitted, startAdding, cancelAdding, submitAdd,
    pendingDeleteItem, deletingItem, requestDelete, confirmDelete, undoDelete, cancelDelete,
    draggingId, dragOverId, onDragStart, onDragOver, onDrop, onDragEnd,
    duplicateItem, toggleRepeat,
} = useBudgetItems(walletId, sections, budget, flash);

// ─── Transaction panel ───────────────────────────────────────────────────────
const { txPanel, txPrefillLabel, txForm, txSection, txFilteredCategories, editingTx, suggestedCategoryId, markCategoryManual, openTxPanel, openEditTx, closeTxPanel, submitTx, submitSplit, onTxSectionChange } =
    useTransactionPanel(walletId, budget, sections, flash, localCategories);

// ─── Item transactions panel ─────────────────────────────────────────────────
const { open: txDetailOpen, loading: txDetailLoading, transactions: txDetailList, currentItem: txDetailItem, openPanel: openTxDetail, closePanel: closeTxDetail } =
    useItemTransactions(walletId);

// ─── Unbudgeted transactions panel ───────────────────────────────────────────
const { open: unbudgetedOpen, loading: unbudgetedLoading, transactions: unbudgetedList, type: unbudgetedType, openPanel: openUnbudgeted, closePanel: closeUnbudgeted } =
    useUnbudgetedTransactions(walletId);

const unbudgetedPseudoItem = computed(() => ({
    id: 'unbudgeted',
    label: t('budgets.table.unbudgeted'),
    category_id: null,
}));

function editTxFromDetail(tx) {
    closeTxDetail();
    openEditTx(tx);
}

const pendingDeleteTx = ref(null);

function deleteTxFromDetail(tx) {
    pendingDeleteTx.value = tx;
}

function confirmDeleteTx() {
    if (!pendingDeleteTx.value) return;
    router.delete(`/transactions/${pendingDeleteTx.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            pendingDeleteTx.value = null;
            if (txDetailItem.value) openTxDetail(txDetailItem.value);
        },
    });
}

function cancelDeleteTx() {
    pendingDeleteTx.value = null;
}

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

// ─── Overage toast ──────────────────────────────────────────────────────────
let prevOverageCount = overageCount.value;

watch(overageCount, (newVal) => {
    if (newVal > prevOverageCount) {
        toast.warning(t('budgets.overageToast', newVal, { count: newVal }), { duration: 4000 });
    }
    prevOverageCount = newVal;
});

watch(deletingItem, (item) => {
    if (item) {
        toast(t('budgets.toast.deleted', { label: item.label }), {
            action: { label: t('budgets.toast.undo'), onClick: () => undoDelete() },
            onDismiss: () => undoDelete(),
            onAutoClose: () => undoDelete(),
            duration: 5000,
        });
    }
});

// ─── Goal deposit modal ───────────────────────────────────────────────────────
const depositGoal = ref(null);

const savingsCategories = computed(() => {
    const savingsItems = sections.value.savings ?? [];
    const ids = new Set(savingsItems.map(i => i.category_id).filter(Boolean));
    return localCategories.value.filter(c => ids.has(c.id));
});

function targetTooltip(item) {
    const amount = fmt(item.target_amount);
    if (item.target_type === BudgetTargetType.Spending) {
        return item.actual_amount <= (item.target_amount ?? 0)
            ? t('budgets.target.onTrack', { amount })
            : t('budgets.target.overBudget', { amount });
    }
    if (item.target_type === BudgetTargetType.ByDate) {
        return t('budgets.target.byDateInfo', { amount, date: item.target_deadline ?? '' });
    }
    return t('budgets.target.savingOnTrack', { amount });
}

// ─── Mobile row context menu ──────────────────────────────────────────────────
const mobileMenuOpenId = ref(null);
function toggleMobileMenu(id) { mobileMenuOpenId.value = mobileMenuOpenId.value === id ? null : id; }
function closeMobileMenu() { mobileMenuOpenId.value = null; }

// ─── Cross-cancel wrappers ────────────────────────────────────────────────────
function openTxPanelFromRow(categoryId, label, type, section) {
    openTxPanel(categoryId, label, type, { cancelEditing, cancelAdding }, section);
}
function addTxFromDetailPanel() {
    if (!txDetailItem.value) return;
    const { category_id, label, type } = txDetailItem.value;
    closeTxDetail();
    openTxPanelFromRow(category_id, label, type === BudgetSection.Income ? TransactionType.Income : TransactionType.Expense, type);
}
function startEditingItem(item) {
    editingOriginalCategoryId.value = item.category_id;
    startEditing(item, { cancelAdding });
}
function startAddingItem(type)  { startAdding(type, { cancelEditing }); }

// ─── Helpers ─────────────────────────────────────────────────────────────────
function progress(planned, actual) {
    if (!planned) return 0;
    return Math.min(100, Math.round((actual / planned) * 100));
}
function diffClass(diff, positiveIsGood, actual = null) {
    if (diff === 0) return 'text-secondary';
    if (actual !== null && actual === 0) return 'text-muted';
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

// ─── Quick-start suggestions ─────────────────────────────────────────────────
const allPresets = props.budgetPresets.map(p => ({ label: p.label, type: p.type, planned_amount: p.planned_amount }));

const existingLabels = computed(() => {
    const labels = new Set();
    Object.values(sections.value).forEach(items => {
        items.forEach(item => labels.add(item.label.toLowerCase()));
    });
    return labels;
});

const quickStartSuggestions = computed(() =>
    allPresets.filter(p => !existingLabels.value.has(p.label.toLowerCase()))
);

const quickStartForm = useForm({ month: '', suggestions: [] });

function quickStart(suggestion) {
    quickStartForm.month = budget.value.month;
    quickStartForm.suggestions = [suggestion];
    quickStartForm.post(`/wallets/${walletId.value}/budget/quick-start`, { preserveScroll: true });
}

function quickStartAll() {
    quickStartForm.month = budget.value.month;
    quickStartForm.suggestions = quickStartSuggestions.value;
    quickStartForm.post(`/wallets/${walletId.value}/budget/quick-start`, { preserveScroll: true });
}

// ─── Clear all ───────────────────────────────────────────────────────────────
const showClearModal = ref(false);

function clearAll() {
    showClearModal.value = true;
}

function confirmClear() {
    router.delete(`/wallets/${walletId.value}/budget/clear`, {
        data: { month: budget.value.month },
        preserveScroll: true,
        onSuccess: () => { showClearModal.value = false; },
    });
}

// ─── Keyboard navigation ──────────────────────────────────────────────────────
function onGlobalKeydown(e) {
    const tag = e.target.tagName;
    if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;
    if (e.key === 'ArrowLeft')  router.visit(`/wallets/${props.wallet.id}/budget?month=${props.prevMonth}`);
    if (e.key === 'ArrowRight') router.visit(`/wallets/${props.wallet.id}/budget?month=${props.nextMonth}`);
    if (e.key === 'n' || e.key === 'N') startAddingItem(Object.keys(props.sections)[0] ?? BudgetSection.Income);
}

function openUnbudgetedPanel(type) {
    openUnbudgeted(type);
}

onMounted(async () => {
    document.addEventListener('keydown', onGlobalKeydown);
    document.addEventListener('click', closeMobileMenu);

    initForPage('budgets.show', {
        expandKpi: () => { showMoreKpi.value = true; },
        openTxPanel: () => { openTxPanel(null, '', TransactionType.Expense, { cancelEditing, cancelAdding }); },
        closeTxPanel: () => { closeTxPanel(); },
        openAddLine: () => { startAddingItem('income'); },
        cancelAdding: () => { cancelAdding(); },
        openDetailPanel: () => {
            // Find first item with actual_amount > 0 and a category
            for (const items of Object.values(props.sections)) {
                const item = items.find(i => i.category_id && i.actual_amount > 0);
                if (item) { openTxDetail(item); return; }
            }
        },
        closeDetailPanel: () => { closeTxDetail(); },
    });

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

        <div class="space-y-3">
            <div class="flex sm:justify-end">
                <AppButton
                    data-tour="new-transaction"
                    class="w-full sm:w-auto"
                    v-on:click="openTxPanel(null, '', TransactionType.Expense, { cancelEditing, cancelAdding })"
                >
                    <Plus class="w-4 h-4 mr-1.5" />
                    {{ t('budgets.newTransaction') }}
                </AppButton>
            </div>
            <div class="flex items-center justify-between" data-tour="month-nav">
                <Link
                    :href="`/wallets/${wallet.id}/budget?month=${prevMonth}`"
                    class="flex items-center gap-1 text-secondary hover:text-primary transition-colors text-sm capitalize"
                >
                    <ChevronLeft class="w-4 h-4 shrink-0" />
                    <span class="hidden sm:inline">{{ fmtMonth(prevMonth) }}</span>
                </Link>
                <div class="flex flex-col items-center gap-1 flex-1">
                    <div class="flex items-center gap-2">
                        <h2 class="text-xl font-bold text-primary capitalize">{{ fmtMonth(budget.month) }}</h2>
                        <button
                            class="flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-500/15 hover:bg-indigo-500/25 text-indigo-400 transition-colors shrink-0"
                            :title="t('balanceAdjustment.button')"
                            v-on:click="showAdjustment = true"
                        >
                            <Settings class="w-4 h-4" />
                        </button>
                    </div>
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
                    <ChevronRight class="w-4 h-4 shrink-0" />
                </Link>
            </div>

            <div class="space-y-3" data-tour="kpi-section">
                <div class="hidden md:grid grid-cols-3 gap-3" data-tour="budget-kpi">
                    <div class="bg-surface border border-line/60 rounded-lg p-4">
                        <AppTooltip :text="t('budgets.kpi.incomeTip')"><p class="text-xs text-muted uppercase tracking-wide mb-1 cursor-help">{{ t('budgets.kpi.income') }}</p></AppTooltip>
                        <p class="text-lg font-bold font-mono" :class="totalIncome.actual > 0 ? 'text-emerald-400' : 'text-muted'">{{ fmt(totalIncome.actual) }}</p>
                        <p class="text-xs text-muted mt-0.5">/ {{ fmt(totalIncome.planned) }} {{ t('budgets.kpi.planned') }}</p>
                    </div>
                    <div class="bg-surface border border-line/60 rounded-lg p-4">
                        <AppTooltip :text="t('budgets.kpi.expensesTip')"><p class="text-xs text-muted uppercase tracking-wide mb-1 cursor-help">{{ t('budgets.kpi.expenses') }}</p></AppTooltip>
                        <p class="text-lg font-bold font-mono" :class="totalExpenses.actual > 0 ? 'text-rose-400' : 'text-muted'">{{ fmt(totalExpenses.actual) }}</p>
                        <p class="text-xs text-muted mt-0.5">/ {{ fmt(totalExpenses.planned) }} {{ t('budgets.kpi.planned') }}</p>
                    </div>
                    <div class="bg-surface border border-line/60 rounded-lg p-4">
                        <AppTooltip :text="t('budgets.kpi.leftToSpendTip')"><p class="text-xs text-muted uppercase tracking-wide mb-1 cursor-help">{{ t('budgets.kpi.leftToSpend') }}</p></AppTooltip>
                        <p class="text-lg font-bold font-mono" :class="leftToSpend.actual < 0 ? 'text-rose-400' : 'text-emerald-400'">{{ fmt(leftToSpend.actual) }}</p>
                        <p class="text-xs text-muted mt-0.5">/ {{ fmt(leftToSpend.planned) }} {{ t('budgets.kpi.planned') }}</p>
                    </div>
                </div>

                <div class="md:hidden">
                    <div :ref="el => trackCarousel(el, 0)" class="flex gap-3 overflow-x-auto snap-x snap-mandatory scrollbar-hide -mx-4 sm:-mx-6 px-4 sm:px-6 pb-2">
                        <div class="bg-surface border border-line/60 rounded-lg p-4 min-w-[75%] snap-center shrink-0">
                            <AppTooltip :text="t('budgets.kpi.incomeTip')"><p class="text-xs text-muted uppercase tracking-wide mb-1 cursor-help">{{ t('budgets.kpi.income') }}</p></AppTooltip>
                            <p class="text-lg font-bold font-mono" :class="totalIncome.actual > 0 ? 'text-emerald-400' : 'text-muted'">{{ fmt(totalIncome.actual) }}</p>
                            <p class="text-xs text-muted mt-0.5">/ {{ fmt(totalIncome.planned) }} {{ t('budgets.kpi.planned') }}</p>
                        </div>
                        <div class="bg-surface border border-line/60 rounded-lg p-4 min-w-[75%] snap-center shrink-0">
                            <AppTooltip :text="t('budgets.kpi.expensesTip')"><p class="text-xs text-muted uppercase tracking-wide mb-1 cursor-help">{{ t('budgets.kpi.expenses') }}</p></AppTooltip>
                            <p class="text-lg font-bold font-mono" :class="totalExpenses.actual > 0 ? 'text-rose-400' : 'text-muted'">{{ fmt(totalExpenses.actual) }}</p>
                            <p class="text-xs text-muted mt-0.5">/ {{ fmt(totalExpenses.planned) }} {{ t('budgets.kpi.planned') }}</p>
                        </div>
                        <div class="bg-surface border border-line/60 rounded-lg p-4 min-w-[75%] snap-center shrink-0">
                            <AppTooltip :text="t('budgets.kpi.leftToSpendTip')"><p class="text-xs text-muted uppercase tracking-wide mb-1 cursor-help">{{ t('budgets.kpi.leftToSpend') }}</p></AppTooltip>
                            <p class="text-lg font-bold font-mono" :class="leftToSpend.actual < 0 ? 'text-rose-400' : 'text-emerald-400'">{{ fmt(leftToSpend.actual) }}</p>
                            <p class="text-xs text-muted mt-0.5">/ {{ fmt(leftToSpend.planned) }} {{ t('budgets.kpi.planned') }}</p>
                        </div>
                    </div>
                    <div class="flex justify-center gap-1.5 mt-2">
                        <span v-for="i in 3" :key="i" class="w-1.5 h-1.5 rounded-full" :class="kpiSlides[0] === i - 1 ? 'bg-indigo-400' : 'bg-surface-3'" />
                    </div>
                </div>

                <div v-if="!isBudgetEmpty && (overageCount > 0 || overageGoodCount > 0)" class="flex flex-col gap-2">
                    <div
                        v-if="overageCount > 0"
                        class="flex items-center gap-3 bg-rose-500/10 border border-rose-500/30 rounded-lg px-4 py-3"
                    >
                        <AlertTriangle class="w-4 h-4 text-rose-400 shrink-0" />
                        <p class="text-sm text-rose-300">{{ t('budgets.overageAlert', overageCount, { count: overageCount }) }}</p>
                    </div>
                    <div
                        v-if="overageGoodCount > 0"
                        class="flex items-center gap-3 bg-emerald-500/10 border border-emerald-500/30 rounded-lg px-4 py-3"
                    >
                        <CheckCircle class="w-4 h-4 text-emerald-400 shrink-0" />
                        <p class="text-sm text-emerald-300">{{ t('budgets.overageGood', overageGoodCount, { count: overageGoodCount }) }}</p>
                    </div>
                </div>

                <div v-if="showMoreKpi" class="space-y-3">
                    <div class="hidden md:grid grid-cols-3 gap-3">
                        <div class="bg-surface border border-line/60 rounded-lg p-4">
                            <AppTooltip :text="t('budgets.kpi.startBalanceTip')"><p class="text-xs text-muted uppercase tracking-wide mb-1 cursor-help">{{ t('budgets.kpi.startBalance') }}</p></AppTooltip>
                            <p class="text-lg font-bold text-primary font-mono">{{ fmt(startBalance) }}</p>
                        </div>
                        <div class="bg-surface border border-line/60 rounded-lg p-4">
                            <AppTooltip :text="t('budgets.kpi.cashFlowTip')"><p class="text-xs text-muted uppercase tracking-wide mb-1 cursor-help">{{ t('budgets.kpi.cashFlow') }}</p></AppTooltip>
                            <p class="text-lg font-bold font-mono" :class="cashFlow.actual !== 0 ? (cashFlow.actual >= 0 ? 'text-emerald-400' : 'text-rose-400') : 'text-muted'">
                                {{ fmt(cashFlow.actual, true) }}
                            </p>
                            <p class="text-xs text-muted mt-0.5">/ {{ fmt(cashFlow.planned, true) }} {{ t('budgets.kpi.planned') }}</p>
                        </div>
                        <div class="bg-surface border border-line/60 rounded-lg p-4">
                            <AppTooltip :text="t('budgets.kpi.savingsRateTip')"><p class="text-xs text-muted uppercase tracking-wide mb-1 cursor-help">{{ t('budgets.kpi.savingsRate') }}</p></AppTooltip>
                            <p class="text-lg font-bold font-mono" :class="savingsRate === null ? 'text-subtle' : savingsRate >= 20 ? 'text-emerald-400' : savingsRate >= 10 ? 'text-amber-400' : 'text-rose-400'">
                                {{ savingsRate !== null ? savingsRate + '%' : '—' }}
                            </p>
                            <p class="text-xs text-muted mt-0.5">{{ t('budgets.kpi.income') }} {{ fmt(totalIncome.actual) }}</p>
                        </div>
                    </div>

                    <div :ref="el => trackCarousel(el, 1)" class="md:hidden flex gap-3 overflow-x-auto snap-x snap-mandatory scrollbar-hide -mx-4 sm:-mx-6 px-4 sm:px-6 pb-2">
                        <div class="bg-surface border border-line/60 rounded-lg p-4 min-w-[75%] snap-center shrink-0">
                            <AppTooltip :text="t('budgets.kpi.startBalanceTip')"><p class="text-xs text-muted uppercase tracking-wide mb-1 cursor-help">{{ t('budgets.kpi.startBalance') }}</p></AppTooltip>
                            <p class="text-lg font-bold text-primary font-mono">{{ fmt(startBalance) }}</p>
                        </div>
                        <div class="bg-surface border border-line/60 rounded-lg p-4 min-w-[75%] snap-center shrink-0">
                            <AppTooltip :text="t('budgets.kpi.cashFlowTip')"><p class="text-xs text-muted uppercase tracking-wide mb-1 cursor-help">{{ t('budgets.kpi.cashFlow') }}</p></AppTooltip>
                            <p class="text-lg font-bold font-mono" :class="cashFlow.actual !== 0 ? (cashFlow.actual >= 0 ? 'text-emerald-400' : 'text-rose-400') : 'text-muted'">
                                {{ fmt(cashFlow.actual, true) }}
                            </p>
                            <p class="text-xs text-muted mt-0.5">/ {{ fmt(cashFlow.planned, true) }} {{ t('budgets.kpi.planned') }}</p>
                        </div>
                        <div class="bg-surface border border-line/60 rounded-lg p-4 min-w-[75%] snap-center shrink-0">
                            <AppTooltip :text="t('budgets.kpi.savingsRateTip')"><p class="text-xs text-muted uppercase tracking-wide mb-1 cursor-help">{{ t('budgets.kpi.savingsRate') }}</p></AppTooltip>
                            <p class="text-lg font-bold font-mono" :class="savingsRate === null ? 'text-subtle' : savingsRate >= 20 ? 'text-emerald-400' : savingsRate >= 10 ? 'text-amber-400' : 'text-rose-400'">
                                {{ savingsRate !== null ? savingsRate + '%' : '—' }}
                            </p>
                            <p class="text-xs text-muted mt-0.5">{{ t('budgets.kpi.income') }} {{ fmt(totalIncome.actual) }}</p>
                        </div>
                    </div>
                    <div class="md:hidden flex justify-center gap-1.5 mt-2">
                        <span v-for="i in 3" :key="i" class="w-1.5 h-1.5 rounded-full" :class="kpiSlides[1] === i - 1 ? 'bg-indigo-400' : 'bg-surface-3'" />
                    </div>

                    <div class="hidden md:grid grid-cols-3 gap-3">
                        <div class="bg-surface border border-line/60 rounded-lg p-4">
                            <AppTooltip :text="t('budgets.kpi.distributionTip')"><p class="text-xs text-muted uppercase tracking-wide mb-3 cursor-help">{{ t('budgets.kpi.distribution') }}</p></AppTooltip>
                            <DonutChart v-if="donutSegments.length" :segments="donutSegments" :size="120" />
                            <EmptyState v-else :message="t('budgets.noneThisMonth')" icon="chart" compact />
                        </div>
                        <div class="bg-surface border border-line/60 rounded-lg p-4 flex flex-col justify-between gap-4">
                            <AppTooltip :text="t('budgets.kpi.spendProgressTip')"><p class="text-xs text-muted uppercase tracking-wide cursor-help">{{ t('budgets.kpi.spendProgress') }}</p></AppTooltip>
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
                        <div class="bg-surface border border-line/60 rounded-lg p-4 flex flex-col justify-between">
                            <div>
                                <AppTooltip :text="t('budgets.kpi.projectionTip')">
                                    <p class="text-xs text-muted uppercase tracking-wide mb-1 cursor-help">
                                        {{ t('budgets.kpi.projection') }}
                                        <span v-if="!isCurrentMonth" class="normal-case text-subtle ml-1">{{ t('budgets.projectionPast') }}</span>
                                    </p>
                                </AppTooltip>
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

                    <div :ref="el => trackCarousel(el, 2)" class="md:hidden flex gap-3 overflow-x-auto snap-x snap-mandatory scrollbar-hide -mx-4 sm:-mx-6 px-4 sm:px-6 pb-2">
                        <div class="bg-surface border border-line/60 rounded-lg p-4 min-w-[75%] snap-center shrink-0">
                            <AppTooltip :text="t('budgets.kpi.distributionTip')"><p class="text-xs text-muted uppercase tracking-wide mb-3 cursor-help">{{ t('budgets.kpi.distribution') }}</p></AppTooltip>
                            <DonutChart v-if="donutSegments.length" :segments="donutSegments" :size="120" />
                            <EmptyState v-else :message="t('budgets.noneThisMonth')" icon="chart" compact />
                        </div>
                        <div class="bg-surface border border-line/60 rounded-lg p-4 min-w-[75%] snap-center shrink-0">
                            <AppTooltip :text="t('budgets.kpi.spendProgressTip')"><p class="text-xs text-muted uppercase tracking-wide mb-1 cursor-help">{{ t('budgets.kpi.spendProgress') }}</p></AppTooltip>
                            <div class="mt-3">
                                <div class="flex items-end justify-between mb-2">
                                    <span class="text-lg font-bold font-mono" :class="leftToSpend.actual < 0 ? 'text-rose-400' : 'text-emerald-400'">
                                        {{ fmt(leftToSpend.actual) }}
                                    </span>
                                    <span class="text-sm text-muted font-mono">
                                        {{ totalIncome.actual > 0 ? Math.min(100, Math.round((totalExpenses.actual / totalIncome.actual) * 100)) : 0 }}%
                                    </span>
                                </div>
                                <div class="h-3 bg-surface-3 rounded-full overflow-hidden">
                                    <div
                                        v-show="totalIncome.actual > 0"
                                        class="h-full rounded-full transition-all duration-500"
                                        :class="totalExpenses.actual > totalIncome.actual ? 'bg-rose-400' : 'bg-emerald-400'"
                                        :style="{ width: totalIncome.actual > 0 ? Math.min(100, (totalExpenses.actual / totalIncome.actual) * 100) + '%' : '0%' }"
                                    />
                                </div>
                                <div class="flex justify-between text-xs text-muted mt-1.5">
                                    <span>{{ fmt(totalExpenses.actual) }} {{ t('budgets.kpi.spent') }}</span>
                                    <span>{{ fmt(totalIncome.actual) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="bg-surface border border-line/60 rounded-lg p-4 min-w-[75%] snap-center shrink-0">
                            <AppTooltip :text="t('budgets.kpi.projectionTip')">
                                <p class="text-xs text-muted uppercase tracking-wide mb-1 cursor-help">
                                    {{ t('budgets.kpi.projection') }}
                                    <span v-if="!isCurrentMonth" class="normal-case text-subtle ml-1">{{ t('budgets.projectionPast') }}</span>
                                </p>
                            </AppTooltip>
                            <p v-if="projectedExpenses !== null" class="text-lg font-bold font-mono" :class="projectedExpenses > totalExpenses.planned ? 'text-rose-400' : 'text-emerald-400'">
                                {{ fmt(projectedExpenses) }}
                            </p>
                            <p v-else class="text-lg font-bold text-muted font-mono">—</p>
                            <p v-if="projectedExpenses !== null" class="text-xs text-muted mt-0.5">
                                vs {{ fmt(totalExpenses.planned) }} {{ t('budgets.kpi.planned') }}
                            </p>
                        </div>
                    </div>
                    <div class="md:hidden flex justify-center gap-1.5 mt-2">
                        <span v-for="i in 3" :key="i" class="w-1.5 h-1.5 rounded-full" :class="kpiSlides[2] === i - 1 ? 'bg-indigo-400' : 'bg-surface-3'" />
                    </div>
                </div>

                <button
                    class="flex items-center gap-1.5 text-xs text-indigo-400 hover:text-indigo-300 transition-colors"
                    data-tour="kpi-toggle"
                    v-on:click="toggleMoreKpi"
                >
                    <ChevronDown
                        class="w-3 h-3 transition-transform duration-200"
                        :class="showMoreKpi ? '' : '-rotate-90'"
                    />
                    {{ showMoreKpi ? t('budgets.kpi.showLess') : t('budgets.kpi.showMore') }}
                </button>
            </div>

            <div v-if="isBudgetEmpty" class="bg-surface border border-dashed border-line rounded-lg p-8 text-center">
                <p class="text-secondary mb-4">{{ t('budgets.emptyBudget') }}</p>
                <button
                    class="inline-flex items-center gap-2 bg-surface-2 hover:bg-surface-3 text-primary text-sm font-medium px-4 py-2 rounded-lg border border-line transition-colors"
                    v-on:click="copyFromPrevious"
                >
                    <Copy class="w-4 h-4" />
                    {{ t('budgets.copyFromPrevious', { month: fmtMonth(prevMonth) }) }}
                </button>
            </div>

            <div v-if="quickStartSuggestions.length > 0" class="space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <span class="text-sm text-secondary">{{ t('budgets.quickStart.label') }}</span>
                    <div class="flex flex-wrap gap-2">
                        <button
                            class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors"
                            :disabled="quickStartForm.processing"
                            v-on:click="quickStartAll"
                        >
                            <Zap class="w-4 h-4" />
                            {{ t('budgets.quickStart.addAll') }}
                        </button>
                        <Link
                            href="/profile"
                            class="inline-flex items-center gap-2 text-sm text-muted hover:text-indigo-400 font-medium px-4 py-2 transition-colors"
                        >
                            <Settings class="w-4 h-4" />
                            {{ t('budgets.quickStart.customize') }}
                        </Link>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                    <button
                        v-for="suggestion in quickStartSuggestions"
                        :key="suggestion.label"
                        class="border-2 border-dashed border-line/60 rounded-xl px-4 py-3 text-left transition-all hover:border-indigo-500/50 hover:bg-indigo-500/5 group"
                        :disabled="quickStartForm.processing"
                        v-on:click="quickStart(suggestion)"
                    >
                        <span class="text-sm font-medium text-muted group-hover:text-indigo-400 transition-colors">
                            {{ suggestion.label }}
                        </span>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs px-1.5 py-0.5 rounded-full" :class="[SECTION_META[suggestion.type]?.bg, SECTION_META[suggestion.type]?.color]">
                                {{ SECTION_META[suggestion.type]?.label }}
                            </span>
                            <span v-if="suggestion.planned_amount" class="text-xs text-muted font-mono">{{ fmt(suggestion.planned_amount) }}</span>
                        </div>
                    </button>
                </div>
            </div>

            <div class="bg-surface border border-line/60 rounded-lg relative" data-tour="budget-notes">
                <button
                    class="flex items-center justify-between w-full px-4 py-3 text-xs transition-colors text-muted hover:text-secondary cursor-pointer"
                    v-on:click="budgetNotesOpen = !budgetNotesOpen"
                >
                    <span class="flex items-center gap-2 uppercase tracking-wide font-medium min-w-0 flex-1">
                        <FileText class="w-3.5 h-3.5 shrink-0" />
                        <span class="shrink-0">{{ t('budgets.notes.label') }}</span>
                        <span v-if="budgetNotesText" class="text-indigo-400 normal-case tracking-normal font-normal truncate">{{ budgetNotesText }}</span>
                    </span>
                    <div class="flex items-center gap-2">
                        <ChevronDown
                            class="w-3.5 h-3.5 transition-transform duration-200"
                            :class="budgetNotesOpen ? '' : '-rotate-90'"
                        />
                    </div>
                </button>
                <div v-if="budgetNotesOpen" class="px-4 pb-3">
                    <textarea
                        v-model="budgetNotesText"
                        :placeholder="t('budgets.notes.placeholder')"
                        rows="3"
                        class="w-full bg-surface-2 text-primary rounded-lg px-3 py-2 text-sm border border-line focus:border-indigo-500 focus:outline-none resize-none"
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
                        class="bg-surface-2 border border-line rounded-xl p-4 flex flex-col gap-3"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-3 h-3 rounded-full shrink-0" :style="{ backgroundColor: goal.color }" />
                                <span class="text-sm font-medium text-primary truncate">{{ goal.name }}</span>
                            </div>
                            <AppTooltip v-if="!goal.category_id" :text="t('goals.deposit')">
                                <button class="shrink-0 text-muted hover:text-emerald-400 transition-colors" v-on:click="depositGoal = goal">
                                    <Plus class="w-4 h-4" />
                                </button>
                            </AppTooltip>
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

            <div v-if="showProFeatures || !isBudgetEmpty" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <p class="hidden sm:block text-xs text-subtle">{{ showProFeatures ? t('budgets.hint') : '' }}</p>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                    <AppButton
                        v-if="showProFeatures && !isBudgetEmpty"
                        size="sm"
                        variant="secondary"
                        v-on:click="copyFromPrevious()"
                    >
                        <Copy class="w-4 h-4 mr-1.5" />
                        {{ t('budgets.copyFromPrevious', { month: fmtMonth(prevMonth) }) }}
                    </AppButton>
                    <AppButton
                        v-if="showProFeatures"
                        size="sm"
                        variant="secondary"
                        v-on:click="copyRepeat()"
                    >
                        <Copy class="w-4 h-4 mr-1.5" />
                        {{ t('budgets.copyRepeat') }}
                    </AppButton>
                    <AppButton
                        v-if="showProFeatures"
                        size="sm"
                        variant="secondary"
                        v-on:click="exportXlsx()"
                    >
                        <FileText class="w-4 h-4 mr-1.5" />
                        {{ t('budgets.exportXlsx') }}
                    </AppButton>
                    <AppButton
                        v-if="!isBudgetEmpty"
                        size="sm"
                        variant="danger-outline"
                        v-on:click="clearAll"
                    >
                        <Trash2 class="w-4 h-4 mr-1.5" />
                        {{ t('budgets.clearAll') }}
                    </AppButton>
                </div>
            </div>

            <div class="bg-surface border border-line/60 rounded-lg overflow-clip" data-tour="budget-table">
                <div class="md:hidden divide-y divide-line/40">
                    <template v-for="(items, type) in sections" :key="`mob-${type}`">
                        <div
                            :data-tour-section="type"
                            :class="[SECTION_META[type].bg, SECTION_META[type].border, 'border-b px-4 py-2.5 flex flex-col gap-1 cursor-pointer select-none']"
                            v-on:click="toggleSection(type)"
                        >
                            <div class="flex items-center justify-between">
                                <span :class="[SECTION_META[type].color, 'font-semibold uppercase text-xs tracking-widest']">
                                    {{ SECTION_META[type].label }}
                                </span>
                                <ChevronDown
                                    class="w-3.5 h-3.5 transition-transform duration-200"
                                    :class="[SECTION_META[type].color, collapsedSections[type] ? '-rotate-90' : '']"
                                />
                            </div>
                            <div class="flex items-center gap-2">
                                <span :class="[SECTION_META[type].color, 'text-sm font-mono font-semibold']">{{ fmt(totals[type]?.actual ?? 0) }}</span>
                                <span class="text-xs text-muted font-mono">/ {{ fmt(totals[type]?.planned ?? 0) }}</span>
                                <span v-if="collapsedSections[type]" class="text-xs text-subtle ml-1">· {{ items.length }} ligne{{ items.length > 1 ? 's' : '' }}</span>
                            </div>
                        </div>

                        <template v-if="!collapsedSections[type]">
                            <template v-for="item in items" :key="`mob-item-${item.id}`">
                                <div v-if="editingId === item.id" class="bg-surface-2 px-4 py-3 space-y-3" data-editing>
                                    <BudgetInput v-model="editForm.label" type="text" :placeholder="t('budgets.editRow.labelPlaceholder')" />

                                    <div class="grid grid-cols-2 gap-2">
                                        <div v-if="creatingCategory && categoryTargetForm === editForm" class="col-span-2 flex items-center gap-1">
                                            <BudgetInput
                                                v-model="newCategoryName"
                                                data-new-category
                                                type="text"
                                                variant="focus"
                                                :placeholder="t('budgets.addRow.newCategoryPlaceholder')"
                                                class="flex-1"
                                                :disabled="creatingCategoryLoading"
                                                v-on:keydown.enter.prevent="createCategory"
                                                v-on:keydown.escape="cancelCreateCategory"
                                            />
                                            <AppButton
                                                variant="icon"
                                                size="none"
                                                class="text-emerald-400 hover:text-emerald-300 shrink-0"
                                                :disabled="creatingCategoryLoading"
                                                v-on:click="createCategory"
                                            >
                                                <Check class="w-4 h-4" />
                                            </AppButton>
                                            <button class="text-rose-400 hover:text-rose-300 transition-colors shrink-0" v-on:click="cancelCreateCategory">
                                                <X class="w-4 h-4" />
                                            </button>
                                        </div>
                                        <BudgetSelect v-else v-model="editForm.category_id" v-on:change="onCategoryChange(editForm)">
                                            <option :value="null">—</option>
                                            <option v-for="cat in availableCategoriesForEdit" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                            <option value="__create__">+ {{ t('budgets.addRow.newCategory') }}</option>
                                        </BudgetSelect>
                                        <BudgetInput
                                            v-model="editForm.planned_amount"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            placeholder="0,00"
                                            variant="mono"
                                        />
                                    </div>

                                    <textarea
                                        v-model="editForm.notes"
                                        :placeholder="t('budgets.editRow.notePlaceholder')"
                                        rows="2"
                                        class="w-full bg-surface-3 text-secondary rounded px-2 py-1.5 text-sm border border-line-strong focus:border-indigo-500 focus:outline-none resize-none"
                                    />

                                    <div class="pt-2 border-t border-line space-y-2">
                                        <div class="grid grid-cols-2 gap-2">
                                            <BudgetSelect v-model="editForm.type" class="text-xs">
                                                <option v-for="(meta, stype) in SECTION_META" :key="stype" :value="stype">{{ meta.label }}</option>
                                            </BudgetSelect>
                                            <BudgetSelect v-model="editForm.target_type" class="text-xs">
                                                <option :value="null">{{ t('budgets.target.none') }}</option>
                                                <option value="spending">{{ t('budgets.target.spending') }}</option>
                                                <option value="saving">{{ t('budgets.target.saving') }}</option>
                                                <option value="by_date">{{ t('budgets.target.byDate') }}</option>
                                            </BudgetSelect>
                                        </div>
                                        <div v-if="editForm.target_type" class="grid gap-2" :class="editForm.target_type === BudgetTargetType.ByDate ? 'grid-cols-2' : ''">
                                            <BudgetInput
                                                v-model="editForm.target_amount"
                                                type="number"
                                                step="0.01"
                                                min="0.01"
                                                variant="mono"
                                                :placeholder="t('budgets.target.amount')"
                                            />
                                            <BudgetInput
                                                v-if="editForm.target_type === BudgetTargetType.ByDate"
                                                v-model="editForm.target_deadline"
                                                type="date"
                                            />
                                        </div>
                                    </div>

                                    <div class="flex gap-2 pt-4 border-t border-line">
                                        <button
                                            class="flex-1 flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium transition-colors"
                                            v-on:click="submitEdit(item)"
                                        >
                                            <Check class="w-4 h-4" />
                                            {{ t('budgets.editRow.confirm') }}
                                        </button>
                                        <button
                                            class="flex-1 flex items-center justify-center gap-2 px-3 py-2 rounded-lg border border-line text-muted hover:text-primary hover:border-primary text-sm font-medium transition-colors"
                                            v-on:click="cancelEditing"
                                        >
                                            <X class="w-4 h-4" />
                                            {{ t('budgets.editRow.cancel') }}
                                        </button>
                                    </div>
                                </div>

                                <div
                                    v-else
                                    :data-tour-item="item.id"
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
                                                class="inline-flex items-center gap-0.5 text-xs text-indigo-400 border border-indigo-500/30 rounded px-1 py-0.5"
                                            >
                                                <Repeat class="w-2.5 h-2.5" />
                                            </span>
                                            <AppTooltip
                                                v-if="item.target_type"
                                                :text="targetTooltip(item)"
                                            >
                                                <span
                                                    class="inline-flex items-center text-xs border rounded px-1 py-0.5 cursor-help"
                                                    :class="item.target_type === BudgetTargetType.Spending
                                                        ? (item.actual_amount <= (item.target_amount ?? 0) ? 'text-emerald-400 border-emerald-500/30' : 'text-rose-400 border-rose-500/30')
                                                        : 'text-amber-400 border-amber-500/30'"
                                                >
                                                    {{ item.target_type === BudgetTargetType.ByDate ? t('budgets.target.byDate') : fmt(item.target_amount) }}
                                                </span>
                                            </AppTooltip>
                                        </div>
                                        <div v-if="!item.category?.is_system" :data-tour-actions="item.id" class="flex items-center gap-2 shrink-0">
                                            <button
                                                class="text-muted hover:text-indigo-400 transition-colors"
                                                v-on:click="openTxPanelFromRow(item.category_id, item.label, type === BudgetSection.Income ? TransactionType.Income : TransactionType.Expense, type)"
                                            >
                                                <Plus class="w-4 h-4" />
                                            </button>
                                            <button class="text-muted hover:text-sky-400 transition-colors" v-on:click="startEditingItem(item)">
                                                <Pencil class="w-4 h-4" />
                                            </button>
                                            <button class="text-muted hover:text-rose-400 transition-colors" v-on:click="requestDelete(item)">
                                                <Trash2 class="w-4 h-4" />
                                            </button>
                                            <div class="relative">
                                                <button
                                                    class="text-muted hover:text-secondary transition-colors"
                                                    v-on:click.stop="toggleMobileMenu(item.id)"
                                                >
                                                    <MoreHorizontal class="w-4 h-4" />
                                                </button>
                                                <div
                                                    v-if="mobileMenuOpenId === item.id"
                                                    class="absolute right-0 top-6 z-20 w-44 bg-surface border border-line rounded-lg shadow-xl overflow-hidden"
                                                    v-on:click.stop
                                                >
                                                    <button
                                                        v-if="showProFeatures"
                                                        class="w-full flex items-center gap-2.5 px-3 py-2.5 text-sm transition-colors hover:bg-surface-2"
                                                        :class="item.repeat_next_month ? 'text-indigo-400' : 'text-secondary'"
                                                        v-on:click="toggleRepeat(item); closeMobileMenu()"
                                                    >
                                                        <Repeat class="w-3.5 h-3.5 shrink-0" />
                                                        {{ t('budgets.actions.toggleRepeat') }}
                                                    </button>
                                                    <button
                                                        v-if="showProFeatures"
                                                        class="w-full flex items-center gap-2.5 px-3 py-2.5 text-sm text-secondary transition-colors hover:bg-surface-2"
                                                        v-on:click="duplicateItem(item); closeMobileMenu()"
                                                    >
                                                        <Copy class="w-3.5 h-3.5 shrink-0" />
                                                        {{ t('budgets.actions.duplicate') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between text-xs">
                                        <span v-if="item.category" :class="item.category.is_system ? 'bg-indigo-500/10 text-indigo-400 border-indigo-500/30' : 'bg-surface-2 text-secondary border-line'" class="inline-flex items-center rounded px-2 py-0.5 border text-xs">
                                            {{ item.category.name }}
                                        </span>
                                        <span v-else class="text-subtle">—</span>
                                        <div class="flex items-center gap-2 font-mono">
                                            <span class="text-muted" :data-tour-carried-over="item.carried_over !== 0 ? item.id : undefined">
                                                {{ fmt(item.planned_amount) }}
                                                <AppTooltip
                                                    v-if="item.carried_over !== 0"
                                                    :text="item.carried_over > 0
                                                        ? t('budgets.carriedOverTooltip', { amount: fmt(item.carried_over) })
                                                        : t('budgets.carriedOverDeficitTooltip', { amount: fmt(Math.abs(item.carried_over)) })"
                                                >
                                                    <span class="text-xs cursor-help" :class="item.carried_over > 0 ? 'text-emerald-400' : 'text-rose-400'">
                                                        {{ item.carried_over > 0 ? '+' : '' }}{{ fmt(item.carried_over) }}
                                                    </span>
                                                </AppTooltip>
                                            </span>
                                            <button
                                                v-if="item.category_id && item.actual_amount > 0"
                                                :data-tour-actual="item.id"
                                                :class="diffClass(item.actual_amount - item.planned_amount, SECTION_META[type].positiveIsGood, item.actual_amount)"
                                                class="font-semibold underline decoration-dotted decoration-muted/50 underline-offset-4 inline-flex items-center gap-1"
                                                v-on:click="openTxDetail(item)"
                                            >
                                                {{ fmt(item.actual_amount) }}
                                                <Eye class="w-3 h-3 opacity-50" />
                                            </button>
                                            <span v-else :class="diffClass(item.actual_amount - item.planned_amount, SECTION_META[type].positiveIsGood, item.actual_amount)" class="font-semibold">{{ fmt(item.actual_amount) }}</span>
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

                            <div v-if="addingType === type" class="bg-surface-2/60 px-4 py-3 space-y-2" data-adding data-tour="add-line-form">
                                <div data-tour="add-field-label">
                                    <BudgetInput
                                        :id="`add-label-${type}`"
                                        v-model="addForm.label"
                                        type="text"
                                        variant="focus"
                                        :error="addFormSubmitted && !addForm.label"
                                        :placeholder="t('budgets.addRow.labelPlaceholder')"
                                        v-on:keydown="onKeydown($event, handleSubmitAdd, cancelAdding)"
                                    />
                                    <FormHint>{{ t('budgets.addRow.labelHint') }}</FormHint>
                                </div>
                                <div data-tour="add-field-category">
                                    <div v-if="creatingCategory && categoryTargetForm === addForm" class="flex items-center gap-1">
                                        <BudgetInput
                                            v-model="newCategoryName"
                                            data-new-category
                                            type="text"
                                            variant="focus"
                                            class="flex-1"
                                            :placeholder="t('budgets.addRow.newCategoryPlaceholder')"
                                            :disabled="creatingCategoryLoading"
                                            v-on:keydown.enter.prevent="createCategory"
                                            v-on:keydown.escape="cancelCreateCategory"
                                        />
                                        <AppButton
                                            variant="icon"
                                            size="none"
                                            class="text-emerald-400 hover:text-emerald-300 shrink-0"
                                            :disabled="creatingCategoryLoading"
                                            v-on:click="createCategory"
                                        >
                                            <Check class="w-4 h-4" />
                                        </AppButton>
                                        <button class="text-rose-400 hover:text-rose-300 transition-colors shrink-0" v-on:click="cancelCreateCategory">
                                            <X class="w-4 h-4" />
                                        </button>
                                    </div>
                                    <BudgetSelect
                                        v-else
                                        v-model="addForm.category_id"
                                        :error="addFormSubmitted && !addForm.category_id"
                                        v-on:keydown="onKeydown($event, handleSubmitAdd, cancelAdding)"
                                        v-on:change="onCategoryChange(addForm)"
                                    >
                                        <option :value="null">—</option>
                                        <option v-for="cat in availableCategories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                        <option value="__create__">+ {{ t('budgets.addRow.newCategory') }}</option>
                                    </BudgetSelect>
                                    <FormHint>{{ t('budgets.addRow.categoryHint') }}</FormHint>
                                </div>
                                <div data-tour="add-field-amount">
                                    <BudgetInput
                                        v-model="addForm.planned_amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="0,00"
                                        variant="mono"
                                        :error="addFormSubmitted && addForm.planned_amount === ''"
                                        v-on:keydown="onKeydown($event, handleSubmitAdd, cancelAdding)"
                                    />
                                    <FormHint>{{ t('budgets.addRow.amountHint') }}</FormHint>
                                </div>
                                <div data-tour="add-field-target">
                                    <BudgetSelect v-model="addForm.target_type">
                                        <option :value="null">{{ t('budgets.target.label') }} — {{ t('budgets.target.none') }}</option>
                                        <option value="spending">{{ t('budgets.target.spending') }}</option>
                                        <option value="saving">{{ t('budgets.target.saving') }}</option>
                                        <option value="by_date">{{ t('budgets.target.byDate') }}</option>
                                    </BudgetSelect>
                                    <BudgetInput
                                        v-if="addForm.target_type"
                                        v-model="addForm.target_amount"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        variant="mono"
                                        :placeholder="t('budgets.target.amount')"
                                    />
                                    <BudgetInput
                                        v-if="addForm.target_type === BudgetTargetType.ByDate"
                                        v-model="addForm.target_deadline"
                                        type="date"
                                    />
                                </div>
                                <div class="flex justify-end gap-3">
                                    <button class="text-emerald-400 hover:text-emerald-300" v-on:click="handleSubmitAdd">
                                        <Check class="w-5 h-5" />
                                    </button>
                                    <button class="text-rose-400 hover:text-rose-300 transition-colors" v-on:click="cancelAdding">
                                        <X class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>

                            <div class="px-4 py-2">
                                <AppLink :data-tour-add-line="type" v-on:click="startAddingItem(type)">
                                    <Plus class="w-3 h-3" />
                                    {{ t('budgets.addRow.addLine') }}
                                </AppLink>
                            </div>
                        </template>
                    </template>

                    <div v-if="hasUnbudgeted" class="border-t-2 border-amber-500/30 bg-amber-500/5 px-4 py-3 space-y-1">
                        <div class="flex items-center gap-2">
                            <AlertTriangle class="w-3.5 h-3.5 text-amber-400 shrink-0" />
                            <span class="text-xs font-semibold uppercase tracking-widest text-amber-400">{{ t('budgets.table.unbudgeted') }}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs font-mono">
                            <button v-if="unbudgeted.income > 0" type="button" class="text-left hover:opacity-70 transition-opacity" v-on:click="openUnbudgetedPanel(TransactionType.Income)">
                                <p class="text-muted">{{ t('budgets.sections.income') }}</p>
                                <p class="font-semibold text-emerald-400 cursor-pointer">+{{ fmt(unbudgeted.income) }}</p>
                            </button>
                            <button v-if="unbudgeted.expenses > 0" type="button" class="text-left hover:opacity-70 transition-opacity" v-on:click="openUnbudgetedPanel(TransactionType.Expense)">
                                <p class="text-muted">{{ t('budgets.kpi.expenses') }}</p>
                                <p class="font-semibold text-rose-400 cursor-pointer">-{{ fmt(unbudgeted.expenses) }}</p>
                            </button>
                        </div>
                    </div>
                </div>

                <table class="hidden md:table w-full text-sm">
                    <thead>
                        <tr class="border-b border-line/60 text-xs text-muted uppercase tracking-wider sticky top-0 z-10 bg-surface">
                            <th class="text-left px-4 py-3 font-medium w-[34%]">{{ t('budgets.table.label') }}</th>
                            <th class="text-left px-4 py-3 font-medium w-[20%]">{{ t('budgets.table.category') }}</th>
                            <th class="text-right px-4 py-3 font-medium w-[13%]">{{ t('budgets.table.planned') }}</th>
                            <th class="text-right px-4 py-3 font-medium w-[13%]">{{ t('budgets.table.actual') }}</th>
                            <th class="text-right px-4 py-3 font-medium w-[10%]">{{ t('budgets.table.diff') }}</th>
                            <th class="w-[10%]" />
                        </tr>
                    </thead>
                    <tbody v-for="(items, type) in sections" :key="type" :data-tour-section="type">
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
                                        <div class="flex-1 max-w-30 h-1.5 bg-surface-3 rounded-full">
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
                                :class="diffClass((totals[type]?.actual ?? 0) - (totals[type]?.planned ?? 0), SECTION_META[type].positiveIsGood, totals[type]?.actual ?? 0)"
                            >
                                {{ fmt((totals[type]?.actual ?? 0) - (totals[type]?.planned ?? 0), true) }}
                            </td>
                            <td class="px-3 py-2 text-right">
                                <ChevronDown
                                    class="w-3.5 h-3.5 inline transition-transform duration-200"
                                    :class="[SECTION_META[type].color, collapsedSections[type] ? '-rotate-90' : '']"
                                />
                            </td>
                        </tr>

                        <template v-if="!collapsedSections[type]">
                            <template v-for="item in items" :key="item.id">
                                <template v-if="editingId === item.id">
                                    <tr class="bg-surface-2 border-b border-line/40" data-editing>
                                        <td class="pl-8 pr-2 py-1.5">
                                            <BudgetInput
                                                :id="`edit-label-${item.id}`"
                                                v-model="editForm.label"
                                                type="text"
                                                tabindex="1"
                                                :placeholder="t('budgets.editRow.labelPlaceholder')"
                                                v-on:keydown="onKeydown($event, () => submitEdit(item), cancelEditing)"
                                            />
                                        </td>
                                        <td class="px-2 py-1.5">
                                            <div v-if="creatingCategory && categoryTargetForm === editForm" class="flex items-center gap-1">
                                                <BudgetInput
                                                    v-model="newCategoryName"
                                                    data-new-category
                                                    type="text"
                                                    variant="focus"
                                                    class="flex-1"
                                                    :placeholder="t('budgets.addRow.newCategoryPlaceholder')"
                                                    :disabled="creatingCategoryLoading"
                                                    v-on:keydown.enter.prevent="createCategory"
                                                    v-on:keydown.escape="cancelCreateCategory"
                                                />
                                                <AppButton
                                                    variant="icon"
                                                    size="none"
                                                    class="text-emerald-400 hover:text-emerald-300 shrink-0"
                                                    :disabled="creatingCategoryLoading"
                                                    v-on:click="createCategory"
                                                >
                                                    <Check class="w-3.5 h-3.5" />
                                                </AppButton>
                                                <button class="text-rose-400 hover:text-rose-300 transition-colors shrink-0" v-on:click="cancelCreateCategory">
                                                    <X class="w-3.5 h-3.5" />
                                                </button>
                                            </div>
                                            <BudgetSelect
                                                v-else
                                                v-model="editForm.category_id"
                                                tabindex="2"
                                                v-on:keydown="onKeydown($event, () => submitEdit(item), cancelEditing)"
                                                v-on:change="onCategoryChange(editForm)"
                                            >
                                                <option :value="null">—</option>
                                                <option v-for="cat in availableCategoriesForEdit" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                                <option value="__create__">+ {{ t('budgets.addRow.newCategory') }}</option>
                                            </BudgetSelect>
                                        </td>
                                        <td class="px-2 py-1.5">
                                            <BudgetInput
                                                v-model="editForm.planned_amount"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                tabindex="3"
                                                placeholder="0,00"
                                                variant="mono"
                                                v-on:keydown="onKeydown($event, () => submitEdit(item), cancelEditing)"
                                            />
                                        </td>
                                        <td class="px-2 py-1.5 text-right text-muted font-mono text-xs">{{ fmt(item.actual_amount) }}</td>
                                        <td />
                                        <td class="px-3 py-1.5">
                                            <div class="flex items-center gap-2 justify-end">
                                                <AppTooltip :text="t('budgets.editRow.confirm')">
                                                    <button class="text-emerald-400 hover:text-emerald-300 transition-colors" v-on:click="submitEdit(item)">
                                                        <Check class="w-4 h-4" />
                                                    </button>
                                                </AppTooltip>
                                                <AppTooltip :text="t('budgets.editRow.cancel')">
                                                    <button class="text-rose-400 hover:text-rose-300 transition-colors" v-on:click="cancelEditing">
                                                        <X class="w-4 h-4" />
                                                    </button>
                                                </AppTooltip>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="bg-surface-2 border-b border-line/40" data-editing>
                                        <td colspan="6" class="pl-8 pr-3 py-2">
                                            <div class="flex gap-4">
                                                <textarea
                                                    v-model="editForm.notes"
                                                    :placeholder="t('budgets.editRow.notePlaceholder')"
                                                    rows="2"
                                                    tabindex="4"
                                                    class="flex-1 bg-surface-3 text-secondary rounded px-2 py-1 text-xs border border-line-strong focus:border-indigo-500 focus:outline-none resize-none"
                                                    v-on:keydown="onNotesKeydown($event, item)"
                                                />
                                                <div class="w-56 space-y-2">
                                                    <div class="space-y-1">
                                                        <label class="text-xs text-muted">{{ t('budgets.editRow.section') }}</label>
                                                        <BudgetSelect v-model="editForm.type" class="w-full text-xs">
                                                            <option v-for="(meta, stype) in SECTION_META" :key="stype" :value="stype">{{ meta.label }}</option>
                                                        </BudgetSelect>
                                                    </div>
                                                    <div class="space-y-1">
                                                        <label class="text-xs text-muted">{{ t('budgets.target.label') }}</label>
                                                        <BudgetSelect v-model="editForm.target_type" class="w-full text-xs">
                                                            <option :value="null">{{ t('budgets.target.none') }}</option>
                                                            <option value="spending">{{ t('budgets.target.spending') }}</option>
                                                            <option value="saving">{{ t('budgets.target.saving') }}</option>
                                                            <option value="by_date">{{ t('budgets.target.byDate') }}</option>
                                                        </BudgetSelect>
                                                        <div v-if="editForm.target_type" class="flex items-center gap-1 pt-1">
                                                            <BudgetInput
                                                                v-model="editForm.target_amount"
                                                                type="number"
                                                                step="0.01"
                                                                min="0.01"
                                                                variant="mono"
                                                                class="flex-1"
                                                                :placeholder="t('budgets.target.amount')"
                                                            />
                                                            <BudgetInput
                                                                v-if="editForm.target_type === BudgetTargetType.ByDate"
                                                                v-model="editForm.target_deadline"
                                                                type="date"
                                                                class="w-32"
                                                            />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </template>

                                <tr
                                    v-else
                                    draggable="true"
                                    :data-tour-item="item.id"
                                    class="border-b border-surface-2/60 group hover:bg-surface-2/40 cursor-pointer transition-colors"
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
                                                <GripVertical class="w-3 h-4" />
                                            </div>
                                            <span>{{ item.label }}</span>
                                            <NoteTooltip v-if="item.notes" :note="item.notes" />
                                            <AppTooltip v-if="item.repeat_next_month" :text="t('budgets.repeatNextMonth')">
                                                <span
                                                    class="inline-flex items-center gap-0.5 text-xs text-indigo-400 border border-indigo-500/30 rounded px-1 py-0.5 leading-none cursor-help"
                                                >
                                                    <Repeat class="w-2.5 h-2.5" />
                                                </span>
                                            </AppTooltip>
                                            <AppTooltip
                                                v-if="item.target_type"
                                                :text="targetTooltip(item)"
                                            >
                                                <span
                                                    class="inline-flex items-center gap-0.5 text-xs border rounded px-1 py-0.5 leading-none cursor-help"
                                                    :class="item.target_type === BudgetTargetType.Spending
                                                        ? (item.actual_amount <= (item.target_amount ?? 0) ? 'text-emerald-400 border-emerald-500/30' : 'text-rose-400 border-rose-500/30')
                                                        : 'text-amber-400 border-amber-500/30'"
                                                >
                                                    {{ item.target_type === BudgetTargetType.ByDate ? t('budgets.target.byDate') : fmt(item.target_amount) }}
                                                </span>
                                            </AppTooltip>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <span v-if="item.category" :class="item.category.is_system ? 'bg-indigo-500/10 text-indigo-400 border-indigo-500/30' : 'bg-surface-2 text-secondary border-line'" class="inline-flex items-center text-xs rounded px-2 py-0.5 border">
                                            {{ item.category.name }}
                                        </span>
                                        <span v-else class="text-subtle">—</span>
                                    </td>
                                    <td class="px-4 py-2.5 text-right text-secondary font-mono" :data-tour-carried-over="item.carried_over !== 0 ? item.id : undefined">
                                        <div>
                                            {{ fmt(item.planned_amount) }}
                                            <AppTooltip
                                                v-if="item.carried_over !== 0"
                                                :text="item.carried_over > 0
                                                    ? t('budgets.carriedOverTooltip', { amount: fmt(item.carried_over) })
                                                    : t('budgets.carriedOverDeficitTooltip', { amount: fmt(Math.abs(item.carried_over)) })"
                                            >
                                                <span class="text-xs ml-1 cursor-help" :class="item.carried_over > 0 ? 'text-emerald-400' : 'text-rose-400'">
                                                    {{ item.carried_over > 0 ? '+' : '' }}{{ fmt(item.carried_over) }}
                                                </span>
                                            </AppTooltip>
                                        </div>
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
                                        :class="diffClass(item.actual_amount - item.planned_amount, SECTION_META[type].positiveIsGood, item.actual_amount)"
                                        :data-tour-actual="(item.category_id && item.actual_amount > 0) ? item.id : undefined"
                                    >
                                        <AppTooltip v-if="item.category_id && item.actual_amount > 0" :text="t('budgets.detailPanel.subtitle')">
                                            <button class="underline decoration-dotted decoration-muted/50 underline-offset-4 hover:decoration-current" v-on:click.stop="openTxDetail(item)">
                                                {{ fmt(item.actual_amount) }}
                                            </button>
                                        </AppTooltip>
                                        <span v-else>{{ fmt(item.actual_amount) }}</span>
                                    </td>
                                    <td
                                        class="px-4 py-2.5 text-right font-mono text-xs"
                                        :class="diffClass(item.actual_amount - item.planned_amount, SECTION_META[type].positiveIsGood, item.actual_amount)"
                                    >
                                        {{ fmt(item.actual_amount - item.planned_amount, true) }}
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <div v-if="!item.category?.is_system" :data-tour-actions="item.id" class="flex items-center gap-1.5 justify-end opacity-0 group-hover:opacity-100 transition-opacity">
                                            <AppTooltip :text="t('budgets.actions.addTx')">
                                                <button class="text-muted hover:text-indigo-400 transition-colors" v-on:click.stop="openTxPanelFromRow(item.category_id, item.label, type === BudgetSection.Income ? TransactionType.Income : TransactionType.Expense, type)">
                                                    <Plus class="w-3.5 h-3.5" />
                                                </button>
                                            </AppTooltip>
                                            <AppTooltip v-if="showProFeatures" :text="t('budgets.actions.toggleRepeat')">
                                                <button class="transition-colors" :class="item.repeat_next_month ? 'text-indigo-400 hover:text-indigo-300' : 'text-muted hover:text-indigo-400'" v-on:click.stop="toggleRepeat(item)">
                                                    <Repeat class="w-3.5 h-3.5" />
                                                </button>
                                            </AppTooltip>
                                            <AppTooltip v-if="showProFeatures" :text="t('budgets.actions.duplicate')">
                                                <button class="text-muted hover:text-amber-400 transition-colors" v-on:click.stop="duplicateItem(item)">
                                                    <Copy class="w-3.5 h-3.5" />
                                                </button>
                                            </AppTooltip>
                                            <AppTooltip :text="t('budgets.actions.edit')">
                                                <button class="text-muted hover:text-sky-400 transition-colors" v-on:click.stop="startEditingItem(item)">
                                                    <Pencil class="w-3.5 h-3.5" />
                                                </button>
                                            </AppTooltip>
                                            <AppTooltip :text="t('budgets.actions.delete')">
                                                <button class="text-muted hover:text-rose-400 transition-colors" v-on:click.stop="requestDelete(item)">
                                                    <Trash2 class="w-3.5 h-3.5" />
                                                </button>
                                            </AppTooltip>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <tr v-if="addingType === type" class="bg-surface-2/60 border-b border-line/40" data-adding data-tour="add-line-form">
                                <td class="pl-8 pr-2 py-1.5 align-top" data-tour="add-field-label">
                                    <BudgetInput
                                        :id="`add-label-${type}`"
                                        v-model="addForm.label"
                                        type="text"
                                        variant="focus"
                                        :error="addFormSubmitted && !addForm.label"
                                        :placeholder="t('budgets.addRow.labelPlaceholder')"
                                        v-on:keydown="onKeydown($event, handleSubmitAdd, cancelAdding)"
                                    />
                                    <FormHint>{{ t('budgets.addRow.labelHint') }}</FormHint>
                                </td>
                                <td class="px-2 py-1.5 align-top" data-tour="add-field-category">
                                    <div v-if="creatingCategory && categoryTargetForm === addForm" class="flex items-center gap-1">
                                        <BudgetInput
                                            v-model="newCategoryName"
                                            data-new-category
                                            type="text"
                                            variant="focus"
                                            class="flex-1"
                                            :placeholder="t('budgets.addRow.newCategoryPlaceholder')"
                                            :disabled="creatingCategoryLoading"
                                            v-on:keydown.enter.prevent="createCategory"
                                            v-on:keydown.escape="cancelCreateCategory"
                                        />
                                        <AppButton
                                            variant="icon"
                                            size="none"
                                            class="text-emerald-400 hover:text-emerald-300 shrink-0"
                                            :disabled="creatingCategoryLoading"
                                            v-on:click="createCategory"
                                        >
                                            <Check class="w-3.5 h-3.5" />
                                        </AppButton>
                                        <button class="text-rose-400 hover:text-rose-300 transition-colors shrink-0" v-on:click="cancelCreateCategory">
                                            <X class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                    <BudgetSelect
                                        v-else
                                        v-model="addForm.category_id"
                                        :error="addFormSubmitted && !addForm.category_id"
                                        v-on:keydown="onKeydown($event, handleSubmitAdd, cancelAdding)"
                                        v-on:change="onCategoryChange(addForm)"
                                    >
                                        <option :value="null">—</option>
                                        <option v-for="cat in availableCategories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                        <option value="__create__">+ {{ t('budgets.addRow.newCategory') }}</option>
                                    </BudgetSelect>
                                    <FormHint>{{ t('budgets.addRow.categoryHint') }}</FormHint>
                                </td>
                                <td class="px-2 py-1.5 align-top" data-tour="add-field-amount">
                                    <BudgetInput
                                        v-model="addForm.planned_amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="0,00"
                                        variant="mono"
                                        :error="addFormSubmitted && addForm.planned_amount === ''"
                                        v-on:keydown="onKeydown($event, handleSubmitAdd, cancelAdding)"
                                    />
                                    <FormHint>{{ t('budgets.addRow.amountHint') }}</FormHint>
                                </td>
                                <td colspan="2" />
                                <td class="px-3 py-1.5">
                                    <div class="flex items-center gap-2 justify-end">
                                        <AppTooltip :text="t('budgets.addRow.confirm')">
                                            <button class="text-emerald-400 hover:text-emerald-300 transition-colors" v-on:click="handleSubmitAdd">
                                                <Check class="w-4 h-4" />
                                            </button>
                                        </AppTooltip>
                                        <AppTooltip :text="t('budgets.addRow.cancel')">
                                            <button class="text-rose-400 hover:text-rose-300 transition-colors" v-on:click="cancelAdding">
                                                <X class="w-4 h-4" />
                                            </button>
                                        </AppTooltip>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="addingType === type" class="bg-surface-2/60 border-b border-line/40" data-adding>
                                <td colspan="6" class="pl-8 pr-3 py-2" data-tour="add-field-target">
                                    <div class="flex items-center gap-3">
                                        <BudgetSelect v-model="addForm.target_type" class="w-auto">
                                            <option :value="null">{{ t('budgets.target.label') }} — {{ t('budgets.target.none') }}</option>
                                            <option value="spending">{{ t('budgets.target.spending') }}</option>
                                            <option value="saving">{{ t('budgets.target.saving') }}</option>
                                            <option value="by_date">{{ t('budgets.target.byDate') }}</option>
                                        </BudgetSelect>
                                        <BudgetInput
                                            v-if="addForm.target_type"
                                            v-model="addForm.target_amount"
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            variant="mono"
                                            class="w-32"
                                            :placeholder="t('budgets.target.amount')"
                                        />
                                        <BudgetInput
                                            v-if="addForm.target_type === BudgetTargetType.ByDate"
                                            v-model="addForm.target_deadline"
                                            type="date"
                                        />
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="addingType !== type" class="border-b border-surface-2/60">
                                <td colspan="6" class="pl-8 py-1.5">
                                    <AppLink :data-tour-add-line="type" v-on:click="startAddingItem(type)">
                                        <Plus class="w-3 h-3" />
                                        {{ t('budgets.addRow.addLine') }}
                                    </AppLink>
                                </td>
                            </tr>
                        </template><!-- end v-if !collapsedSections -->
                    </tbody>

                    <tbody>
                        <tr v-if="hasUnbudgeted" class="border-t border-amber-500/30 bg-amber-500/5">
                            <td class="px-4 py-2.5" colspan="2">
                                <div class="flex items-center gap-2">
                                    <AlertTriangle class="w-3.5 h-3.5 text-amber-400 shrink-0" />
                                    <span class="text-xs font-semibold uppercase tracking-widest text-amber-400">{{ t('budgets.table.unbudgeted') }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-2.5 text-right font-mono text-xs text-muted">—</td>
                            <td class="px-4 py-2.5 text-right font-mono text-xs space-x-2">
                                <button v-if="unbudgeted.income > 0" type="button" class="text-emerald-400 hover:text-emerald-300 transition-colors cursor-pointer" v-on:click="openUnbudgetedPanel(TransactionType.Income)">+{{ fmt(unbudgeted.income) }}</button>
                                <span v-if="unbudgeted.income > 0 && unbudgeted.expenses > 0" class="text-muted">/</span>
                                <button v-if="unbudgeted.expenses > 0" type="button" class="text-rose-400 hover:text-rose-300 transition-colors cursor-pointer" v-on:click="openUnbudgetedPanel(TransactionType.Expense)">-{{ fmt(unbudgeted.expenses) }}</button>
                            </td>
                            <td colspan="2" />
                        </tr>

                        <tr class="border-t-2 border-line-strong bg-surface-2/30">
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
            :editing="editingTx !== null"
            :prefill-label="txPrefillLabel"
            :tx-section="txSection"
            :tx-form="txForm"
            :section-meta="SECTION_META"
            :filtered-categories="txFilteredCategories"
            :suggested-category-id="suggestedCategoryId"
            :is-pro="showProFeatures"
            v-on:close="closeTxPanel"
            v-on:submit="submitTx"
            v-on:submit-split="submitSplit"
            v-on:section-change="onTxSectionChange"
            v-on:category-manual-change="markCategoryManual"
        />

        <BudgetDetailPanel
            :open="txDetailOpen"
            :item="txDetailItem"
            :loading="txDetailLoading"
            :transactions="txDetailList"
            v-on:close="closeTxDetail"
            v-on:edit="editTxFromDetail"
            v-on:delete="deleteTxFromDetail"
            v-on:add="addTxFromDetailPanel"
        />

        <BudgetDetailPanel
            :open="unbudgetedOpen"
            :item="unbudgetedPseudoItem"
            :loading="unbudgetedLoading"
            :transactions="unbudgetedList"
            v-on:close="closeUnbudgeted"
            v-on:edit="editTxFromDetail"
            v-on:delete="deleteTxFromDetail"
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

        <ConfirmModal
            :show="pendingDeleteTx !== null"
            :message="t('budgets.detailPanel.confirmDelete')"
            v-on:confirm="confirmDeleteTx"
            v-on:cancel="cancelDeleteTx"
        />

        <ConfirmModal
            :show="showClearModal"
            :message="t('budgets.confirmClear')"
            v-on:confirm="confirmClear"
            v-on:cancel="showClearModal = false"
        />

        <BalanceAdjustmentModal
            :show="showAdjustment"
            :wallet="wallet"
            :budget-month="budget.month"
            v-on:close="showAdjustment = false"
        />
    </AuthenticatedLayout>
</template>
