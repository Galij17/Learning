export function formatDate(val?: string | null, fallback = '-'): string {
    if (!val) return fallback;
    const date = new Date(val);
    if (Number.isNaN(date.getTime())) return fallback;
    return date.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
}

export function toDateTimeLocalInput(val?: string | null): string {
    if (!val) return '';
    const date = new Date(val);
    if (Number.isNaN(date.getTime())) return '';
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}
