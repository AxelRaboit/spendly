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
            <h2 class="font-semibold text-xl text-primary leading-tight">
                {{ isEdit ? t('categories.editTitle') : t('categories.createTitle') }}
            </h2>
        </template>

        <div class="bg-surface overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-primary">
                <form v-on:submit.prevent="submit">
                    <div class="mb-4">
                        <InputLabel :value="t('categories.fieldName')" />
                        <TextInput v-model="form.name" type="text" :placeholder="t('categories.placeholder')" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="flex gap-2">
                        <AppButton type="submit">{{ isEdit ? t('common.update') : t('common.create') }}</AppButton>
                        <Link href="/categories">
                            <AppButton variant="secondary">{{ t('common.cancel') }}</AppButton>
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
