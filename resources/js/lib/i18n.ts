import { usePage } from '@inertiajs/vue3';

export function t(key: string, params: Record<string, string | number> = {}): string {
    const translations = (usePage().props.translations ?? {}) as Record<string, string>;
    let text = translations[key] ?? key;

    // Longest names first, so :to never eats the start of :total.
    for (const name of Object.keys(params).sort((a, b) => b.length - a.length)) {
        text = text.replaceAll(`:${name}`, String(params[name]));
    }

    return text;
}

export function locale(): string {
    return (usePage().props.locale as string) ?? 'en';
}

export function number(value: number | null | undefined, digits = 0): string {
    if (value === null || value === undefined || Number.isNaN(value)) {
        return '—';
    }

    return new Intl.NumberFormat(locale() === 'es' ? 'es-DO' : 'en-US', { maximumFractionDigits: digits }).format(value);
}

export function r(value: number | null | undefined): string {
    return value === null || value === undefined ? '—' : `r ${value.toFixed(3)}`;
}

export function percent(value: number | null | undefined, digits = 0): string {
    return value === null || value === undefined ? '—' : `${(value * 100).toFixed(digits)}%`;
}

export function date(value: string): string {
    return new Intl.DateTimeFormat(locale() === 'es' ? 'es-DO' : 'en-US', { day: 'numeric', month: 'short', year: 'numeric' }).format(
        new Date(`${value}T00:00:00`),
    );
}

export const COLORS = { modis: '#3b82f6', viirs: '#f97316', harmonized: '#16a34a', muted: '#94a3b8' };
