<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-100 leading-tight">Ajouter une dépense</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-gray-900 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-100">
                        <form @submit.prevent="submit">
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-1">Catégorie</label>
                                <select v-model="form.category_id" class="w-full px-3 py-2 border border-gray-700 rounded-md bg-gray-700 text-gray-100">
                                    <option value="" disabled>Sélectionner une catégorie</option>
                                    <option v-for="category in categories" :key="category.id" :value="category.id">
                                        {{ category.name }}
                                    </option>
                                </select>
                                <span v-if="form.errors.category_id" class="text-red-600 text-sm">{{ form.errors.category_id }}</span>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-1">Montant (€)</label>
                                <input v-model="form.amount" type="number" step="0.01" min="0.01"
                                    class="w-full px-3 py-2 border border-gray-700 rounded-md bg-gray-700 text-gray-100" />
                                <span v-if="form.errors.amount" class="text-red-600 text-sm">{{ form.errors.amount }}</span>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-1">Description</label>
                                <input v-model="form.description" type="text"
                                    class="w-full px-3 py-2 border border-gray-700 rounded-md bg-gray-700 text-gray-100"
                                    placeholder="Description (optionnelle)" />
                                <span v-if="form.errors.description" class="text-red-600 text-sm">{{ form.errors.description }}</span>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-1">Date</label>
                                <input v-model="form.date" type="date"
                                    class="w-full px-3 py-2 border border-gray-700 rounded-md bg-gray-700 text-gray-100" />
                                <span v-if="form.errors.date" class="text-red-600 text-sm">{{ form.errors.date }}</span>
                            </div>

                            <div class="flex gap-2">
                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                                    Ajouter
                                </button>
                                <Link href="/transactions" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">
                                    Annuler
                                </Link>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';

defineProps({
    categories: Array,
});

const form = useForm({
    category_id: '',
    amount: '',
    description: '',
    date: '',
});

const submit = () => {
    form.post('/transactions');
};
</script>
