<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BudgetPresetsForm from './Partials/BudgetPresetsForm.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
    mustVerifyEmail: { type: Boolean },
    status:          { type: String },
    budgetPresets:   { type: Array, default: () => [] },
});
</script>

<template>
    <Head :title="t('profile.title')" />

    <AuthenticatedLayout>
        <template #header>
            <AppPageHeader :title="t('profile.title')" />
        </template>

        <div class="mx-auto max-w-2xl space-y-6">
            <FormSection>
                <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
            </FormSection>

            <FormSection>
                <BudgetPresetsForm :presets="budgetPresets" />
            </FormSection>

            <FormSection>
                <UpdatePasswordForm />
            </FormSection>

            <FormSection border-class="border-rose-900/40">
                <DeleteUserForm />
            </FormSection>
        </div>
    </AuthenticatedLayout>
</template>
