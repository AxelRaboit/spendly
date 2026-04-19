<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import PasswordStrength from '@/components/form/PasswordStrength.vue';

const { t } = useI18n();

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password:      '',
    password:              '',
    password_confirmation: '',
});

function updatePassword() {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
}
</script>

<template>
    <section>
        <header class="mb-6">
            <h2 class="text-lg font-semibold text-primary">{{ t('profile.password.title') }}</h2>
            <p class="mt-1 text-sm text-secondary">{{ t('profile.password.subtitle') }}</p>
        </header>

        <form class="space-y-5" v-on:submit.prevent="updatePassword">
            <AppInput
                ref="currentPasswordInput"
                v-model="form.current_password"
                type="password"
                :label="t('profile.password.fieldCurrent')"
                :placeholder="t('profile.password.fieldPlaceholder')"
                :error="form.errors.current_password"
                required
                autocomplete="current-password"
            />

            <div>
                <AppInput
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    :label="t('profile.password.fieldNew')"
                    :placeholder="t('profile.password.fieldPlaceholder')"
                    :error="form.errors.password"
                    required
                    autocomplete="new-password"
                />
                <PasswordStrength :password="form.password" />
            </div>

            <AppInput
                v-model="form.password_confirmation"
                type="password"
                :label="t('profile.password.fieldConfirm')"
                :placeholder="t('profile.password.fieldPlaceholder')"
                :error="form.errors.password_confirmation"
                required
                autocomplete="new-password"
            />

            <div class="flex items-center gap-3 pt-1">
                <AppButton type="submit" :disabled="form.processing">{{ t('common.save') }}</AppButton>
                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                    <p v-if="form.recentlySuccessful" class="text-sm text-emerald-400">{{ t('common.saved') }}</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
