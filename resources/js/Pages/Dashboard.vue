<script setup>
import AppTooltip from '@/components/ui/AppTooltip.vue';
import PlanSelectionModal from '@/components/ui/PlanSelectionModal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ChevronRight, Star, Map } from 'lucide-vue-next';
import { Head, Link } from '@inertiajs/vue3';
import '@/plugins/chartjs';
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useTour } from '@/composables/ui/useTour';
import { Line } from 'vue-chartjs';
import { useCurrency } from '@/composables/core/useCurrency';
import { useChartTheme } from '@/composables/ui/useChartTheme';
import { useFmtDate } from '@/composables/core/useFmtDate';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { fmtDayLong: fmtDay } = useFmtDate();
const page = usePage();

const wasShowingPlanModal = page.props.flash?.show_plan_modal === true;
const showPlanModal = ref(wasShowingPlanModal);
const { startTour, isActive: tourIsActive, isCompleted: tourIsCompleted } = useTour();
const showTourBanner = computed(() => !showPlanModal.value && tourIsActive() && !tourIsCompleted());

// Start tour automatically when the post-registration modal is closed
if (wasShowingPlanModal) {
    watch(showPlanModal, (val) => {
        if (!val) startTour();
    }, { once: true });
}

const props = defineProps({
    spentThisMonth: Number,
    totalWallets: Number,
    favoriteWallets: Array,
    recentTransactions: Array,
    sparkline: Array,
    topCategories: Array,
    dailyAverage: Number,
    bestDay: Object,
});

const { fmt } = useCurrency();
const { labelColor } = useChartTheme();

const sparklineData = computed(() => ({
    labels: props.sparkline.map((d) => d.day.slice(5)),
    datasets: [{
        data: props.sparkline.map((d) => parseFloat(d.total)),
        borderColor: '#a78bfa',
        backgroundColor: 'rgba(167, 139, 250, 0.15)',
        borderWidth: 2,
        pointRadius: 0,
        tension: 0.4,
        fill: true,
    }],
}));

const sparklineOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
    scales: {
        x: { display: false },
        y: { display: false },
    },
};

const topCategoryMax = computed(() => {
    if (!props.topCategories.length) return 1;
    return Math.max(...props.topCategories.map((c) => parseFloat(c.total)));
});
</script>

