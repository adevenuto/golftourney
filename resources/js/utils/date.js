/**
 * Timezone-safe calendar-date helpers.
 *
 * Round dates (and similar) are calendar days stored at UTC midnight. Formatting
 * the raw instant with the viewer's local timezone shifts the day (e.g. UTC
 * midnight shows as the previous evening in the Americas). These helpers work
 * purely from the Y-M-D parts so a date displays as the same calendar day in
 * every timezone. Use these everywhere instead of `new Date(iso)` for dates.
 */

const DEFAULT_FORMAT = { year: 'numeric', month: 'short', day: 'numeric' };

/**
 * Format an ISO date(-time) string as a calendar date, ignoring time/zone.
 * @param {string|null|undefined} value
 * @param {Intl.DateTimeFormatOptions} [options]
 * @returns {string}
 */
export function formatDate(value, options = DEFAULT_FORMAT) {
    if (!value) return '';
    const [y, m, d] = String(value).slice(0, 10).split('-').map(Number);
    return new Date(y, m - 1, d).toLocaleDateString('en-US', options);
}

/**
 * The date portion (YYYY-MM-DD) of an ISO string, for date inputs/pickers.
 * @param {string|null|undefined} value
 * @returns {string}
 */
export function toDateInput(value) {
    return value ? String(value).slice(0, 10) : '';
}

/**
 * Today's date as YYYY-MM-DD in the viewer's local calendar.
 * @returns {string}
 */
export function todayDate() {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}
