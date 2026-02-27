export type CurrencyFormatOptions = {
    locale?: string;
    currencyDisplay?: 'symbol' | 'code';
    decimals?: number;
};

export function formatCurrencyRaw(
    value: number | string,
    currency = 'COP',
    options: CurrencyFormatOptions = {},
): string {
    const amount = typeof value === 'string' ? Number(value) : value;
    const locale = options.locale ?? 'es-CO';
    const decimals = options.decimals ?? 2;
    const currencyDisplay = options.currencyDisplay ?? 'symbol';

    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency,
        currencyDisplay,
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    }).format(Number.isFinite(amount) ? amount : 0);
}
