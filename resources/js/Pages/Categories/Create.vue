<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useCategoryForm } from '@/composables/useCategoryForm';

const { form, submit } = useCategoryForm();
</script>

<template>
    <Head title="Créer une catégorie" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-100 leading-tight">Créer une catégorie</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-gray-900 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-100">
                        <form v-on:submit.prevent="submit">
                            <div class="mb-4">
                                <InputLabel value="Nom" />
                                <TextInput v-model="form.name" type="text" placeholder="Nom de la catégorie" />
                                <InputError :message="form.errors.name" />
                            </div>

                            <div class="flex gap-2">
                                <AppButton type="submit">Créer</AppButton>
                                <Link href="/categories">
                                    <AppButton variant="secondary">Annuler</AppButton>
                                </Link>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
