import { usePage } from '@inertiajs/vue3';

export function useDateFormat() {
    const page = usePage();
    const timezone = (page.props.display_timezone || 'Europe/Paris') as string;
    const locale = (page.props.display_locale || 'fr-FR') as string;
    const generalSettings = page.props.generalSettings as { date_format?: string; time_format?: string } | undefined;
    const dateFormat = generalSettings?.date_format ?? 'd/m/Y';
    const timeFormat = generalSettings?.time_format ?? 'H:i';

    function getDateParts(d: Date): { day: string; month: string; year: string } {
        const parts = new Intl.DateTimeFormat('sv-SE', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            timeZone: timezone,
        }).formatToParts(d);

        return {
            day: parts.find((p) => p.type === 'day')?.value ?? '',
            month: parts.find((p) => p.type === 'month')?.value ?? '',
            year: parts.find((p) => p.type === 'year')?.value ?? '',
        };
    }

    function applyDateFormat(d: Date): string {
        const { day, month, year } = getDateParts(d);

        switch (dateFormat) {
            case 'd/m/Y':
                return `${day}/${month}/${year}`;
            case 'm/d/Y':
                return `${month}/${day}/${year}`;
            case 'Y-m-d':
                return `${year}-${month}-${day}`;
            case 'd M Y': {
                const shortMonth = new Intl.DateTimeFormat(locale, { month: 'short', timeZone: timezone }).format(d);
                return `${day} ${shortMonth} ${year}`;
            }
            default:
                return `${day}/${month}/${year}`;
        }
    }

    function applyTimeFormat(d: Date): string {
        return new Intl.DateTimeFormat(locale, {
            hour: '2-digit',
            minute: '2-digit',
            timeZone: timezone,
            hour12: timeFormat === 'h:i A',
        }).format(d);
    }

    function formatTime(date: string | null | undefined): string {
        if (!date) {
            return '—';
        }

        return applyTimeFormat(new Date(date));
    }

    function formatDate(date: string | null | undefined): string {
        if (!date) {
            return '—';
        }

        return applyDateFormat(new Date(date));
    }

    function formatDateTime(date: string | null | undefined): string {
        if (!date) {
            return '—';
        }

        const d = new Date(date);

        return `${applyDateFormat(d)} ${applyTimeFormat(d)}`;
    }

    // sv-SE locale produces ISO-like "YYYY-MM-DD HH:MM:SS" format, useful for technical/log display
    function formatDateTimeISO(date: string | null | undefined): string {
        if (!date) {
            return '—';
        }

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
        if (!date) {
            return '—';
        }

        return new Intl.DateTimeFormat(locale, {
            month: 'long',
            day: 'numeric',
            year: 'numeric',
            timeZone: timezone,
        }).format(typeof date === 'string' ? new Date(date) : date);
    }

    // "March 13" — without year, for use in ranges
    function formatDateShort(date: string | Date | null | undefined): string {
        if (!date) {
            return '—';
        }

        return new Intl.DateTimeFormat(locale, {
            month: 'long',
            day: 'numeric',
            timeZone: timezone,
        }).format(typeof date === 'string' ? new Date(date) : date);
    }

    return { formatTime, formatDate, formatDateTime, formatDateTimeISO, toLocalDateString, formatDateLong, formatDateShort };
}
