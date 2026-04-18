<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/components/form/InputError.vue';
import InputLabel from '@/components/form/InputLabel.vue';

import { AlertTriangle } from 'lucide-vue-next';
import TextInput from '@/components/form/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    registrationEnabled: {
        type: Boolean,
        default: true,
    },
});

const urlParams = new URLSearchParams(window.location.search);
const expectedEmail = urlParams.get('email');
const redirectUrl = urlParams.get('redirect');

const form = useForm({
    name: '',
    email: expectedEmail || '',
    password: '',
    password_confirmation: '',
});

const emailMismatch = computed(() =>
    expectedEmail && form.email && form.email.toLowerCase() !== expectedEmail.toLowerCase()
);

const submit = () => {
    const url = redirectUrl ? `${route('register')}?redirect=${encodeURIComponent(redirectUrl)}` : route('register');
    form.post(url, {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="t('auth.register.title')" />

        <div v-if="!props.registrationEnabled" class="space-y-2 text-center">
            <p class="text-primary font-semibold">{{ t('auth.register.closedTitle') }}</p>
            <p class="text-secondary text-sm">{{ t('auth.register.closedDesc') }}</p>
            <p class="mt-4 text-sm text-secondary">
                {{ t('auth.register.alreadyAccount') }}
                <Link :href="route('login')" class="text-primary underline">{{ t('auth.login.submit') }}</Link>
            </p>
        </div>

        <form v-else v-on:submit.prevent="submit">
            <div>
                <InputLabel for="name" :value="t('auth.register.name')" />

                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full"
                    :placeholder="t('auth.register.namePlaceholder')"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" :value="t('auth.register.email')" />

                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full"
                    :placeholder="t('auth.register.emailPlaceholder')"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
                <p v-if="emailMismatch" class="mt-2 flex items-center gap-1.5 text-xs text-amber-400">
                    <AlertTriangle class="w-3.5 h-3.5 shrink-0" />
                    {{ t('auth.register.emailMismatch', { email: expectedEmail }) }}
                </p>
            </div>

            <div class="mt-4">
                <InputLabel for="password" :value="t('auth.register.password')" />

                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full"
                    :placeholder="t('auth.register.passwordPlaceholder')"
                    required
                    autocomplete="new-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel
                    for="password_confirmation"
                    :value="t('auth.register.passwordConfirm')"
                />

                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    :placeholder="t('auth.register.passwordConfirmPlaceholder')"
                    required
                    autocomplete="new-password"
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />
            </div>

            <div class="mt-4 flex items-center justify-end">
                <Link
                    :href="route('login')"
                    class="text-sm text-primary underline"
                >
                    {{ t('auth.register.alreadyAccount') }}
                </Link>

                <AppButton
                    type="submit"
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    {{ t('auth.register.submit') }}
                </AppButton>
            </div>
        </form>
    </GuestLayout>
</template>
