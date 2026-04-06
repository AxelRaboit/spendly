<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCategoryForm } from '@/composables/forms/useCategoryForm';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    category: { type: Object, default: null },
    wallets: { type: Array, default: () => [] },
});

const isEdit           = !!props.category;
const { form, submit } = useCategoryForm(props.category ?? undefined);
</script>

<template>
    <Head :title="isEdit ? t('categories.editTitle') : t('categories.createTitle')" />

    <AuthenticatedLayout>
        <template #header>
            <AppPageHeader :title="isEdit ? t('categories.editTitle') : t('categories.createTitle')" />
        </template>

        <div class="max-w-lg">
            <FormSection>
                <form class="space-y-4" v-on:submit.prevent="submit">
                    <div>
                        <InputLabel :value="t('categories.fieldName')" />
                        <TextInput v-model="form.name" type="text" :placeholder="t('categories.placeholder')" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel :value="t('categories.fieldWallet')" />
                        <SelectInput v-model="form.wallet_id" :disabled="isEdit">
                            <option value="" disabled>— {{ t('categories.pickWallet') }} —</option>
                            <option v-for="w in wallets" :key="w.id" :value="w.id">{{ w.name }}</option>
                        </SelectInput>
                        <InputError :message="form.errors.wallet_id" />
                    </div>

                    <div class="flex gap-2 pt-2">
                        <AppButton type="submit">{{ isEdit ? t('common.update') : t('common.create') }}</AppButton>
                        <Link href="/categories">
                            <AppButton variant="secondary">{{ t('common.cancel') }}</AppButton>
                        </Link>
                    </div>
                </form>
            </FormSection>
        </div>
    </AuthenticatedLayout>
</template>
