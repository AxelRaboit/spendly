<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
    mustVerifyEmail: { type: Boolean },
    status:          { type: String },
});
</script>

<template>
    <Head :title="t('profile.title')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-primary">{{ t('profile.title') }}</h2>
        </template>

        <div class="mx-auto max-w-2xl space-y-6">
            <div class="bg-surface border border-base/60 rounded-lg p-6">
                <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
            </div>

            <div class="bg-surface border border-base/60 rounded-lg p-6">
                <UpdatePasswordForm />
            </div>

            <div class="bg-surface border border-rose-900/40 rounded-lg p-6">
                <DeleteUserForm />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
