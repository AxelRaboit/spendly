<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useWalletForm } from '@/composables/useWalletForm';
import { useCurrency } from '@/composables/useCurrency';

const props = defineProps({
    wallet: Object,
});

const { form, submit } = useWalletForm(props.wallet);
const { symbol } = useCurrency();
</script>

<template>
    <Head title="Modifier le portefeuille" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-100 leading-tight">Modifier le portefeuille</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-gray-900 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-100">
                        <form v-on:submit.prevent="submit">
                            <div class="mb-4">
                                <InputLabel value="Nom" />
                                <TextInput v-model="form.name" type="text" placeholder="Ex: Compte courant" />
                                <InputError :message="form.errors.name" />
                            </div>

                            <div class="mb-6">
                                <InputLabel :value="`Solde de départ (${symbol})`" />
                                <TextInput
                                    v-model="form.start_balance"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00"
                                />
                                <InputError :message="form.errors.start_balance" />
                            </div>

                            <div class="flex gap-2">
                                <SubmitButton>Mettre à jour</SubmitButton>
                                <Link href="/wallets">
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
