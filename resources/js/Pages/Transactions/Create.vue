<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useTransactionForm } from '@/composables/useTransactionForm';
import TypeToggle from '@/components/form/TypeToggle.vue';
import { useCurrency } from '@/composables/useCurrency';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
    categories: Array,
});

const { form, submit } = useTransactionForm();
const { symbol } = useCurrency();
</script>

<template>
    <Head :title="t('transactions.addTitle')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-100 leading-tight">{{ t('transactions.addTitle') }}</h2>
        </template>

        <div class="bg-gray-900 shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-100">
                <form v-on:submit.prevent="submit">
                    <div class="mb-6">
                        <InputLabel :value="t('transactions.fieldType')" />
                        <TypeToggle v-model="form.type" />
                        <InputError :message="form.errors.type" />
                    </div>

                    <div class="mb-4">
                        <InputLabel :value="t('transactions.fieldCategory')" />
                        <SelectInput v-model="form.category_id">
                            <option value="" disabled>{{ t('transactions.placeholderCategory') }}</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </SelectInput>
                        <InputError :message="form.errors.category_id" />
                    </div>

                    <div class="mb-4">
                        <InputLabel :value="t('transactions.fieldAmount', { symbol })" />
                        <TextInput v-model="form.amount" type="number" step="0.01" min="0.01" />
                        <InputError :message="form.errors.amount" />
                    </div>

                    <div class="mb-4">
                        <InputLabel :value="t('transactions.fieldDescription')" />
                        <TextInput v-model="form.description" type="text" :placeholder="t('transactions.placeholderDesc')" />
                        <InputError :message="form.errors.description" />
                    </div>

                    <div class="mb-4">
                        <InputLabel :value="t('transactions.fieldDate')" />
                        <DateInput v-model="form.date" />
                        <InputError :message="form.errors.date" />
                    </div>

                    <div class="flex gap-2">
                        <AppButton type="submit">{{ t('common.add') }}</AppButton>
                        <Link href="/transactions">
                            <AppButton variant="secondary">{{ t('common.cancel') }}</AppButton>
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
