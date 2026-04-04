import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

export const SUPPORTED_LOCALES = [
    { code: 'fr', label: 'Français' },
    { code: 'en', label: 'English' },
    { code: 'es', label: 'Español' },
    { code: 'de', label: 'Deutsch' },
];

export function useLocale() {
    const { locale: i18nLocale } = useI18n();
    const locale = computed(() => usePage().props.locale ?? 'fr');

    function setLocale(newLocale) {
        i18nLocale.value = newLocale;
        router.patch(route('locale.update'), { locale: newLocale }, { preserveScroll: true });
    }

    return { locale, setLocale, SUPPORTED_LOCALES };
}
