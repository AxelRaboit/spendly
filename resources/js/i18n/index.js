import { createI18n } from 'vue-i18n';
import fr from './fr.js';
import en from './en.js';
import es from './es.js';
import de from './de.js';

export function createI18nInstance(locale = 'fr') {
    return createI18n({
        legacy: false,
        locale,
        fallbackLocale: 'fr',
        messages: { fr, en, es, de },
        missingWarn: false,
        fallbackWarn: false,
    });
}
