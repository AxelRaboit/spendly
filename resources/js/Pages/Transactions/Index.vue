<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Transactions</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="mb-6">
                            <Link href="/transactions/create" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                                Add Transaction
                            </Link>
                        </div>

                        <table class="min-w-full table-auto">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left py-2">Date</th>
                                    <th class="text-left py-2">Description</th>
                                    <th class="text-left py-2">Category</th>
                                    <th class="text-left py-2">Amount</th>
                                    <th class="text-left py-2">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="transaction in transactions" :key="transaction.id" class="border-b hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="py-2">{{ transaction.date }}</td>
                                    <td class="py-2">{{ transaction.description ?? '—' }}</td>
                                    <td class="py-2">{{ transaction.category.name }}</td>
                                    <td class="py-2">{{ transaction.amount }} €</td>
                                    <td class="py-2 space-x-2">
                                        <Link :href="`/transactions/${transaction.id}/edit`" class="text-indigo-600 hover:text-indigo-900">
                                            Edit
                                        </Link>
                                        <button @click="deleteTransaction(transaction.id)" class="text-red-600 hover:text-red-900">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div v-if="transactions.length === 0" class="text-center py-8 text-gray-500">
                            No transactions yet.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, router } from '@inertiajs/vue3';

defineProps({
    transactions: Array,
});

const deleteTransaction = (transactionId) => {
    if (confirm('Are you sure you want to delete this transaction?')) {
        router.delete(`/transactions/${transactionId}`);
    }
};
</script>
