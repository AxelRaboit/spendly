import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export const CURRENCIES = [
    { code: 'EUR', label: 'Euro (€)' },
    { code: 'USD', label: 'Dollar US ($)' },
    { code: 'GBP', label: 'Livre sterling (£)' },
    { code: 'CHF', label: 'Franc suisse (Fr.)' },
    { code: 'CAD', label: 'Dollar canadien (CA$)' },
    { code: 'JPY', label: 'Yen japonais (¥)' },
    { code: 'KRW', label: 'Won coréen (₩)' },
    { code: 'TWD', label: 'Dollar taïwanais (NT$)' },
    { code: 'CNY', label: 'Yuan chinois (¥)' },
];

const LOCALE_MAP = { fr: 'fr-FR', en: 'en-US', es: 'es-ES', de: 'de-DE' };

export function useCurrency() {
    const currency   = computed(() => usePage().props.auth?.user?.currency ?? 'EUR');
    const intlLocale = computed(() => LOCALE_MAP[usePage().props.locale ?? 'fr'] ?? 'fr-FR');

    const symbol = computed(
        () =>
            new Intl.NumberFormat(intlLocale.value, { style: 'currency', currency: currency.value })
                .formatToParts(0)
                .find((p) => p.type === 'currency')?.value ?? currency.value
    );

    function fmt(value, sign = false) {
        const n = Number(value);
        return new Intl.NumberFormat(intlLocale.value, {
            style: 'currency',
            currency: currency.value,
            signDisplay: sign ? 'exceptZero' : 'auto',
        }).format(n);
    }

    return { fmt, currency, symbol };
}
