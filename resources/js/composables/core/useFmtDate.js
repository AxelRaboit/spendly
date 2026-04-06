import { usePage } from '@inertiajs/vue3';

const LOCALE_MAP = { fr: 'fr-FR', en: 'en-US', es: 'es-ES', de: 'de-DE' };

export function useFmtDate() {
    const page = usePage();

    function intlLocale() {
        return LOCALE_MAP[page.props.locale] ?? 'fr-FR';
    }

    function fmtDay(date) {
        if (!date) return '';
        return new Intl.DateTimeFormat(intlLocale(), {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            timeZone: 'UTC',
        }).format(new Date(date));
    }

    function fmtDayLong(date) {
        if (!date) return '';
        return new Intl.DateTimeFormat(intlLocale(), { day: 'numeric', month: 'long', timeZone: 'UTC' }).format(
            new Date(date)
        );
    }

    function fmtDate(date) {
        if (!date) return '';
        return new Intl.DateTimeFormat(intlLocale(), {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            timeZone: 'UTC',
        }).format(new Date(date));
    }

    return { fmtDay, fmtDayLong, fmtDate };
}
