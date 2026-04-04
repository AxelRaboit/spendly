<template>
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
                                <tr class="border-b">
                                    <th class="text-left py-2">Nom</th>
                                    <th class="text-left py-2">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="category in categories" :key="category.id" class="border-b hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="py-2">{{ category.name }}</td>
                                    <td class="py-2 space-x-2">
                                        <Link :href="`/categories/${category.id}/edit`" class="text-indigo-600 hover:text-indigo-900">
                                            Modifier
                                        </Link>
                                        <button @click="deleteCategory(category.id)" class="text-red-600 hover:text-red-900">
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
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, router } from '@inertiajs/vue3';

defineProps({
    categories: Array,
});

const deleteCategory = (categoryId) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?')) {
        router.delete(`/categories/${categoryId}`);
    }
};
</script>
