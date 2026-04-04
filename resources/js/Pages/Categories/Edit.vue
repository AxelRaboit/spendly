<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useCategoryForm } from '@/composables/useCategoryForm';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    category: Object,
});

const { form, submit } = useCategoryForm(props.category);
</script>

<template>
    <Head :title="t('categories.editTitle')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-100 leading-tight">{{ t('categories.editTitle') }}</h2>
        </template>

        <div class="bg-gray-900 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-100">
                <form v-on:submit.prevent="submit">
                    <div class="mb-4">
                        <InputLabel :value="t('categories.fieldName')" />
                        <TextInput v-model="form.name" type="text" :placeholder="t('categories.placeholder')" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="flex gap-2">
                        <AppButton type="submit">{{ t('common.update') }}</AppButton>
                        <Link href="/categories">
                            <AppButton variant="secondary">{{ t('common.cancel') }}</AppButton>
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
