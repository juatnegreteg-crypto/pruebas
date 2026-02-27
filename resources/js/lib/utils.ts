import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { Updater } from '@tanstack/vue-table';
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';
import type { Ref } from 'vue';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
    return typeof href === 'string' ? href : href?.url;
}

export function valueUpdater<T>(updaterOrValue: Updater<T>, ref: Ref<T>): void {
    if (typeof updaterOrValue === 'function') {
        ref.value = (updaterOrValue as (old: T) => T)(ref.value);
        return;
    }

    ref.value = updaterOrValue;
}
