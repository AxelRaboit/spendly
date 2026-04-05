<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useCurrency } from '@/composables/core/useCurrency';
import { useFmtMonth } from '@/composables/core/useFmtMonth';
import { useI18n } from 'vue-i18n';

const { t }        = useI18n();
const { fmt }      = useCurrency();
const { fmtMonth } = useFmtMonth();

defineProps({
    month:   String,
    prev:    String,
    next:    String,
    wallets: Array,
    totals:  Object,
});

function cashFlowClass(val) {
    if (val > 0) return 'text-emerald-400';
    if (val < 0) return 'text-rose-400';
    return 'text-secondary';
}
</script>

<template>
    <Head :title="t('overview.title')" />

    <AuthenticatedLayout>
        <template #header>
            <AppPageHeader :title="t('overview.title')" />
        </template>

        <div class="space-y-6">
            <!-- Month navigation -->
            <div class="flex items-center justify-between">
                <Link
                    :href="`/overview?month=${prev}`"
                    class="flex items-center gap-1 text-secondary hover:text-primary transition-colors text-sm capitalize"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    <span class="hidden sm:inline">{{ fmtMonth(prev) }}</span>
                </Link>
                <h2 class="text-xl font-bold text-primary capitalize">{{ fmtMonth(month) }}</h2>
                <Link
                    :href="`/overview?month=${next}`"
                    class="flex items-center gap-1 text-secondary hover:text-primary transition-colors text-sm capitalize"
                >
                    <span class="hidden sm:inline">{{ fmtMonth(next) }}</span>
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </Link>
            </div>

            <!-- Global totals KPIs -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="bg-surface border border-base/60 rounded-xl p-4">
                    <p class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('overview.income') }}</p>
                    <p class="text-xl font-bold text-emerald-400 font-mono">{{ fmt(totals.income) }}</p>
                </div>
                <div class="bg-surface border border-base/60 rounded-xl p-4">
                    <p class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('overview.expenses') }}</p>
                    <p class="text-xl font-bold text-rose-400 font-mono">{{ fmt(totals.expenses) }}</p>
                </div>
                <div class="bg-surface border border-base/60 rounded-xl p-4">
                    <p class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('overview.cashFlow') }}</p>
                    <p class="text-xl font-bold font-mono" :class="cashFlowClass(totals.cash_flow)">{{ fmt(totals.cash_flow, true) }}</p>
                </div>
            </div>

            <!-- Per-wallet breakdown -->
            <div class="bg-surface border border-base/60 rounded-xl overflow-hidden">
                <template v-if="wallets.length > 0">
                    <!-- Mobile cards -->
                    <div class="sm:hidden divide-y divide-base/40">
                        <div v-for="wallet in wallets" :key="wallet.id" class="p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <svg v-if="wallet.is_favorite" class="w-3.5 h-3.5 text-amber-400 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                    </svg>
                                    <span class="font-medium text-primary">{{ wallet.name }}</span>
                                </div>
                                <Link
                                    :href="`/wallets/${wallet.id}/budget?month=${month}`"
                                    class="text-xs text-indigo-400 hover:text-indigo-300 transition-colors"
                                >
                                    {{ t('overview.viewBudget') }} →
                                </Link>
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-center">
                                <div>
                                    <p class="text-xs text-muted">{{ t('overview.income') }}</p>
                                    <p class="text-sm font-mono font-semibold text-emerald-400">{{ fmt(wallet.income) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-muted">{{ t('overview.expenses') }}</p>
                                    <p class="text-sm font-mono font-semibold text-rose-400">{{ fmt(wallet.expenses) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-muted">{{ t('overview.cashFlow') }}</p>
                                    <p class="text-sm font-mono font-semibold" :class="cashFlowClass(wallet.cash_flow)">{{ fmt(wallet.cash_flow, true) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Desktop table -->
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-surface-2/50 border-b border-base/40">
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('overview.wallet') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('overview.income') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('overview.expenses') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('overview.cashFlow') }}</th>
                                    <th class="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-base/40">
                                <tr v-for="wallet in wallets" :key="wallet.id" class="hover:bg-surface-2/40 transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <svg v-if="wallet.is_favorite" class="w-3.5 h-3.5 text-amber-400 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                            </svg>
                                            <span class="font-medium text-primary">{{ wallet.name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-emerald-400">{{ fmt(wallet.income) }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-rose-400">{{ fmt(wallet.expenses) }}</td>
                                    <td class="px-4 py-3 text-right font-mono font-semibold" :class="cashFlowClass(wallet.cash_flow)">{{ fmt(wallet.cash_flow, true) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <Link
                                            :href="`/wallets/${wallet.id}/budget?month=${month}`"
                                            class="text-xs text-indigo-400 hover:text-indigo-300 transition-colors"
                                        >
                                            {{ t('overview.viewBudget') }} →
                                        </Link>
                                    </td>
                                </tr>

                                <!-- Total row -->
                                <tr class="bg-surface-2/30 font-semibold border-t-2 border-base/60">
                                    <td class="px-4 py-3 text-sm text-primary uppercase tracking-wide">{{ t('overview.total') }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-emerald-400">{{ fmt(totals.income) }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-rose-400">{{ fmt(totals.expenses) }}</td>
                                    <td class="px-4 py-3 text-right font-mono" :class="cashFlowClass(totals.cash_flow)">{{ fmt(totals.cash_flow, true) }}</td>
                                    <td />
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>

                <div v-else class="flex items-center justify-center py-16 text-secondary">
                    <p class="text-sm">{{ t('overview.noWallets') }}</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
