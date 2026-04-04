<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useConfirmDelete } from '@/composables/useConfirmDelete';
import { useCurrency } from '@/composables/useCurrency';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { fmt } = useCurrency();

defineProps({
    transactions: Object,
    categories: Array,
    filters: Object,
});

const { isOpen, message, confirmDelete, onConfirm, onCancel } = useConfirmDelete(t('transactions.confirmDelete'));
</script>

<template>
    <Head :title="t('transactions.title')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-100 leading-tight">{{ t('transactions.title') }}</h2>
        </template>

        <div class="bg-gray-900 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-100">
                <div class="mb-6 flex flex-wrap items-center gap-3">
                    <SearchInput :model-value="filters.search" :placeholder="t('transactions.searchPlaceholder')" class="max-w-xs" />
                    <FilterSelect param="category_id" :model-value="filters.category_id" :placeholder="t('transactions.allCategories')">
                        <option v-for="category in categories" :key="category.id" :value="category.id">
                            {{ category.name }}
                        </option>
                    </FilterSelect>
                    <Link href="/transactions/create" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded shrink-0 ms-auto">
                        {{ t('transactions.addBtn') }}
                    </Link>
                </div>

                <table class="min-w-full table-auto">
                    <thead>
                        <tr class="border-b border-gray-700">
                            <th class="text-left py-2">{{ t('transactions.colDate') }}</th>
                            <th class="text-left py-2">{{ t('transactions.colDescription') }}</th>
                            <th class="text-left py-2">{{ t('transactions.colCategory') }}</th>
                            <th class="text-left py-2">{{ t('transactions.colAmount') }}</th>
                            <th class="text-right py-2">{{ t('transactions.colActions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="transaction in transactions.data" :key="transaction.id" class="border-b border-gray-700 hover:bg-gray-800">
                            <td class="py-2">{{ transaction.date }}</td>
                            <td class="py-2">{{ transaction.description ?? '—' }}</td>
                            <td class="py-2">{{ transaction.category.name }}</td>
                            <td class="py-2">{{ fmt(transaction.amount) }}</td>
                            <td class="py-2 space-x-2 text-right">
                                <EditButton :href="`/transactions/${transaction.id}/edit`" />
                                <DeleteButton v-on:click="confirmDelete(`/transactions/${transaction.id}`)" />
                            </td>
                        </tr>
                    </tbody>
                </table>

                <EmptyState v-if="transactions.data.length === 0" :message="t('transactions.none')" />

                <AppPagination :meta="transactions" />
            </div>
        </div>

        <ConfirmModal
            :show="isOpen"
            :message="message"
            v-on:confirm="onConfirm"
            v-on:cancel="onCancel"
        />
    </AuthenticatedLayout>
</template>
