import { usePage } from '@inertiajs/vue3';

const LOCALE_MAP = { fr: 'fr-FR', en: 'en-US', es: 'es-ES', de: 'de-DE' };

export function useFmtMonth() {
    const page = usePage();

    function fmtMonth(monthStr, opts = { month: 'long', year: 'numeric' }) {
        if (!monthStr) return '';
        const [year, month] = monthStr.split('-').map(Number);
        const intlLocale = LOCALE_MAP[page.props.locale] ?? 'fr-FR';
        return new Intl.DateTimeFormat(intlLocale, opts).format(new Date(year, month - 1));
    }

    return { fmtMonth };
}
