<script setup>
import AppModal from '@/components/ui/AppModal.vue';
import Checkbox from '@/components/form/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/components/form/InputError.vue';
import InputLabel from '@/components/form/InputLabel.vue';
import TextInput from '@/components/form/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ref } from 'vue';

const { t } = useI18n();

defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
    demoEnabled: { type: Boolean, default: false },
    demoAccessProtected: { type: Boolean, default: false },
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

const showDemoModal = ref(false);
const demoForm = useForm({ access_password: '' });

const loginAsDemo = () => {
    demoForm.post(route('demo.login'), {
        onSuccess: () => { showDemoModal.value = false; },
        onFinish: () => demoForm.reset('access_password'),
    });
};

const openDemo = (demoAccessProtected) => {
    if (demoAccessProtected) {
        showDemoModal.value = true;
    } else {
        demoForm.post(route('demo.login'));
    }
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
                    <span class="ms-2 text-sm text-secondary">{{ t('auth.login.rememberMe') }}</span>
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm text-primary underline"
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
                <div class="flex-1 border-t border-line" />
                <span class="text-sm text-secondary">{{ t('common.or') }}</span>
                <div class="flex-1 border-t border-line" />
            </div>

            <div class="mt-4 text-center">
                <Link
                    :href="route('register')"
                    class="text-sm text-primary underline"
                >
                    {{ t('auth.login.noAccount') }}
                </Link>
            </div>

            <template v-if="demoEnabled">
                <div class="mt-6 flex items-center gap-4">
                    <div class="flex-1 border-t border-line" />
                    <span class="text-sm text-secondary">{{ t('common.or') }}</span>
                    <div class="flex-1 border-t border-line" />
                </div>

                <AppButton
                    variant="secondary"
                    class="mt-4 w-full justify-center"
                    :class="{ 'opacity-25': demoForm.processing }"
                    :disabled="demoForm.processing"
                    v-on:click="openDemo(demoAccessProtected)"
                >
                    {{ t('auth.login.tryDemo') }}
                </AppButton>
            </template>
        </form>
    </GuestLayout>

    <AppModal :show="showDemoModal" max-width="max-w-sm" v-on:close="showDemoModal = false">
        <h2 class="text-lg font-semibold text-primary">{{ t('auth.login.demoModal.title') }}</h2>
        <p class="text-sm text-secondary">{{ t('auth.login.demoModal.description') }}</p>

        <form class="space-y-4" v-on:submit.prevent="loginAsDemo">
            <div class="space-y-1">
                <InputLabel :value="t('auth.login.demoModal.password')" />
                <TextInput
                    v-model="demoForm.access_password"
                    type="password"
                    class="w-full"
                    autofocus
                    autocomplete="off"
                />
                <InputError :message="demoForm.errors.access_password" />
            </div>

            <AppButton
                type="submit"
                class="w-full justify-center"
                :class="{ 'opacity-25': demoForm.processing }"
                :disabled="demoForm.processing"
            >
                {{ t('auth.login.demoModal.submit') }}
            </AppButton>
        </form>
    </AppModal>
</template>
