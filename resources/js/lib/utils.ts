import type { InertiaLinkProps } from '@inertiajs/vue3';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
    return typeof href === 'string' ? href : href?.url;
}

export function formatMinutes(minutes: number): string {
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;

    if (minutes === 0) {
        return '0h';
    }

    if (h === 0) {
        return `${m}m`;
    }

    if (m === 0) {
        return `${h}h`;
    }

    return `${h}h${String(m).padStart(2, '0')}m`;
}
