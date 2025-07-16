import { createI18n } from "vue-i18n";

import en from '../../../lang/en.json'
import it from '../../../lang/it.json'

const i18n = createI18n({
    legacy: false,
    locale: localStorage.getItem('lang') && ['en','it'].includes(localStorage.getItem('lang')) ? localStorage.getItem('lang') : 'en',
    globalInjection: true,
    fallbackLocale: 'en',
    messages: {
        en,
        it
    },
    availableLocales:['en','it'],
    dateTimeFormats: {
        en: {
            short: {
                year: 'numeric', month: 'short', day: 'numeric'
            },
            long: {
                year: 'numeric', month: 'short', day: 'numeric',
                weekday: 'short', hour: 'numeric', minute: 'numeric', second: 'numeric'
            }
        },
        it: {
            short: {
                year: 'numeric', month: 'short', day: 'numeric'
            },
            long: {
                year: 'numeric', month: 'short', day: 'numeric',
                weekday: 'short', hour: 'numeric', minute: 'numeric', second: 'numeric'
            }
        }
    }
});

export default i18n;