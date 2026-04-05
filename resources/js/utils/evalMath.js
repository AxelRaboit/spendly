/**
 * Safely evaluate a simple math expression (+ - * /).
 * Returns the result rounded to 2 decimals, or null if invalid.
 * Only allows digits, operators, dots, commas, spaces and parentheses.
 */
export function evalMath(expr) {
    if (typeof expr !== 'string') return null;
    const sanitized = expr.replace(/,/g, '.').replace(/\s/g, '');
    if (!sanitized || /[^0-9+\-*/.()]/.test(sanitized)) return null;
    try {
        const result = new Function(`return (${sanitized})`)();
        if (typeof result !== 'number' || !isFinite(result)) return null;
        return Math.round(result * 100) / 100;
    } catch {
        return null;
    }
}
