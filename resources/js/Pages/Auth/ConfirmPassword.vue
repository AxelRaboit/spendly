<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/components/form/InputError.vue';
import InputLabel from '@/components/form/InputLabel.vue';

import TextInput from '@/components/form/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="t('auth.confirmPassword.title')" />

        <div class="mb-4 text-sm text-secondary">
            {{ t('auth.confirmPassword.instructions') }}
        </div>

        <form v-on:submit.prevent="submit">
            <div>
                <InputLabel for="password" :value="t('auth.confirmPassword.password')" />
                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="current-password"
                    autofocus
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 flex justify-end">
                <AppButton
                    type="submit"
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    {{ t('auth.confirmPassword.submit') }}
                </AppButton>
            </div>
        </form>
    </GuestLayout>
</template>
