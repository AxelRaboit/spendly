<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useCategoryForm } from '@/composables/useCategoryForm';

const props = defineProps({
    category: Object,
});

const { form, submit } = useCategoryForm(props.category);
</script>

<template>
    <Head title="Modifier la catégorie" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-100 leading-tight">Modifier la catégorie</h2>
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
                                <PrimaryButton type="submit">Mettre à jour</PrimaryButton>
                                <Link href="/categories">
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
