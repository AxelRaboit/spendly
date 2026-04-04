<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useWalletForm } from '@/composables/forms/useWalletForm';
import { useCurrency } from '@/composables/core/useCurrency';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t }      = useI18n();
const { symbol } = useCurrency();

const props = defineProps({
    wallet: { type: Object, default: null },
});

const isEdit           = !!props.wallet;
const { form, submit } = useWalletForm(props.wallet ?? undefined);
</script>

<template>
    <Head :title="isEdit ? t('wallets.editTitle') : t('wallets.createTitle')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-100 leading-tight">
                {{ isEdit ? t('wallets.editTitle') : t('wallets.createTitle') }}
            </h2>
        </template>

        <div class="bg-gray-900 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-100">
                <form v-on:submit.prevent="submit">
                    <div class="mb-4">
                        <InputLabel :value="t('wallets.fieldName')" />
                        <TextInput v-model="form.name" type="text" :placeholder="t('wallets.placeholder')" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="mb-6">
                        <InputLabel :value="t('wallets.fieldBalance', { symbol })" />
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
                        <AppButton type="submit">{{ isEdit ? t('common.update') : t('common.create') }}</AppButton>
                        <Link href="/wallets">
                            <AppButton variant="secondary">{{ t('common.cancel') }}</AppButton>
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
