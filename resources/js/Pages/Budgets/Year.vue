<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCurrency } from '@/composables/core/useCurrency';
import { useFmtMonth } from '@/composables/core/useFmtMonth';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t }        = useI18n();
const { fmt }      = useCurrency();
const { fmtMonth } = useFmtMonth();

const props = defineProps({
    wallet:   Object,
    year:     Number,
    prevYear: Number,
    nextYear: Number,
    months:   Object,
});

const totalIncome   = computed(() => Object.values(props.months).reduce((s, m) => s + (m.income_actual   ?? 0), 0));
const totalExpenses = computed(() => Object.values(props.months).reduce((s, m) => s + (m.expenses_actual ?? 0), 0));
const totalCashFlow = computed(() => totalIncome.value - totalExpenses.value);
</script>

<template>
    <Head :title="`${t('budgets.year.title')} ${year} — ${wallet.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3 text-sm">
                <Link href="/wallets" class="text-secondary hover:text-primary transition-colors">{{ t('nav.wallets') }}</Link>
                <span class="text-subtle">/</span>
                <span class="text-primary font-medium">{{ wallet.name }}</span>
                <span class="text-subtle">/</span>
                <span class="text-secondary">{{ t('budgets.year.title') }}</span>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Year navigation -->
            <div class="flex items-center justify-between">
                <Link
                    :href="`/wallets/${wallet.id}/budget/year?year=${prevYear}`"
                    class="flex items-center gap-1 text-secondary hover:text-primary transition-colors text-sm"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    {{ prevYear }}
                </Link>
                <h2 class="text-xl font-bold text-primary">{{ year }}</h2>
                <Link
                    :href="`/wallets/${wallet.id}/budget/year?year=${nextYear}`"
                    class="flex items-center gap-1 text-secondary hover:text-primary transition-colors text-sm"
                >
                    {{ nextYear }}
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </Link>
            </div>

            <!-- Annual totals -->
            <div class="grid grid-cols-3 gap-3">
                <div class="bg-surface border border-base/60 rounded-lg p-4">
                    <p class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('budgets.kpi.income') }}</p>
                    <p class="text-lg font-bold text-emerald-400 font-mono">{{ fmt(totalIncome) }}</p>
                </div>
                <div class="bg-surface border border-base/60 rounded-lg p-4">
                    <p class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('budgets.sections.expenses') }}</p>
                    <p class="text-lg font-bold text-rose-400 font-mono">{{ fmt(totalExpenses) }}</p>
                </div>
                <div class="bg-surface border border-base/60 rounded-lg p-4">
                    <p class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('budgets.table.cashFlow') }}</p>
                    <p class="text-lg font-bold font-mono" :class="totalCashFlow >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                        {{ fmt(totalCashFlow, true) }}
                    </p>
                </div>
            </div>

            <!-- Month grid -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                <Link
                    v-for="(month, key) in months"
                    :key="key"
                    :href="`/wallets/${wallet.id}/budget?month=${key}`"
                    class="bg-surface border rounded-lg p-4 transition-colors hover:border-indigo-500/50 hover:bg-surface-2/60"
                    :class="month.has_budget ? 'border-base/60' : 'border-dashed border-base/40'"
                >
                    <p class="text-sm font-semibold text-secondary capitalize mb-3">
                        {{ fmtMonth(key, { month: 'long' }) }}
                    </p>

                    <template v-if="month.has_budget">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-muted">{{ t('budgets.kpi.income') }}</span>
                                <span class="font-mono text-emerald-400">{{ fmt(month.income_actual) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-muted">{{ t('budgets.sections.expenses') }}</span>
                                <span class="font-mono text-rose-400">{{ fmt(month.expenses_actual) }}</span>
                            </div>
                            <div class="border-t border-base/60 pt-2 flex items-center justify-between text-xs">
                                <span class="text-muted">{{ t('budgets.table.cashFlow') }}</span>
                                <span
                                    class="font-mono font-semibold"
                                    :class="month.cash_flow_actual >= 0 ? 'text-emerald-400' : 'text-rose-400'"
                                >
                                    {{ fmt(month.cash_flow_actual, true) }}
                                </span>
                            </div>
                            <div v-if="month.income_actual > 0" class="h-1 bg-surface-3 rounded-full overflow-hidden mt-1">
                                <div
                                    class="h-full rounded-full transition-all duration-300"
                                    :class="month.expenses_actual > month.income_actual ? 'bg-rose-400' : 'bg-indigo-400'"
                                    :style="{ width: Math.min(100, Math.round((month.expenses_actual / month.income_actual) * 100)) + '%' }"
                                />
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <p class="text-xs text-subtle">{{ t('budgets.year.noBudget') }}</p>
                    </template>
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
