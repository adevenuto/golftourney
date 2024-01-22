/**
 * @param {string} date
 *
 * @return {string}
 */
export function format_date(date) {
    if(date===null||date===undefined||date==='') return ''
    const dateObject = new Date(date)
    const year = dateObject.getFullYear()
    const month = dateObject.getMonth() + 1
    const day = dateObject.getDate()

    // Create a formatted date string in the "YYYY-MM-DD" format
    const formattedDate = `${year}-${month.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`

    return formattedDate;
}
