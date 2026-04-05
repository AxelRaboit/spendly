<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCategoryForm } from '@/composables/forms/useCategoryForm';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    category: { type: Object, default: null },
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
