import { ref } from 'vue';
import { createI18n } from 'vue-i18n';
import en from './messages/en';
import es from './messages/es';

const storedKeyMode =
    typeof window !== 'undefined'
        ? window.localStorage.getItem('i18n:key-mode')
        : null;

const isKeyModeEnabled = ref(storedKeyMode === 'true');

export const i18nKeyMode = {
    isEnabled: isKeyModeEnabled,
    toggle() {
        isKeyModeEnabled.value = !isKeyModeEnabled.value;
        if (typeof window !== 'undefined') {
            window.localStorage.setItem(
                'i18n:key-mode',
                String(isKeyModeEnabled.value),
            );
        }
    },
    set(value: boolean) {
        isKeyModeEnabled.value = value;
        if (typeof window !== 'undefined') {
            window.localStorage.setItem('i18n:key-mode', String(value));
        }
    },
};

export const i18n = createI18n({
    legacy: false,
    locale: 'es',
    fallbackLocale: 'es',
    postTranslation: (translation, key) =>
        isKeyModeEnabled.value ? String(key) : translation,
    messages: {
        en,
        es,
    },
});
