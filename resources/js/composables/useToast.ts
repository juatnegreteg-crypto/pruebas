import { ref } from 'vue';

export type ToastType = 'success' | 'error' | 'warning' | 'info';
export type ToastPayload = {
    type: ToastType;
    message: string;
    ctaLabel?: string;
    onCta?: () => void;
};

const currentToast = ref<ToastPayload | null>(null);

export function useToast() {
    const show = (
        message: string,
        type: ToastType = 'info',
        durationMs = 3000,
        options: Pick<ToastPayload, 'ctaLabel' | 'onCta'> = {},
    ) => {
        currentToast.value = {
            type,
            message,
            ctaLabel: options.ctaLabel,
            onCta: options.onCta,
        };
        if (durationMs > 0) {
            setTimeout(() => {
                currentToast.value = null;
            }, durationMs);
        }
    };

    const success = (
        message: string,
        durationMs = 3000,
        options: Pick<ToastPayload, 'ctaLabel' | 'onCta'> = {},
    ) => show(message, 'success', durationMs, options);
    const error = (
        message: string,
        durationMs = 5000,
        options: Pick<ToastPayload, 'ctaLabel' | 'onCta'> = {},
    ) => show(message, 'error', durationMs, options);
    const warning = (
        message: string,
        durationMs = 4000,
        options: Pick<ToastPayload, 'ctaLabel' | 'onCta'> = {},
    ) => show(message, 'warning', durationMs, options);
    const info = (
        message: string,
        durationMs = 3000,
        options: Pick<ToastPayload, 'ctaLabel' | 'onCta'> = {},
    ) => show(message, 'info', durationMs, options);

    return {
        toast: currentToast,
        show,
        success,
        error,
        warning,
        info,
    };
}
