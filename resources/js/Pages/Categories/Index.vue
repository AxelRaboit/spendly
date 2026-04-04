<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useConfirmDelete } from '@/composables/useConfirmDelete';

defineProps({
    categories: Object,
    filters: Object,
});

const { isOpen, message, confirmDelete, onConfirm, onCancel } = useConfirmDelete('Êtes-vous sûr de vouloir supprimer cette catégorie ?');
</script>

<template>
    <Head title="Catégories" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-100 leading-tight">Catégories</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-gray-900 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-100">
                        <div class="mb-6 flex items-center justify-between gap-4">
                            <SearchInput :model-value="filters.search" placeholder="Rechercher une catégorie…" class="max-w-xs" />
                            <Link href="/categories/create" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded shrink-0">
                                Créer une catégorie
                            </Link>
                        </div>

                        <table class="min-w-full table-auto">
                            <thead>
                                <tr class="border-b border-gray-700">
                                    <th class="text-left py-2">Nom</th>
                                    <th class="text-left py-2">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="category in categories.data" :key="category.id" class="border-b border-gray-700 hover:bg-gray-800">
                                    <td class="py-2">{{ category.name }}</td>
                                    <td class="py-2 space-x-2">
                                        <EditButton :href="`/categories/${category.id}/edit`" />
                                        <DeleteButton v-on:click="confirmDelete(`/categories/${category.id}`)" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <EmptyState v-if="categories.data.length === 0" message="Aucune catégorie pour l'instant." />

                        <AppPagination :meta="categories" />
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
