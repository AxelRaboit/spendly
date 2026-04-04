<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';

import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="t('auth.login.title')" />

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
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

            <div class="mt-4">
                <InputLabel for="password" :value="t('auth.login.password')" />

                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 flex items-center justify-between">
                <label class="flex items-center">
                    <Checkbox v-model:checked="form.remember" name="remember" />
                    <span class="ms-2 text-sm text-gray-300">{{ t('auth.login.rememberMe') }}</span>
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm text-white underline"
                >
                    {{ t('auth.login.forgot') }}
                </Link>
            </div>

            <AppButton
                type="submit"
                class="mt-6 w-full justify-center"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
            >
                {{ t('auth.login.submit') }}
            </AppButton>

            <div class="mt-6 flex items-center gap-4">
                <div class="flex-1 border-t border-gray-300" />
                <span class="text-sm text-gray-300">{{ t('common.or') }}</span>
                <div class="flex-1 border-t border-gray-300" />
            </div>

            <div class="mt-4 text-center">
                <Link
                    :href="route('register')"
                    class="text-sm text-white underline"
                >
                    {{ t('auth.login.noAccount') }}
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
