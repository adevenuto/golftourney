/**
 * @param {string} date
 *
 * @return {string}
 */
export function format_date(date) {
    const dateObject = new Date(date); // Convert the datetime string to a Date object
    const year = dateObject.getFullYear(); // Get the year
    const month = dateObject.getMonth() + 1; // Get the month (0-indexed, so add 1)
    const day = dateObject.getDate(); // Get the day

    // Create a formatted date string in the "YYYY-MM-DD" format
    const formattedDate = `${year}-${month.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;

    return formattedDate;
}

/**
 * @param {string} string
 *
 * @return {string}
 */
export function remove_decimals(string) {
    if(string!=='' && string.includes('.00')) return string.slice(0, -3)
    return string
}
