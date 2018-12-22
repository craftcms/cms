import Accounting from 'accounting'

/**
 * Formats a value as a currency value
 */
export function currency(value) {
    let precision = 2;
    let floatValue = parseFloat(value);

    // Auto precision
    if(Math.round(floatValue) === floatValue) {
        precision = 0;
    }

    return Accounting.formatMoney(floatValue, '$', precision);
}