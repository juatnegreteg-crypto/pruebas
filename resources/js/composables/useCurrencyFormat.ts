import { computed, onMounted, ref } from 'vue';
import type { ComputedRef, Ref } from 'vue';
import { formatCurrencyRaw } from '@/lib/currency';

export type CurrencyFormatDisplay = 'symbol' | 'code';

export type CurrencyFormat = {
    locale: string;
    display: CurrencyFormatDisplay;
    decimals: number;
};

export type UseCurrencyFormatReturn = {
    currencyFormat: Ref<CurrencyFormat>;
    formatCurrency: (value: number | string, currency?: string) => string;
    updateCurrencyFormat: (value: Partial<CurrencyFormat>) => void;
    preview: ComputedRef<string>;
};

const STORAGE_KEY = 'currencyFormat';

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;

    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const defaultFormat: CurrencyFormat = {
    locale: 'es-CO',
    display: 'symbol',
    decimals: 2,
};

const currencyFormat = ref<CurrencyFormat>({ ...defaultFormat });

const getStoredFormat = (): CurrencyFormat | null => {
    if (typeof window === 'undefined') {
        return null;
    }

    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) {
        return null;
    }

    try {
        const parsed = JSON.parse(raw) as CurrencyFormat;
        if (!parsed?.locale || !parsed?.display) {
            return null;
        }

        return {
            locale: parsed.locale,
            display: parsed.display,
            decimals: Number.isFinite(parsed.decimals)
                ? parsed.decimals
                : defaultFormat.decimals,
        };
    } catch {
        return null;
    }
};

export function useCurrencyFormat(): UseCurrencyFormatReturn {
    onMounted(() => {
        const stored = getStoredFormat();
        if (stored) {
            currencyFormat.value = stored;
        }
    });

    const formatCurrency = (value: number | string, currency = 'COP') =>
        formatCurrencyRaw(value, currency, {
            locale: currencyFormat.value.locale,
            currencyDisplay: currencyFormat.value.display,
            decimals: currencyFormat.value.decimals,
        });

    const preview = computed(() =>
        formatCurrencyRaw(1234.56, 'COP', {
            locale: currencyFormat.value.locale,
            currencyDisplay: currencyFormat.value.display,
            decimals: currencyFormat.value.decimals,
        }),
    );

    function updateCurrencyFormat(value: Partial<CurrencyFormat>) {
        currencyFormat.value = {
            ...currencyFormat.value,
            ...value,
        };

        if (typeof window !== 'undefined') {
            const serialized = JSON.stringify(currencyFormat.value);
            localStorage.setItem(STORAGE_KEY, serialized);
            setCookie(STORAGE_KEY, serialized);
        }
    }

    return {
        currencyFormat,
        formatCurrency,
        updateCurrencyFormat,
        preview,
    };
}
