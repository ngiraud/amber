import { ref } from 'vue';

export const locale = ref(document.documentElement.lang || 'en');

export function setLocale(newLocale: string): void {
    locale.value = newLocale;
    document.documentElement.lang = newLocale;
}

export function t(key: string, replacements: Record<string, string | number> = {}): string {
    const allTranslations = window.__translations as Record<string, Record<string, string>>;
    const map = allTranslations[locale.value] ?? allTranslations['en'] ?? {};
    let message = map[key] ?? key;

    if ('count' in replacements && message.includes('|')) {
        const parts = message.split('|');
        message = Number(replacements.count) === 1 ? parts[0] : (parts[1] ?? parts[0]);
    }

    for (const [k, v] of Object.entries(replacements)) {
        message = message.replace(`:${k}`, String(v));
    }

    return message;
}
