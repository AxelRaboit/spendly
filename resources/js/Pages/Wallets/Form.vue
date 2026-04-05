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
            <AppPageHeader :title="isEdit ? t('wallets.editTitle') : t('wallets.createTitle')" />
        </template>

        <div class="max-w-lg">
            <div class="bg-surface border border-base/60 rounded-2xl p-6 shadow-sm space-y-4">
                <form class="space-y-4" v-on:submit.prevent="submit">
                    <div>
                        <InputLabel :value="t('wallets.fieldName')" />
                        <TextInput v-model="form.name" type="text" :placeholder="t('wallets.placeholder')" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div>
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

                    <div class="flex gap-2 pt-2">
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
