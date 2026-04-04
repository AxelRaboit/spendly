<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useConfirmDelete } from '@/composables/useConfirmDelete';

defineProps({
    categories: Array,
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
                        <div class="mb-6">
                            <Link href="/categories/create" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
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
                                <tr v-for="category in categories" :key="category.id" class="border-b border-gray-700 hover:bg-gray-800">
                                    <td class="py-2">{{ category.name }}</td>
                                    <td class="py-2 space-x-2">
                                        <Link :href="`/categories/${category.id}/edit`" class="text-indigo-400 hover:text-indigo-300">
                                            Modifier
                                        </Link>
                                        <button class="text-red-400 hover:text-red-300" v-on:click="confirmDelete(`/categories/${category.id}`)">
                                            Supprimer
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div v-if="categories.length === 0" class="text-center py-8 text-gray-500">
                            Aucune catégorie pour l'instant.
                        </div>
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
