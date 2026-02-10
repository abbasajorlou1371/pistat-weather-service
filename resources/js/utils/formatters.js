import { toJalaali } from 'jalaali-js';

/**
 * Format date for display (Persian locale with full date/time)
 */
export function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleString('fa-IR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

/**
 * Format date for table display (Jalali format: YYYY/MM/DD)
 */
export function formatTableDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    const jDate = toJalaali(date.getFullYear(), date.getMonth() + 1, date.getDate());
    const month = String(jDate.jm).padStart(2, '0');
    const day = String(jDate.jd).padStart(2, '0');
    const year = jDate.jy;
    return `${year}/${month}/${day}`;
}

/**
 * Format hour time for display (Persian locale)
 */
export function formatHourTime(timeString) {
    if (!timeString) return '';
    const date = new Date(timeString);
    return date.toLocaleTimeString('fa-IR', {
        hour: '2-digit',
        minute: '2-digit',
    });
}
