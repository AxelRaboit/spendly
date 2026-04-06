<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BudgetPresetsForm from './Partials/BudgetPresetsForm.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import LocaleForm from './Partials/LocaleForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useTour } from '@/composables/ui/useTour';

const { t } = useI18n();
const { initForPage } = useTour();
onMounted(() => initForPage('profile.edit'));

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
                <LocaleForm />
            </FormSection>

            <FormSection>
                <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
            </FormSection>

            <FormSection data-tour="budget-presets">
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
