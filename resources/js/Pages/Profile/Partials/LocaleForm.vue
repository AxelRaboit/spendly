<script setup>
import { useI18n } from 'vue-i18n';
import { useLocale, SUPPORTED_LOCALES } from '@/composables/core/useLocale.js';

const { t } = useI18n();
const { locale, setLocale } = useLocale();
</script>

<template>
    <section>
        <header class="mb-6">
            <h2 class="text-lg font-semibold text-primary">{{ t('profile.locale.title') }}</h2>
            <p class="mt-1 text-sm text-secondary">{{ t('profile.locale.subtitle') }}</p>
        </header>

        <div>
            <label for="locale" class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t('profile.info.fieldLocale') }}</label>
            <select
                id="locale"
                :value="locale"
                class="w-full bg-surface-2 text-primary rounded-lg px-3 py-2.5 border border-line focus:border-indigo-500 focus:outline-none"
                v-on:change="setLocale($event.target.value)"
            >
                <option v-for="loc in SUPPORTED_LOCALES" :key="loc.code" :value="loc.code">{{ t(`locales.${loc.code}`) }}</option>
            </select>
        </div>
    </section>
</template>
