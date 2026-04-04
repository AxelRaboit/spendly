<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';

import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head :title="t('auth.verifyEmail.title')" />

        <div class="mb-4 text-sm text-secondary">
            {{ t('auth.verifyEmail.instructions') }}
        </div>

        <div
            v-if="verificationLinkSent"
            class="mb-4 text-sm font-medium text-green-600"
        >
            {{ t('auth.verifyEmail.resent') }}
        </div>

        <form v-on:submit.prevent="submit">
            <div class="mt-4 flex items-center justify-between">
                <AppButton
                    type="submit"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    {{ t('auth.verifyEmail.resend') }}
                </AppButton>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="text-sm text-primary underline"
                >
                    {{ t('auth.verifyEmail.logout') }}
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