<template>
    <Head :title="t('dashboard.title')" />

    <AuthenticatedLayout>
        <template #header>
            <AppPageHeader :title="t('dashboard.title')" />
        </template>

        <div class="space-y-6">
            <div v-if="showTourBanner" class="rounded-lg bg-indigo-600/10 border border-indigo-500/30 px-4 py-3 flex items-center justify-between gap-3 text-sm text-indigo-300">
                <div class="flex items-center gap-2">
                    <Map class="w-4 h-4 shrink-0" />
                    <span>{{ t('tour.resumeBanner') }}</span>
                </div>
                <button
                    class="text-xs font-semibold bg-indigo-600 hover:bg-indigo-500 text-white px-3 py-1.5 rounded-lg transition-colors shrink-0"
                    v-on:click="startTour"
                >
                    {{ t('tour.resumeBtn') }}
                </button>
            </div>

            <div v-if="favoriteWallets.length > 0">
                <h3 class="text-xs font-medium text-muted uppercase tracking-wider mb-3">{{ t('wallets.favoriteWallets') }}</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <Link
                        v-for="wallet in favoriteWallets"
                        :key="wallet.id"
                        :href="`/wallets/${wallet.id}/budget`"
                        class="flex items-center justify-between bg-surface border border-base/60 rounded-xl px-4 py-3 hover:border-indigo-500/50 hover:bg-surface-2/60 transition-colors group"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <Star class="w-3.5 h-3.5 text-amber-400 shrink-0" fill="currentColor" />
                            <span class="text-sm font-medium text-primary truncate">{{ wallet.name }}</span>
                        </div>
                        <ChevronRight class="w-4 h-4 text-muted group-hover:text-indigo-400 transition-colors shrink-0" />
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-violet-600 to-indigo-600 p-6 shadow-lg">
                    <div class="bubble-1 pointer-events-none absolute -top-4 -right-4 h-24 w-24 rounded-full bg-white/10" />
                    <div class="bubble-2 pointer-events-none absolute -bottom-6 -left-6 h-32 w-32 rounded-full bg-white/10" />
                    <AppTooltip :text="t('dashboard.spentThisMonthTip')"><p class="text-sm font-medium text-violet-200 cursor-help">{{ t('dashboard.spentThisMonth') }}</p></AppTooltip>
                    <p class="mt-2 text-4xl font-bold text-white font-mono">{{ fmt(spentThisMonth) }}</p>
                </div>

                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 p-6 shadow-lg">
                    <div class="bubble-2 pointer-events-none absolute -top-4 -right-4 h-24 w-24 rounded-full bg-white/10" />
                    <div class="bubble-1 pointer-events-none absolute -bottom-6 -left-6 h-32 w-32 rounded-full bg-white/10" />
                    <AppTooltip :text="t('dashboard.walletsTip')"><p class="text-sm font-medium text-emerald-100 cursor-help">{{ t('dashboard.wallets') }}</p></AppTooltip>
                    <p class="mt-2 text-5xl font-bold text-white">{{ totalWallets }}</p>
                    <div class="mt-6">
                        <Link href="/wallets" class="rounded-lg bg-white/20 px-4 py-2 text-sm font-medium text-white hover:bg-white/30 transition">
                            {{ t('common.seeAll') }}
                        </Link>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-surface rounded-2xl p-6 shadow-lg">
                    <AppTooltip :text="t('dashboard.chartTip')"><h3 class="text-sm font-medium text-secondary mb-1 cursor-help">{{ t('dashboard.chart') }}</h3></AppTooltip>
                    <div class="h-32">
                        <Line :data="sparklineData" :options="sparklineOptions" />
                    </div>
                </div>

                <div class="bg-surface rounded-2xl p-6 shadow-lg space-y-4">
                    <div>
                        <AppTooltip :text="t('dashboard.avgExpenseTip')"><p class="text-xs text-muted uppercase tracking-wider cursor-help">{{ t('dashboard.avgExpense') }}</p></AppTooltip>
                        <p class="text-2xl font-bold text-primary mt-1">{{ fmt(dailyAverage) }}</p>
                    </div>
                    <div v-if="bestDay">
                        <AppTooltip :text="t('dashboard.biggestDayTip')"><p class="text-xs text-muted uppercase tracking-wider cursor-help">{{ t('dashboard.biggestDay') }}</p></AppTooltip>
                        <p class="text-lg font-semibold text-primary mt-1">{{ fmtDay(bestDay.day) }}</p>
                        <p class="text-sm text-secondary">{{ fmt(bestDay.total) }}</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-surface rounded-2xl p-6 shadow-lg">
                    <AppTooltip :text="t('dashboard.topCategoriesTip')"><h3 class="text-sm font-medium text-secondary mb-4 cursor-help">{{ t('dashboard.topCategories') }}</h3></AppTooltip>
                    <div v-if="topCategories.length" class="space-y-3">
                        <div v-for="cat in topCategories" :key="cat.name">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-secondary">{{ cat.name }}</span>
                                <span class="text-secondary">{{ fmt(cat.total) }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-surface-3">
                                <div
                                    class="h-2 rounded-full bg-indigo-500 transition-all"
                                    :style="{ width: (parseFloat(cat.total) / topCategoryMax * 100) + '%' }"
                                />
                            </div>
                        </div>
                    </div>
                    <EmptyState v-else :message="t('dashboard.noCatExpenses')" icon="chart" />
                </div>

                <div class="lg:col-span-2 overflow-hidden rounded-2xl bg-surface shadow-lg">
                    <div class="flex items-center justify-between border-b border-subtle px-6 py-4">
                        <AppTooltip :text="t('dashboard.recentExpensesTip')"><h3 class="text-base font-semibold text-primary cursor-help">{{ t('dashboard.recentExpenses') }}</h3></AppTooltip>
                    </div>

                    <template v-if="recentTransactions.length > 0">
                        <div class="sm:hidden divide-y divide-subtle">
                            <Link v-for="transaction in recentTransactions" :key="transaction.id" :href="`/wallets/${transaction.wallet_id}/budget?month=${transaction.date.substring(0, 7)}&flash_category=${transaction.category_id}`" class="flex items-center justify-between px-4 py-3 gap-3 hover:bg-surface-2/50 transition-colors">
                                <div class="flex flex-col gap-1 min-w-0">
                                    <span class="text-sm text-primary font-medium truncate">{{ transaction.description ?? '—' }}</span>
                                    <div class="flex items-center gap-2">
                                        <span class="rounded-full bg-badge-primary-bg px-2 py-0.5 text-xs font-medium text-badge-primary-text">{{ transaction.category.name }}</span>
                                        <span class="text-xs text-muted">{{ fmtDay(transaction.date) }}</span>
                                        <span class="text-xs text-muted">· {{ transaction.wallet.name }}</span>
                                    </div>
                                </div>
                                <span class="text-sm font-semibold text-primary shrink-0 font-mono">{{ fmt(transaction.amount) }}</span>
                            </Link>
                        </div>

                        <div class="hidden sm:block overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="bg-surface-2/50">
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('common.date') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('common.description') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('common.category') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('common.wallet') }}</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('common.amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-subtle">
                                    <tr v-for="transaction in recentTransactions" :key="transaction.id" class="hover:bg-surface-2/50 transition cursor-pointer" v-on:click="$inertia.visit(`/wallets/${transaction.wallet_id}/budget?month=${transaction.date.substring(0, 7)}&flash_category=${transaction.category_id}`)">
                                        <td class="px-6 py-4 text-sm text-secondary">{{ fmtDay(transaction.date) }}</td>
                                        <td class="px-6 py-4 text-sm text-secondary">{{ transaction.description ?? '—' }}</td>
                                        <td class="px-6 py-4">
                                            <span class="rounded-full bg-badge-primary-bg px-3 py-1 text-xs font-medium text-badge-primary-text">
                                                {{ transaction.category.name }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-secondary">{{ transaction.wallet.name }}</td>
                                        <td class="px-6 py-4 text-right text-sm font-semibold text-primary">{{ fmt(transaction.amount) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>

                    <EmptyState v-else :message="t('dashboard.noExpenses')" icon="receipt" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>

    <PlanSelectionModal :show="showPlanModal" v-on:close="showPlanModal = false" />
</template>
