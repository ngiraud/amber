import { usePage } from '@inertiajs/vue3';

export function useDateFormat() {
    const timezone = (usePage().props.display_timezone || 'Europe/Paris') as string;
    const locale = (usePage().props.display_locale || 'fr-FR') as string;

    function formatTime(date: string | null | undefined): string {
        if (!date) return '—';

        return new Intl.DateTimeFormat(locale, {
            hour: '2-digit',
            minute: '2-digit',
            timeZone: timezone,
            hour12: false,
        }).format(new Date(date));
    }

    function formatDate(date: string | null | undefined): string {
        if (!date) return '—';

        return new Intl.DateTimeFormat(locale, {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            timeZone: timezone,
        }).format(new Date(date));
    }

    function formatDateTime(date: string | null | undefined): string {
        if (!date) return '—';

        const d = new Date(date);

        const datePart = new Intl.DateTimeFormat(locale, {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            timeZone: timezone,
        }).format(d);

        const timePart = new Intl.DateTimeFormat(locale, {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            timeZone: timezone,
            hour12: false,
        }).format(d);

        return `${datePart} ${timePart}`;
    }

    // sv-SE locale produces ISO-like "YYYY-MM-DD HH:MM:SS" format, useful for technical/log display
    function formatDateTimeISO(date: string | null | undefined): string {
        if (!date) return '—';

        return new Intl.DateTimeFormat('sv-SE', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            timeZone: timezone,
        })
            .format(new Date(date))
            .replace('T', ' ');
    }

    // Convert a local Date object to YYYY-MM-DD without UTC conversion
    function toLocalDateString(d: Date): string {
        return [d.getFullYear(), String(d.getMonth() + 1).padStart(2, '0'), String(d.getDate()).padStart(2, '0')].join('-');
    }

    // "March 13, 2026" — full date with month name in user locale
    function formatDateLong(date: string | Date | null | undefined): string {
        if (!date) return '—';
        return new Intl.DateTimeFormat(locale, {
            month: 'long',
            day: 'numeric',
            year: 'numeric',
            timeZone: timezone,
        }).format(typeof date === 'string' ? new Date(date) : date);
    }

    // "March 13" — without year, for use in ranges
    function formatDateShort(date: string | Date | null | undefined): string {
        if (!date) return '—';
        return new Intl.DateTimeFormat(locale, {
            month: 'long',
            day: 'numeric',
            timeZone: timezone,
        }).format(typeof date === 'string' ? new Date(date) : date);
    }

    return { formatTime, formatDate, formatDateTime, formatDateTimeISO, toLocalDateString, formatDateLong, formatDateShort };
}
