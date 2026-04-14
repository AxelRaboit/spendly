<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Eye, EyeOff } from 'lucide-vue-next';

const { t } = useI18n();

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const showCurrent = ref(false);
const showNew = ref(false);
const showConfirm = ref(false);

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
            <div>
                <label for="current_password" class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t('profile.password.fieldCurrent') }}</label>
                <div class="relative">
                    <input
                        id="current_password"
                        ref="currentPasswordInput"
                        v-model="form.current_password"
                        :type="showCurrent ? 'text' : 'password'"
                        autocomplete="current-password"
                        class="w-full bg-surface-2 text-primary rounded-lg px-3 py-2.5 pr-10 border border-line focus:border-indigo-500 focus:outline-none"
                    >
                    <button type="button" tabindex="-1" class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted hover:text-secondary transition-colors" v-on:click="showCurrent = !showCurrent">
                        <Eye v-if="!showCurrent" class="w-4 h-4" />
                        <EyeOff v-else class="w-4 h-4" />
                    </button>
                </div>
                <p v-if="form.errors.current_password" class="mt-1 text-xs text-rose-400">{{ form.errors.current_password }}</p>
            </div>

            <div>
                <label for="password" class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t('profile.password.fieldNew') }}</label>
                <div class="relative">
                    <input
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        :type="showNew ? 'text' : 'password'"
                        autocomplete="new-password"
                        class="w-full bg-surface-2 text-primary rounded-lg px-3 py-2.5 pr-10 border border-line focus:border-indigo-500 focus:outline-none"
                    >
                    <button type="button" tabindex="-1" class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted hover:text-secondary transition-colors" v-on:click="showNew = !showNew">
                        <Eye v-if="!showNew" class="w-4 h-4" />
                        <EyeOff v-else class="w-4 h-4" />
                    </button>
                </div>
                <p v-if="form.errors.password" class="mt-1 text-xs text-rose-400">{{ form.errors.password }}</p>
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t('profile.password.fieldConfirm') }}</label>
                <div class="relative">
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        :type="showConfirm ? 'text' : 'password'"
                        autocomplete="new-password"
                        class="w-full bg-surface-2 text-primary rounded-lg px-3 py-2.5 pr-10 border border-line focus:border-indigo-500 focus:outline-none"
                    >
                    <button type="button" tabindex="-1" class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted hover:text-secondary transition-colors" v-on:click="showConfirm = !showConfirm">
                        <Eye v-if="!showConfirm" class="w-4 h-4" />
                        <EyeOff v-else class="w-4 h-4" />
                    </button>
                </div>
                <p v-if="form.errors.password_confirmation" class="mt-1 text-xs text-rose-400">{{ form.errors.password_confirmation }}</p>
            </div>

            <div class="flex items-center gap-3 pt-1">
                <AppButton type="submit" :disabled="form.processing">{{ t('common.save') }}</AppButton>
                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                    <p v-if="form.recentlySuccessful" class="text-sm text-emerald-400">{{ t('common.saved') }}</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
