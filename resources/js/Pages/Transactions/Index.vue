<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useConfirmDelete } from '@/composables/useConfirmDelete';

defineProps({
    transactions: Object,
});

const { isOpen, message, confirmDelete, onConfirm, onCancel } = useConfirmDelete('Êtes-vous sûr de vouloir supprimer cette dépense ?');
</script>

<template>
    <Head title="Dépenses" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-100 leading-tight">Dépenses</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-gray-900 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-100">
                        <div class="mb-6">
                            <Link href="/transactions/create" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                                Ajouter une dépense
                            </Link>
                        </div>

                        <table class="min-w-full table-auto">
                            <thead>
                                <tr class="border-b border-gray-700">
                                    <th class="text-left py-2">Date</th>
                                    <th class="text-left py-2">Description</th>
                                    <th class="text-left py-2">Catégorie</th>
                                    <th class="text-left py-2">Montant</th>
                                    <th class="text-left py-2">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="transaction in transactions.data" :key="transaction.id" class="border-b border-gray-700 hover:bg-gray-800">
                                    <td class="py-2">{{ transaction.date }}</td>
                                    <td class="py-2">{{ transaction.description ?? '—' }}</td>
                                    <td class="py-2">{{ transaction.category.name }}</td>
                                    <td class="py-2">{{ transaction.amount }} €</td>
                                    <td class="py-2 space-x-2">
                                        <EditButton :href="`/transactions/${transaction.id}/edit`" />
                                        <DeleteButton v-on:click="confirmDelete(`/transactions/${transaction.id}`)" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <EmptyState v-if="transactions.data.length === 0" message="Aucune dépense pour l'instant." />

                        <AppPagination :meta="transactions.meta" />
                    </div>
                </div>
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
