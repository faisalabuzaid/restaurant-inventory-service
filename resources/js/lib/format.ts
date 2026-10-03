import type { Unit } from '@/api/types';

/** Up to 3 decimals, no trailing zeros: 150 -> "150", 8.65 -> "8.65", 0.1 -> "0.1". */
export function qty(value: number, unit?: Unit): string {
    const text = Number(value.toFixed(3)).toLocaleString('en-US', { maximumFractionDigits: 3 });
    return unit ? `${text} ${unit}` : text;
}

export function dateTime(iso: string | null | undefined): string {
    if (!iso) return '-';
    return new Date(iso).toLocaleString('en-GB', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export function secondsSince(iso: string | null | undefined, now: number = Date.now()): number | null {
    if (!iso) return null;
    return Math.max(0, Math.round((now - new Date(iso).getTime()) / 1000));
}
