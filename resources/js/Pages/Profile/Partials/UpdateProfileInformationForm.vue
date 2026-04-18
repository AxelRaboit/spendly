<script setup>
import { CURRENCIES } from '@/composables/core/useCurrency';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

defineProps({
    mustVerifyEmail: { type: Boolean },
    status:          { type: String },
});

const { t } = useI18n();

const user = usePage().props.auth.user;

const form = useForm({
    name:     user.name,
    email:    user.email,
    currency: user.currency ?? 'EUR',
});
</script>

<template>
    <section>
        <header class="mb-6">
            <h2 class="text-lg font-semibold text-primary">{{ t('profile.info.title') }}</h2>
            <p class="mt-1 text-sm text-secondary">{{ t('profile.info.subtitle') }}</p>
        </header>

        <form class="space-y-5" v-on:submit.prevent="form.patch(route('profile.update'))">
            <AppInput
                v-model="form.name"
                :label="t('profile.info.fieldName')"
                :placeholder="t('profile.info.fieldNamePlaceholder')"
                :error="form.errors.name"
                required
                autofocus
                autocomplete="name"
            />

            <AppInput
                v-model="form.email"
                type="email"
                :label="t('profile.info.fieldEmail')"
                :placeholder="t('profile.info.fieldEmailPlaceholder')"
                :error="form.errors.email"
                required
                autocomplete="username"
            />

            <div>
                <label for="currency" class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t('profile.info.fieldCurrency') }}</label>
                <select
                    id="currency"
                    v-model="form.currency"
                    class="w-full bg-surface-2 text-primary rounded-lg px-3 py-2.5 border border-line focus:border-indigo-500 focus:outline-none"
                >
                    <option v-for="c in CURRENCIES" :key="c.code" :value="c.code">{{ c.label }}</option>
                </select>
                <p v-if="form.errors.currency" class="mt-1 text-xs text-rose-400">{{ form.errors.currency }}</p>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-secondary">
                    {{ t('profile.info.unverified') }}
                    <Link :href="route('verification.send')" method="post" as="button" class="underline text-indigo-400 hover:text-indigo-300">
                        {{ t('profile.info.resend') }}
                    </Link>
                </p>
                <div v-show="status === 'verification-link-sent'" class="mt-2 text-xs text-emerald-400">
                    {{ t('profile.info.verificationSent') }}
                </div>
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
