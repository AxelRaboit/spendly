<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/components/form/InputError.vue';
import InputLabel from '@/components/form/InputLabel.vue';

import TextInput from '@/components/form/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head :title="t('auth.forgotPassword.title')" />

        <div class="mb-4 text-sm text-secondary">
            {{ t('auth.forgotPassword.instructions') }}
        </div>

        <div
            v-if="status"
            class="mb-4 text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <form v-on:submit.prevent="submit">
            <div>
                <InputLabel for="email" :value="t('auth.login.email')" />

                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4 flex items-center justify-end">
                <AppButton
                    type="submit"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    {{ t('auth.forgotPassword.submit') }}
                </AppButton>
            </div>
        </form>
    </GuestLayout>
</template>
