<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/components/ui/AppButton.vue';
import DateInput from '@/components/form/DateInput.vue';
import SelectInput from '@/components/form/SelectInput.vue';
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import { useCurrency } from '@/composables/core/useCurrency';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();
const { fmt } = useCurrency();

const props = defineProps({
    transactions: Object,
    categories:   Array,
    wallets:      Array,
    filters:      Object,
});

const form = reactive({
    q:           props.filters.q           ?? '',
    category_id: props.filters.category_id ?? '',
    wallet_id:   props.filters.wallet_id   ?? '',
    type:        props.filters.type        ?? '',
    date_from:   props.filters.date_from   ?? '',
    date_to:     props.filters.date_to     ?? '',
    tag:         props.filters.tag         ?? '',
});

let timeout = null;
function search() {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get('/search', clean(form), { preserveState: true, preserveScroll: true, replace: true });
    }, 300);
}

function reset() {
    Object.assign(form, { q: '', category_id: '', wallet_id: '', type: '', date_from: '', date_to: '', tag: '' });
    router.get('/search', {}, { replace: true });
}

function clean(obj) {
    return Object.fromEntries(Object.entries(obj).filter(([, v]) => v !== ''));
}

function fmtDay(date) {
    if (!date) return '';
    return new Intl.DateTimeFormat(locale.value, { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' }).format(new Date(date));
}

function filterByTag(tag) {
    form.tag = tag;
    search();
}

const hasFilters = () => Object.values(form).some(v => v !== '');
</script>

<template>
    <Head :title="t('search.title')" />

    <AuthenticatedLayout>
        <template #header>
            <AppPageHeader :title="t('search.title')" />
        </template>

        <div class="space-y-4">
            <!-- Filters -->
            <div class="bg-surface border border-base/60 rounded-xl p-4 space-y-3">
                <!-- Search + reset -->
                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <input
                            v-model="form.q"
                            type="text"
                            :placeholder="t('search.placeholder')"
                            class="w-full pl-9 pr-3 py-2 bg-surface-2 text-primary border border-base rounded-lg text-sm focus:border-indigo-500 focus:outline-none"
                            v-on:input="search"
                        >
                        <svg class="absolute left-2.5 top-2.5 h-4 w-4 text-muted pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" />
                        </svg>
                    </div>
                    <AppButton v-if="hasFilters()" variant="secondary" size="sm" v-on:click="reset">
                        {{ t('search.reset') }}
                    </AppButton>
                </div>

                <!-- Filter row -->
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

                <!-- Date row -->
                <div class="flex flex-col sm:flex-row gap-2">
                    <DateInput v-model="form.date_from" class="flex-1" v-on:change="search" />
                    <DateInput v-model="form.date_to" class="flex-1" v-on:change="search" />
                </div>
            </div>

            <!-- Results -->
            <div class="bg-surface border border-base/60 rounded-xl overflow-hidden">
                <template v-if="transactions.data.length > 0">
                    <!-- Mobile cards -->
                    <div class="sm:hidden divide-y divide-base/40">
                        <div
                            v-for="tx in transactions.data"
                            :key="tx.id"
                            class="px-4 py-3 space-y-1.5"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-sm text-primary font-medium truncate">{{ tx.description ?? '—' }}</span>
                                <span
                                    class="text-sm font-semibold font-mono shrink-0"
                                    :class="tx.type === 'income' ? 'text-emerald-400' : 'text-primary'"
                                >
                                    {{ tx.type === 'income' ? '+' : '' }}{{ fmt(tx.amount) }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span v-if="tx.transfer_id" class="rounded-full bg-sky-900/60 px-2 py-0.5 text-xs font-medium text-sky-300">{{ t('transfers.badge') }}</span>
                                <span v-else class="rounded-full bg-indigo-900/60 px-2 py-0.5 text-xs font-medium text-indigo-300">{{ tx.category?.name ?? '—' }}</span>
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

                    <!-- Desktop table -->
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-surface-2/50 border-b border-base/40">
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('common.date') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('common.description') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('common.category') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('nav.wallets') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">Tags</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('common.amount') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-base/40">
                                <tr v-for="tx in transactions.data" :key="tx.id" class="hover:bg-surface-2/40 transition-colors">
                                    <td class="px-4 py-3 text-sm text-secondary whitespace-nowrap">{{ fmtDay(tx.date) }}</td>
                                    <td class="px-4 py-3 text-sm text-primary">{{ tx.description ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span v-if="tx.transfer_id" class="rounded-full bg-sky-900/60 px-2.5 py-0.5 text-xs font-medium text-sky-300">{{ t('transfers.badge') }}</span>
                                        <span v-else class="rounded-full bg-indigo-900/60 px-2.5 py-0.5 text-xs font-medium text-indigo-300">{{ tx.category?.name ?? '—' }}</span>
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
                                        :class="tx.type === 'income' ? 'text-emerald-400' : 'text-primary'"
                                    >
                                        {{ tx.type === 'income' ? '+' : '' }}{{ fmt(tx.amount) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>

                <div v-else class="flex items-center justify-center py-16 text-secondary">
                    <p class="text-sm">{{ t('search.noResults') }}</p>
                </div>
            </div>

            <AppPagination :meta="transactions" />
        </div>
    </AuthenticatedLayout>
</template>
