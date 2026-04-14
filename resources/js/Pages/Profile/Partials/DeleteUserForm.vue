<script setup>
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { AlertTriangle, Eye, EyeOff } from 'lucide-vue-next';

const { t } = useI18n();

const confirmingDeletion = ref(false);
const passwordInput = ref(null);
const showPassword = ref(false);

const form = useForm({ password: '' });

function openModal() {
    confirmingDeletion.value = true;
    nextTick(() => passwordInput.value?.focus());
}

function closeModal() {
    confirmingDeletion.value = false;
    form.clearErrors();
    form.reset();
}

function deleteUser() {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError:   () => passwordInput.value?.focus(),
        onFinish:  () => form.reset(),
    });
}
</script>

<template>
    <section>
        <header class="mb-6">
            <h2 class="text-lg font-semibold text-rose-400">{{ t('profile.delete.title') }}</h2>
            <p class="mt-1 text-sm text-secondary">
                {{ t('profile.delete.subtitle') }}
            </p>
        </header>

        <button
            type="button"
            class="px-4 py-2 bg-rose-600/20 hover:bg-rose-600/30 text-rose-400 border border-rose-600/40 rounded-lg text-sm font-medium transition-colors"
            v-on:click="openModal"
        >
            {{ t('profile.delete.openBtn') }}
        </button>
    </section>

    <AppModal :show="confirmingDeletion" v-on:close="closeModal">
        <div class="flex items-center gap-4 mb-4">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-badge-danger-bg">
                <AlertTriangle class="h-5 w-5 text-badge-danger-text" />
            </div>
            <div>
                <h3 class="text-base font-semibold text-primary">{{ t('profile.delete.modalTitle') }}</h3>
                <p class="text-sm text-secondary mt-0.5">{{ t('profile.delete.modalSubtitle') }}</p>
            </div>
        </div>

        <div class="mt-4">
            <label for="delete-password" class="sr-only">{{ t('profile.delete.fieldPassword') }}</label>
            <div class="relative">
                <input
                    id="delete-password"
                    ref="passwordInput"
                    v-model="form.password"
                    :type="showPassword ? 'text' : 'password'"
                    :placeholder="t('profile.delete.fieldPassword')"
                    class="w-full bg-surface-2 text-primary rounded-lg px-3 py-2.5 pr-10 border border-line focus:border-rose-500 focus:outline-none"
                    v-on:keyup.enter="deleteUser"
                >
                <button type="button" tabindex="-1" class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted hover:text-secondary transition-colors" v-on:click="showPassword = !showPassword">
                    <Eye v-if="!showPassword" class="w-4 h-4" />
                    <EyeOff v-else class="w-4 h-4" />
                </button>
            </div>
            <p v-if="form.errors.password" class="mt-1 text-xs text-rose-400">{{ form.errors.password }}</p>
        </div>

        <div class="flex justify-end gap-3 mt-6">
            <button
                type="button"
                class="px-4 py-2 text-sm text-secondary hover:text-primary border border-line rounded-lg transition-colors"
                v-on:click="closeModal"
            >
                {{ t('common.cancel') }}
            </button>
            <button
                type="button"
                :disabled="form.processing"
                class="px-4 py-2 text-sm font-medium bg-rose-600 hover:bg-rose-500 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg transition-colors"
                v-on:click="deleteUser"
            >
                {{ form.processing ? t('profile.delete.submitting') : t('profile.delete.submitConfirm') }}
            </button>
        </div>
    </AppModal>
</template>
