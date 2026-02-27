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

    return { formatTime, formatDate, formatDateTime, formatDateTimeISO };
}
