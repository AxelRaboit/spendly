<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useTransactionForm } from '@/composables/useTransactionForm';
import TypeToggle from '@/components/form/TypeToggle.vue';
import { useCurrency } from '@/composables/useCurrency';

defineProps({
    categories: Array,
});

const { form, submit } = useTransactionForm();
const { symbol } = useCurrency();
</script>

<template>
    <Head title="Ajouter une transaction" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-100 leading-tight">Ajouter une transaction</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-gray-900 shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-100">
                        <form v-on:submit.prevent="submit">
                            <div class="mb-6">
                                <InputLabel value="Type" />
                                <TypeToggle v-model="form.type" />
                                <InputError :message="form.errors.type" />
                            </div>

                            <div class="mb-4">
                                <InputLabel value="Catégorie" />
                                <SelectInput v-model="form.category_id">
                                    <option value="" disabled>Sélectionner une catégorie</option>
                                    <option v-for="category in categories" :key="category.id" :value="category.id">
                                        {{ category.name }}
                                    </option>
                                </SelectInput>
                                <InputError :message="form.errors.category_id" />
                            </div>

                            <div class="mb-4">
                                <InputLabel :value="`Montant (${symbol})`" />
                                <TextInput v-model="form.amount" type="number" step="0.01" min="0.01" />
                                <InputError :message="form.errors.amount" />
                            </div>

                            <div class="mb-4">
                                <InputLabel value="Description" />
                                <TextInput v-model="form.description" type="text" placeholder="Description (optionnelle)" />
                                <InputError :message="form.errors.description" />
                            </div>

                            <div class="mb-4">
                                <InputLabel value="Date" />
                                <DateInput v-model="form.date" />
                                <InputError :message="form.errors.date" />
                            </div>

                            <div class="flex gap-2">
                                <SubmitButton>Ajouter</SubmitButton>
                                <Link href="/transactions">
                                    <SecondaryButton type="button">Annuler</SecondaryButton>
                                </Link>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
