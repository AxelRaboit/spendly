<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-100 leading-tight">Créer une catégorie</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-gray-900 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-100">
                        <form @submit.prevent="submit">
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-1">Nom</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    class="w-full px-3 py-2 border border-gray-700 rounded-md bg-gray-700 text-gray-100"
                                    placeholder="Nom de la catégorie"
                                />
                                <span v-if="errors.name" class="text-red-600 text-sm">{{ errors.name }}</span>
                            </div>

                            <div class="flex gap-2">
                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                                    Créer
                                </button>
                                <Link href="/categories" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">
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

const form = useForm({
    name: '',
});

const errors = form.errors;

const submit = () => {
    form.post('/categories', {
        onSuccess: () => form.reset(),
    });
};
</script>
